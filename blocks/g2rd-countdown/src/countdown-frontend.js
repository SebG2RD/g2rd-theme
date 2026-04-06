document.addEventListener("DOMContentLoaded", function () {
  const countdowns = document.querySelectorAll(".g2rd-countdown");

  countdowns.forEach((countdown) => {
    const endDate = new Date(countdown.dataset.endDate).getTime();

    if (!endDate || isNaN(endDate)) {
      return;
    }

    const container = countdown.querySelector(".countdown-container");

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
    }

    // Mise à jour initiale
    updateCountdown();

    // Mise à jour toutes les secondes
    setInterval(updateCountdown, 1000);
  });
});
