/**
 * Articles cliquables — bouton dans la toolbar du bloc
 *
 * Ajoute un bouton toggle dans la toolbar des blocs core/group et core/columns
 * pour activer/désactiver la fonctionnalité d'articles entièrement cliquables.
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
    const { BlockControls } = wp.blockEditor;
    const { ToolbarGroup, ToolbarButton } = wp.components;
    const { addFilter } = wp.hooks;

    // ── Ajout de l'attribut clickableArticles aux blocs concernés ──────────────
    function addClickableAttribute(settings, name) {
      if (name !== "core/group" && name !== "core/columns") {
        return settings;
      }
      return {
        ...settings,
        attributes: {
          ...settings.attributes,
          clickableArticles: {
            type: "boolean",
            default: false,
          },
        },
      };
    }

    // ── Ajout du bouton dans la toolbar ────────────────────────────────────────
    const withClickableToolbarButton = createHigherOrderComponent(
      (BlockEdit) => {
        return (props) => {
          if (
            props.name !== "core/group" &&
            props.name !== "core/columns"
          ) {
            return createElement(BlockEdit, props);
          }

          const { attributes, setAttributes } = props;
          const { clickableArticles } = attributes;

          return createElement(
            Fragment,
            null,
            createElement(BlockEdit, props),
            createElement(
              BlockControls,
              { group: "other" },
              createElement(
                ToolbarGroup,
                null,
                createElement(ToolbarButton, {
                  icon: "admin-links",
                  label: clickableArticles
                    ? __("Désactiver articles cliquables", "g2rd")
                    : __("Activer articles cliquables", "g2rd"),
                  isPressed: !!clickableArticles,
                  onClick: () =>
                    setAttributes({ clickableArticles: !clickableArticles }),
                })
              )
            )
          );
        };
      },
      "withClickableToolbarButton"
    );

    addFilter(
      "blocks.registerBlockType",
      "g2rd/clickable-articles-attribute",
      addClickableAttribute,
      9
    );

    addFilter(
      "editor.BlockEdit",
      "g2rd/clickable-articles-toolbar",
      withClickableToolbarButton,
      9
    );
  } catch (e) {
    console.error("G2RD clickable articles toolbar error:", e);
  }
})(window.wp);
