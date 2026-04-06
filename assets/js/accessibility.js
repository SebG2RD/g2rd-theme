/**
 * Gestion de l'accessibilité
 *
 * @package G2RD
 * @since 1.0.2
 */

(function () {
  "use strict";

  // Vérifier si on est en front-end
  function isFrontEnd() {
    return !document.body.classList.contains("wp-admin");
  }

  // Gestion de la navigation au clavier
  function handleKeyboardNavigation() {
    document.addEventListener("keydown", (e) => {
      if (e.key === "Tab") {
        document.body.classList.add("keyboard-navigation");
      }
    });

    document.addEventListener("mousedown", () => {
      document.body.classList.remove("keyboard-navigation");
    });
  }

  // Support des raccourcis clavier
  function setupKeyboardShortcuts() {
    document.addEventListener("keydown", (e) => {
      // Alt + M : Aller au contenu principal
      if (e.altKey && e.key === "m") {
        e.preventDefault();
        const main = document.getElementById("main");
        if (main) {
          main.focus();
        }
      }

      // Alt + S : Aller à la recherche
      if (e.altKey && e.key === "s") {
        e.preventDefault();
        const search = document.querySelector(
          '.search-form input[type="search"]'
        );
        if (search && "focus" in search) {
          search.focus();
        }
      }
    });
  }

  // Gestion des modales
  function setupModals() {
    const modals = document.querySelectorAll('[role="dialog"]');

    modals.forEach((modal) => {
      const closeButton = modal.querySelector('[aria-label="Fermer"]');
      if (closeButton) {
        closeButton.addEventListener("click", () => {
          modal.setAttribute("aria-hidden", "true");
          document.body.classList.remove("modal-open");
        });
      }

      // Focus trap
      modal.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
          modal.setAttribute("aria-hidden", "true");
          document.body.classList.remove("modal-open");
        }
      });
    });
  }

  // Gestion des formulaires
  function setupForms() {
    const forms = document.querySelectorAll("form");

    forms.forEach((form) => {
      const requiredFields = form.querySelectorAll("[required]");

      requiredFields.forEach((field) => {
        const label = field.previousElementSibling;
        if (label && label.tagName === "LABEL") {
          const requiredSpan = document.createElement("span");
          requiredSpan.className = "required";
          requiredSpan.textContent = " *";
          label.appendChild(requiredSpan);
        }
      });

      // Validation en temps réel
      form.addEventListener("input", (e) => {
        const target = e.target;
        if (
          target &&
          "hasAttribute" in target &&
          target.hasAttribute("required")
        ) {
          validateField(target);
        }
      });
    });
  }

  // Validation des champs
  function validateField(field) {
    const errorMessage = field.nextElementSibling;

    if (!field.value.trim()) {
      if (!errorMessage || !errorMessage.classList.contains("error-message")) {
        const error = document.createElement("div");
        error.className = "error-message";
        error.textContent = "Ce champ est requis";
        field.parentNode.insertBefore(error, field.nextSibling);
      }
      field.setAttribute("aria-invalid", "true");
    } else {
      if (errorMessage && errorMessage.classList.contains("error-message")) {
        errorMessage.remove();
      }
      field.setAttribute("aria-invalid", "false");
    }
  }

  // Support des animations réduites
  function handleReducedMotion() {
    const prefersReducedMotion = window.matchMedia(
      "(prefers-reduced-motion: reduce)"
    );

    if (prefersReducedMotion.matches) {
      document.body.classList.add("reduced-motion");
    }
  }

  // Fonction pour détecter automatiquement le chemin du thème
  function getThemePath() {
    // Méthode 1: Chercher dans les liens CSS
    const cssLinks = document.querySelectorAll('link[rel="stylesheet"]');
    for (let link of cssLinks) {
      const href = link.getAttribute("href");
      if (href && href.includes("/wp-content/themes/")) {
        const match = href.match(/(.+\/wp-content\/themes\/[^\/]+)/);
        if (match) {
          console.log("Chemin du thème détecté (CSS):", match[1]);
          return match[1];
        }
      }
    }

    // Méthode 2: Chercher dans les scripts
    const scripts = document.querySelectorAll("script[src]");
    for (let script of scripts) {
      const src = script.getAttribute("src");
      if (src && src.includes("/wp-content/themes/")) {
        const match = src.match(/(.+\/wp-content\/themes\/[^\/]+)/);
        if (match) {
          console.log("Chemin du thème détecté (JS):", match[1]);
          return match[1];
        }
      }
    }

    // Méthode 3: Utiliser l'URL actuelle + chemin standard
    const origin = window.location.origin;
    const fallbackPath = `${origin}/wp-content/themes/g2rd-theme`;
    console.log("Chemin du thème (fallback):", fallbackPath);
    return fallbackPath;
  }

  // Création du panneau d'accessibilité
  function createAccessibilityPanel() {
    const panel = document.createElement("div");
    panel.className = "accessibility-panel";
    panel.innerHTML = `
      <div class="accessibility-panel__header">
        <h2 class="accessibility-panel__title">Accessibilité</h2>
        <button class="accessibility-panel__toggle" aria-expanded="false" aria-label="Fermer le panneau d’accessibilité">
          <span style="display:inline-block;width:24px;height:24px;border-radius:50%;border:2px solid currentColor;position:relative;vertical-align:middle;">
            <span style="display:block;width:12px;height:12px;border-radius:50%;background:currentColor;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);"></span>
          </span>
        </button>
      </div>
      <div class="accessibility-panel__content">
        <button class="accessibility-panel__button" data-action="increase-text">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4v3h5v12h3V7h5V4H9zm-6 8h3v7h3v-7h3V9H3v3z" fill="currentColor"/>
          </svg>
          Augmenter la taille du texte
        </button>
        <button class="accessibility-panel__button" data-action="decrease-text">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4v3h5v12h3V7h5V4H9zm-6 8h9v3H3v-3z" fill="currentColor"/>
          </svg>
          Diminuer la taille du texte
        </button>
        <button class="accessibility-panel__button" data-action="toggle-contrast">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/>
            <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" fill="currentColor"/>
          </svg>
          Contraste élevé
        </button>
        <button class="accessibility-panel__button" data-action="toggle-motion">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" fill="currentColor"/>
            <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" fill="currentColor"/>
          </svg>
          Réduire les animations
        </button>
        <button class="accessibility-panel__button" data-action="line-height">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4v3h5v12h3V7h5V4H9zm-6 8h3v7h3v-7h3V9H3v3z" fill="currentColor"/>
          </svg>
          Hauteur de ligne
        </button>
        <button class="accessibility-panel__button" data-action="text-align">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4v3h5v12h3V7h5V4H9zm-6 8h3v7h3v-7h3V9H3v3z" fill="currentColor"/>
          </svg>
          Alignement du texte
        </button>
        <button class="accessibility-panel__button" data-action="readable-font">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4v3h5v12h3V7h5V4H9zm-6 8h3v7h3v-7h3V9H3v3z" fill="currentColor"/>
          </svg>
          Police lisible
        </button>
        <button class="accessibility-panel__button" data-action="grayscale">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4v3h5v12h3V7h5V4H9zm-6 8h3v7h3v-7h3V9H3v3z" fill="currentColor"/>
          </svg>
          Niveaux de gris
        </button>
        <button class="accessibility-panel__button" data-action="reading-mask">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4v3h5v12h3V7h5V4H9zm-6 8h3v7h3v-7h3V9H3v3z" fill="currentColor"/>
          </svg>
          Masque de lecture
        </button>
        <button class="accessibility-panel__button" data-action="hide-images">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4v3h5v12h3V7h5V4H9zm-6 8h3v7h3v-7h3V9H3v3z" fill="currentColor"/>
          </svg>
          Masquer les images
        </button>
        <button class="accessibility-panel__button" data-action="pause-animations">
          <svg class="accessibility-panel__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4v3h5v12h3V7h5V4H9zm-6 8h3v7h3v-7h3V9H3v3z" fill="currentColor"/>
          </svg>
          Mettre en pause les animations
        </button>
      </div>
    `;
    document.body.appendChild(panel);
    return panel;
  }

  // Gestion du masque de lecture
  function enableReadingMask() {
    function updateMask(e) {
      document.body.style.setProperty(
        "--reading-mask-top",
        e.clientY - 40 + "px"
      );
    }
    document.addEventListener("mousemove", updateMask);
    document.body._readingMaskHandler = updateMask;
  }

  function disableReadingMask() {
    if (document.body._readingMaskHandler) {
      document.removeEventListener(
        "mousemove",
        document.body._readingMaskHandler
      );
      document.body._readingMaskHandler = null;
    }
    document.body.style.removeProperty("--reading-mask-top");
  }

  // Gestion du panneau d'accessibilité
  function setupAccessibilityPanel() {
    // Ne créer le panneau que si on est en front-end
    if (!isFrontEnd()) {
      return;
    }

    const panel = createAccessibilityPanel();
    const toggle = panel.querySelector(".accessibility-panel__toggle");
    const buttons = panel.querySelectorAll(".accessibility-panel__button");

    // Toggle du panneau
    if (toggle) {
      toggle.addEventListener("click", () => {
        const isExpanded = toggle.getAttribute("aria-expanded") === "true";
        toggle.setAttribute("aria-expanded", String(!isExpanded));
        panel.classList.toggle("is-open");
      });
    }

    // Gestion des boutons
    buttons.forEach((button) => {
      button.addEventListener("click", () => {
        const action = button.getAttribute("data-action");

        switch (action) {
          case "increase-text":
            document.body.classList.add("text-larger");
            document.body.classList.remove("text-large");
            break;
          case "decrease-text":
            document.body.classList.add("text-large");
            document.body.classList.remove("text-larger");
            break;
          case "toggle-contrast":
            document.body.classList.toggle("high-contrast");
            button.classList.toggle("is-active");
            break;
          case "toggle-motion":
            document.body.classList.toggle("reduced-motion");
            button.classList.toggle("is-active");
            break;
          case "line-height":
            document.body.classList.toggle("line-height-large");
            break;
          case "text-align":
            document.body.classList.toggle("text-align-center");
            break;
          case "readable-font":
            document.body.classList.toggle("readable-font");
            break;
          case "grayscale":
            document.body.classList.toggle("grayscale");
            break;
          case "reading-mask":
            document.body.classList.toggle("reading-mask");
            if (document.body.classList.contains("reading-mask")) {
              enableReadingMask();
            } else {
              disableReadingMask();
            }
            break;
          case "hide-images":
            document.body.classList.toggle("hide-images");
            break;
          case "pause-animations":
            document.body.classList.toggle("pause-animations");
            break;
        }
      });
    });

    // Ajout d'un bouton flottant d'ouverture du panneau d'accessibilité
    const floatingBtn = createFloatingAccessibilityButton();
    floatingBtn.addEventListener("click", () => {
      panel.classList.toggle("is-open");
    });
  }

  // Ajout d'un bouton flottant d'ouverture du panneau d'accessibilité
  function createFloatingAccessibilityButton() {
    const btn = document.createElement("button");
    btn.className = "accessibility-floating-btn";
    btn.setAttribute("aria-label", "Ouvrir le panneau d'accessibilité");

    // Récupération automatique du chemin du thème
    const themePath = getThemePath();

    btn.innerHTML = `
      <span style="font-size: 24px; line-height: 1; display: block; text-align: center;">Ⓐ</span>
    `;
    document.body.appendChild(btn);
    return btn;
  }

  // Ajout du bouton scroll to top
  function createScrollToTopButton() {
    const btn = document.createElement("button");
    btn.className = "scroll-to-top-btn";
    btn.setAttribute("aria-label", "Remonter en haut de la page");

    // Récupération automatique du chemin du thème
    const themePath = getThemePath();

    btn.innerHTML = `
      <span style="font-size: 24px; line-height: 1; display: block; text-align: center;">⇪</span>
    `;
    btn.style.display = "none";
    document.body.appendChild(btn);
    btn.addEventListener("click", () => {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
    return btn;
  }

  // Initialisation
  function init() {
    // Ne charger que les fonctionnalités front-end si on est en front-end
    if (isFrontEnd()) {
      handleKeyboardNavigation();
      setupKeyboardShortcuts();
      setupModals();
      setupForms();
      handleReducedMotion();
      setupAccessibilityPanel();
      const scrollBtn = createScrollToTopButton();
      window.addEventListener("scroll", () => {
        if (window.scrollY > 200) {
          scrollBtn.style.display = "flex";
        } else {
          scrollBtn.style.display = "none";
        }
      });
    }
  }

  // Démarrage
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
