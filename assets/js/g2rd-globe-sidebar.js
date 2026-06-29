/**
 * G2RD Globe Sidebar Control
 *
 * Ajoute un contrôle dans la sidebar de l'éditeur Gutenberg (onglet Réglages)
 * pour activer le globe filaire animé sur les blocs de type group et choisir
 * sa position (centre / droite / gauche / haut / bas).
 *
 * Le globe est rendu en CSS pur (assets/css/globe.css, chargé aussi dans le
 * canvas). Le filtre editor.BlockListBlock applique la classe au bloc dans
 * l'éditeur → aperçu live dans le BO, identique au front.
 */
(function (wp) {
  if (!wp) {
    console.error("WordPress API not available");
    return;
  }

  try {
    const { __ } = wp.i18n;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment, createElement } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, ToggleControl, SelectControl } = wp.components;
    const { addFilter } = wp.hooks;

    const POSITIONS = [
      { label: __("Centre", "g2rd"), value: "center" },
      { label: __("Droite", "g2rd"), value: "right" },
      { label: __("Gauche", "g2rd"), value: "left" },
      { label: __("Haut", "g2rd"), value: "top" },
      { label: __("Bas", "g2rd"), value: "bottom" },
    ];

    /**
     * Déclare les attributs personnalisés sur les blocs de type group.
     */
    function addGlobeAttributes(settings, name) {
      if (name !== "core/group") {
        return settings;
      }

      if (settings.attributes) {
        settings.attributes = Object.assign({}, settings.attributes, {
          globeEffect: {
            type: "boolean",
            default: false,
          },
          globePosition: {
            type: "string",
            default: "center",
          },
        });
      }

      return settings;
    }

    /**
     * Ajoute le panneau de contrôle dans l'inspecteur.
     */
    const withGlobeControl = createHigherOrderComponent((BlockEdit) => {
      return (props) => {
        if (props.name !== "core/group") {
          return createElement(BlockEdit, props);
        }

        const { attributes, setAttributes } = props;
        const { globeEffect, globePosition } = attributes;

        return createElement(
          Fragment,
          {},
          createElement(BlockEdit, props),
          createElement(
            InspectorControls,
            null,
            createElement(
              PanelBody,
              {
                title: __("Animation globe", "g2rd"),
                initialOpen: false,
                className: "g2rd-globe-panel",
              },
              createElement(ToggleControl, {
                label: __("Activer le globe filaire", "g2rd"),
                help: globeEffect
                  ? __("Globe activé sur cette section", "g2rd")
                  : __("Globe désactivé", "g2rd"),
                checked: !!globeEffect,
                onChange: (value) => setAttributes({ globeEffect: value }),
                __nextHasNoMarginBottom: true,
              }),
              globeEffect &&
                createElement(SelectControl, {
                  label: __("Position du globe", "g2rd"),
                  value: globePosition || "center",
                  options: POSITIONS,
                  onChange: (value) => setAttributes({ globePosition: value }),
                  help: __(
                    "Position du globe dans la section. Idéal sur un fond sombre.",
                    "g2rd"
                  ),
                  __nextHasNoMarginBottom: true,
                })
            )
          )
        );
      };
    }, "withGlobeControl");

    /**
     * Applique les classes du globe au bloc dans l'éditeur (aperçu live).
     */
    const withGlobeClasses = createHigherOrderComponent((BlockListBlock) => {
      return (props) => {
        const { block, attributes } = props;

        if (!block || block.name !== "core/group" || !attributes?.globeEffect) {
          return createElement(BlockListBlock, props);
        }

        const position = attributes.globePosition || "center";
        const extra = `g2rd-globe-bg is-globe-${position}`;
        const className = props.className
          ? `${props.className} ${extra}`
          : extra;

        return createElement(
          BlockListBlock,
          Object.assign({}, props, { className })
        );
      };
    }, "withGlobeClasses");

    addFilter(
      "blocks.registerBlockType",
      "g2rd/globe-effect-attribute",
      addGlobeAttributes,
      9
    );

    addFilter(
      "editor.BlockEdit",
      "g2rd/globe-effect-control",
      withGlobeControl,
      9
    );

    addFilter(
      "editor.BlockListBlock",
      "g2rd/globe-effect-preview",
      withGlobeClasses,
      9
    );
  } catch (e) {
    console.error("Error initializing G2RD Globe Sidebar Control", e);
  }
})(window.wp);
