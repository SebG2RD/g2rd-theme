/**
 * Bloc g2rd/route-map — script d'éditeur.
 *
 * L'éditeur ne rend pas la carte : Leaflet dans l'iframe de l'éditeur de site
 * pose plus de problèmes qu'il n'en résout. Il affiche à la place une fiche de
 * contrôle — fichier GPX sélectionné, points remarquables, état de validation —
 * qui est ce dont l'équipe a réellement besoin pour vérifier son travail.
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
  var MediaUpload = wp.blockEditor.MediaUpload;
  var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
  var C = wp.components;

  var POINT_VIDE = { lat: "", lng: "", titre: "", texte: "", type: "repere" };

  var TYPES_POINT = [
    { label: __("Repère", "g2rd"), value: "repere" },
    { label: __("Ravitaillement", "g2rd"), value: "ravitaillement" },
    { label: __("Animation", "g2rd"), value: "animation" },
    { label: __("Poste de secours", "g2rd"), value: "secours" },
  ];

  registerBlockType("g2rd/route-map", {
    edit: function (props) {
      var a = props.attributes;
      var set = props.setAttributes;
      var points = Array.isArray(a.points) ? a.points : [];
      var blockProps = useBlockProps({ className: "g2rd-route-map-editeur" });

      function majPoint(index, cle, valeur) {
        set({
          points: points.map(function (p, i) {
            if (i !== index) return p;
            var copie = Object.assign({}, p);
            copie[cle] = valeur;
            return copie;
          }),
        });
      }

      var inspecteur = el(
        InspectorControls,
        null,
        el(
          C.PanelBody,
          { title: __("Tracé", "g2rd"), initialOpen: true },
          el(C.SelectControl, {
            label: __("État du parcours", "g2rd"),
            help: __(
              "Tant que la Ville et la Police municipale n'ont pas validé le tracé, le bloc affiche un message d'attente au lieu d'une carte.",
              "g2rd"
            ),
            value: a.etatValidation,
            options: [
              { label: __("Publié", "g2rd"), value: "publie" },
              { label: __("En cours de validation", "g2rd"), value: "attente" },
            ],
            onChange: function (v) { set({ etatValidation: v }); },
            __nextHasNoMarginBottom: true,
          }),
          a.etatValidation === "attente"
            ? el(C.TextareaControl, {
                label: __("Message d'attente", "g2rd"),
                value: a.messageAttente,
                rows: 4,
                onChange: function (v) { set({ messageAttente: v }); },
                __nextHasNoMarginBottom: true,
              })
            : null,
          el(
            MediaUploadCheck,
            null,
            el(MediaUpload, {
              allowedTypes: ["application/gpx+xml", "text/xml", "application/xml"],
              value: a.gpxId,
              onSelect: function (media) {
                set({ gpxId: media.id });
              },
              render: function (obj) {
                return el(
                  C.Button,
                  { variant: "secondary", onClick: obj.open, style: { marginBlockEnd: "1rem" } },
                  a.gpxId
                    ? __("Remplacer le fichier GPX", "g2rd")
                    : __("Choisir le fichier GPX", "g2rd")
                );
              },
            })
          ),
          el(C.RangeControl, {
            label: __("Hauteur de la carte (px)", "g2rd"),
            value: a.hauteur,
            min: 240,
            max: 800,
            step: 20,
            onChange: function (v) { set({ hauteur: v }); },
            __nextHasNoMarginBottom: true,
          })
        ),
        el(
          C.PanelBody,
          { title: __("Repère de départ", "g2rd"), initialOpen: false },
          el(C.TextControl, {
            label: __("Libellé du repère", "g2rd"),
            help: __(
              "Laisser vide pour ne poser aucun marqueur au point de départ.",
              "g2rd"
            ),
            value: a.titreDepart,
            onChange: function (v) { set({ titreDepart: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.TextareaControl, {
            label: __("Précision affichée dans l'infobulle", "g2rd"),
            help: __("Exemple : l'adresse exacte du point de rassemblement.", "g2rd"),
            value: a.texteDepart,
            rows: 2,
            onChange: function (v) { set({ texteDepart: v }); },
            __nextHasNoMarginBottom: true,
          })
        ),
        el(
          C.PanelBody,
          { title: __("Affichage", "g2rd"), initialOpen: false },
          el(C.ToggleControl, {
            label: __("Profil altimétrique", "g2rd"),
            checked: !!a.afficherProfil,
            onChange: function (v) { set({ afficherProfil: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.ToggleControl, {
            label: __("Statistiques du parcours", "g2rd"),
            checked: !!a.afficherStats,
            onChange: function (v) { set({ afficherStats: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.ToggleControl, {
            label: __("Boutons de téléchargement", "g2rd"),
            checked: !!a.afficherTelechargements,
            onChange: function (v) { set({ afficherTelechargements: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.TextControl, {
            label: __("URL du fichier KML", "g2rd"),
            value: a.urlKml,
            onChange: function (v) { set({ urlKml: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.TextControl, {
            label: __("URL du plan imprimable (PDF)", "g2rd"),
            value: a.urlPlanPdf,
            onChange: function (v) { set({ urlPlanPdf: v }); },
            __nextHasNoMarginBottom: true,
          })
        ),
        el(
          C.PanelBody,
          { title: __("Points remarquables", "g2rd"), initialOpen: false },
          points.map(function (p, index) {
            return el(
              C.Card,
              { key: index, size: "small", style: { marginBlockEnd: "0.8rem" } },
              el(
                C.CardBody,
                null,
                el(C.TextControl, {
                  label: __("Titre", "g2rd"),
                  value: p.titre,
                  onChange: function (v) { majPoint(index, "titre", v); },
                  __nextHasNoMarginBottom: true,
                }),
                el(C.TextareaControl, {
                  label: __("Description", "g2rd"),
                  value: p.texte,
                  rows: 2,
                  onChange: function (v) { majPoint(index, "texte", v); },
                  __nextHasNoMarginBottom: true,
                }),
                el(C.SelectControl, {
                  label: __("Type", "g2rd"),
                  value: p.type,
                  options: TYPES_POINT,
                  onChange: function (v) { majPoint(index, "type", v); },
                  __nextHasNoMarginBottom: true,
                }),
                el(
                  C.Flex,
                  { gap: 2 },
                  el(
                    C.FlexItem,
                    null,
                    el(C.TextControl, {
                      label: __("Latitude", "g2rd"),
                      value: p.lat,
                      placeholder: "43.5263",
                      onChange: function (v) { majPoint(index, "lat", v); },
                      __nextHasNoMarginBottom: true,
                    })
                  ),
                  el(
                    C.FlexItem,
                    null,
                    el(C.TextControl, {
                      label: __("Longitude", "g2rd"),
                      value: p.lng,
                      placeholder: "5.4454",
                      onChange: function (v) { majPoint(index, "lng", v); },
                      __nextHasNoMarginBottom: true,
                    })
                  )
                ),
                el(
                  C.Button,
                  {
                    size: "small",
                    isDestructive: true,
                    onClick: function () {
                      set({
                        points: points.filter(function (_, i) { return i !== index; }),
                      });
                    },
                  },
                  __("Supprimer ce point", "g2rd")
                )
              )
            );
          }),
          el(
            C.Button,
            {
              variant: "secondary",
              onClick: function () {
                set({ points: points.concat([Object.assign({}, POINT_VIDE)]) });
              },
            },
            __("Ajouter un point remarquable", "g2rd")
          )
        )
      );

      /* ── Fiche de contrôle ────────────────────────────────────────────── */
      var fiche = el(
        C.Placeholder,
        {
          icon: "location-alt",
          label: __("Carte du parcours", "g2rd"),
          instructions:
            a.etatValidation === "attente"
              ? __(
                  "Le tracé est marqué « en cours de validation ». Le message d'attente s'affichera à la place de la carte.",
                  "g2rd"
                )
              : a.gpxId
              ? __(
                  "Le GPX est analysé côté serveur à l'affichage : distance, dénivelé et profil sont calculés automatiquement.",
                  "g2rd"
                )
              : __(
                  "Choisissez un fichier GPX dans le panneau latéral pour tracer le parcours.",
                  "g2rd"
                ),
        },
        el(
          "ul",
          { className: "g2rd-route-map-editeur__resume" },
          el(
            "li",
            null,
            el("strong", null, __("Fichier GPX : ", "g2rd")),
            a.gpxId
              ? __("sélectionné (média n° ", "g2rd") + a.gpxId + ")"
              : __("aucun", "g2rd")
          ),
          el(
            "li",
            null,
            el("strong", null, __("Points remarquables : ", "g2rd")),
            String(points.length)
          ),
          el(
            "li",
            null,
            el("strong", null, __("Profil altimétrique : ", "g2rd")),
            a.afficherProfil ? __("affiché", "g2rd") : __("masqué", "g2rd")
          )
        )
      );

      return el(Fragment, null, inspecteur, el("div", blockProps, fiche));
    },

    save: function () {
      // Bloc dynamique : le GPX est analysé au rendu.
      return null;
    },
  });
})(window.wp);
