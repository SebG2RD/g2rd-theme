/**
 * G2RD Glass Effect Sidebar Control
 *
 * Ajoute un contrôle dans la sidebar de l'éditeur Gutenberg (onglet Réglages)
 * pour activer/désactiver l'effet de verre sur les blocs.
 *
 * @package G2RD
 * @since 1.0.2
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */
(function (wp) {
  // Vérifier si l'API WordPress est disponible
  if (!wp) {
    console.error("WordPress API not available");
    return;
  }

  try {
    // Récupérer les composants nécessaires
    const { __ } = wp.i18n;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, ToggleControl, RangeControl, ColorPicker, BaseControl } =
      wp.components;
    const { addFilter } = wp.hooks;
    const { createElement } = wp.element;

    /**
     * Ajouter l'attribut personnalisé aux blocs de type group, columns et rows
     */
    function addGlassAttribute(settings, name) {
      // N'appliquer qu'aux blocs de type group, columns et rows
      if (!["core/group", "core/columns", "core/row"].includes(name)) {
        return settings;
      }

      // Ajouter les attributs glassEffect
      if (settings.attributes) {
        settings.attributes = Object.assign({}, settings.attributes, {
          glassEffect: {
            type: "boolean",
            default: false,
          },
          glassOpacity: {
            type: "number",
            default: 0.2,
          },
          glassBlur: {
            type: "number",
            default: 5,
          },
          glassBorderRadius: {
            type: "number",
            default: 16,
          },
          glassBorderColor: {
            type: "string",
            default: "rgba(255, 255, 255, 0.3)",
          },
          glassShadowColor: {
            type: "string",
            default: "rgba(0, 0, 0, 0.1)",
          },
        });
      }

      return settings;
    }

    /**
     * Ajouter le contrôle dans la sidebar pour les blocs de type group, columns et rows
     */
    const withGlassControl = createHigherOrderComponent((BlockEdit) => {
      return (props) => {
        // N'appliquer qu'aux blocs de type group, columns et rows
        if (!["core/group", "core/columns", "core/row"].includes(props.name)) {
          return createElement(BlockEdit, props);
        }

        const { attributes, setAttributes } = props;
        const {
          glassEffect,
          glassOpacity,
          glassBlur,
          glassBorderRadius,
          glassBorderColor,
          glassShadowColor,
        } = attributes;

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
                title: __("Effet de verre", "G2RD"),
                initialOpen: false,
                className: "g2rd-glass-panel",
              },
              createElement(ToggleControl, {
                label: __("Activer l'effet de verre", "G2RD"),
                help: glassEffect
                  ? __("L'effet de verre est activé", "G2RD")
                  : __("L'effet de verre est désactivé", "G2RD"),
                checked: !!glassEffect,
                onChange: (value) => setAttributes({ glassEffect: value }),
                __nextHasNoMarginBottom: true,
              }),
              glassEffect &&
                createElement(
                  Fragment,
                  null,
                  createElement(
                    BaseControl,
                    {
                      className: "g2rd-glass-effects-section",
                      label: __("Paramètres de l'effet", "G2RD"),
                      help: __(
                        "Ajustez les paramètres principaux de l'effet de verre",
                        "G2RD"
                      ),
                    },
                    createElement(RangeControl, {
                      label: __("Opacité du fond", "G2RD"),
                      help: __(
                        "Contrôle la transparence du fond de l'effet de verre",
                        "G2RD"
                      ),
                      value: glassOpacity,
                      onChange: (value) =>
                        setAttributes({ glassOpacity: value }),
                      min: 0,
                      max: 1,
                      step: 0.1,
                    }),
                    createElement(RangeControl, {
                      label: __("Intensité du flou", "G2RD"),
                      help: __(
                        "Détermine l'intensité de l'effet de flou appliqué derrière l'élément",
                        "G2RD"
                      ),
                      value: glassBlur,
                      onChange: (value) => setAttributes({ glassBlur: value }),
                      min: 0,
                      max: 20,
                      step: 1,
                    }),
                    createElement(RangeControl, {
                      label: __("Rayon de bordure", "G2RD"),
                      help: __(
                        "Contrôle l'arrondi des coins de l'effet de verre",
                        "G2RD"
                      ),
                      value: glassBorderRadius,
                      onChange: (value) =>
                        setAttributes({ glassBorderRadius: value }),
                      min: 0,
                      max: 50,
                      step: 1,
                    })
                  ),
                  createElement(
                    BaseControl,
                    {
                      className: "g2rd-glass-colors-section",
                      label: __("Personnalisation des couleurs", "G2RD"),
                      help: __(
                        "Ces paramètres vous permettent de personnaliser l'apparence de l'effet de verre",
                        "G2RD"
                      ),
                    },
                    createElement(ColorPicker, {
                      label: __("Couleur de la bordure", "G2RD"),
                      help: __(
                        "Définit la couleur et l'opacité de la bordure qui entoure l'effet de verre",
                        "G2RD"
                      ),
                      color: glassBorderColor,
                      onChangeComplete: (value) =>
                        setAttributes({ glassBorderColor: value.hex }),
                    })
                  ),
                  createElement(
                    BaseControl,
                    {
                      className: "g2rd-glass-shadow-section",
                      label: __("Paramètres de l'ombre portée", "G2RD"),
                      help: __(
                        "Personnalisez l'ombre qui donne de la profondeur à l'effet de verre",
                        "G2RD"
                      ),
                    },
                    createElement(ColorPicker, {
                      label: __("Couleur de l'ombre", "G2RD"),
                      help: __(
                        "Définit la couleur et l'intensité de l'ombre portée derrière l'effet de verre",
                        "G2RD"
                      ),
                      color: glassShadowColor,
                      onChangeComplete: (value) =>
                        setAttributes({ glassShadowColor: value.hex }),
                    })
                  )
                )
            )
          )
        );
      };
    }, "withGlassControl");

    // Ajouter les filtres
    addFilter(
      "blocks.registerBlockType",
      "g2rd/glass-effect-attribute",
      addGlassAttribute,
      9
    );

    addFilter(
      "editor.BlockEdit",
      "g2rd/glass-effect-control",
      withGlassControl,
      9
    );
  } catch (e) {
    console.error("Error initializing G2RD Glass Effect Sidebar Control", e);
  }
})(window.wp);
