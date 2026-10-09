/**
 * G2RD mockup V2 — animations.js
 * GSAP Core + ScrollTrigger (fichiers locaux).
 * Principes :
 *  - décoratif uniquement : aucun contenu ne dépend du JS ;
 *  - prefers-reduced-motion => aucune animation ;
 *  - transform/opacity uniquement, scrub léger ;
 *  - tilt souris et particules désactivés sur mobile.
 */
(function () {
  "use strict";

  var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduced || typeof gsap === "undefined" || typeof ScrollTrigger === "undefined") return;

  gsap.registerPlugin(ScrollTrigger);
  var EASE = "expo.out";
  var isDesktop = window.matchMedia("(min-width: 901px)").matches;
  var initialized = false;
  var observers = []; // IntersectionObservers des reveals (v1.5.2)

  /* v1.5.2 — les états initiaux (opacity 0) d'animations.css ne s'appliquent
     que si GSAP est réellement chargé : la classe est posée ici, pas dans
     global.js. Si le CDN GSAP échoue, tout le contenu reste visible. */
  document.documentElement.classList.add("g2rd-gsap");

  /* v1.5.2 — ScrollTrigger mesure les positions au chargement ; tout ce qui
     change la hauteur de la page ensuite (polices, avis Google hydratés,
     images, widgets injectés) fausse pins et parallaxes. On rafraîchit après
     les polices, après le load complet et à chaque variation de taille du
     document (debounce). Les reveals, eux, ne dépendent plus de ScrollTrigger. */
  var refresh = function () { if (initialized) ScrollTrigger.refresh(); };
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(refresh);
  window.addEventListener("load", function () { setTimeout(refresh, 100); });
  window.addEventListener("pageshow", function (e) { if (e.persisted) refresh(); });
  if ("ResizeObserver" in window) {
    var rt = null;
    new ResizeObserver(function () {
      clearTimeout(rt);
      rt = setTimeout(refresh, 200);
    }).observe(document.body);
  }

  /* API publique : le bouton "Désactiver les animations" (main.js) pilote ces deux methodes */
  window.g2rdAnim = {
    enable: function () {
      document.documentElement.classList.add("g2rd-gsap");
      init();
    },
    disable: function () {
      observers.forEach(function (o) { o.disconnect(); });
      observers = [];
      ScrollTrigger.getAll().forEach(function (st) { st.kill(); });
      gsap.globalTimeline.clear();
      document.documentElement.classList.remove("g2rd-gsap");
      gsap.set([".g2rd-reveal", ".g2rd-reveal-line", "[data-g2rd-stagger] > *",
        ".g2rd-hero__panel", ".g2rd-hero__bar i", ".g2rd-project__media img",
        ".g2rd-progress i", ".g2rd-process__track i", ".g2rd-glow", "[data-g2rd-depth]",
        ".g2rd-line", ".g2rd-node"], { clearProps: "all" });
      document.querySelectorAll("[data-g2rd-count]").forEach(function (el) {
        el.textContent = el.dataset.g2rdCount;
      });
      initialized = false;
    }
  };

  if (document.documentElement.classList.contains("g2rd-anim-off")) {
    document.documentElement.classList.remove("g2rd-gsap");
    document.querySelectorAll("[data-g2rd-count]").forEach(function (el) {
      el.textContent = el.dataset.g2rdCount;
    });
    return; // preference utilisateur : aucune animation au chargement
  }
  init();

  function init() {
  if (initialized) return;
  initialized = true;

  /* Les blocs Gutenberg ne peuvent pas porter de data-attributes :
     les cadres de dossiers sont tagués ici pour bénéficier du tilt (§3). */
  document.querySelectorAll(".g2rd-case__frame").forEach(function (el) {
    if (!el.hasAttribute("data-g2rd-tilt")) el.setAttribute("data-g2rd-tilt", "");
  });

  /* ---- 1. Barre de progression de lecture ---- */
  var progress = document.querySelector(".g2rd-progress i");
  if (progress) {
    gsap.to(progress, {
      scaleX: 1,
      ease: "none",
      scrollTrigger: { trigger: document.body, start: "top top", end: "bottom bottom", scrub: 0.4 }
    });
  }

  /* ---- 2. Séquence d'entrée du hero (contenu + décor) ---- */
  var idle = null;
  if (document.querySelector(".g2rd-hero")) {
    var tl = gsap.timeline({ defaults: { ease: EASE, duration: 1 } });
    tl.fromTo(".g2rd-hero .g2rd-reveal-line",
        { opacity: 0, y: 30 },
        { opacity: 1, y: 0, stagger: 0.06 })
      .fromTo(".g2rd-hero__panel",
        { opacity: 0, y: 60, rotateX: 18, rotateY: -10 },
        { opacity: 1, y: 0, rotateX: 6, rotateY: -6, duration: 1.4 }, "-=0.7")
      .to(".g2rd-hero__bar i",
        { scaleX: 1, duration: 0.9, stagger: 0.12, ease: "power3.out" }, "-=0.9")
      .to(".g2rd-line",
        { strokeDashoffset: 0, duration: 1.6, stagger: 0.2, ease: "power2.inOut" }, "-=1")
      .to(".g2rd-node",
        { opacity: 1, duration: 0.4, stagger: 0.15 }, "-=0.4");

    // Respiration légère du panneau au repos
    idle = gsap.to(".g2rd-hero__panel", {
      y: -10, duration: 3.2, yoyo: true, repeat: -1, ease: "sine.inOut", delay: 2.4, paused: false
    });
  }

  /* ---- 2bis. Séquence d'entrée des heros de pages intérieures ----
     Les .g2rd-reveal-line d'un .g2rd-page-hero ne sont pas couverts par la
     timeline du hero d'accueil : sans cette séquence, ils resteraient
     invisibles (état initial opacity: 0 posé par animations.css). */
  if (document.querySelector(".g2rd-page-hero")) {
    var tlp = gsap.timeline({ defaults: { ease: EASE, duration: 1 } });
    tlp.fromTo(".g2rd-page-hero .g2rd-reveal-line",
        { opacity: 0, y: 30 },
        { opacity: 1, y: 0, stagger: 0.06 });
    if (document.querySelector(".g2rd-page-hero .g2rd-hero__panel")) {
      tlp.fromTo(".g2rd-page-hero .g2rd-hero__panel",
          { opacity: 0, y: 60, rotateX: 18, rotateY: -10 },
          { opacity: 1, y: 0, rotateX: 6, rotateY: -6, duration: 1.4 }, "-=0.7")
        .to(".g2rd-page-hero .g2rd-hero__bar i",
          { scaleX: 1, duration: 0.9, stagger: 0.12, ease: "power3.out" }, "-=0.9");
      if (!idle) {
        idle = gsap.to(".g2rd-page-hero .g2rd-hero__panel", {
          y: -10, duration: 3.2, yoyo: true, repeat: -1, ease: "sine.inOut", delay: 2.4, paused: false
        });
      }
    }
  }

  /* ---- 3. Tilt 3D à la souris (desktop, décoratif) ---- */
  if (isDesktop) {
    document.querySelectorAll("[data-g2rd-tilt]").forEach(function (el) {
      if (el.dataset.g2rdBound) return; // ecouteurs deja poses (reactivation)
      el.dataset.g2rdBound = "1";
      var isPanel = el.classList.contains("g2rd-hero__panel");
      var rx = gsap.quickTo(el, "rotationX", { duration: 0.5, ease: "power3.out" });
      var ry = gsap.quickTo(el, "rotationY", { duration: 0.5, ease: "power3.out" });
      var zone = el.parentElement;
      zone.addEventListener("mousemove", function (e) {
        var r = el.getBoundingClientRect();
        var dx = (e.clientX - (r.left + r.width / 2)) / r.width;
        var dy = (e.clientY - (r.top + r.height / 2)) / r.height;
        if (isPanel && idle) idle.pause();
        rx((isPanel ? 6 : 0) + dy * -7);
        ry((isPanel ? -6 : 0) + dx * 9);
      });
      zone.addEventListener("mouseleave", function () {
        rx(isPanel ? 6 : 0);
        ry(isPanel ? -6 : 0);
        if (isPanel && idle) idle.resume();
      });
    });
  }

  /* ---- 4. Particules du hero (canvas, très léger) ---- */
  var canvas = document.querySelector(".g2rd-stars");
  if (canvas && isDesktop) {
    var ctx = canvas.getContext("2d");
    var dots = [];
    var running = false;
    var DPR = Math.min(window.devicePixelRatio || 1, 2);

    var resize = function () {
      canvas.width = canvas.offsetWidth * DPR;
      canvas.height = canvas.offsetHeight * DPR;
    };
    var seed = function () {
      dots = [];
      var n = Math.round(canvas.width / (28 * DPR)); // ~50 points en 1440
      for (var i = 0; i < n; i++) {
        dots.push({
          x: Math.random() * canvas.width,
          y: Math.random() * canvas.height,
          r: (Math.random() * 1.4 + 0.4) * DPR,
          s: Math.random() * 0.25 + 0.06,
          p: Math.random() * Math.PI * 2
        });
      }
    };
    var frame = function (t) {
      if (!running) return;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      for (var i = 0; i < dots.length; i++) {
        var d = dots[i];
        d.y -= d.s * DPR;
        if (d.y < -4) { d.y = canvas.height + 4; d.x = Math.random() * canvas.width; }
        var a = 0.25 + Math.sin(t / 900 + d.p) * 0.2;
        ctx.fillStyle = "rgba(163, 230, 53, " + a.toFixed(3) + ")";
        ctx.beginPath();
        ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
        ctx.fill();
      }
      requestAnimationFrame(frame);
    };
    resize(); seed();
    if (!canvas.dataset.g2rdBound) {
      canvas.dataset.g2rdBound = "1";
      window.addEventListener("resize", function () { resize(); seed(); }, { passive: true });
    }
    // N'anime que lorsque le hero est visible (économie CPU)
    ScrollTrigger.create({
      trigger: ".g2rd-hero",
      start: "top bottom",
      end: "bottom top",
      onToggle: function (self) {
        running = self.isActive;
        if (running) requestAnimationFrame(frame);
      }
    });
  }

  /* ---- 5. Parallaxe multi-couches (décor) au scroll ---- */
  document.querySelectorAll("[data-g2rd-depth]").forEach(function (el) {
    var depth = parseFloat(el.dataset.g2rdDepth) || 0.2;
    gsap.to(el, {
      yPercent: depth * -120,
      ease: "none",
      scrollTrigger: {
        trigger: el.closest("section") || el,
        start: "top bottom", end: "bottom top", scrub: true
      }
    });
  });

  /* ---- 6. Reveals & staggers avec relief ----
     v1.5.2 : déclenchés par IntersectionObserver et non plus par ScrollTrigger.
     Sur l'accueil, les reveals situés après la pile de dossiers (pins) ne se
     jouaient pas au premier chargement (il fallait recharger la page).
     L'observer joue le tween dès que l'élément entre dans les 86 % hauts du
     viewport, quel que soit l'état des pins, des rafraîchissements ou de la
     hauteur réelle de la page. Un élément déjà visible est joué aussitôt. */
  var revealIO = function (play) {
    if (!("IntersectionObserver" in window)) return null;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        io.unobserve(entry.target);
        play(entry.target);
      });
    }, { rootMargin: "0px 0px -14% 0px", threshold: 0 });
    observers.push(io);
    return io;
  };
  var revealTweens = new Map();
  var ioReveal = revealIO(function (el) {
    var tw = revealTweens.get(el);
    if (tw) tw.play();
  });
  document.querySelectorAll(".g2rd-reveal").forEach(function (el) {
    var tw = gsap.fromTo(el,
      { opacity: 0, y: 44, rotateX: 5, transformPerspective: 900 },
      { opacity: 1, y: 0, rotateX: 0, duration: 1, ease: EASE, paused: true });
    if (ioReveal) { revealTweens.set(el, tw); ioReveal.observe(el); }
    else tw.play();
  });
  var staggerTweens = new Map();
  var ioStagger = revealIO(function (group) {
    var tw = staggerTweens.get(group);
    if (tw) tw.play();
  });
  document.querySelectorAll("[data-g2rd-stagger]").forEach(function (group) {
    var tw = gsap.fromTo(group.children,
      { opacity: 0, y: 36, rotateX: 6, transformPerspective: 900 },
      { opacity: 1, y: 0, rotateX: 0, duration: 0.6, ease: EASE, stagger: 0.06, paused: true });
    if (ioStagger) { staggerTweens.set(group, tw); ioStagger.observe(group); }
    else tw.play();
  });
  /* Filet de sécurité : si un reveal est dans le viewport sans avoir joué
     (onglet chargé en arrière-plan, ticker GSAP gelé), on le joue au retour
     de visibilité et à chaque fin de défilement. */
  var forceVisible = function () {
    var check = function (tw, el) {
      var r = el.getBoundingClientRect();
      if (r.top < window.innerHeight && r.bottom > 0 && tw.progress() === 0 && !tw.isActive()) tw.play();
    };
    revealTweens.forEach(check);
    staggerTweens.forEach(check);
  };
  document.addEventListener("visibilitychange", function () { if (!document.hidden) forceVisible(); });
  var scrollEndTimer = null;
  window.addEventListener("scroll", function () {
    clearTimeout(scrollEndTimer);
    scrollEndTimer = setTimeout(forceVisible, 250);
  }, { passive: true });

  /* ---- 7. Portfolio : parallaxe des images (scrub) ---- */
  document.querySelectorAll(".g2rd-project__media img").forEach(function (img) {
    gsap.fromTo(img,
      { yPercent: -9 },
      {
        yPercent: 5, ease: "none",
        scrollTrigger: { trigger: img.closest(".g2rd-project"), start: "top bottom", end: "bottom top", scrub: true }
      });
  });

  /* ---- 8. Processus (progression scrubée) + compteurs ---- */
  var track = document.querySelector(".g2rd-process__track i");
  if (track) {
    gsap.to(track, {
      scaleX: 1, ease: "none",
      scrollTrigger: { trigger: ".g2rd-process__grid", start: "top 75%", end: "bottom 55%", scrub: 0.5 }
    });
  }
  var countTweens = new Map();
  var ioCount = revealIO(function (el) {
    var tw = countTweens.get(el);
    if (tw) tw.play();
  });
  document.querySelectorAll("[data-g2rd-count]").forEach(function (el) {
    var target = parseFloat(el.dataset.g2rdCount);
    var obj = { v: 0 };
    var tw = gsap.to(obj, {
      v: target, duration: 1.6, ease: "power2.out", paused: true,
      onUpdate: function () { el.textContent = Math.round(obj.v); }
    });
    if (ioCount) { countTweens.set(el, tw); ioCount.observe(el); }
    else tw.play();
  });

  /* ---- 9. Halo suiveur (sections sombres, desktop) ---- */
  var glow = document.querySelector(".g2rd-glow");
  if (glow && isDesktop && !glow.dataset.g2rdBound) {
    glow.dataset.g2rdBound = "1";
    var qx = gsap.quickTo(glow, "left", { duration: 0.6, ease: "power3.out" });
    var qy = gsap.quickTo(glow, "top", { duration: 0.6, ease: "power3.out" });
    document.addEventListener("mousemove", function (e) {
      var overDark = e.target.closest(".g2rd-theme-ink, .g2rd-theme-slate");
      gsap.to(glow, { opacity: overDark ? 1 : 0, duration: 0.4 });
      qx(e.clientX);
      qy(e.clientY);
    });
  }
  } // fin init()
})();


/* ==========================================================================
   Compteurs — markup Gutenberg (.g2rd-stat__value en texte brut)
   Extrait le nombre du paragraphe ("+50", "100%", "+150%") et l'anime
   au scroll. Respecte prefers-reduced-motion et le bouton "Desactiver".
   ========================================================================== */
(function () {
  "use strict";
  if (typeof window.gsap === "undefined" || typeof window.ScrollTrigger === "undefined") return;
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
  if (document.documentElement.classList.contains("g2rd-anim-off")) return;

  document.querySelectorAll(".g2rd-stat__value").forEach(function (el) {
    if (el.dataset.g2rdCountBound || el.querySelector("[data-g2rd-count]")) return;
    var match = el.textContent.match(/^(\D*?)(\d+)(\D*)$/);
    if (!match) return;
    el.dataset.g2rdCountBound = "1";
    var prefix = match[1];
    var target = parseInt(match[2], 10);
    var suffix = match[3];
    var state = { n: 0 };
    var tw = window.gsap.to(state, {
      n: target,
      duration: 1.4,
      ease: "power2.out",
      paused: true,
      onUpdate: function () {
        el.textContent = prefix + Math.round(state.n) + suffix;
      },
      onComplete: function () {
        el.textContent = prefix + target + suffix;
      }
    });
    /* v1.5.2 : déclenchement par IntersectionObserver (voir §6) */
    if ("IntersectionObserver" in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          io.disconnect();
          tw.play();
        });
      }, { rootMargin: "0px 0px -12% 0px" });
      io.observe(el);
    } else {
      tw.play();
    }
  });
})();


/* ==========================================================================
   Portfolio « dossiers » — pile sticky au scroll (desktop uniquement).
   Chaque dossier se fige sous le header, le suivant glisse par-dessus,
   le précédent recule (scale) puis disparaît complètement (opacity 0) :
   une fois le dossier suivant à sa place définitive, aucun élément ne
   reste visible en transparence. Mobile, reduced-motion et bouton
   « Désactiver les animations » : flux vertical natif, aucun pin.
   Markup Gutenberg : les .wp-block-post de la Query Loop sont en
   display: contents, les .g2rd-case restent les cibles directes.
   ========================================================================== */
(function () {
  "use strict";
  if (typeof window.gsap === "undefined" || typeof window.ScrollTrigger === "undefined") return;
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
  if (document.documentElement.classList.contains("g2rd-anim-off")) return;
  if (!window.matchMedia("(min-width: 1024px)").matches) return;

  var stack = document.querySelector(".g2rd-showcase__stack");
  if (!stack) return;
  var cards = window.gsap.utils.toArray(".g2rd-showcase__stack .g2rd-case");
  if (cards.length < 2) return;

  cards.forEach(function (card, i) {
    if (i === cards.length - 1) return;
    window.ScrollTrigger.create({
      trigger: card,
      start: "top 96px",
      endTrigger: stack,
      end: "bottom 96px",
      pin: true,
      pinSpacing: false
    });
    window.gsap.fromTo(card,
      { scale: 1, opacity: 1 },
      {
        scale: 0.94,
        opacity: 0, /* disparition complète, validée sur la maquette v3.0.2 */
        ease: "none",
        scrollTrigger: {
          trigger: cards[i + 1],
          start: "top bottom",
          end: "top 130px",
          scrub: true
        }
      });
  });
})();
