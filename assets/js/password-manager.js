/**
 * Gestionnaire de mot de passe
 *
 * Ce script gère la validation et la gestion des mots de passe
 * dans les formulaires du thème.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

jQuery(document).ready(function ($) {
  // 1. Gestion des mots de passe dans la liste des plugins
  $(".password-container .toggle-password").on("click", function () {
    console.log("Clic sur toggle-password dans la liste");
    const container = $(this).closest(".password-container");
    const passwordMask = container.find(".password-mask");
    const password = passwordMask.data("password");

    if (passwordMask.text() === "••••••••") {
      passwordMask.text(password);
      $(this).removeClass("dashicons-visibility").addClass("dashicons-hidden");
    } else {
      passwordMask.text("••••••••");
      $(this).removeClass("dashicons-hidden").addClass("dashicons-visibility");
    }
  });

  // 2. Gestion des mots de passe dans la page d'édition
  $(".password-container .toggle-password[data-target]").on(
    "click",
    function (e) {
      console.log("Clic sur toggle-password dans l'édition");
      e.preventDefault();
      e.stopPropagation();

      const targetId = $(this).data("target");
      const passwordInput = $("#" + targetId);
      const container = passwordInput.closest(".password-container");
      const isVisible = container.hasClass("password-visible");

      if (isVisible) {
        // Masquer le mot de passe
        container.removeClass("password-visible");
        passwordInput.attr("type", "password");
        $(this)
          .removeClass("dashicons-hidden")
          .addClass("dashicons-visibility");
      } else {
        // Afficher le mot de passe
        container.addClass("password-visible");
        passwordInput.attr("type", "text");
        $(this)
          .removeClass("dashicons-visibility")
          .addClass("dashicons-hidden");
      }
    }
  );

  // 3. Gestion de la copie des mots de passe
  $(".password-mask").on("dblclick", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const passwordElement = $(this);
    const password = passwordElement.data("password");

    if (password) {
      const originalText = passwordElement.text();
      const wasVisible = originalText !== "••••••••";

      function showCopyFeedback() {
        passwordElement.addClass("copied").text("Copié !");

        setTimeout(() => {
          passwordElement.removeClass("copied");
          if (wasVisible) {
            passwordElement.text(password);
          } else {
            passwordElement.text("••••••••");
          }
        }, 1000);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(password).then(showCopyFeedback);
      } else {
        const tempInput = $("<input>");
        $("body").append(tempInput);
        tempInput.val(password).select();
        document.execCommand("copy");
        tempInput.remove();
        showCopyFeedback();
      }
    }
  });
});
