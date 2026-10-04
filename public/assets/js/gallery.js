/* Lightbox fuer Galerien: alle Links .js-lightbox (href = grosses Bild, data-caption). Tastatur: Esc, Pfeile; Wischen am Handy. */
(function () {
    'use strict';

    var links = Array.prototype.slice.call(document.querySelectorAll('.js-lightbox'));
    if (links.length === 0) { return; }

    var box = document.createElement('div');
    box.className = 'lightbox';
    box.innerHTML = '<button type="button" class="lightbox__close" aria-label="' + ((window.I18N && window.I18N.close) || 'Schließen') + '">✕</button>'
        + '<button type="button" class="lightbox__nav lightbox__nav--prev" aria-label="' + ((window.I18N && window.I18N.prev) || 'Zurück') + '">‹</button>'
        + '<figure class="lightbox__figure"><img class="lightbox__img" alt=""><figcaption class="lightbox__caption"></figcaption></figure>'
        + '<button type="button" class="lightbox__nav lightbox__nav--next" aria-label="' + ((window.I18N && window.I18N.next) || 'Weiter') + '">›</button>';
    document.body.appendChild(box);

    var img = box.querySelector('.lightbox__img'), cap = box.querySelector('.lightbox__caption'), cur = 0;

    function show(i) {
        cur = (i + links.length) % links.length;
        img.src = links[cur].getAttribute('href');
        cap.textContent = links[cur].getAttribute('data-caption') || '';
        box.classList.add('lightbox--offen');
        document.body.style.overflow = 'hidden';
        [cur + 1, cur - 1].forEach(function (n) { var p = new Image(); p.src = links[(n + links.length) % links.length].getAttribute('href'); });
    }
    function close() { box.classList.remove('lightbox--offen'); document.body.style.overflow = ''; }

    links.forEach(function (a, i) { a.addEventListener('click', function (e) { e.preventDefault(); show(i); }); });
    box.querySelector('.lightbox__close').addEventListener('click', close);
    box.querySelector('.lightbox__nav--prev').addEventListener('click', function () { show(cur - 1); });
    box.querySelector('.lightbox__nav--next').addEventListener('click', function () { show(cur + 1); });
    box.addEventListener('click', function (e) { if (e.target === box) { close(); } });
    document.addEventListener('keydown', function (e) {
        if (!box.classList.contains('lightbox--offen')) { return; }
        if (e.key === 'Escape') { close(); } else if (e.key === 'ArrowLeft') { show(cur - 1); } else if (e.key === 'ArrowRight') { show(cur + 1); }
    });
    var sx = 0;
    box.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', function (e) { var dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) { show(cur + (dx < 0 ? 1 : -1)); } });
})();
