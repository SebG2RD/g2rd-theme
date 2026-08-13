/**
 * Bloc g2rd/pricing-tiers — script d'éditeur.
 *
 * L'aperçu reproduit la logique de bascule du rendu PHP : l'éditrice voit
 * immédiatement quel palier est actif, lequel est écoulé, et ce qu'affichera
 * le bandeau d'urgence. Sans cela, il faut publier pour vérifier — et une
 * erreur de date sur une grille tarifaire se paie en inscriptions perdues.
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
  var C = wp.components;

  var PALIER_VIDE = { nom: "", prix: "", debut: "", fin: "", mention: "" };

  /** Date du jour au format Y-m-d, dans le fuseau du navigateur. */
  function aujourdhui() {
    var d = new Date();
    return (
      d.getFullYear() +
      "-" +
      ("0" + (d.getMonth() + 1)).slice(-2) +
      "-" +
      ("0" + d.getDate()).slice(-2)
    );
  }

  /** Même règle que render.php : passé / actif / futur / permanent. */
  function statut(palier, jour) {
    var debut = (palier.debut || "").trim();
    var fin = (palier.fin || "").trim();
    if (!debut && !fin) return "permanent";
    if (fin && jour > fin) return "passe";
    if (debut && jour < debut) return "futur";
    return "actif";
  }

  function dateLongue(iso) {
    if (!iso) return "";
    var parts = iso.split("-");
    if (parts.length !== 3) return iso;
    var mois = [
      "janvier", "février", "mars", "avril", "mai", "juin",
      "juillet", "août", "septembre", "octobre", "novembre", "décembre",
    ];
    return (
      parseInt(parts[2], 10) + " " + mois[parseInt(parts[1], 10) - 1] + " " + parts[0]
    );
  }

  registerBlockType("g2rd/pricing-tiers", {
    edit: function (props) {
      var a = props.attributes;
      var set = props.setAttributes;
      var paliers = Array.isArray(a.paliers) ? a.paliers : [];
      var jour = aujourdhui();

      function maj(index, cle, valeur) {
        set({
          paliers: paliers.map(function (p, i) {
            if (i !== index) return p;
            var copie = Object.assign({}, p);
            copie[cle] = valeur;
            return copie;
          }),
        });
      }

      function ajouter() {
        set({ paliers: paliers.concat([Object.assign({}, PALIER_VIDE)]) });
      }

      function supprimer(index) {
        set({
          paliers: paliers.filter(function (_, i) {
            return i !== index;
          }),
        });
      }

      function deplacer(index, delta) {
        var cible = index + delta;
        if (cible < 0 || cible >= paliers.length) return;
        var copie = paliers.slice();
        var tmp = copie[index];
        copie[index] = copie[cible];
        copie[cible] = tmp;
        set({ paliers: copie });
      }

      // Prochain palier daté à entrer en vigueur — alimente le bandeau.
      var suivant = null;
      paliers.forEach(function (p) {
        if (statut(p, jour) !== "futur" || !p.debut) return;
        if (!suivant || p.debut < suivant.debut) suivant = p;
      });

      // Contrôle de cohérence : trous et chevauchements de dates.
      var alertes = [];
      var dates = paliers
        .map(function (p, i) {
          return { i: i, nom: p.nom || "Palier " + (i + 1), debut: p.debut, fin: p.fin };
        })
        .filter(function (p) {
          return p.debut && p.fin;
        })
        .sort(function (x, y) {
          return x.debut < y.debut ? -1 : 1;
        });

      dates.forEach(function (p) {
        if (p.debut > p.fin) {
          alertes.push(
            __("« ", "g2rd") + p.nom + __(" » : la date de fin précède la date de début.", "g2rd")
          );
        }
      });

      for (var i = 0; i < dates.length - 1; i++) {
        if (dates[i].fin >= dates[i + 1].debut) {
          alertes.push(
            __("Chevauchement entre « ", "g2rd") + dates[i].nom +
            __(" » et « ", "g2rd") + dates[i + 1].nom +
            __(" » : deux tarifs seraient actifs le même jour.", "g2rd")
          );
        }
      }

      var actifs = paliers.filter(function (p) {
        return statut(p, jour) === "actif" && (p.debut || p.fin);
      });
      if (actifs.length === 0 && paliers.length > 0) {
        alertes.push(
          __("Aucun palier daté n'est actif aujourd'hui : vérifiez les périodes.", "g2rd")
        );
      }

      var blockProps = useBlockProps({ className: "g2rd-pricing-tiers" });

      var inspecteur = el(
        InspectorControls,
        null,
        el(
          C.PanelBody,
          { title: __("Affichage", "g2rd"), initialOpen: true },
          el(C.TextControl, {
            label: __("Devise", "g2rd"),
            value: a.devise,
            onChange: function (v) { set({ devise: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.TextControl, {
            label: __("Libellé du badge", "g2rd"),
            value: a.badgeActif,
            onChange: function (v) { set({ badgeActif: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.ToggleControl, {
            label: __("Afficher les paliers écoulés", "g2rd"),
            help: __(
              "Grisés et barrés. Les laisser visibles est ce qui crée la pression temporelle : on voit ce qu'on a manqué.",
              "g2rd"
            ),
            checked: !!a.afficherPasses,
            onChange: function (v) { set({ afficherPasses: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.ToggleControl, {
            label: __("Afficher le bandeau de bascule", "g2rd"),
            help: __("« Le tarif passe à X € le [date] à minuit. »", "g2rd"),
            checked: !!a.afficherBandeau,
            onChange: function (v) { set({ afficherBandeau: v }); },
            __nextHasNoMarginBottom: true,
          })
        ),
        el(
          C.PanelBody,
          { title: __("Bouton d'inscription", "g2rd"), initialOpen: false },
          el(C.ToggleControl, {
            label: __("Afficher le bouton", "g2rd"),
            checked: !!a.afficherBouton,
            onChange: function (v) { set({ afficherBouton: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.TextControl, {
            label: __("Texte du bouton", "g2rd"),
            value: a.texteBouton,
            onChange: function (v) { set({ texteBouton: v }); },
            __nextHasNoMarginBottom: true,
          }),
          el(C.TextControl, {
            label: __("Lien", "g2rd"),
            help: __(
              "Laisser « #inscription:tarifs » : le lien est réécrit automatiquement vers la plateforme avec ses paramètres UTM.",
              "g2rd"
            ),
            value: a.urlInscription,
            onChange: function (v) { set({ urlInscription: v }); },
            __nextHasNoMarginBottom: true,
          })
        )
      );

      var grille = el(
        "ul",
        { className: "g2rd-pricing-tiers__grille" },
        paliers.map(function (p, index) {
          var etat = statut(p, jour);
          return el(
            "li",
            {
              key: index,
              className:
                "g2rd-pricing-tiers__palier g2rd-pricing-tiers__palier--" + etat,
            },
            etat === "actif"
              ? el("span", { className: "g2rd-pricing-tiers__badge" }, a.badgeActif)
              : null,
            el(C.TextControl, {
              label: __("Nom du palier", "g2rd"),
              value: p.nom,
              placeholder: "Early",
              onChange: function (v) { maj(index, "nom", v); },
              __nextHasNoMarginBottom: true,
            }),
            el(C.TextControl, {
              label: __("Prix", "g2rd"),
              value: p.prix,
              placeholder: "18",
              help: __("Un jeton {{TARIF_EARLY}} est accepté.", "g2rd"),
              onChange: function (v) { maj(index, "prix", v); },
              __nextHasNoMarginBottom: true,
            }),
            el(C.TextControl, {
              label: __("Début", "g2rd"),
              type: "date",
              value: p.debut,
              onChange: function (v) { maj(index, "debut", v); },
              __nextHasNoMarginBottom: true,
            }),
            el(C.TextControl, {
              label: __("Fin", "g2rd"),
              type: "date",
              value: p.fin,
              help: __("Laisser les deux dates vides pour un tarif permanent.", "g2rd"),
              onChange: function (v) { maj(index, "fin", v); },
              __nextHasNoMarginBottom: true,
            }),
            el(C.TextControl, {
              label: __("Mention", "g2rd"),
              value: p.mention,
              placeholder: __("Le meilleur tarif de l'année", "g2rd"),
              onChange: function (v) { maj(index, "mention", v); },
              __nextHasNoMarginBottom: true,
            }),
            el(
              "p",
              { className: "g2rd-pricing-tiers__etat" },
              {
                passe: __("Écoulé", "g2rd"),
                actif: __("Actif aujourd'hui", "g2rd"),
                futur: __("À venir", "g2rd"),
                permanent: __("Permanent", "g2rd"),
              }[etat]
            ),
            el(
              "div",
              { className: "g2rd-pricing-tiers__outils" },
              el(C.Button, {
                size: "small",
                icon: "arrow-left-alt2",
                label: __("Déplacer avant", "g2rd"),
                disabled: index === 0,
                onClick: function () { deplacer(index, -1); },
              }),
              el(C.Button, {
                size: "small",
                icon: "arrow-right-alt2",
                label: __("Déplacer après", "g2rd"),
                disabled: index === paliers.length - 1,
                onClick: function () { deplacer(index, 1); },
              }),
              el(C.Button, {
                size: "small",
                icon: "trash",
                isDestructive: true,
                label: __("Supprimer ce palier", "g2rd"),
                onClick: function () { supprimer(index); },
              })
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
          alertes.length
            ? el(
                C.Notice,
                { status: "warning", isDismissible: false },
                el(
                  "ul",
                  { style: { margin: 0, paddingInlineStart: "1.2em" } },
                  alertes.map(function (t, i) {
                    return el("li", { key: i }, t);
                  })
                )
              )
            : null,
          paliers.length
            ? grille
            : el(
                C.Placeholder,
                {
                  icon: "tickets-alt",
                  label: __("Tarifs à paliers datés", "g2rd"),
                  instructions: __(
                    "Chaque palier a une période. Le palier en cours est déterminé automatiquement par la date : rien à basculer à la main.",
                    "g2rd"
                  ),
                },
                el(
                  C.Button,
                  { variant: "primary", onClick: ajouter },
                  __("Ajouter un premier palier", "g2rd")
                )
              ),
          a.afficherBandeau && suivant
            ? el(
                "p",
                { className: "g2rd-pricing-tiers__bascule" },
                __("Le tarif passe à ", "g2rd"),
                el("strong", null, suivant.prix),
                " " + a.devise + __(" le ", "g2rd"),
                el("strong", null, dateLongue(suivant.debut)),
                __(" à minuit. Il n'y a pas de rattrapage.", "g2rd")
              )
            : null,
          paliers.length
            ? el(
                C.Button,
                {
                  variant: "secondary",
                  onClick: ajouter,
                  className: "g2rd-pricing-tiers__ajouter",
                },
                __("Ajouter un palier", "g2rd")
              )
            : null
        )
      );
    },

    save: function () {
      // Bloc dynamique : la bascule dépend de la date de consultation, donc
      // du serveur. Rien ne peut être figé au moment de l'enregistrement.
      return null;
    },
  });
})(window.wp);
