/**
 * Bloc g2rd/route-map — script de rendu public.
 *
 * Le tracé, le profil et les statistiques sont déjà calculés côté serveur. Ce
 * script ne fait qu'instancier Leaflet, poser le tracé et les marqueurs.
 *
 * La carte n'est initialisée que lorsqu'elle approche du champ de vision : elle
 * se trouve souvent sous le premier écran, et charger les tuiles immédiatement
 * pénaliserait le LCP pour rien.
 *
 * Aucune chaîne de langue ni aucune couleur n'est écrite ici : les libellés
 * viennent de render.php, les couleurs des variables CSS du thème — le tracé
 * suit donc la variation de styles active.
 *
 * @package G2RD
 * @since   1.36.0
 */

(function () {
  "use strict";

  var MOUVEMENT_REDUIT = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /**
   * Lit une variable CSS sur l'élément, avec repli.
   *
   * @param {Element} element  Élément porteur.
   * @param {string}  variable Nom de la variable CSS.
   * @param {string}  repli    Valeur si la variable est vide.
   * @return {string} Couleur résolue.
   */
  function couleur(element, variable, repli) {
    var valeur = window.getComputedStyle(element).getPropertyValue(variable);
    return valeur && valeur.trim() ? valeur.trim() : repli;
  }

  function initialiser(conteneur) {
    if (conteneur.dataset.g2rdInit) return;
    if (typeof window.L === "undefined") return;

    var donnees;
    try {
      donnees = JSON.parse(conteneur.dataset.g2rdRouteMap);
    } catch (e) {
      return;
    }
    if (!donnees || !Array.isArray(donnees.trace) || donnees.trace.length < 2) return;

    conteneur.dataset.g2rdInit = "1";

    var L = window.L;
    var i18n = donnees.i18n || {};

    // Les variables sont déclarées sur le bloc, pas sur le conteneur de carte.
    var bloc = conteneur.closest(".g2rd-route-map") || conteneur;
    var couleurTrace = couleur(bloc, "--g2rd-rm-trace", "#000000");
    var couleurLiseret = couleur(bloc, "--g2rd-rm-trace-liseret", "#ffffff");

    var carte = L.map(conteneur, {
      scrollWheelZoom: false, // Ne jamais capturer le défilement de la page.
      zoomControl: true,
      attributionControl: true,
      keyboard: true,
    });

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: i18n.attribution || "",
    }).addTo(carte);

    // Deux traits superposés : un liseré clair dessous pour que la trace reste
    // lisible sur un fond de plan clair comme sur un fond dense.
    L.polyline(donnees.trace, {
      color: couleurLiseret,
      weight: 8,
      opacity: 0.9,
      lineJoin: "round",
      interactive: false,
    }).addTo(carte);

    var trace = L.polyline(donnees.trace, {
      color: couleurTrace,
      weight: 4,
      opacity: 1,
      lineJoin: "round",
    }).addTo(carte);

    carte.fitBounds(trace.getBounds(), { padding: [28, 28] });

    /* ── Départ ────────────────────────────────────────────────────────
       Sur une boucle, départ et arrivée se superposent : on ne pose qu'un
       seul repère, dont le libellé est décidé dans l'éditeur. */
    var depart = donnees.depart || {};
    if (depart.titre) {
      var marqueurDepart = L.marker(donnees.trace[0], {
        icon: L.divIcon({
          className: "g2rd-route-map__depart",
          html: '<span aria-hidden="true"></span>',
          iconSize: [18, 18],
          iconAnchor: [9, 9],
        }),
        alt: depart.titre,
        keyboard: true,
      }).addTo(carte);

      marqueurDepart.bindPopup(
        "<strong>" +
          echapper(depart.titre) +
          "</strong>" +
          (depart.texte ? "<br>" + echapper(depart.texte) : "")
      );
    }

    /* ── Points remarquables ───────────────────────────────────────────── */
    (donnees.points || []).forEach(function (p) {
      var marqueur = L.marker([p.lat, p.lng], {
        icon: L.divIcon({
          className: "g2rd-route-map__point g2rd-route-map__point--" + (p.type || "repere"),
          html: '<span aria-hidden="true"></span>',
          iconSize: [14, 14],
          iconAnchor: [7, 7],
        }),
        alt: p.titre || i18n.point || "",
        keyboard: true,
      }).addTo(carte);

      if (p.titre || p.texte) {
        marqueur.bindPopup(
          (p.titre ? "<strong>" + echapper(p.titre) + "</strong>" : "") +
            (p.texte ? "<br>" + echapper(p.texte) : "")
        );
      }
    });

    /* ── Zoom à la molette : uniquement après un clic ────────────────────
       Sinon la carte avale le défilement de la page dès qu'on la survole —
       l'un des irritants les plus fréquents sur mobile comme sur desktop. */
    carte.on("click", function () {
      carte.scrollWheelZoom.enable();
    });
    carte.on("mouseout", function () {
      carte.scrollWheelZoom.disable();
    });

    if (MOUVEMENT_REDUIT) {
      carte.options.zoomAnimation = false;
      carte.options.fadeAnimation = false;
    }
  }

  function echapper(texte) {
    var d = document.createElement("div");
    d.textContent = texte;
    return d.innerHTML;
  }

  function demarrer() {
    var cartes = document.querySelectorAll("[data-g2rd-route-map]");
    if (!cartes.length) return;

    if (!("IntersectionObserver" in window)) {
      cartes.forEach(initialiser);
      return;
    }

    var observateur = new IntersectionObserver(
      function (entrees) {
        entrees.forEach(function (entree) {
          if (!entree.isIntersecting) return;
          initialiser(entree.target);
          observateur.unobserve(entree.target);
        });
      },
      { rootMargin: "400px 0px" }
    );

    cartes.forEach(function (c) {
      observateur.observe(c);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", demarrer);
  } else {
    demarrer();
  }
})();
