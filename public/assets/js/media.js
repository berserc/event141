/* NAFN – Kämpfer-Medien: Foto, Video (Endlosschleife) oder Wechsel
   Foto → Video → Foto. Videos laufen stumm und nur, solange sie sichtbar sind.
   Im Wechsel-Modus laufen alle Kämpfer eines Kampfs (data-group) synchron:
   gemeinsame Foto-Dauer (längste Einstellung), gemeinsamer Videostart; das
   kürzeste Video bestimmt die Länge – sobald es fertig ist, gehen alle
   gemeinsam zurück zum Foto (längere werden abgeschnitten). Wird von main.js und fight.js benutzt. */

(function () {
  "use strict";

  var groups = {};
  var groupSeq = 0;

  var io = ("IntersectionObserver" in window)
    ? new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          var st = e.target._nafnMedia;
          if (!st) return;
          st.visible = e.isIntersecting;
          st.update();
        });
      }, { threshold: 0.2 })
    : null;

  /* ---------- Gruppe (ein Kampf) für den Wechsel-Modus ---------- */
  function getGroup(id) {
    if (!groups[id]) groups[id] = { id: id, members: [], timer: null, phase: "photo", endedCount: 0 };
    return groups[id];
  }

  function groupVisible(g) {
    return g.members.some(function (m) { return m.visible; });
  }

  function groupPhotoMs(g) {
    var ms = 0;
    g.members.forEach(function (m) { if (m.photoMs > ms) ms = m.photoMs; });
    return ms || 3000;
  }

  function groupMinDuration(g) {
    var d = 0;
    g.members.forEach(function (m) { if (isFinite(m.v.duration) && m.v.duration > 0 && (d === 0 || m.v.duration < d)) d = m.v.duration; });
    return d;
  }

  function clearGroupTimer(g) {
    if (g.timer) { clearTimeout(g.timer); g.timer = null; }
  }

  function groupToPhoto(g) {
    clearGroupTimer(g);
    g.members.forEach(function (m) { m.el.classList.remove("is-playing"); m.v.pause(); });
    g.phase = "photo";
    if (groupVisible(g)) g.timer = setTimeout(function () { groupToVideo(g); }, groupPhotoMs(g));
  }

  function groupToVideo(g) {
    g.timer = null;
    if (!groupVisible(g)) { g.phase = "photo"; return; }
    g.phase = "video";
    g.endedCount = 0;
    var total = g.members.length;
    var started = 0;

    /* kürzestes Video bestimmt die Länge: erstes Ende beendet die Videophase für alle */
    function oneEnded() {
      g.endedCount++;
      if (g.phase === "video") groupToPhoto(g);
    }
    g.onEnded = oneEnded;

    g.members.forEach(function (m) {
      try { m.v.currentTime = 0; } catch (e) {}
      var p = m.v.play();
      function ok() { m.el.classList.add("is-playing"); started++; }
      if (p && p.then) {
        p.then(ok).catch(function () { oneEnded(); });
      } else {
        ok();
      }
    });

    /* Sicherheitsnetz, falls ein "ended" ausbleibt (z. B. Stream hängt) */
    var minDur = groupMinDuration(g);
    g.timer = setTimeout(function () { if (g.phase === "video") groupToPhoto(g); }, (minDur > 0 ? minDur : 12) * 1000 + 2000);
  }

  function groupUpdate(g) {
    if (groupVisible(g)) {
      /* sichtbar: Videos vorladen, damit beide zeitgleich starten */
      g.members.forEach(function (m) { if (m.v.preload !== "auto") m.v.preload = "auto"; });
      if (g.phase === "photo" && !g.timer) g.timer = setTimeout(function () { groupToVideo(g); }, groupPhotoMs(g));
    } else {
      clearGroupTimer(g);
      g.members.forEach(function (m) { m.v.pause(); m.el.classList.remove("is-playing"); });
      g.phase = "photo";
    }
  }

  /* ---------- Einzel-Element ---------- */
  function setup(el) {
    var v = el.querySelector("video");
    if (!v || el._nafnMedia) return;
    var mode = el.getAttribute("data-mode");
    var st = { el: el, v: v, visible: !io, group: null,
               photoMs: (parseInt(el.getAttribute("data-photo"), 10) || 3) * 1000 };
    el._nafnMedia = st;

    v.muted = true;
    v.defaultMuted = true;
    v.playsInline = true;

    if (mode === "video") {
      v.loop = true;
      v.addEventListener("playing", function () { el.classList.add("is-playing"); });
      st.update = function () {
        if (st.visible) {
          var p = v.play();
          if (p && p.catch) p.catch(function () {});
        } else {
          v.pause();
        }
      };
    } else {
      /* both: synchron in der Gruppe des Kampfs */
      v.loop = false;
      var gid = el.getAttribute("data-group") || ("solo-" + (++groupSeq));
      var g = getGroup(gid);
      st.group = g;
      g.members.push(st);
      v.addEventListener("ended", function () { if (g.phase === "video" && g.onEnded) g.onEnded(); });
      v.addEventListener("error", function () { if (g.phase === "video" && g.onEnded) g.onEnded(); });
      st.update = function () { groupUpdate(g); };
    }

    st.destroy = function () {
      if (io) io.unobserve(el);
      v.pause();
      v.removeAttribute("src");
      try { v.load(); } catch (e) {}
      if (st.group) {
        var g2 = st.group;
        g2.members = g2.members.filter(function (m) { return m !== st; });
        if (!g2.members.length) { clearGroupTimer(g2); delete groups[g2.id]; }
      }
      el._nafnMedia = null;
    };

    if (io) io.observe(el); else st.update();
  }

  window.NAFNMedia = {
    /* HTML für Foto/Video eines Kämpfers (Objekt mit img, video, media, photoSec, name);
       group = Kampf-ID, damit beide Kämpfer eines Kampfs synchron wechseln */
    html: function (f, lazy, esc, group) {
      var img = '<img src="' + esc(f.img) + '" alt="' + esc(f.name) + '"' + (lazy ? ' loading="lazy"' : "") + ">";
      var mode = f.video ? (f.media || "photo") : "photo";
      if (mode === "photo") return img;
      var sec = parseInt(f.photoSec, 10);
      if (!(sec >= 1 && sec <= 5)) sec = 3;
      return '<span class="media-stack" data-mode="' + esc(mode) + '" data-photo="' + sec + '"' +
        (group ? ' data-group="' + esc(group) + '"' : "") + ">" + img +
        '<video src="' + esc(f.video) + '" muted playsinline preload="metadata" disablepictureinpicture disableremoteplayback></video></span>';
    },
    init: function (root) {
      if (!root) return;
      Array.prototype.forEach.call(root.querySelectorAll(".media-stack"), setup);
    },
    teardown: function (root) {
      if (!root) return;
      Array.prototype.forEach.call(root.querySelectorAll(".media-stack"), function (el) {
        if (el._nafnMedia && el._nafnMedia.destroy) el._nafnMedia.destroy();
      });
    }
  };
})();
