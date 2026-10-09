/**
 * G2RD — Magic Bento
 * Halo et liseré liés au curseur (CSS custom properties + rAF),
 * effet de proximité, tilt léger et flèches magnétiques (GSAP),
 * entrée au scroll (ScrollTrigger). Sans souris : liseré statique,
 * contenu inchangé. Supporte plusieurs Bento par page.
 */
(function () {
  "use strict";

  var grids = document.querySelectorAll("[data-g2rd-bento], .g2rd-bento-grid");
  if (!grids.length) return;

  var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)");
  var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  var animOff = function () {
    return document.documentElement.classList.contains("g2rd-anim-off");
  };
  var hasGsap = typeof window.gsap !== "undefined";
  var hasST = hasGsap && typeof window.ScrollTrigger !== "undefined";

  grids.forEach(function (grid) {
    if (grid.dataset.g2rdBentoBound) return;
    grid.dataset.g2rdBentoBound = "1";

    var cards = Array.prototype.slice.call(
      grid.querySelectorAll(".g2rd-bento-card")
    );
    if (!cards.length) return;

    /* Elements decoratifs injectes cote client (le pattern Gutenberg reste
       purement editorial) : halo + fleche du lien "en savoir plus". */
    cards.forEach(function (card) {
      if (!card.querySelector(".g2rd-bento-glow")) {
        var glow = document.createElement("span");
        glow.className = "g2rd-bento-glow";
        glow.setAttribute("aria-hidden", "true");
        card.prepend(glow);
      }
      var more = card.querySelector(".g2rd-bento-card__more a, a.g2rd-bento-card__more");
      if (more && !more.querySelector(".g2rd-magnet")) {
        var arrow = document.createElement("span");
        arrow.className = "g2rd-magnet";
        arrow.setAttribute("aria-hidden", "true");
        arrow.textContent = "\u2192";
        more.appendChild(arrow);
      }
    });

    /* ---- Entrée au scroll : stagger sobre (opacity / y / scale léger) ---- */
    /* v1.5.2 : déclenché par IntersectionObserver (même correctif que les
       reveals d'animations.js — ScrollTrigger ne jouait pas toujours au
       premier chargement). Sans IntersectionObserver : aucun masquage. */
    if (hasGsap && !reducedMotion.matches && !animOff() && "IntersectionObserver" in window) {
      window.gsap.set(cards, { opacity: 0, y: 24, scale: 0.985 });
      var played = false;
      var reveal = function () {
        if (played) return;
        played = true;
        window.gsap.to(cards, {
          opacity: 1,
          y: 0,
          scale: 1,
          duration: 0.55,
          ease: "power3.out",
          stagger: 0.06,
          clearProps: "opacity,transform"
        });
      };
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          io.disconnect();
          reveal();
        });
      }, { rootMargin: "0px 0px -20% 0px" });
      io.observe(grid);
      /* Filet : bouton « Désactiver les animations » ou onglet en arrière-plan */
      document.addEventListener("visibilitychange", function () {
        var r = grid.getBoundingClientRect();
        if (!document.hidden && r.top < window.innerHeight && r.bottom > 0) reveal();
      });
      document.addEventListener("g2rd:anim-off", reveal);
    }

    /* ---- Effets curseur : uniquement pointeur fin (jamais au tactile) ---- */
    if (!finePointer.matches) return;

    /* Halo + liseré + proximité — un seul listener, throttlé par rAF.
       Lectures (getBoundingClientRect) groupées avant les écritures. */
    var pending = null;
    var rafId = 0;

    var frame = function () {
      rafId = 0;
      if (!pending) return;
      var x = pending.clientX;
      var y = pending.clientY;
      var rects = cards.map(function (card) {
        return card.getBoundingClientRect();
      });
      cards.forEach(function (card, i) {
        var r = rects[i];
        var dx = Math.max(r.left - x, 0, x - r.right);
        var dy = Math.max(r.top - y, 0, y - r.bottom);
        var dist = Math.hypot(dx, dy);
        var near = reducedMotion.matches || animOff() ? 0 : Math.max(0, 1 - dist / 150);
        card.style.setProperty("--g2rd-mx", (x - r.left) + "px");
        card.style.setProperty("--g2rd-my", (y - r.top) + "px");
        card.style.setProperty("--g2rd-near", near.toFixed(3));
      });
    };

    grid.addEventListener(
      "pointermove",
      function (event) {
        pending = event;
        if (!rafId) rafId = window.requestAnimationFrame(frame);
      },
      { passive: true }
    );
    grid.addEventListener("pointerleave", function () {
      pending = null;
      cards.forEach(function (card) {
        card.style.setProperty("--g2rd-near", "0");
      });
    });

    if (!hasGsap) return;

    /* ---- Tilt 3D léger (cartes grandes et moyennes), retour propre ---- */
    cards.forEach(function (card) {
      if (card.classList.contains("g2rd-bento-card--small")) return;
      window.gsap.set(card, { transformPerspective: 900 });
      var toRx = window.gsap.quickTo(card, "rotationX", { duration: 0.5, ease: "power3.out" });
      var toRy = window.gsap.quickTo(card, "rotationY", { duration: 0.5, ease: "power3.out" });
      card.addEventListener(
        "pointermove",
        function (event) {
          if (reducedMotion.matches || animOff()) return;
          var r = card.getBoundingClientRect();
          toRy(((event.clientX - r.left) / r.width - 0.5) * 4.5);
          toRx(-((event.clientY - r.top) / r.height - 0.5) * 4.5);
        },
        { passive: true }
      );
      card.addEventListener("pointerleave", function () {
        /* Retour physique type ressort (principe Motion), leger et amorti */
        window.gsap.to(card, {
          rotationX: 0,
          rotationY: 0,
          duration: 0.6,
          ease: "elastic.out(1, 0.45)",
          overwrite: "auto"
        });
      });
    });

    /* ---- Flèches magnétiques : déplacement discret, jamais la carte ---- */
    grid.querySelectorAll(".g2rd-magnet").forEach(function (magnet) {
      var card = magnet.closest(".g2rd-bento-card");
      if (!card) return;
      var toX = window.gsap.quickTo(magnet, "x", { duration: 0.35, ease: "power3.out" });
      var toY = window.gsap.quickTo(magnet, "y", { duration: 0.35, ease: "power3.out" });
      card.addEventListener(
        "pointermove",
        function (event) {
          if (reducedMotion.matches || animOff()) return;
          var r = magnet.getBoundingClientRect();
          var dx = event.clientX - (r.left + r.width / 2);
          var dy = event.clientY - (r.top + r.height / 2);
          var dist = Math.hypot(dx, dy) || 1;
          var pull = Math.max(0, 1 - dist / 130) * 7;
          toX((dx / dist) * pull);
          toY((dy / dist) * pull);
        },
        { passive: true }
      );
      card.addEventListener("pointerleave", function () {
        window.gsap.to(magnet, {
          x: 0,
          y: 0,
          duration: 0.55,
          ease: "elastic.out(1, 0.35)",
          overwrite: "auto"
        });
      });
    });
  });
})();
