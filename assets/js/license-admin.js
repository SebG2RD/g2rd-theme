/**
 * Script d'administration pour la gestion des licences G2RD
 *
 * @package G2RD
 * @since 1.0.0
 */

jQuery(document).ready(function ($) {
  // Vérification de la licence
  $("#verify-license-btn").on("click", function () {
    var $btn = $(this);
    var $result = $("#verify-license-result");

    // Désactiver le bouton et afficher le message de chargement
    $btn.prop("disabled", true).text(g2rdLicense.strings.verifying);
    $result.html(
      '<span style="color: blue;">' + g2rdLicense.strings.verifying + "</span>"
    );

    // Appel AJAX
    $.ajax({
      url: g2rdLicense.ajaxUrl,
      type: "POST",
      data: {
        action: "g2rd_verify_license",
        nonce: g2rdLicense.nonce,
      },
      success: function (response) {
        if (response.success) {
          $result.html(
            '<span style="color: green;">✓ ' + response.data.message + "</span>"
          );
          // Recharger la page après 2 secondes pour mettre à jour le statut
          setTimeout(function () {
            location.reload();
          }, 2000);
        } else {
          $result.html(
            '<span style="color: red;">✗ ' + response.data.message + "</span>"
          );
        }
      },
      error: function (xhr, status, error) {
        $result.html(
          '<span style="color: red;">✗ ' +
            g2rdLicense.strings.error +
            ": " +
            error +
            "</span>"
        );
      },
      complete: function () {
        // Réactiver le bouton
        $btn.prop("disabled", false).text("Vérifier la licence maintenant");
      },
    });
  });
});
