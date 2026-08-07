/**
 * Rendu frontend du bloc Compte à rebours.
 *
 * Accessibilité (RGAA 13.8, WCAG 2.2.2 « auto-updating information ») :
 *
 * 1. Les chiffres se réécrivaient chaque seconde sans aucun balisage ARIA.
 *    Sans région live, un lecteur d'écran ne signale jamais que la valeur a
 *    changé : l'internaute entend l'état du chargement de la page et rien de
 *    plus. Les mettre tels quels dans une région live serait pire — une
 *    annonce par seconde rendrait la page inutilisable.
 *
 * 2. Le compteur visuel est donc masqué aux technologies d'assistance
 *    (aria-hidden) et doublé d'un résumé en français, annoncé poliment et
 *    seulement quand l'unité la plus significative change (le jour, puis
 *    l'heure, puis la minute). Sous la minute, une formule stable — « Moins
 *    d'une minute » — évite d'égrener les secondes dans la région live.
 *
 * 3. La fin du compte à rebours est annoncée, c'est le seul moment où
 *    l'information est réellement nouvelle.
 */
document.addEventListener("DOMContentLoaded", function () {
  const countdowns = document.querySelectorAll(".g2rd-countdown");

  const UNITS = [
    ["years", "an", "ans"],
    ["months", "mois", "mois"],
    ["days", "jour", "jours"],
    ["hours", "heure", "heures"],
    ["minutes", "minute", "minutes"],
  ];

  const MS_DAY = 1000 * 60 * 60 * 24;
  const MS_HOUR = 1000 * 60 * 60;
  const MS_MINUTE = 1000 * 60;

  /**
   * Répartit le temps restant entre les unités réellement affichées.
   *
   * Les années et les mois étaient auparavant retranchés que l'auteur les
   * affiche ou non : un compte à rebours de deux ans réglé sur jours et heures
   * montrait « 0 jours, 2 heures », les 730 jours ayant été absorbés par des
   * unités invisibles. La plus grande unité affichée doit au contraire porter
   * tout ce qui la dépasse.
   *
   * Années et mois restent calculés sur le calendrier — leur durée varie — et
   * ne sont retranchés que s'ils sont montrés.
   */
  function computeValues(endDate, distance, visible) {
    const shows = function (unit) {
      // Aucune unité repérée (balisage antérieur à data-unit) : tout est montré.
      return !Array.isArray(visible) || 0 === visible.length
        ? true
        : visible.indexOf(unit) !== -1;
    };

    const now = new Date();
    const end = new Date(endDate);

    let years = 0;
    let months = 0;
    let rest = distance;

    if (shows("years") || shows("months")) {
      let y = end.getFullYear() - now.getFullYear();
      let m = end.getMonth() - now.getMonth();
      if (m < 0) {
        y--;
        m += 12;
      }

      // Sans affichage des années, leur durée bascule dans les mois.
      if (shows("years")) {
        years = Math.max(0, y);
        months = Math.max(0, m);
      } else {
        months = Math.max(0, y * 12 + m);
      }

      const afterCalendar = new Date(now);
      afterCalendar.setFullYear(afterCalendar.getFullYear() + Math.max(0, y));
      afterCalendar.setMonth(afterCalendar.getMonth() + Math.max(0, m));
      rest = Math.max(0, end - afterCalendar);
    }

    const days = shows("days") ? Math.floor(rest / MS_DAY) : 0;
    if (shows("days")) {
      rest -= days * MS_DAY;
    }

    const hours = shows("hours") ? Math.floor(rest / MS_HOUR) : 0;
    if (shows("hours")) {
      rest -= hours * MS_HOUR;
    }

    const minutes = shows("minutes") ? Math.floor(rest / MS_MINUTE) : 0;
    if (shows("minutes")) {
      rest -= minutes * MS_MINUTE;
    }

    const seconds = Math.floor(rest / 1000);

    return { years, months, days, hours, minutes, seconds };
  }

  /**
   * Formate le temps restant en une phrase lisible à voix haute.
   *
   * `visible` limite le résumé aux unités réellement affichées : un bloc réglé
   * sur jours/heures seulement ne doit pas annoncer « Il reste 2 ans » quand
   * l'écran montre « 730 jours ». Le résumé et l'affichage racontent alors deux
   * choses différentes du même compte à rebours.
   */
  function spokenSummary(values, visible) {
    const parts = [];

    /*
     * Une liste vide veut dire « aucune unité repérée », pas « aucune unité à
     * annoncer ». En JavaScript un tableau vide est vrai : tester `visible`
     * seul écartait donc toutes les unités et figeait le résumé sur « Moins
     * d'une minute » pendant tout le compte à rebours. Le cas se produit sur
     * les blocs enregistrés avant l'introduction de `data-unit`.
     */
    const restrict = Array.isArray(visible) && visible.length > 0;

    for (const [key, one, many] of UNITS) {
      if (restrict && visible.indexOf(key) === -1) {
        continue;
      }
      const n = values[key];
      if (n > 0) {
        parts.push(n + " " + (n > 1 ? many : one));
      }
      // Deux unités suffisent : « 3 jours et 5 heures » se retient,
      // « 3 jours, 5 heures, 12 minutes et 4 secondes » non.
      if (parts.length === 2) {
        break;
      }
    }

    if (parts.length === 0) {
      /*
       * Aucune unité d'une minute ou plus : il reste des secondes. Renvoyer le
       * message de fin annoncerait le compte à rebours terminé jusqu'à une
       * minute avant l'échéance réelle, en contradiction avec l'affichage.
       *
       * Les secondes ne sont pas énoncées : elles changeraient la phrase à
       * chaque tick et la région live parlerait en continu. Une formule stable
       * dit la même chose sans ce défaut.
       */
      return "Moins d'une minute";
    }

    return "Il reste " + parts.join(" et ");
  }

  countdowns.forEach((countdown) => {
    const endDate = new Date(countdown.dataset.endDate).getTime();

    if (!endDate || isNaN(endDate)) {
      return;
    }

    const container = countdown.querySelector(".countdown-container");

    /*
     * Le compteur chiffré devient décoratif : sa valeur est portée par le
     * résumé ci-dessous. Sans cela, un lecteur d'écran lit une suite de
     * nombres nus (« 03 05 12 »), sans indiquer qu'ils s'écoulent.
     */
    if (container) {
      container.setAttribute("aria-hidden", "true");
    }

    let summary = countdown.querySelector(".countdown-a11y-summary");
    if (!summary) {
      summary = document.createElement("p");
      summary.className = "countdown-a11y-summary screen-reader-text";
      summary.setAttribute("aria-live", "polite");
      summary.setAttribute("aria-atomic", "true");
      countdown.appendChild(summary);
    }

    // Mémorise la dernière phrase annoncée pour n'écrire dans la région live
    // que lorsqu'elle change réellement.
    let lastSpoken = "";

    // Unités effectivement présentes dans le DOM : le résumé ne doit
    // parler que de ce que l'internaute voit.
    const visibleUnits = Array.prototype.map.call(
      countdown.querySelectorAll(".countdown-item[data-unit]"),
      (item) => item.getAttribute("data-unit")
    );

    function announce(text) {
      if (text !== lastSpoken) {
        lastSpoken = text;
        summary.textContent = text;
      }
    }

    function updateCountdown() {
      const now = new Date().getTime();
      const distance = endDate - now;

      if (distance < 0) {
        // Masquer les items sans détruire la structure du bloc
        if (container && !container.querySelector(".countdown-ended")) {
          container
            .querySelectorAll(".countdown-item")
            .forEach((item) => (item.style.display = "none"));
          const ended = document.createElement("div");
          ended.className = "countdown-ended";
          ended.textContent = countdown.dataset.endedMessage || "Terminé !";
          container.appendChild(ended);
        }
        // Seul instant où l'information est réellement neuve : on l'annonce.
        announce(countdown.dataset.endedMessage || "Terminé !");
        return;
      }

      const values = computeValues(endDate, distance, visibleUnits);

      // Mise à jour via data-unit (indépendant de la langue des labels)
      countdown.querySelectorAll(".countdown-item[data-unit]").forEach((item) => {
        const unit = item.getAttribute("data-unit");
        const valueElement = item.querySelector(".countdown-value");
        if (valueElement && unit in values) {
          valueElement.textContent = values[unit].toString().padStart(2, "0");
        }
      });

      /*
       * announce() ne réécrit la région live que si la phrase change. Comme le
       * résumé s'arrête à deux unités, il reste stable pendant une minute
       * entière au moins : le lecteur d'écran annonce donc au rythme de la
       * minute, pas de la seconde.
       */
      announce(
        spokenSummary(values, visibleUnits)
      );
    }

    // Mise à jour initiale
    updateCountdown();

    // Mise à jour toutes les secondes
    setInterval(updateCountdown, 1000);
  });
});
