/* Event141 – Event-Website: Countdown, Live-Aktualisierung der Fightcard,
   Kämpfer-Medien (media.js), Karte erst nach Klick laden. */

(function () {
  "use strict";

  function pad(n) { return n < 10 ? "0" + n : String(n); }

  /* ---------- Countdown ---------- */
  var cd = document.querySelector("[data-countdown]");
  if (cd) {
    var target = parseInt(cd.getAttribute("data-countdown"), 10) * 1000;
    var parts = {};
    cd.querySelectorAll("[data-cd]").forEach(function (el) { parts[el.getAttribute("data-cd")] = el; });
    var tick = function () {
      var diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
      if (parts.d) parts.d.textContent = Math.floor(diff / 86400);
      if (parts.h) parts.h.textContent = pad(Math.floor(diff % 86400 / 3600));
      if (parts.m) parts.m.textContent = pad(Math.floor(diff % 3600 / 60));
      if (parts.s) parts.s.textContent = pad(diff % 60);
      if (diff === 0) { clearInterval(timer); setTimeout(function () { location.reload(); }, 5000); }
    };
    var timer = setInterval(tick, 1000);
    tick();
  }

  /* ---------- Kämpfer-Medien ---------- */
  if (window.NAFNMedia) window.NAFNMedia.init(document);

  /* ---------- Fightcard: alle 45 s frisch laden, DOM nur bei Änderung tauschen
     (sonst würden die Kämpfer-Videos ständig neu starten) ---------- */
  var slot = document.querySelector("[data-fightcard]");
  if (slot) {
    var last = slot.innerHTML.replace(/\s+/g, " ").trim();
    setInterval(function () {
      if (document.hidden) return;
      fetch(slot.getAttribute("data-fightcard"), { cache: "no-store", credentials: "same-origin" })
        .then(function (r) { return r.ok ? r.text() : null; })
        .then(function (html) {
          if (html === null) return;
          var key = html.replace(/\s+/g, " ").trim();
          if (key === last) return;
          last = key;
          if (window.NAFNMedia) window.NAFNMedia.teardown(slot);
          slot.innerHTML = html;
          if (window.NAFNMedia) window.NAFNMedia.init(slot);
        })
        .catch(function () {});
    }, 45000);
  }

  /* ---------- Kampf-Detailseite: bei Statuswechsel neu laden ---------- */
  var detail = document.querySelector("[data-refresh][data-bout]");
  if (detail) {
    setInterval(function () {
      if (document.hidden) return;
      fetch(detail.getAttribute("data-refresh"), { cache: "no-store" })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
          if (!data || !data.bouts) return;
          var id = parseInt(detail.getAttribute("data-bout"), 10);
          data.bouts.forEach(function (b) {
            if (b.id !== id) return;
            if (b.status !== detail.getAttribute("data-status")) { location.reload(); return; }
            var t = detail.querySelector("[data-sched]");
            if (t && b.time) t.textContent = b.time;
          });
        })
        .catch(function () {});
    }, 30000);
  }

  /* ---------- Karte erst nach Klick (Datenschutz) ---------- */
  document.querySelectorAll("[data-map]").forEach(function (wrap) {
    var btn = wrap.querySelector("[data-map-load]");
    if (!btn) return;
    btn.addEventListener("click", function () {
      var f = document.createElement("iframe");
      f.src = wrap.getAttribute("data-map");
      f.loading = "lazy";
      f.referrerPolicy = "no-referrer-when-downgrade";
      f.title = "Karte";
      wrap.innerHTML = "";
      wrap.appendChild(f);
      wrap.classList.add("is-loaded");
    });
  });
})();
