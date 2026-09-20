<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Url;
use App\Core\View;
use App\Models\PageRepo;

final class PageAdminController
{
    public function index(): void
    {
        AuthController::requireRole('superuser');

        View::display('admin/pages/index', [
            'title' => 'Seiten',
            'pages' => PageRepo::all(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        AuthController::requireRole('superuser');

        View::display('admin/pages/form', [
            'title'  => 'Neue Seite',
            'page'   => Flash::oldInput() + ['id' => 0, 'slug' => '', 'title' => '', 'body' => '', 'in_footer' => 1, 'sort_order' => 0, 'published' => 1],
            'errors' => Flash::errors(),
            'isNew'  => true,
        ], 'layouts/admin');
    }

    public function edit(array $args): void
    {
        AuthController::requireRole('superuser');

        $page = PageRepo::find((int) ($args['id'] ?? 0));

        if ($page === null) {
            Flash::error('Seite nicht gefunden.');
            Url::redirect('/admin/seiten');
        }

        View::display('admin/pages/form', [
            'title'  => (string) $page['title'],
            'page'   => Flash::oldInput() + $page,
            'errors' => Flash::errors(),
            'isNew'  => false,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        [$data, $errors] = $this->validate();

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Url::redirect('/admin/seiten/neu');
        }

        $data['slug'] = PageRepo::uniqueSlug($data['slug'] !== '' ? $data['slug'] : slugify($data['title']));
        $id           = Database::insert('pages', $data);

        Audit::log('page_created', 'page', $id, (string) $data['title']);
        Flash::success('Seite angelegt.');
        Url::redirect('/admin/seiten/' . $id);
    }

    public function update(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $id   = (int) ($args['id'] ?? 0);
        $page = PageRepo::find($id);

        if ($page === null) {
            Flash::error('Seite nicht gefunden.');
            Url::redirect('/admin/seiten');
        }

        [$data, $errors] = $this->validate();

        if ($errors !== []) {
            Flash::withInput($_POST, $errors);
            Url::redirect('/admin/seiten/' . $id);
        }

        $data['slug']       = PageRepo::uniqueSlug($data['slug'] !== '' ? $data['slug'] : slugify($data['title']), $id);
        $data['updated_at'] = gmdate('Y-m-d H:i:s');

        Database::update('pages', $id, $data);

        Audit::log('page_updated', 'page', $id, (string) $data['title']);
        Flash::success('Seite gespeichert.');
        Url::redirect('/admin/seiten/' . $id);
    }

    public function destroy(array $args): void
    {
        AuthController::requireRole('superuser');
        Csrf::verify();

        $id   = (int) ($args['id'] ?? 0);
        $page = PageRepo::find($id);

        if ($page === null) {
            Flash::error('Seite nicht gefunden.');
            Url::redirect('/admin/seiten');
        }

        if (in_array((string) $page['slug'], ['impressum', 'datenschutz'], true)) {
            Flash::error('Impressum und Datenschutz müssen bleiben – Inhalt bitte anpassen statt löschen.');
            Url::redirect('/admin/seiten/' . $id);
        }

        Database::run('DELETE FROM pages WHERE id = ?', [$id]);

        Audit::log('page_deleted', 'page', $id, (string) $page['title']);
        Flash::success('Seite gelöscht.');
        Url::redirect('/admin/seiten');
    }

    /** @return array{0:array<string,mixed>,1:array<string,string>} */
    private function validate(): array
    {
        $errors = [];
        $title  = post('title');

        if ($title === '') {
            $errors['title'] = 'Bitte einen Titel angeben.';
        }

        return [[
            'title'      => $title,
            'slug'       => slugify(post('slug')),
            'body'       => safe_html((string) ($_POST['body'] ?? '')),
            'in_footer'  => post_bool('in_footer'),
            'sort_order' => post_int('sort_order'),
            'published'  => post_bool('published'),
        ], $errors];
    }
}
