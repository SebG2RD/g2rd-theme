/**
 * Bloc g2rd/timeline — script d'éditeur.
 *
 * Écrit sans JSX ni chaîne de compilation : le fichier est chargé tel quel par
 * WordPress. C'est la convention des blocs pré-compilés du thème parent, et
 * cela évite d'imposer un `npm run build` pour modifier une étiquette.
 *
 * @package G2RD
 * @since   1.0.0
 */

(function (wp) {
  "use strict";

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var __ = wp.i18n.__;
  var registerBlockType = wp.blocks.registerBlockType;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var RichText = wp.blockEditor.RichText;
  var C = wp.components;

  var ETAPE_VIDE = { repere: "", titre: "", texte: "", lien: "", fort: false };

  registerBlockType("g2rd/timeline", {
    edit: function (props) {
      var a = props.attributes;
      var set = props.setAttributes;
      var items = Array.isArray(a.items) ? a.items : [];

      function majItem(index, cle, valeur) {
        var copie = items.map(function (it, i) {
          return i === index ? Object.assign({}, it, defObj(cle, valeur)) : it;
        });
        set({ items: copie });
      }

      function defObj(cle, valeur) {
        var o = {};
        o[cle] = valeur;
        return o;
      }

      function ajouter() {
        set({ items: items.concat([Object.assign({}, ETAPE_VIDE)]) });
      }

      function supprimer(index) {
        set({
          items: items.filter(function (_, i) {
            return i !== index;
          }),
        });
      }

      function deplacer(index, delta) {
        var cible = index + delta;
        if (cible < 0 || cible >= items.length) return;
        var copie = items.slice();
        var tmp = copie[index];
        copie[index] = copie[cible];
        copie[cible] = tmp;
        set({ items: copie });
      }

      var classes = [
        "g2rd-timeline",
        "g2rd-timeline--" + (a.orientation || "verticale"),
      ];
      if (a.afficherAxe) classes.push("g2rd-timeline--axe");
      if (a.afficherNumeros) classes.push("g2rd-timeline--numerotee");

      var blockProps = useBlockProps({ className: classes.join(" ") });

      /* ── Panneau latéral ─────────────────────────────────────────────── */
      var inspecteur = el(
        InspectorControls,
        null,
        el(
          C.PanelBody,
          { title: __("Disposition", "g2rd"), initialOpen: true },
          el(C.SelectControl, {
            label: __("Orientation", "g2rd"),
            value: a.orientation,
            options: [
              { label: __("Verticale — programme, parcours", "g2rd"), value: "verticale" },
              { label: __("Horizontale — étapes d'un processus", "g2rd"), value: "horizontale" },
            ],
            onChange: function (v) {
              set({ orientation: v });
            },
            __nextHasNoMarginBottom: true,
          }),
          el(C.SelectControl, {
            label: __("Nature du repère", "g2rd"),
            help: __(
              "Une heure sort en balise <time> lisible par les lecteurs d'écran et les moteurs.",
              "g2rd"
            ),
            value: a.repere,
            options: [
              { label: __("Heure — 7h30, 9h00", "g2rd"), value: "heure" },
              { label: __("Distance — km 1, km 2", "g2rd"), value: "distance" },
              { label: __("Libellé libre", "g2rd"), value: "libelle" },
            ],
            onChange: function (v) {
              set({ repere: v });
            },
            __nextHasNoMarginBottom: true,
          }),
          el(C.SelectControl, {
            label: __("Niveau des titres d'étape", "g2rd"),
            help: __(
              "Doit suivre la hiérarchie de la page : pas de saut de niveau.",
              "g2rd"
            ),
            value: a.niveauTitre,
            options: [
              { label: "H2", value: "h2" },
              { label: "H3", value: "h3" },
              { label: "H4", value: "h4" },
              { label: __("Paragraphe", "g2rd"), value: "p" },
            ],
            onChange: function (v) {
              set({ niveauTitre: v });
            },
            __nextHasNoMarginBottom: true,
          }),
          el(C.ToggleControl, {
            label: __("Afficher l'axe", "g2rd"),
            help: __("Le filet corail qui relie les étapes.", "g2rd"),
            checked: !!a.afficherAxe,
            onChange: function (v) {
              set({ afficherAxe: v });
            },
            __nextHasNoMarginBottom: true,
          }),
          el(C.ToggleControl, {
            label: __("Numéroter les étapes", "g2rd"),
            help: __(
              "À réserver aux vraies séquences, où l'ordre porte une information.",
              "g2rd"
            ),
            checked: !!a.afficherNumeros,
            onChange: function (v) {
              set({ afficherNumeros: v });
            },
            __nextHasNoMarginBottom: true,
          })
        ),
        el(
          C.PanelBody,
          { title: __("Accessibilité", "g2rd"), initialOpen: false },
          el(C.TextControl, {
            label: __("Titre pour les lecteurs d'écran", "g2rd"),
            help: __(
              "Visuellement masqué. Exemple : « Programme du dimanche 7 mars ». À renseigner si la timeline n'est pas déjà précédée d'un titre.",
              "g2rd"
            ),
            value: a.titreAccessible,
            onChange: function (v) {
              set({ titreAccessible: v });
            },
            __nextHasNoMarginBottom: true,
          })
        )
      );

      /* ── Aperçu éditable ─────────────────────────────────────────────── */
      var liste = el(
        "ol",
        { className: "g2rd-timeline__liste" },
        items.map(function (item, index) {
          return el(
            "li",
            {
              key: index,
              className:
                "g2rd-timeline__etape" + (item.fort ? " est-fort" : ""),
            },
            a.afficherNumeros
              ? el(
                  "span",
                  { className: "g2rd-timeline__numero" },
                  ("0" + (index + 1)).slice(-2)
                )
              : null,
            el(RichText, {
              tagName: "span",
              className: "g2rd-timeline__repere",
              value: item.repere,
              allowedFormats: [],
              placeholder: a.repere === "heure" ? "9h00" : "km 1",
              onChange: function (v) {
                majItem(index, "repere", v);
              },
            }),
            el(
              "div",
              { className: "g2rd-timeline__corps" },
              el(RichText, {
                tagName: "p",
                className: "g2rd-timeline__titre",
                value: item.titre,
                allowedFormats: [],
                placeholder: __("Titre de l'étape", "g2rd"),
                onChange: function (v) {
                  majItem(index, "titre", v);
                },
              }),
              el(RichText, {
                tagName: "p",
                className: "g2rd-timeline__texte",
                value: item.texte,
                allowedFormats: ["core/bold", "core/italic", "core/link"],
                placeholder: __("Description — une à deux phrases.", "g2rd"),
                onChange: function (v) {
                  majItem(index, "texte", v);
                },
              }),
              el(
                "div",
                { className: "g2rd-timeline__outils" },
                el(
                  C.Button,
                  {
                    size: "small",
                    variant: item.fort ? "primary" : "secondary",
                    onClick: function () {
                      majItem(index, "fort", !item.fort);
                    },
                    label: __("Mettre cette étape en avant", "g2rd"),
                  },
                  __("Temps fort", "g2rd")
                ),
                el(C.Button, {
                  size: "small",
                  icon: "arrow-up-alt2",
                  label: __("Monter", "g2rd"),
                  disabled: index === 0,
                  onClick: function () {
                    deplacer(index, -1);
                  },
                }),
                el(C.Button, {
                  size: "small",
                  icon: "arrow-down-alt2",
                  label: __("Descendre", "g2rd"),
                  disabled: index === items.length - 1,
                  onClick: function () {
                    deplacer(index, 1);
                  },
                }),
                el(C.Button, {
                  size: "small",
                  icon: "trash",
                  isDestructive: true,
                  label: __("Supprimer cette étape", "g2rd"),
                  onClick: function () {
                    supprimer(index);
                  },
                })
              )
            )
          );
        })
      );

      return el(
        Fragment,
        null,
        inspecteur,
        el(
          "div",
          blockProps,
          items.length
            ? liste
            : el(
                C.Placeholder,
                {
                  icon: "clock",
                  label: __("Timeline", "g2rd"),
                  instructions: __(
                    "Programme heure par heure, parcours kilomètre par kilomètre, étapes d'une inscription.",
                    "g2rd"
                  ),
                },
                el(
                  C.Button,
                  { variant: "primary", onClick: ajouter },
                  __("Ajouter une première étape", "g2rd")
                )
              ),
          items.length
            ? el(
                C.Button,
                {
                  variant: "secondary",
                  onClick: ajouter,
                  className: "g2rd-timeline__ajouter",
                },
                __("Ajouter une étape", "g2rd")
              )
            : null
        )
      );
    },

    save: function () {
      // Bloc dynamique : le rendu est assuré par render.php.
      return null;
    },
  });
})(window.wp);
