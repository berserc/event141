<?php

declare(strict_types=1);

use App\Controllers\ApiController;
use App\Controllers\AthleteAdminController;
use App\Controllers\AuthController;
use App\Controllers\BoutController;
use App\Controllers\DashboardController;
use App\Controllers\EntryAdminController;
use App\Controllers\EventAdminController;
use App\Controllers\EventBuildController;
use App\Controllers\GymAdminController;
use App\Controllers\GymAreaController;
use App\Controllers\PageAdminController;
use App\Controllers\PublicController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\GymAuth;
use App\Core\Router;
use App\Core\Url;
use App\Core\View;
use App\Models\GymRepo;
use App\Models\PageRepo;
use App\Models\Setting;

require dirname(__DIR__) . '/app/bootstrap.php';

// ------------------------------------------------------------------ Health --
$healthBase = rtrim((string) Config::get('base_path', ''), '/');
$healthPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

if ($healthPath === $healthBase . '/health') {
    $dbFile = is_file((string) Config::get('db_path'));
    $dbOk   = false;

    if ($dbFile) {
        try {
            $dbOk = App\Core\Database::one('SELECT 1 AS ok', []) !== null;
        } catch (Throwable) {
            $dbOk = false;
        }
    }

    http_response_code($dbOk ? 200 : 503);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');

    echo json_encode([
        'status'  => $dbOk ? 'ok' : ($dbFile ? 'error' : 'setup'),
        'version' => trim((string) @file_get_contents(dirname(__DIR__) . '/VERSION')),
    ]);
    exit;
}

// Ohne Datenbank kann nur der Installationshinweis ausgegeben werden.
if (!is_file((string) Config::get('db_path'))) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo View::render('errors/setup', ['title' => 'Einrichtung erforderlich'], null);
    exit;
}

// CORS-Preflight der API (Browser-Clients mit Authorization-Header).
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS' && str_starts_with($healthPath, $healthBase . '/api/')) {
    http_response_code(204);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, X-Api-Key, Content-Type');
    header('Access-Control-Max-Age: 86400');
    exit;
}

Auth::startSession();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Type: text/html; charset=UTF-8');

// In jedem Template verfuegbar
View::share('appName', (string) Config::get('app_name', 'Event141'));
View::share('flash', Flash::take());
View::share('authUser', Auth::user());
View::share('authGym', GymAuth::gym());
View::share('csrf', Csrf::token());
View::share('footerPages', PageRepo::footerPages());
View::share('settings', Setting::all());
View::share('title', '');
View::share('metaDesc', '');
View::share('activePage', '');
View::share('isDev', (string) Config::get('env', 'live') === 'dev');
View::share('noindex', (bool) Config::get('noindex', false));
View::share('showEnvBanner', (bool) Config::get('show_env_banner', false));
View::share('pendingGyms', Auth::check() ? GymRepo::pendingCount() : 0);

$publicSite = Setting::get('public_site', '1') !== '0';
$gymArea    = Setting::get('gym_area', '1') !== '0';

View::share('publicSite', $publicSite);
View::share('gymArea', $gymArea);

$router = new Router();

// ------------------------------------------------------------- Oeffentlich --
$public = new PublicController();

if ($publicSite) {
    $router->get('/', [$public, 'home']);
    $router->get('/e/{slug}', [$public, 'event']);
    $router->get('/e/{slug}/kaempfe', [$public, 'bouts']);
    $router->get('/e/{slug}/kampf/{id}', [$public, 'fight']);
    $router->get('/e/{slug}/zeitplan', [$public, 'schedule']);
    $router->get('/e/{slug}/teilnehmer', [$public, 'entries']);
    $router->get('/e/{slug}/ergebnisse', [$public, 'results']);
    $router->get('/sitemap.xml', [$public, 'sitemap']);
} else {
    $router->get('/', static fn () => Url::redirect('/admin'));
}

$router->get('/seite/{slug}', [$public, 'page']);
$router->get('/impressum', static fn () => (new PublicController())->page(['slug' => 'impressum']));
$router->get('/datenschutz', static fn () => (new PublicController())->page(['slug' => 'datenschutz']));
$router->get('/robots.txt', [$public, 'robots']);

// Lese-API (JSON, ohne Anmeldung)
$api = new ApiController();

$router->get('/api/events', [$api, 'events']);
$router->get('/api/event/{slug}', [$api, 'event']);
$router->get('/api/event/{slug}/live', [$api, 'live']);
$router->get('/api/event/{slug}/fightcard', [$api, 'fightcard']);

// Plattform-API v1 (mit API-Schluessel: auch Entwuerfe lesen, schreiben, Gym-Endpunkte)
$router->get('/api/v1/ping', [$api, 'ping']);
$router->get('/api/v1/events', [$api, 'events']);
$router->get('/api/v1/event/{slug}', [$api, 'event']);
$router->get('/api/v1/event/{slug}/live', [$api, 'live']);
$router->get('/api/v1/event/{slug}/fightcard', [$api, 'fightcard']);
$router->post('/api/v1/event/{slug}/bout/{id}', [$api, 'boutAction']);
$router->get('/api/v1/gym', [$api, 'gymInfo']);
$router->get('/api/v1/gym/events', [$api, 'gymEvents']);
$router->get('/api/v1/gym/athletes', [$api, 'gymAthletes']);
$router->post('/api/v1/gym/athletes', [$api, 'gymAthletesUpsert']);
$router->post('/api/v1/gym/event/{slug}/entries', [$api, 'gymEnter']);
$router->post('/api/v1/gym/entry/{id}/withdraw', [$api, 'gymWithdraw']);
$router->get('/api/v1/gym/results', [$api, 'gymResults']);

// ------------------------------------------------------------- Gym-Bereich --
if ($gymArea) {
    $gym = new GymAreaController();

    $router->get('/gym/registrieren', [$gym, 'showRegister']);
    $router->post('/gym/registrieren', [$gym, 'register']);
    $router->get('/gym/login', [$gym, 'showLogin']);
    $router->post('/gym/login', [$gym, 'login']);
    $router->post('/gym/logout', [$gym, 'logout']);
    $router->get('/gym', [$gym, 'home']);
    $router->get('/gym/sportler', [$gym, 'athletes']);
    $router->get('/gym/sportler/neu', [$gym, 'athleteCreate']);
    $router->post('/gym/sportler', [$gym, 'athleteStore']);
    $router->get('/gym/sportler/{id}', [$gym, 'athleteEdit']);
    $router->post('/gym/sportler/{id}', [$gym, 'athleteUpdate']);
    $router->post('/gym/sportler/{id}/loeschen', [$gym, 'athleteDelete']);
    $router->get('/gym/gym141', [$gym, 'gym141']);
    $router->post('/gym/gym141/verbinden', [$gym, 'gym141Connect']);
    $router->post('/gym/gym141/trennen', [$gym, 'gym141Disconnect']);
    $router->post('/gym/gym141/uebernehmen', [$gym, 'gym141Import']);
    $router->post('/gym/api-schluessel', [$gym, 'createApiKey']);
    $router->post('/gym/api-schluessel/{id}/loeschen', [$gym, 'deleteApiKey']);
    $router->get('/gym/event/{id}', [$gym, 'event']);
    $router->post('/gym/event/{id}/anmelden', [$gym, 'enter']);
    $router->post('/gym/anmeldung/{eid}/abmelden', [$gym, 'withdraw']);
    $router->get('/gym/profil', [$gym, 'profile']);
    $router->post('/gym/profil', [$gym, 'updateProfile']);
}

// ------------------------------------------------------------------ Login --
$auth = new AuthController();

$router->get('/admin/login', [$auth, 'showLogin']);
$router->post('/admin/login', [$auth, 'login']);
$router->post('/admin/logout', [$auth, 'logout']);
$router->get('/admin/profil', [$auth, 'profile']);
$router->post('/admin/profil', [$auth, 'updatePassword']);

$router->get('/admin', [new DashboardController(), 'index']);

// ------------------------------------------------------------------ Events --
$events = new EventAdminController();

$router->get('/admin/events', [$events, 'index']);
$router->get('/admin/events/neu', [$events, 'create']);

$extras = new App\Controllers\EventExtrasController();

$router->get('/admin/events/import', [$extras, 'importForm']);
$router->post('/admin/events/import', [$extras, 'import']);
$router->post('/admin/events', [$events, 'store']);
$router->get('/admin/events/{id}', [$events, 'show']);
$router->post('/admin/events/{id}', [$events, 'update']);
$router->post('/admin/events/{id}/status', [$events, 'setStatus']);
$router->post('/admin/events/{id}/bild-entfernen', [$events, 'removeImage']);
$router->post('/admin/events/{id}/loeschen', [$events, 'destroy']);

$router->get('/admin/events/{id}/sponsoren', [$extras, 'sponsors']);
$router->post('/admin/events/{id}/sponsor', [$extras, 'saveSponsor']);
$router->post('/admin/events/{id}/sponsor-loeschen', [$extras, 'deleteSponsor']);
$router->get('/admin/events/{id}/listen', [$extras, 'lists']);
$router->post('/admin/events/{id}/listen', [$extras, 'saveChecklist']);
$router->get('/admin/events/{id}/druck/{doc}', [$extras, 'printView']);
$router->post('/admin/events/{id}/live-modus', [$extras, 'liveMode']);

$build = new EventBuildController();

$router->get('/admin/events/{id}/aufbau', [$build, 'build']);
$router->post('/admin/events/{id}/tag', [$build, 'saveDay']);
$router->post('/admin/events/{id}/tag-loeschen', [$build, 'deleteDay']);
$router->post('/admin/events/{id}/abschnitt', [$build, 'saveSession']);
$router->post('/admin/events/{id}/abschnitt-loeschen', [$build, 'deleteSession']);
$router->post('/admin/events/{id}/staette', [$build, 'saveVenue']);
$router->post('/admin/events/{id}/staette-loeschen', [$build, 'deleteVenue']);
$router->get('/admin/events/{id}/kategorien', [$build, 'categories']);
$router->post('/admin/events/{id}/kategorie', [$build, 'saveCategory']);
$router->post('/admin/events/{id}/kategorie-loeschen', [$build, 'deleteCategory']);
$router->post('/admin/events/{id}/kategorien-kopieren', [$build, 'copyCategories']);

$entries = new EntryAdminController();

$router->get('/admin/events/{id}/anmeldungen', [$entries, 'index']);
$router->get('/admin/events/{id}/anmeldungen.csv', [$entries, 'exportCsv']);
$router->post('/admin/events/{id}/anmeldungen', [$entries, 'store']);
$router->post('/admin/events/{id}/anmeldungen/sammelaktion', [$entries, 'bulk']);
$router->post('/admin/events/{id}/anmeldung/{eid}', [$entries, 'update']);
$router->post('/admin/events/{id}/anmeldung/{eid}/loeschen', [$entries, 'destroy']);

$bouts = new BoutController();

$router->get('/admin/events/{id}/kaempfe', [$bouts, 'index']);
$router->post('/admin/events/{id}/kaempfe', [$bouts, 'store']);
$router->post('/admin/events/{id}/turnierbaum', [$bouts, 'generate']);
$router->get('/admin/events/{id}/zeitplan', [$bouts, 'schedule']);
$router->post('/admin/events/{id}/zeitplan/einplanen', [$bouts, 'place']);
$router->post('/admin/events/{id}/zeitplan/verteilen', [$bouts, 'distribute']);
$router->post('/admin/events/{id}/zeitplan/nummerieren', [$bouts, 'renumber']);
$router->get('/admin/events/{id}/ergebnisse', [$bouts, 'results']);
$router->get('/admin/events/{id}/ring/{vid}', [$bouts, 'ring']);
$router->get('/admin/events/{id}/kampf/{bid}', [$bouts, 'edit']);
$router->post('/admin/events/{id}/kampf/{bid}', [$bouts, 'update']);
$router->post('/admin/events/{id}/kampf/{bid}/ergebnis', [$bouts, 'result']);
$router->post('/admin/events/{id}/kampf/{bid}/status', [$bouts, 'status']);
$router->post('/admin/events/{id}/kampf/{bid}/verschieben', [$bouts, 'move']);
$router->post('/admin/events/{id}/kampf/{bid}/loeschen', [$bouts, 'destroy']);
$router->post('/admin/events/{id}/kampf/{bid}/aktiv', [$bouts, 'toggleActive']);

// ------------------------------------------------------------------- Gyms --
$gyms = new GymAdminController();

$router->get('/admin/gyms', [$gyms, 'index']);
$router->get('/admin/gyms/neu', [$gyms, 'create']);
$router->post('/admin/gyms', [$gyms, 'store']);
$router->get('/admin/gyms/{id}', [$gyms, 'edit']);
$router->post('/admin/gyms/{id}', [$gyms, 'update']);
$router->post('/admin/gyms/{id}/status', [$gyms, 'setStatus']);
$router->post('/admin/gyms/{id}/anmelden-als', [$gyms, 'loginAs']);
$router->post('/admin/gyms/{id}/loeschen', [$gyms, 'destroy']);
$router->get('/admin/gyms/{id}/gym141', [$gyms, 'gym141Members']);
$router->post('/admin/gyms/{id}/gym141/verbinden', [$gyms, 'gym141Connect']);
$router->post('/admin/gyms/{id}/gym141/trennen', [$gyms, 'gym141Disconnect']);
$router->post('/admin/gyms/{id}/gym141/uebernehmen', [$gyms, 'gym141Import']);

$athletes = new AthleteAdminController();

$router->get('/admin/sportler', [$athletes, 'index']);
$router->get('/admin/sportler/neu', [$athletes, 'create']);
$router->post('/admin/sportler', [$athletes, 'store']);
$router->get('/admin/sportler/{id}', [$athletes, 'edit']);
$router->post('/admin/sportler/{id}', [$athletes, 'update']);
$router->post('/admin/sportler/{id}/loeschen', [$athletes, 'destroy']);

// ----------------------------------------------------------------- System --
$users = new UserController();

$router->get('/admin/benutzer', [$users, 'index']);
$router->get('/admin/benutzer/neu', [$users, 'create']);
$router->post('/admin/benutzer', [$users, 'store']);
$router->get('/admin/benutzer/{id}', [$users, 'edit']);
$router->post('/admin/benutzer/{id}', [$users, 'update']);
$router->post('/admin/benutzer/{id}/loeschen', [$users, 'destroy']);

$pages = new PageAdminController();

$router->get('/admin/seiten', [$pages, 'index']);
$router->get('/admin/seiten/neu', [$pages, 'create']);
$router->post('/admin/seiten', [$pages, 'store']);
$router->get('/admin/seiten/{id}', [$pages, 'edit']);
$router->post('/admin/seiten/{id}', [$pages, 'update']);
$router->post('/admin/seiten/{id}/loeschen', [$pages, 'destroy']);

$settings = new SettingsController();

$router->get('/admin/einstellungen', [$settings, 'index']);
$router->post('/admin/einstellungen', [$settings, 'save']);
$router->post('/admin/einstellungen/lizenz-pruefen', [$settings, 'checkLicense']);
$router->get('/admin/protokoll', [$settings, 'auditLog']);

$system = new App\Controllers\SystemController();

$router->get('/admin/updates', [$system, 'updates']);
$router->post('/admin/updates/installieren', [$system, 'installUpdate']);

$apiAdmin = new App\Controllers\ApiAdminController();

$router->get('/admin/api', [$apiAdmin, 'index']);
$router->post('/admin/api/schluessel', [$apiAdmin, 'createKey']);
$router->post('/admin/api/schluessel/{id}/umschalten', [$apiAdmin, 'toggleKey']);
$router->post('/admin/api/schluessel/{id}/loeschen', [$apiAdmin, 'deleteKey']);
$router->post('/admin/api/webhook', [$apiAdmin, 'createWebhook']);
$router->post('/admin/api/webhook/{id}/test', [$apiAdmin, 'testWebhook']);
$router->post('/admin/api/webhook/{id}/loeschen', [$apiAdmin, 'deleteWebhook']);

$router->notFound([$public, 'notFound']);

// ------------------------------------------------------------------ Start --
$basePath = rtrim((string) Config::get('base_path', ''), '/');
$path     = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}

try {
    $router->dispatch((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $path === '' ? '/' : $path);
} catch (Throwable $e) {
    error_log('[event141] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);

    if ((bool) Config::get('debug', false)) {
        echo '<pre>' . e($e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>';
        exit;
    }

    echo View::render('errors/500', ['title' => 'Fehler'], null);
}
