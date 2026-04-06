/**
 * G2RD Particles Sidebar Control
 *
 * Ajoute un contrôle dans la sidebar de l'éditeur Gutenberg (onglet Réglages)
 * pour activer/désactiver l'effet de particules sur les blocs de type group.
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
     * Ajouter l'attribut personnalisé aux blocs de type group
     */
    function addParticlesAttribute(settings, name) {
      // N'appliquer qu'aux blocs de type group
      if (name !== "core/group") {
        return settings;
      }

      // Ajouter les attributs particlesEffect
      if (settings.attributes) {
        settings.attributes = Object.assign({}, settings.attributes, {
          particlesEffect: {
            type: "boolean",
            default: false,
          },
          particlesColor: {
            type: "string",
            default: "#cccccc",
          },
          particlesSpeed: {
            type: "number",
            default: 4.5,
          },
          particlesOpacity: {
            type: "number",
            default: 0.6,
          },
        });
      }

      return settings;
    }

    /**
     * Ajouter le contrôle dans la sidebar pour les blocs de type group
     */
    const withParticlesControl = createHigherOrderComponent((BlockEdit) => {
      return (props) => {
        // N'appliquer qu'aux blocs de type group
        if (props.name !== "core/group") {
          return createElement(BlockEdit, props);
        }

        const { attributes, setAttributes } = props;
        const {
          particlesEffect,
          particlesColor,
          particlesSpeed,
          particlesOpacity,
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
                title: __("Effet de particules", "G2RD"),
                initialOpen: false,
                className: "g2rd-particles-panel",
              },
              createElement(ToggleControl, {
                label: __("Activer l'effet", "G2RD"),
                help: particlesEffect
                  ? __("Effet particules activé", "G2RD")
                  : __("Effet particules désactivé", "G2RD"),
                checked: !!particlesEffect,
                onChange: (value) => setAttributes({ particlesEffect: value }),
                __nextHasNoMarginBottom: true,
              }),
              particlesEffect &&
                createElement(
                  Fragment,
                  null,
                  createElement(
                    BaseControl,
                    {
                      className: "g2rd-particles-effects-section",
                      label: __("Paramètres de l'effet", "G2RD"),
                      help: __(
                        "Ajustez les paramètres principaux de l'effet de particules",
                        "G2RD"
                      ),
                    },
                    createElement(RangeControl, {
                      label: __("Vitesse de déplacement", "G2RD"),
                      help: __(
                        "Contrôle la vitesse de déplacement des particules",
                        "G2RD"
                      ),
                      value: particlesSpeed,
                      onChange: (value) =>
                        setAttributes({ particlesSpeed: value }),
                      min: 1,
                      max: 10,
                      step: 0.5,
                    }),
                    createElement(RangeControl, {
                      label: __("Opacité", "G2RD"),
                      help: __(
                        "Contrôle la transparence des particules",
                        "G2RD"
                      ),
                      value: particlesOpacity,
                      onChange: (value) =>
                        setAttributes({ particlesOpacity: value }),
                      min: 0.1,
                      max: 1,
                      step: 0.1,
                    })
                  ),
                  createElement(
                    BaseControl,
                    {
                      className: "g2rd-particles-colors-section",
                      label: __("Personnalisation des couleurs", "G2RD"),
                      help: __(
                        "Personnalisez l'apparence des particules",
                        "G2RD"
                      ),
                    },
                    createElement(ColorPicker, {
                      label: __("Couleur des particules", "G2RD"),
                      help: __(
                        "Définit la couleur des particules et des lignes de connexion",
                        "G2RD"
                      ),
                      color: particlesColor,
                      onChangeComplete: (value) =>
                        setAttributes({ particlesColor: value.hex }),
                    })
                  )
                )
            )
          )
        );
      };
    }, "withParticlesControl");

    // Ajouter les filtres
    addFilter(
      "blocks.registerBlockType",
      "g2rd/particles-effect-attribute",
      addParticlesAttribute,
      9
    );

    addFilter(
      "editor.BlockEdit",
      "g2rd/particles-effect-control",
      withParticlesControl,
      9
    );
  } catch (e) {
    console.error("Error initializing G2RD Particles Sidebar Control", e);
  }
})(window.wp);
