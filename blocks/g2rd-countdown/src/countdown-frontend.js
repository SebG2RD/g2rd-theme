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
 *    l'heure, puis la minute). La dernière minute est laissée silencieuse :
 *    l'échéance y est déjà connue et l'annonce n'apporterait rien.
 *
 * 3. La fin du compte à rebours est annoncée, c'est le seul moment où
 *    l'information est réellement nouvelle.
 */
document.addEventListener("DOMContentLoaded", function () {
  const countdowns = document.querySelectorAll(".g2rd-countdown");

  /** Formate le temps restant en une phrase lisible à voix haute. */
  function spokenSummary(values, endedMessage) {
    const parts = [];
    const units = [
      ["years", "an", "ans"],
      ["months", "mois", "mois"],
      ["days", "jour", "jours"],
      ["hours", "heure", "heures"],
      ["minutes", "minute", "minutes"],
    ];

    for (const [key, one, many] of units) {
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
      return endedMessage;
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

      // Calculs exacts des unités de temps
      const nowDate = new Date();
      const endDateObj = new Date(endDate);

      let years = endDateObj.getFullYear() - nowDate.getFullYear();
      let months = endDateObj.getMonth() - nowDate.getMonth();
      if (months < 0) {
        years--;
        months += 12;
      }

      // Jours restants après soustraction des mois complets
      const dateAfterMonths = new Date(nowDate);
      dateAfterMonths.setFullYear(dateAfterMonths.getFullYear() + years);
      dateAfterMonths.setMonth(dateAfterMonths.getMonth() + months);
      const remainingMs = endDateObj - dateAfterMonths;
      const days = Math.max(0, Math.floor(remainingMs / (1000 * 60 * 60 * 24)));

      const hours = Math.floor(
        (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
      );
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      const values = { years, months, days, hours, minutes, seconds };

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
      announce(spokenSummary(values, countdown.dataset.endedMessage || "Terminé !"));
    }

    // Mise à jour initiale
    updateCountdown();

    // Mise à jour toutes les secondes
    setInterval(updateCountdown, 1000);
  });
});
