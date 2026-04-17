/**
 * G2RD Theme Options — admin-options.js
 * Améliorations visuelles uniquement (la navigation fonctionne sans JS).
 * Chargé en footer : DOM déjà prêt, pas de DOMContentLoaded nécessaire.
 */

(function () {
    'use strict';

    // ── État visuel des cartes feature/client ─────────────────────────────────
    document.querySelectorAll('.g2rd-card .g2rd-toggle input').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var card = cb.closest('.g2rd-card');
            if (!card) { return; }
            card.classList.toggle('is-active',   cb.checked);
            card.classList.toggle('is-inactive', !cb.checked);
        });
    });

    // ── Blocs Gutenberg : état visuel + compteur ──────────────────────────────
    document.querySelectorAll('.g2rd-block-checkbox').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var card = cb.closest('.g2rd-card');
            if (card) {
                card.classList.toggle('is-active',   cb.checked);
                card.classList.toggle('is-inactive', !cb.checked);
            }
            var n = document.querySelectorAll('.g2rd-block-checkbox:checked').length;
            var el = document.getElementById('g2rd-active-count');
            if (el) { el.textContent = n; }
        });
    });

    document.querySelectorAll('.g2rd-toggle-all').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var on = btn.dataset.state === 'on';
            document.querySelectorAll('.g2rd-block-checkbox').forEach(function (cb) {
                cb.checked = on;
                var card = cb.closest('.g2rd-card');
                if (card) {
                    card.classList.toggle('is-active',   on);
                    card.classList.toggle('is-inactive', !on);
                }
            });
            var n = document.querySelectorAll('.g2rd-block-checkbox:checked').length;
            var el = document.getElementById('g2rd-active-count');
            if (el) { el.textContent = n; }
        });
    });

    // ── CPT panels ───────────────────────────────────────────────────────────
    document.querySelectorAll('.g2rd-cpt-enabled-cb').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var panel = cb.closest('.g2rd-cpt-panel');
            if (!panel) { return; }
            panel.classList.toggle('is-active',   cb.checked);
            panel.classList.toggle('is-inactive', !cb.checked);
        });
    });

    document.querySelectorAll('.g2rd-tax-enabled-cb').forEach(function (cb) {
        function sync() {
            var tax    = cb.closest('.g2rd-cpt-taxonomy');
            var fields = tax ? tax.querySelector('.g2rd-cpt-tax-fields') : null;
            if (fields) { fields.classList.toggle('is-disabled', !cb.checked); }
        }
        sync();
        cb.addEventListener('change', sync);
    });

    // ── Icon picker ───────────────────────────────────────────────────────────
    document.querySelectorAll('.g2rd-icon-input').forEach(function (input) {
        var picker  = input.closest('.g2rd-icon-picker');
        var preview = picker ? picker.querySelector('.g2rd-icon-preview-dash') : null;
        if (!preview) { return; }
        input.addEventListener('input', function () {
            var val = input.value.trim();
            preview.className = preview.className.replace(/dashicons-\S+/g, '');
            if (val) {
                preview.classList.add(val.startsWith('dashicons-') ? val : 'dashicons-' + val);
            }
        });
    });

    // ── Swatches : prévisualisation couleurs ──────────────────────────────────
    function getColor(slot) {
        var r = document.querySelector('input[name="colors[' + slot + ']"]:checked');
        if (!r) { return null; }
        var s = r.closest('.g2rd-swatch');
        return s ? s.dataset.color : null;
    }

    function updatePreview() {
        var preview = document.getElementById('g2rd-admin-preview');
        if (!preview) { return; }
        var bg      = getColor('admin_bg');
        var text    = getColor('admin_text');
        var btnBg   = getColor('btn_bg');
        var btnText = getColor('btn_text');
        preview.querySelectorAll('.g2rd-preview-bg').forEach(function (el) {
            if (bg)   { el.style.backgroundColor = bg; }
            if (text) { el.style.color = text; }
        });
        preview.querySelectorAll('.g2rd-preview-btn').forEach(function (el) {
            if (btnBg)   { el.style.backgroundColor = btnBg; el.style.borderColor = btnBg; }
            if (btnText) { el.style.color = btnText; }
        });
    }

    document.querySelectorAll('.g2rd-palette-swatches').forEach(function (group) {
        group.querySelectorAll('.g2rd-swatch').forEach(function (swatch) {
            var radio = swatch.querySelector('input[type="radio"]');
            if (!radio) { return; }
            radio.addEventListener('change', function () {
                group.querySelectorAll('.g2rd-swatch').forEach(function (s) {
                    s.classList.toggle('is-selected', s === swatch);
                });
                updatePreview();
            });
        });
    });

}());
