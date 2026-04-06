/**
 * G2RD — Infobulle sur n'importe quel bloc
 *
 * Ajoute un panneau "Infobulle" dans l'Inspector de chaque bloc Gutenberg
 * (natif ou G2RD). Quand l'option est activée, un champ texte apparaît.
 * Le texte est stocké dans l'attribut g2rdTooltipText du bloc (sauvé dans
 * le commentaire HTML) et rendu côté serveur via un filtre render_block.
 *
 * @package G2RD
 * @since 1.1.0
 */
(function (wp) {
  if (!wp) return;

  try {
    const { __ } = wp.i18n;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment, createElement } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, ToggleControl, TextareaControl } = wp.components;
    const { addFilter } = wp.hooks;

    // Blocs à exclure (gestion interne ou sans wrapper HTML utile)
    const EXCLUDED_BLOCKS = [
      "core/freeform",
      "core/html",
      "core/shortcode",
      "core/legacy-widget",
      "core/widget-group",
    ];

    // ── Injection des attributs sur chaque bloc ─────────────────────────────
    addFilter(
      "blocks.registerBlockType",
      "g2rd/tooltip-attributes",
      (settings, name) => {
        if (EXCLUDED_BLOCKS.includes(name)) return settings;
        return {
          ...settings,
          attributes: {
            ...settings.attributes,
            g2rdTooltipEnabled: { type: "boolean", default: false },
            g2rdTooltipText: { type: "string", default: "" },
          },
        };
      }
    );

    // ── Panneau Inspector sur chaque bloc ───────────────────────────────────
    const withTooltipPanel = createHigherOrderComponent((BlockEdit) => {
      return (props) => {
        if (EXCLUDED_BLOCKS.includes(props.name)) {
          return createElement(BlockEdit, props);
        }

        const { attributes, setAttributes } = props;
        const { g2rdTooltipEnabled, g2rdTooltipText } = attributes;

        return createElement(
          Fragment,
          null,
          createElement(BlockEdit, props),
          createElement(
            InspectorControls,
            null,
            createElement(
              PanelBody,
              {
                title: __("Infobulle (tooltip)", "g2rd"),
                initialOpen: false,
                className: "g2rd-tooltip-panel",
              },
              createElement(ToggleControl, {
                label: __("Activer l'infobulle", "g2rd"),
                checked: !!g2rdTooltipEnabled,
                onChange: (v) => {
                  setAttributes({ g2rdTooltipEnabled: v });
                  if (!v) setAttributes({ g2rdTooltipText: "" });
                },
                __nextHasNoMarginBottom: true,
              }),
              g2rdTooltipEnabled &&
                createElement(TextareaControl, {
                  label: __("Texte de l'infobulle", "g2rd"),
                  value: g2rdTooltipText,
                  onChange: (v) => setAttributes({ g2rdTooltipText: v }),
                  placeholder: __("Texte affiché au survol…", "g2rd"),
                  rows: 3,
                  __nextHasNoMarginBottom: true,
                })
            )
          )
        );
      };
    }, "withTooltipPanel");

    addFilter("editor.BlockEdit", "g2rd/tooltip-panel", withTooltipPanel);
  } catch (e) {
    console.error("G2RD tooltip plugin error:", e);
  }
})(window.wp);
