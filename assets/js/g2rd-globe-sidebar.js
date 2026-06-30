/**
 * G2RD Globe Sidebar Control
 *
 * Ajoute un contrôle dans la sidebar de l'éditeur Gutenberg (onglet Réglages)
 * pour activer le globe filaire animé sur les blocs de type group, choisir sa
 * position et l'affiner (décalage + taille).
 *
 * Activation et position = CLASSES (`g2rd-globe-bg`, `is-globe-{position}`)
 * posées directement sur le bloc → source unique de vérité : le toggle reflète
 * et pilote la classe, qu'elle ait été ajoutée par le contrôle ou par un
 * pattern/template. Décocher retire réellement le globe (front + éditeur).
 *
 * Décalage / taille = attributs (réglage fin) appliqués en variables CSS, en
 * aperçu live dans le canvas (BlockListBlock) et sur le front (render_block).
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
    const { PanelBody, ToggleControl, SelectControl, RangeControl } =
      wp.components;
    const { addFilter } = wp.hooks;

    const GLOBE_CLASS = "g2rd-globe-bg";
    const POS_PREFIX = "is-globe-";

    const POSITIONS = [
      { label: __("Centre", "g2rd"), value: "center" },
      { label: __("Droite", "g2rd"), value: "right" },
      { label: __("Gauche", "g2rd"), value: "left" },
      { label: __("Haut", "g2rd"), value: "top" },
      { label: __("Bas", "g2rd"), value: "bottom" },
    ];

    /** Découpe une chaîne de classes en tableau filtré. */
    function classList(className) {
      return (className || "").split(/\s+/).filter(Boolean);
    }

    function isPosClass(c) {
      return c.indexOf(POS_PREFIX) === 0;
    }

    /**
     * Déclare les attributs de réglage fin sur les blocs de type group.
     */
    function addGlobeAttributes(settings, name) {
      if (name !== "core/group") {
        return settings;
      }
      if (settings.attributes) {
        settings.attributes = Object.assign({}, settings.attributes, {
          globeOffsetX: { type: "number", default: 0 },
          globeOffsetY: { type: "number", default: 0 },
          globeSize: { type: "number", default: 0 },
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
        const { className, globeOffsetX, globeOffsetY, globeSize } = attributes;

        const classes = classList(className);
        const hasGlobe = classes.indexOf(GLOBE_CLASS) !== -1;

        // Portée : sections sombres uniquement. Règle « OU » : on affiche aussi
        // le panneau si un globe est déjà présent, pour qu'il reste désactivable
        // même si le style sombre a été retiré après coup.
        const isDarkSection = classes.indexOf("is-style-section-dark") !== -1;
        if (!isDarkSection && !hasGlobe) {
          return createElement(BlockEdit, props);
        }

        const posClass = classes.find(isPosClass);
        const position = posClass ? posClass.slice(POS_PREFIX.length) : "center";

        const writeClasses = (list) =>
          setAttributes({ className: list.join(" ") || undefined });

        const toggleGlobe = (on) => {
          // Retire d'abord toute trace de globe (activation + position).
          const base = classes.filter(
            (c) => c !== GLOBE_CLASS && !isPosClass(c)
          );
          if (on) {
            base.push(GLOBE_CLASS, POS_PREFIX + "center");
            writeClasses(base);
          } else {
            // Désactivation complète : classes + réglages fins remis à zéro.
            setAttributes({
              className: base.join(" ") || undefined,
              globeOffsetX: 0,
              globeOffsetY: 0,
              globeSize: 0,
            });
          }
        };

        const setPosition = (pos) => {
          const base = classes.filter((c) => !isPosClass(c));
          base.push(POS_PREFIX + (pos || "center"));
          writeClasses(base);
        };

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
                help: hasGlobe
                  ? __("Globe activé sur cette section", "g2rd")
                  : __("Globe désactivé", "g2rd"),
                checked: hasGlobe,
                onChange: toggleGlobe,
                __nextHasNoMarginBottom: true,
              }),
              hasGlobe &&
                createElement(
                  Fragment,
                  null,
                  createElement(SelectControl, {
                    label: __("Position du globe", "g2rd"),
                    value: position,
                    options: POSITIONS,
                    onChange: setPosition,
                    help: __(
                      "Point d'ancrage du globe. Affinez ensuite avec les décalages.",
                      "g2rd"
                    ),
                    __nextHasNoMarginBottom: true,
                  }),
                  createElement(RangeControl, {
                    label: __("Décalage horizontal (px)", "g2rd"),
                    value: globeOffsetX || 0,
                    onChange: (value) =>
                      setAttributes({ globeOffsetX: value || 0 }),
                    min: -500,
                    max: 500,
                    step: 10,
                    allowReset: true,
                    __nextHasNoMarginBottom: true,
                  }),
                  createElement(RangeControl, {
                    label: __("Décalage vertical (px)", "g2rd"),
                    value: globeOffsetY || 0,
                    onChange: (value) =>
                      setAttributes({ globeOffsetY: value || 0 }),
                    min: -500,
                    max: 500,
                    step: 10,
                    allowReset: true,
                    __nextHasNoMarginBottom: true,
                  }),
                  createElement(RangeControl, {
                    label: __("Taille du globe (px, 0 = auto)", "g2rd"),
                    value: globeSize || 0,
                    onChange: (value) =>
                      setAttributes({ globeSize: value || 0 }),
                    min: 0,
                    max: 1000,
                    step: 20,
                    allowReset: true,
                    help: __(
                      "0 = taille automatique responsive. Réduisez pour éviter que le globe ne déborde.",
                      "g2rd"
                    ),
                    __nextHasNoMarginBottom: true,
                  })
                )
            )
          )
        );
      };
    }, "withGlobeControl");

    /**
     * Injecte les variables CSS de décalage/taille dans le canvas (aperçu live).
     * L'activation et la position sont rendues nativement via la classe du bloc.
     */
    const withGlobeVars = createHigherOrderComponent((BlockListBlock) => {
      return (props) => {
        const { block, attributes } = props;
        if (!block || block.name !== "core/group" || !attributes) {
          return createElement(BlockListBlock, props);
        }
        if (classList(attributes.className).indexOf(GLOBE_CLASS) === -1) {
          return createElement(BlockListBlock, props);
        }

        const styleVars = {};
        if (attributes.globeOffsetX)
          styleVars["--g2rd-globe-dx"] = `${attributes.globeOffsetX}px`;
        if (attributes.globeOffsetY)
          styleVars["--g2rd-globe-dy"] = `${attributes.globeOffsetY}px`;
        if (attributes.globeSize)
          styleVars["--g2rd-globe-size"] = `${attributes.globeSize}px`;

        if (!Object.keys(styleVars).length) {
          return createElement(BlockListBlock, props);
        }

        const wrapperProps = Object.assign({}, props.wrapperProps, {
          style: Object.assign(
            {},
            props.wrapperProps && props.wrapperProps.style,
            styleVars
          ),
        });
        return createElement(
          BlockListBlock,
          Object.assign({}, props, { wrapperProps })
        );
      };
    }, "withGlobeVars");

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
      withGlobeVars,
      9
    );
  } catch (e) {
    console.error("Error initializing G2RD Globe Sidebar Control", e);
  }
})(window.wp);
