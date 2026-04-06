/**
 * G2RD Theme Options — admin-options.js
 *
 * Gère les interactions de la page d'options du thème :
 * - Mise à jour visuelle des cartes au changement de toggle
 * - Compteur de blocs actifs en temps réel
 * - Boutons "Tout activer / Tout désactiver"
 */

document.addEventListener("DOMContentLoaded", function () {

    /**
     * Met à jour la classe CSS d'une carte selon l'état de sa checkbox.
     *
     * @param {HTMLInputElement} checkbox
     */
    function updateCardState(checkbox) {
        var card = checkbox.closest(".g2rd-card");
        if (!card) return;
        card.classList.toggle("is-active",   checkbox.checked);
        card.classList.toggle("is-inactive", !checkbox.checked);
    }

    /**
     * Met à jour le compteur "X / Y blocs actifs".
     */
    function updateBlockCount() {
        var checked = document.querySelectorAll(".g2rd-block-checkbox:checked").length;
        var counter = document.getElementById("g2rd-active-count");
        if (counter) counter.textContent = checked;
    }

    // Écouter les changements sur toutes les checkboxes de fonctionnalité
    document.querySelectorAll(".g2rd-section:first-of-type .g2rd-toggle input").forEach(function (cb) {
        cb.addEventListener("change", function () {
            updateCardState(cb);
        });
    });

    // Écouter les changements sur toutes les checkboxes de blocs
    document.querySelectorAll(".g2rd-block-checkbox").forEach(function (cb) {
        cb.addEventListener("change", function () {
            updateCardState(cb);
            updateBlockCount();
        });
    });

    // Boutons "Tout activer" / "Tout désactiver"
    document.querySelectorAll(".g2rd-toggle-all").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var activate = btn.dataset.state === "on";
            document.querySelectorAll(".g2rd-block-checkbox").forEach(function (cb) {
                cb.checked = activate;
                updateCardState(cb);
            });
            updateBlockCount();
        });
    });

    // ── CPT : toggle panel / icône preview ──────────────────────

    // Masquer/afficher le corps du panel selon l'état du toggle
    document.querySelectorAll(".g2rd-cpt-enabled-cb").forEach(function (cb) {
        cb.addEventListener("change", function () {
            var panel = cb.closest(".g2rd-cpt-panel");
            if (!panel) return;
            panel.classList.toggle("is-active",   cb.checked);
            panel.classList.toggle("is-inactive", !cb.checked);
        });
    });

    // Activer/désactiver visuellement les champs de taxonomie
    document.querySelectorAll(".g2rd-tax-enabled-cb").forEach(function (cb) {
        function syncTaxFields() {
            var fields = cb.closest(".g2rd-cpt-taxonomy").querySelector(".g2rd-cpt-tax-fields");
            if (!fields) return;
            fields.classList.toggle("is-disabled", !cb.checked);
        }
        syncTaxFields();
        cb.addEventListener("change", syncTaxFields);
    });

    // Prévisualisation live de l'icône Dashicons
    document.querySelectorAll(".g2rd-icon-input").forEach(function (input) {
        var preview = input.closest(".g2rd-icon-picker")
                          ? input.closest(".g2rd-icon-picker").querySelector(".g2rd-icon-preview-dash")
                          : null;
        if (!preview) return;
        input.addEventListener("input", function () {
            var val = input.value.trim();
            // Retirer toutes les classes dashicons-* existantes
            preview.className = preview.className.replace(/dashicons-\S+/g, "");
            if (val) {
                // Ajouter la nouvelle classe (avec ou sans préfixe "dashicons-")
                var cls = val.startsWith("dashicons-") ? val : "dashicons-" + val;
                preview.classList.add(cls);
            }
        });
    });

    // ── Sélection de couleurs (swatches) ────────────────────────

    /**
     * Retourne la couleur hex du swatch sélectionné pour un slot donné.
     *
     * @param {string} slot  Identifiant du slot (ex: "admin_bg").
     * @returns {string|null}
     */
    function getSelectedColor(slot) {
        var radio = document.querySelector('input[name="colors[' + slot + ']"]:checked');
        if (!radio) return null;
        var swatch = radio.closest(".g2rd-swatch");
        return swatch ? swatch.dataset.color : null;
    }

    /**
     * Met à jour la prévisualisation admin selon les swatches cochés.
     */
    function updateAdminPreview() {
        var preview = document.getElementById("g2rd-admin-preview");
        if (!preview) return;

        var bg      = getSelectedColor("admin_bg");
        var text    = getSelectedColor("admin_text");
        var btnBg   = getSelectedColor("btn_bg");
        var btnText = getSelectedColor("btn_text");

        // Barre admin et sidebar
        preview.querySelectorAll(".g2rd-preview-bg").forEach(function (el) {
            if (bg)   el.style.backgroundColor = bg;
            if (text) el.style.color = text;
        });

        // Bouton
        preview.querySelectorAll(".g2rd-preview-btn").forEach(function (el) {
            if (btnBg)   { el.style.backgroundColor = btnBg; el.style.borderColor = btnBg; }
            if (btnText) el.style.color = btnText;
        });
    }

    // Écouter les changements sur tous les swatches
    document.querySelectorAll(".g2rd-palette-swatches").forEach(function (group) {
        group.querySelectorAll(".g2rd-swatch").forEach(function (swatch) {
            var radio = swatch.querySelector('input[type="radio"]');
            if (!radio) return;

            radio.addEventListener("change", function () {
                // Mettre à jour la classe is-selected dans ce groupe
                group.querySelectorAll(".g2rd-swatch").forEach(function (s) {
                    s.classList.toggle("is-selected", s === swatch);
                });
                updateAdminPreview();
            });
        });
    });
});
