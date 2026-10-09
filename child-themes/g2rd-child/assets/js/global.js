/**
 * G2RD Child — global.js
 * Comportements d'interface hors GSAP. Chaque bloc verifie ses elements.
 */
(function () {
  "use strict";

  document.documentElement.classList.add("js");

  /* ---- Header : etat au scroll ---- */
  var header = document.querySelector(".g2rd-header");
  if (header) {
    var onScroll = function () {
      header.classList.toggle("is-scrolled", window.scrollY > 24);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  /* ---- Decoupage des libelles de boutons (effet de traversee du point) ----
     Compatible boutons Gutenberg (.wp-block-button.g2rd-btn) : l'effet vise
     le lien interne. HTML source intact, lettres aria-hidden, nom accessible
     conserve. Aucun impact SEO. */
  if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    document.querySelectorAll(".g2rd-btn").forEach(function (btn) {
      var target = btn.classList.contains("wp-block-button")
        ? btn.querySelector(".wp-block-button__link")
        : btn;
      if (!target || target.querySelector(".g2rd-btn__label")) return;
      var label = target.textContent.replace(/\s+/g, " ").trim();
      if (!label) return;
      /* RGAA 13.2 : avertir de l'ouverture dans une nouvelle fenetre */
      var accessibleLabel = target.getAttribute("target") === "_blank"
        ? label + " (nouvelle fenêtre)"
        : label;
      target.setAttribute("aria-label", accessibleLabel);
      var wrap = document.createElement("span");
      wrap.className = "g2rd-btn__label";
      wrap.setAttribute("aria-hidden", "true");
      var chars = Array.from(label);
      var n = chars.filter(function (c) { return c !== " "; }).length;
      var i = 0;
      chars.forEach(function (c) {
        if (c === " ") { wrap.appendChild(document.createTextNode(" ")); return; }
        var s = document.createElement("span");
        s.className = "g2rd-btn__char";
        s.style.setProperty("--i", i++);
        s.textContent = c;
        wrap.appendChild(s);
      });
      target.textContent = "";
      target.appendChild(wrap);
      target.style.setProperty("--n", n);
    });
  }


  /* ---- RGAA 13.2 : liens ouvrant une nouvelle fenetre (hors boutons decoupes) ---- */
  document.querySelectorAll('a[target="_blank"]:not(.wp-block-button__link)').forEach(function (link) {
    if (link.getAttribute("aria-label")) return;
    var text = link.textContent.replace(/\s+/g, " ").trim();
    if (text && text.indexOf("nouvelle fenêtre") === -1) {
      link.setAttribute("aria-label", text + " (nouvelle fenêtre)");
    }
  });

  /* ---- Marquee technologies : duplication de la liste pour la boucle ---- */
  var track = document.querySelector(".g2rd-tech__track");
  if (track) {
    var techList = track.querySelector("ul");
    if (techList && !track.querySelector("[data-g2rd-clone]")) {
      var clone = techList.cloneNode(true);
      clone.setAttribute("aria-hidden", "true");
      clone.setAttribute("data-g2rd-clone", "1");
      track.appendChild(clone);
    }
  }

  /* ---- Titres animes (bloc parent g2rd/advanced-heading) ----
     Le bloc ne gere pas prefers-reduced-motion : on fige la rotation sur le
     premier mot (le H1 SEO par defaut) quand l'utilisateur refuse le mouvement. */
  var freezeAdvancedHeadings = function () {
    document.querySelectorAll(".g2rd-adv-heading__animated-wrap").forEach(function (wrap) {
      var first = wrap.querySelector(".g2rd-adv-heading__word");
      if (!first) return;
      var word = document.createElement("span");
      word.className = "g2rd-adv-heading__word is-visible";
      word.textContent = first.textContent;
      wrap.replaceChildren(word);
      wrap.style.cssText = "";
    });
  };
  if (
    window.matchMedia("(prefers-reduced-motion: reduce)").matches ||
    document.documentElement.classList.contains("g2rd-anim-off")
  ) {
    freezeAdvancedHeadings();
  }

  /* ---- Controle utilisateur des animations (RGAA 13.8 / WCAG 2.2.2) ---- */
  var toggle = document.querySelector(".g2rd-anim-toggle");
  if (toggle) {
    var label = toggle.querySelector(".g2rd-anim-toggle__label");
    var isOff = function () {
      return document.documentElement.classList.contains("g2rd-anim-off");
    };
    var render = function () {
      var off = isOff();
      toggle.setAttribute("aria-pressed", String(off));
      if (label) {
        label.textContent = off ? "Activer les animations" : "Désactiver les animations";
      }
    };
    render();
    toggle.addEventListener("click", function () {
      var off = !isOff();
      document.documentElement.classList.toggle("g2rd-anim-off", off);
      try { localStorage.setItem("g2rd-anim", off ? "off" : "on"); } catch (e) {}
      if (window.g2rdAnim) {
        window.g2rdAnim[off ? "disable" : "enable"]();
      }
      if (off) document.dispatchEvent(new CustomEvent("g2rd:anim-off"));
      if (off) {
        freezeAdvancedHeadings();
      }
      render();
    });
  }
})();
