/* ==========================================================================
   G2RD Child — decor.js
   Injecte tout le décor purement visuel (aria-hidden) afin que les patterns
   et template parts ne contiennent QUE des blocs natifs ou du thème g2rd
   (aucun bloc HTML personnalisé). Même convention que magic-bento.js qui
   injecte déjà le halo et les flèches.

   Chargé AVANT global.js / animations.js / magic-bento.js (dépendances
   déclarées dans functions.php) : les éléments injectés ici (canvas des
   étoiles, panneau, track, bouton animations) existent donc au moment où
   ces scripts les ciblent. Sans JavaScript, le site reste complet : seul
   le décor est absent.

   Toutes les chaînes sont statiques et décoratives (aria-hidden), aucune
   donnée dynamique n'est interpolée.
   ========================================================================== */
(function () {
  "use strict";

  /* ---- 1. Scène du hero d'accueil (étoiles, sol, orbes, lignes) ---- */
  var hero = document.querySelector(".g2rd-hero");
  if (hero && !hero.querySelector(".g2rd-hero__scene")) {
    hero.insertAdjacentHTML("afterbegin",
      '<div class="g2rd-hero__scene" aria-hidden="true">' +
        '<canvas class="g2rd-stars"></canvas>' +
        '<div class="g2rd-hero__floor"></div>' +
        '<span class="g2rd-orb g2rd-orb--a" data-g2rd-depth="0.35"></span>' +
        '<span class="g2rd-orb g2rd-orb--b" data-g2rd-depth="0.2"></span>' +
        '<svg class="g2rd-hero__lines" viewBox="0 0 1440 900" fill="none" preserveAspectRatio="xMidYMid slice">' +
          '<path class="g2rd-line" d="M-40 640 H420 q24 0 24 -24 V330 q0 -24 24 -24 H760" stroke="url(#g2rd-beam)" stroke-width="1.5"/>' +
          '<path class="g2rd-line" d="M1480 210 H1120 q-24 0 -24 24 V520 q0 24 -24 24 H820" stroke="url(#g2rd-beam)" stroke-width="1.5"/>' +
          '<circle class="g2rd-node" cx="760" cy="306" r="4"/>' +
          '<circle class="g2rd-node" cx="820" cy="544" r="4"/>' +
          '<defs><linearGradient id="g2rd-beam" x1="0" y1="0" x2="1" y2="0">' +
            '<stop offset="0" stop-color="#a3e635" stop-opacity="0"/>' +
            '<stop offset="0.5" stop-color="#a3e635" stop-opacity="0.6"/>' +
            '<stop offset="1" stop-color="#a3e635" stop-opacity="0"/>' +
          '</linearGradient></defs>' +
        '</svg>' +
      '</div>');
  }

  /* ---- 2. Panneau « supervision » du hero d'accueil ---- */
  var heroGrid = document.querySelector(".g2rd-hero .g2rd-hero__grid");
  if (heroGrid && !heroGrid.querySelector(".g2rd-hero__panel-wrap")) {
    heroGrid.insertAdjacentHTML("beforeend",
      '<div class="g2rd-hero__panel-wrap" aria-hidden="true">' +
        '<div class="g2rd-hero__panel" data-g2rd-tilt>' +
          '<div class="g2rd-hero__panel-head"><span><i class="g2rd-dot"></i>g2rd — supervision</span><span>temps réel</span></div>' +
          '<div class="g2rd-hero__rows">' +
            '<div class="g2rd-hero__row"><span>build</span><span class="g2rd-hero__bar"><i style="width:96%"></i></span><span>ok</span></div>' +
            '<div class="g2rd-hero__row"><span>seo</span><span class="g2rd-hero__bar"><i style="width:92%"></i></span><span>ok</span></div>' +
            '<div class="g2rd-hero__row"><span>a11y</span><span class="g2rd-hero__bar"><i style="width:95%"></i></span><span>ok</span></div>' +
            '<div class="g2rd-hero__row"><span>sécurité</span><span class="g2rd-hero__bar"><i style="width:98%"></i></span><span>ok</span></div>' +
          '</div>' +
          '<div class="g2rd-hero__chips">' +
            '<span class="g2rd-hero__chip">LCP <strong>&lt; 2,5 s</strong></span>' +
            '<span class="g2rd-hero__chip">CLS <strong>&lt; 0,1</strong></span>' +
            '<span class="g2rd-hero__chip">INP <strong>&lt; 200 ms</strong></span>' +
          '</div>' +
        '</div>' +
      '</div>');
  }

  /* ---- 2bis. Panneau « état du parc » du hero de la page WP Manager ---- */
  var wpmGrid = document.querySelector(".g2rd-page-hero__grid--wpm");
  if (wpmGrid && !wpmGrid.querySelector(".g2rd-hero__panel-wrap")) {
    wpmGrid.insertAdjacentHTML("beforeend",
      '<div class="g2rd-hero__panel-wrap" aria-hidden="true">' +
        '<div class="g2rd-hero__panel" data-g2rd-tilt>' +
          '<div class="g2rd-hero__panel-head"><span><i class="g2rd-dot"></i>wp-manager — état du parc</span><span>temps réel</span></div>' +
          '<div class="g2rd-hero__rows">' +
            '<div class="g2rd-hero__row"><span>sites suivis</span><span class="g2rd-hero__bar"><i style="width:100%"></i></span><span>12</span></div>' +
            '<div class="g2rd-hero__row"><span>maj dispo</span><span class="g2rd-hero__bar"><i style="width:33%"></i></span><span>4</span></div>' +
            '<div class="g2rd-hero__row"><span>cve ouvertes</span><span class="g2rd-hero__bar"><i style="width:16%"></i></span><span>2</span></div>' +
            '<div class="g2rd-hero__row"><span>sauvegardes</span><span class="g2rd-hero__bar"><i style="width:100%"></i></span><span>ok</span></div>' +
          '</div>' +
          '<div class="g2rd-hero__chips">' +
            '<span class="g2rd-hero__chip">Extensions <strong>24</strong></span>' +
            '<span class="g2rd-hero__chip">MAJ prêtes <strong>06</strong></span>' +
            '<span class="g2rd-hero__chip">Sécurité <strong>100&nbsp;%</strong></span>' +
          '</div>' +
        '</div>' +
      '</div>');
  }

  /* ---- 3. Liseré animé de la section bento ---- */
  var bento = document.querySelector(".g2rd-bento");
  if (bento && !bento.querySelector(".g2rd-bento__link-line")) {
    bento.insertAdjacentHTML("afterbegin",
      '<span class="g2rd-bento__link-line" aria-hidden="true"></span>');
  }

  /* ---- 4. Visuels décoratifs des cartes bento ----
     Chaque carte porte un groupe natif VIDE .g2rd-bvis--{variante} ;
     le visuel correspondant est injecté ici. */
  var BVIS = {
    wp:
      '<span class="g2rd-bvis__row" style="--w:78%"></span>' +
      '<span class="g2rd-bvis__row" style="--w:92%"></span>' +
      '<span class="g2rd-bvis__row is-on" style="--w:64%"><i>bloc — hero</i></span>' +
      '<span class="g2rd-bvis__row" style="--w:85%"></span>' +
      '<span class="g2rd-bvis__row" style="--w:52%"></span>',
    woo:
      '<svg viewBox="0 0 240 56" fill="none">' +
        '<path d="M34 28 H96 M146 28 H206" stroke="rgba(148,163,184,.4)" stroke-width="1.5" stroke-dasharray="3 4"/>' +
        '<rect x="4" y="14" width="66" height="28" rx="8" class="g2rd-bvis__chip"/>' +
        '<rect x="90" y="14" width="66" height="28" rx="8" class="g2rd-bvis__chip"/>' +
        '<rect x="176" y="14" width="60" height="28" rx="8" class="g2rd-bvis__chip is-ok"/>' +
        '<text x="37" y="32" text-anchor="middle" class="g2rd-bvis__txt">panier</text>' +
        '<text x="123" y="32" text-anchor="middle" class="g2rd-bvis__txt">paiement</text>' +
        '<text x="206" y="32" text-anchor="middle" class="g2rd-bvis__txt is-ok">validé ✓</text>' +
      '</svg>',
    seo:
      '<svg viewBox="0 0 240 64" fill="none" preserveAspectRatio="none">' +
        '<path d="M4 56 C60 52 88 40 120 30 S196 12 236 6" stroke="#a3e635" stroke-width="2" stroke-linecap="round"/>' +
        '<path d="M4 56 C60 52 88 40 120 30 S196 12 236 6 V64 H4 Z" fill="url(#g2rd-seo-fade)" opacity=".35"/>' +
        '<defs><linearGradient id="g2rd-seo-fade" x1="0" y1="0" x2="0" y2="1">' +
          '<stop stop-color="#a3e635" stop-opacity=".5"/>' +
          '<stop offset="1" stop-color="#a3e635" stop-opacity="0"/>' +
        '</linearGradient></defs>' +
      '</svg>' +
      '<span class="g2rd-bvis__chips"><i>LCP&nbsp;&lt;&nbsp;2,5&nbsp;s</i><i>CLS&nbsp;&lt;&nbsp;0,1</i><i>INP&nbsp;&lt;&nbsp;200&nbsp;ms</i></span>',
    care:
      '<span class="g2rd-bvis__stat"><i class="g2rd-dot"></i>cœur — à jour</span>' +
      '<span class="g2rd-bvis__stat"><i class="g2rd-dot"></i>failles — surveillées</span>' +
      '<span class="g2rd-bvis__stat"><i class="g2rd-dot"></i>sauvegardes — ok</span>',
    auto:
      '<svg viewBox="0 0 200 56" fill="none">' +
        '<path d="M28 28 C60 28 60 12 92 12 M28 28 C60 28 60 44 92 44 M112 12 H150 M112 44 C140 44 148 30 168 30" stroke="rgba(163,230,53,.55)" stroke-width="1.5"/>' +
        '<circle cx="24" cy="28" r="7" class="g2rd-bvis__node is-on"/>' +
        '<circle cx="102" cy="12" r="6" class="g2rd-bvis__node"/>' +
        '<circle cx="102" cy="44" r="6" class="g2rd-bvis__node"/>' +
        '<circle cx="176" cy="30" r="7" class="g2rd-bvis__node is-on"/>' +
      '</svg>',
    wpm:
      '<span class="g2rd-bvis__side"><i></i><i></i><i></i></span>' +
      '<span class="g2rd-bvis__panel">' +
        '<span class="g2rd-bvis__stat"><i class="g2rd-dot"></i>12 sites — à jour</span>' +
        '<span class="g2rd-bvis__stat"><i class="g2rd-dot"></i>0 CVE critique</span>' +
        '<svg viewBox="0 0 180 30" fill="none" preserveAspectRatio="none">' +
          '<path d="M2 24 C30 22 44 14 66 16 S120 6 178 4" stroke="#a3e635" stroke-width="1.5" stroke-linecap="round"/>' +
        '</svg>' +
      '</span>'
  };
  document.querySelectorAll(".g2rd-bvis").forEach(function (el) {
    if (el.childElementCount > 0) return; // déjà rempli (idempotent)
    var variant = null;
    el.classList.forEach(function (c) {
      if (c.indexOf("g2rd-bvis--") === 0) variant = c.slice("g2rd-bvis--".length);
    });
    if (variant && BVIS[variant]) {
      el.setAttribute("aria-hidden", "true");
      el.innerHTML = BVIS[variant];
    }
  });

  /* ---- 5. Piste de progression du processus ---- */
  var processGrid = document.querySelector(".g2rd-process__grid");
  if (processGrid && !processGrid.parentElement.querySelector(".g2rd-process__track")) {
    processGrid.insertAdjacentHTML("beforebegin",
      '<div class="g2rd-process__track" aria-hidden="true"><i></i></div>');
  }

  /* ---- 6. Attributs du placeholder de capture WP Manager ----
     (les blocs natifs ne portent ni data-attributes ni role) */
  document.querySelectorAll(".g2rd-wp-manager__shot").forEach(function (el) {
    if (!el.hasAttribute("data-g2rd-tilt")) el.setAttribute("data-g2rd-tilt", "");
    if (!el.hasAttribute("role")) {
      el.setAttribute("role", "img");
      el.setAttribute("aria-label", "Emplacement réservé à une capture d'écran réelle de WP Manager");
    }
  });

  /* ---- 7. Bouton « Désactiver les animations » (RGAA) ----
     Injecté en tête du footer ; global.js (chargé après) le pilote. */
  var footer = document.querySelector(".g2rd-footer");
  if (footer && !document.querySelector(".g2rd-anim-toggle")) {
    var off = document.documentElement.classList.contains("g2rd-anim-off");
    footer.insertAdjacentHTML("afterbegin",
      '<button class="g2rd-anim-toggle" type="button" aria-pressed="' + (off ? "true" : "false") + '">' +
        '<span class="g2rd-anim-toggle__dot" aria-hidden="true"></span>' +
        '<span class="g2rd-anim-toggle__label">' + (off ? "Activer les animations" : "Désactiver les animations") + '</span>' +
      '</button>');
  }
})();
