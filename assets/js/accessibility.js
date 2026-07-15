/**
 * Gestion de l'accessibilité
 *
 * @package G2RD
 * @since 1.0.2
 */

(function () {
  "use strict";

  var STORAGE_KEY = "g2rd_a11y";
  var PANEL_KEY   = "g2rd_a11y_open";

  var ACTIONS = [
    { key: "increase-text",      label: "Agrandir le texte",            toggle: false },
    { key: "decrease-text",      label: "Réduire le texte",             toggle: false },
    { key: "readable-font",      label: "Police lisible (dyslexie)",    toggle: true  },
    { key: "line-height",        label: "Interligne augmenté",          toggle: true  },
    { key: "text-align-left",    label: "Texte aligné à gauche",        toggle: true  },
    { key: "toggle-contrast",    label: "Contraste élevé",              toggle: true  },
    { key: "grayscale",          label: "Niveaux de gris",              toggle: true  },
    { key: "toggle-motion",      label: "Réduire les animations",       toggle: true  },
    { key: "pause-animations",   label: "Pause des animations",         toggle: true  },
    { key: "reading-mask",       label: "Masque de lecture",            toggle: true  },
    { key: "hide-images",        label: "Masquer les images",           toggle: true  },
  ];

  var ICONS = {
    "increase-text":    '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><text x="2" y="18" font-size="14" font-weight="700" fill="currentColor">A</text><text x="13" y="21" font-size="10" fill="currentColor">A</text><path d="M20 10h-2V8h-2v2h-2v2h2v2h2v-2h2v-2z" fill="currentColor"/></svg>',
    "decrease-text":    '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><text x="2" y="18" font-size="10" fill="currentColor">A</text><text x="11" y="21" font-size="14" font-weight="700" fill="currentColor">A</text><path d="M20 11h-6v2h6v-2z" fill="currentColor"/></svg>',
    "readable-font":    '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4v3h4.5v11h3V7H17V4H5z" fill="currentColor"/><path d="M3 20h18v1H3z" fill="currentColor" opacity=".4"/></svg>',
    "line-height":      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h12M6 12h12M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3 5l2-2 2 2M3 19l2 2 2-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    "text-align-left":  '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 5h18M3 10h12M3 15h18M3 20h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    "toggle-contrast":  '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 3v18A9 9 0 0012 3z" fill="currentColor"/></svg>',
    "grayscale":        '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M9 6.5A5.5 5.5 0 0012 17.5V6.5H9z" fill="currentColor"/></svg>',
    "toggle-motion":    '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4a8 8 0 100 16A8 8 0 0012 4z" stroke="currentColor" stroke-width="2"/><path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    "pause-animations": '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1" fill="currentColor"/><rect x="14" y="5" width="4" height="14" rx="1" fill="currentColor"/></svg>',
    "reading-mask":     '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="9" width="20" height="6" rx="2" fill="currentColor" opacity=".3"/><rect x="2" y="10" width="20" height="4" rx="1" fill="currentColor"/></svg>',
    "hide-images":      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="14" height="11" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 13l4-4 3 3 2-2 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 2l20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
  };

  // ─── État ────────────────────────────────────────────────────────────────────

  var state = {
    textLevel:        0,  // -1 small, 0 normal, 1 large, 2 larger
    "readable-font":      false,
    "line-height":        false,
    "text-align-left":    false,
    "toggle-contrast":    false,
    "grayscale":          false,
    "toggle-motion":      false,
    "pause-animations":   false,
    "reading-mask":       false,
    "hide-images":        false,
  };

  var CLASS_MAP = {
    "readable-font":    "a11y-readable-font",
    "line-height":      "a11y-line-height",
    "text-align-left":  "a11y-text-left",
    "toggle-contrast":  "a11y-high-contrast",
    "grayscale":        "a11y-grayscale",
    "toggle-motion":    "a11y-reduced-motion",
    "pause-animations": "a11y-pause-anim",
    "reading-mask":     "a11y-reading-mask",
    "hide-images":      "a11y-hide-images",
  };

  var TEXT_CLASSES = ["a11y-text-small", "a11y-text-large", "a11y-text-larger"];

  // ─── Persistence ─────────────────────────────────────────────────────────────

  function loadState() {
    try {
      var saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || "{}");
      if (typeof saved.textLevel === "number") state.textLevel = saved.textLevel;
      Object.keys(CLASS_MAP).forEach(function (key) {
        if (typeof saved[key] === "boolean") state[key] = saved[key];
      });
    } catch (_) {}
  }

  function saveState() {
    try {
      var toSave = { textLevel: state.textLevel };
      Object.keys(CLASS_MAP).forEach(function (key) { toSave[key] = state[key]; });
      localStorage.setItem(STORAGE_KEY, JSON.stringify(toSave));
    } catch (_) {}
  }

  function resetState() {
    state.textLevel = 0;
    Object.keys(CLASS_MAP).forEach(function (key) { state[key] = false; });
    saveState();
  }

  // ─── Application des classes ──────────────────────────────────────────────────

  function applyState(panel) {
    var body = document.body;

    TEXT_CLASSES.forEach(function (c) { body.classList.remove(c); });
    if (state.textLevel === -1) body.classList.add("a11y-text-small");
    if (state.textLevel === 1)  body.classList.add("a11y-text-large");
    if (state.textLevel === 2)  body.classList.add("a11y-text-larger");

    Object.keys(CLASS_MAP).forEach(function (key) {
      body.classList.toggle(CLASS_MAP[key], state[key]);
    });

    if (state["reading-mask"]) {
      enableReadingMask();
    } else {
      disableReadingMask();
    }

    if (panel) syncButtonStates(panel);
  }

  function syncButtonStates(panel) {
    panel.querySelectorAll(".a11y-btn[data-action]").forEach(function (btn) {
      var action = btn.getAttribute("data-action");
      if (action === "increase-text" || action === "decrease-text") return;
      if (CLASS_MAP[action]) {
        btn.classList.toggle("is-active", !!state[action]);
        btn.setAttribute("aria-pressed", state[action] ? "true" : "false");
      }
    });

    var textBtns = panel.querySelector(".a11y-text-controls");
    if (textBtns) {
      textBtns.querySelector('[data-action="increase-text"]').disabled = state.textLevel >= 2;
      textBtns.querySelector('[data-action="decrease-text"]').disabled = state.textLevel <= -1;
    }
  }

  // ─── Annonces aria-live ───────────────────────────────────────────────────────

  var liveRegion;

  function announce(msg) {
    if (!liveRegion) return;
    liveRegion.textContent = "";
    setTimeout(function () { liveRegion.textContent = msg; }, 50);
  }

  // ─── Masque de lecture ────────────────────────────────────────────────────────

  function enableReadingMask() {
    if (document.body._a11yMaskHandler) return;
    function update(e) {
      document.body.style.setProperty("--a11y-mask-top", (e.clientY - 40) + "px");
    }
    document.addEventListener("mousemove", update);
    document.body._a11yMaskHandler = update;
  }

  function disableReadingMask() {
    if (document.body._a11yMaskHandler) {
      document.removeEventListener("mousemove", document.body._a11yMaskHandler);
      document.body._a11yMaskHandler = null;
    }
    document.body.style.removeProperty("--a11y-mask-top");
  }

  // ─── Création du panneau ──────────────────────────────────────────────────────

  function buildButton(action) {
    var def = ACTIONS.find(function (a) { return a.key === action; });
    if (!def) return "";
    var icon = ICONS[action] || "";
    var ariaPressed = def.toggle ? ' aria-pressed="false"' : "";
    return (
      '<button class="a11y-btn" data-action="' + action + '"' + ariaPressed + ">" +
      '<span class="a11y-btn__icon">' + icon + "</span>" +
      '<span class="a11y-btn__label">' + def.label + "</span>" +
      "</button>"
    );
  }

  function createPanel() {
    var panel = document.createElement("div");
    panel.className = "a11y-panel";
    panel.setAttribute("role", "region");
    panel.setAttribute("aria-label", "Options d'accessibilité");

    panel.innerHTML =
      '<div class="a11y-panel__header">' +
        '<h2 class="a11y-panel__title">Accessibilité</h2>' +
        '<button class="a11y-panel__close" aria-label="Fermer le panneau d\'accessibilité">' +
          '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
        "</button>" +
      "</div>" +
      '<div class="a11y-panel__body">' +

        '<section class="a11y-section">' +
          '<h3 class="a11y-section__title">Texte</h3>' +
          '<div class="a11y-text-controls">' +
            buildButton("decrease-text") +
            '<span class="a11y-text-level" aria-live="polite" aria-atomic="true">Normal</span>' +
            buildButton("increase-text") +
          "</div>" +
          buildButton("readable-font") +
          buildButton("line-height") +
          buildButton("text-align-left") +
        "</section>" +

        '<section class="a11y-section">' +
          '<h3 class="a11y-section__title">Affichage</h3>' +
          buildButton("toggle-contrast") +
          buildButton("grayscale") +
          buildButton("hide-images") +
        "</section>" +

        '<section class="a11y-section">' +
          '<h3 class="a11y-section__title">Mouvement</h3>' +
          buildButton("toggle-motion") +
          buildButton("pause-animations") +
          buildButton("reading-mask") +
        "</section>" +

        '<section class="a11y-section a11y-section--presets">' +
          '<h3 class="a11y-section__title">Profils rapides</h3>' +
          '<div class="a11y-presets">' +
            '<button class="a11y-preset" data-preset="dyslexia">Dyslexie</button>' +
            '<button class="a11y-preset" data-preset="low-vision">Basse vision</button>' +
            '<button class="a11y-preset" data-preset="motion">Anti-mouvement</button>' +
          "</div>" +
        "</section>" +

        '<button class="a11y-reset">Réinitialiser</button>' +

      "</div>";

    liveRegion = document.createElement("div");
    liveRegion.setAttribute("aria-live", "polite");
    liveRegion.setAttribute("aria-atomic", "true");
    liveRegion.className = "a11y-live";
    panel.appendChild(liveRegion);

    // Ajouté au wrapper .a11y-widget par setupPanel (pas directement au body),
    // pour rester solidaire du bouton même sous un ancêtre transformé.
    return panel;
  }

  // ─── Logique d'action ─────────────────────────────────────────────────────────

  var TEXT_LABELS = ["Très petit", "Petit", "Normal", "Grand", "Très grand"];

  function getLevelLabel() {
    return TEXT_LABELS[state.textLevel + 2] || "Normal";
  }

  function handleAction(action, panel) {
    switch (action) {
      case "increase-text":
        if (state.textLevel < 2) {
          state.textLevel++;
          announce("Taille du texte : " + getLevelLabel());
        }
        break;
      case "decrease-text":
        if (state.textLevel > -1) {
          state.textLevel--;
          announce("Taille du texte : " + getLevelLabel());
        }
        break;
      default:
        if (typeof state[action] === "boolean") {
          state[action] = !state[action];
          var def = ACTIONS.find(function (a) { return a.key === action; });
          announce((def ? def.label : action) + " : " + (state[action] ? "activé" : "désactivé"));
        }
    }

    var lvlEl = panel.querySelector(".a11y-text-level");
    if (lvlEl) lvlEl.textContent = getLevelLabel();

    applyState(panel);
    saveState();
  }

  function applyPreset(preset, panel) {
    resetState();
    switch (preset) {
      case "dyslexia":
        state["readable-font"]  = true;
        state["line-height"]    = true;
        state["text-align-left"] = true;
        state.textLevel         = 1;
        announce("Profil Dyslexie activé");
        break;
      case "low-vision":
        state.textLevel           = 2;
        state["toggle-contrast"]  = true;
        announce("Profil Basse vision activé");
        break;
      case "motion":
        state["toggle-motion"]    = true;
        state["pause-animations"] = true;
        announce("Profil Anti-mouvement activé");
        break;
    }
    var lvlEl = panel.querySelector(".a11y-text-level");
    if (lvlEl) lvlEl.textContent = getLevelLabel();
    applyState(panel);
    saveState();
  }

  // ─── Bouton flottant ──────────────────────────────────────────────────────────

  function createFloatingButton() {
    var btn = document.createElement("button");
    btn.className = "a11y-float-btn";
    btn.setAttribute("aria-label", "Ouvrir le panneau d'accessibilité");
    btn.setAttribute("aria-expanded", "false");
    btn.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">' +
        '<circle cx="12" cy="4" r="2" fill="currentColor"/>' +
        '<path d="M16 8H8l-2 6h3l-1 6h8l-1-6h3l-2-6z" fill="currentColor"/>' +
      "</svg>";
    // Ajouté au wrapper .a11y-widget par setupPanel.
    return btn;
  }

  // ─── Bouton scroll to top ─────────────────────────────────────────────────────

  function createScrollToTopButton() {
    var btn = document.createElement("button");
    btn.className = "a11y-scroll-top";
    btn.setAttribute("aria-label", "Remonter en haut de la page");
    btn.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">' +
        '<path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
      "</svg>";
    btn.hidden = true;
    document.body.appendChild(btn);
    btn.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
    return btn;
  }

  // ─── Navigation clavier ───────────────────────────────────────────────────────

  function handleKeyboardNavigation() {
    document.addEventListener("keydown", function (e) {
      if (e.key === "Tab") document.body.classList.add("a11y-keyboard-nav");
    });
    document.addEventListener("mousedown", function () {
      document.body.classList.remove("a11y-keyboard-nav");
    });
  }

  function setupKeyboardShortcuts() {
    document.addEventListener("keydown", function (e) {
      if (e.altKey && e.key === "m") {
        e.preventDefault();
        var main = document.getElementById("main");
        if (main) main.focus();
      }
      if (e.altKey && e.key === "s") {
        e.preventDefault();
        var search = document.querySelector('.search-form input[type="search"]');
        if (search) search.focus();
      }
    });
  }

  // ─── Motions système ──────────────────────────────────────────────────────────

  function handleReducedMotion() {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      document.body.classList.add("a11y-reduced-motion");
    }
  }

  // ─── Setup du panneau ─────────────────────────────────────────────────────────

  function setupPanel() {
    // Conteneur fixe unique : le bouton et le panneau y vivent ensemble, donc
    // le panneau reste ancré au bouton même si un ancêtre du body est transformé
    // (transform/filter/will-change capturent sinon le position:fixed du panneau).
    var wrapper = document.createElement("div");
    wrapper.className = "a11y-widget";

    var panel = createPanel();
    var floatBtn = createFloatingButton();

    wrapper.appendChild(floatBtn);
    wrapper.appendChild(panel);
    document.body.appendChild(wrapper);

    function openPanel() {
      panel.classList.add("is-open");
      floatBtn.setAttribute("aria-expanded", "true");
      floatBtn.setAttribute("aria-label", "Fermer le panneau d'accessibilité");
      try { localStorage.setItem(PANEL_KEY, "1"); } catch (_) {}
    }

    function closePanel() {
      panel.classList.remove("is-open");
      floatBtn.setAttribute("aria-expanded", "false");
      floatBtn.setAttribute("aria-label", "Ouvrir le panneau d'accessibilité");
      try { localStorage.removeItem(PANEL_KEY); } catch (_) {}
    }

    floatBtn.addEventListener("click", function () {
      if (panel.classList.contains("is-open")) {
        closePanel();
      } else {
        openPanel();
      }
    });

    panel.querySelector(".a11y-panel__close").addEventListener("click", closePanel);

    panel.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        closePanel();
        floatBtn.focus();
      }
    });

    panel.querySelectorAll(".a11y-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        handleAction(btn.getAttribute("data-action"), panel);
      });
    });

    panel.querySelectorAll(".a11y-preset").forEach(function (btn) {
      btn.addEventListener("click", function () {
        applyPreset(btn.getAttribute("data-preset"), panel);
      });
    });

    panel.querySelector(".a11y-reset").addEventListener("click", function () {
      resetState();
      var lvlEl = panel.querySelector(".a11y-text-level");
      if (lvlEl) lvlEl.textContent = "Normal";
      applyState(panel);
      announce("Préférences réinitialisées");
    });

    try {
      if (localStorage.getItem(PANEL_KEY) === "1") openPanel();
    } catch (_) {}

    return panel;
  }

  // ─── Init ─────────────────────────────────────────────────────────────────────

  function init() {
    if (document.body.classList.contains("wp-admin")) return;

    // Flags posés par le thème : quels éléments créer. Défaut : les deux (repli
    // si la config n'est pas injectée, ex. ancien cache).
    var cfg = window.g2rdA11yConfig || { panel: true, backToTop: true };

    // ── Panneau d'accessibilité ────────────────────────────────────────────────
    if (cfg.panel) {
      handleKeyboardNavigation();
      setupKeyboardShortcuts();
      handleReducedMotion();

      loadState();

      var panel = setupPanel();
      var lvlEl = panel.querySelector(".a11y-text-level");
      if (lvlEl) lvlEl.textContent = getLevelLabel();
      applyState(panel);
    }

    // ── Bouton retour en haut ──────────────────────────────────────────────────
    if (cfg.backToTop) {
      var scrollBtn = createScrollToTopButton();
      var ticking = false;
      window.addEventListener("scroll", function () {
        if (!ticking) {
          window.requestAnimationFrame(function () {
            scrollBtn.hidden = window.scrollY <= 200;
            ticking = false;
          });
          ticking = true;
        }
      }, { passive: true });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
