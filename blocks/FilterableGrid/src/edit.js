import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import {
  PanelBody,
  CheckboxControl,
  RangeControl,
  ToggleControl,
  SelectControl,
  TextControl,
  Spinner,
  Notice,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { useEffect, useState, useMemo } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";

/**
 * Labels et couleurs des sources ecommerce supportées.
 *
 * @type {Record<string, { label: string, color: string }>}
 */
const SOURCE_LABELS = {
  wordpress:  { label: "WordPress",   color: "#3858e9" },
  woocommerce:{ label: "WooCommerce", color: "#7f54b3" },
  surecart:   { label: "SureCart",    color: "#6366f1" },
  fluentcart: { label: "FluentCart",  color: "#0ea5e9" },
};

/**
 * Composant d'édition de la Grille filtrable.
 *
 * Charge les types de contenu disponibles via l'endpoint REST /g2rd/v1/content-types
 * et expose les contrôles InspectorControls pour configurer la grille.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes    - Attributs du bloc
 * @param {Function} props.setAttributes - Setter des attributs
 * @param {string}   props.clientId      - Identifiant unique du bloc dans l'éditeur
 * @returns {JSX.Element}
 */
export default function Edit({ attributes, setAttributes, clientId }) {
  const {
    selectedPostTypes,
    postsPerPage,
    showSearch,
    showTaxonomyFilter,
    taxonomy,
    layoutColumns,
    cardDisplay,
    linkType,
    readMoreText,
    excerptLength,
    showPagination,
    showPrice,
    showBadge,
    showDate,
    showRating,
    showAddToCart,
    orderby,
    order,
  } = attributes;

  // Générer un blockId unique à la création
  useEffect(() => {
    if (!attributes.blockId) {
      setAttributes({ blockId: `g2rd-fg-${clientId.slice(0, 8)}` });
    }
  }, []);

  const blockProps = useBlockProps({ className: "g2rd-filter-grid g2rd-filter-grid--editor" });

  // ── Chargement des types de contenu via notre endpoint ──────────────────────
  const [contentTypes, setContentTypes] = useState(null);
  const [loadingTypes, setLoadingTypes] = useState(true);

  useEffect(() => {
    // Utilise wp.apiFetch pour bénéficier du nonce WordPress automatiquement
    apiFetch({ path: "/g2rd/v1/content-types" })
      .then((data) => {
        setContentTypes(Array.isArray(data) ? data : []);
        setLoadingTypes(false);
      })
      .catch(() => {
        setContentTypes([]);
        setLoadingTypes(false);
      });
  }, []);

  // ── Taxonomies disponibles pour les types sélectionnés ──────────────────────
  const availableTaxonomies = contentTypes
    ? contentTypes
        .filter((t) => selectedPostTypes.includes(t.slug))
        .flatMap((t) => t.taxonomies || [])
        .reduce((acc, tax) => {
          if (!acc.find((t) => t.slug === tax.slug)) acc.push(tax);
          return acc;
        }, [])
    : [];

  // Vérifier si un des types sélectionnés est un produit
  const hasProductType = contentTypes
    ? contentTypes.some((t) => selectedPostTypes.includes(t.slug) && t.is_product)
    : false;

  // Toggle un type dans la sélection
  const toggleType = (slug, checked) => {
    if (checked) {
      setAttributes({ selectedPostTypes: [...selectedPostTypes, slug] });
    } else {
      setAttributes({ selectedPostTypes: selectedPostTypes.filter((s) => s !== slug) });
    }
  };

  // Grouper les types par source (mémoïsé pour éviter le recalcul à chaque rendu)
  const typesBySource = useMemo(() => {
    if (!Array.isArray(contentTypes)) return {};
    return contentTypes.reduce((acc, t) => {
      const src = t.source || "wordpress";
      if (!acc[src]) acc[src] = [];
      acc[src].push(t);
      return acc;
    }, {});
  }, [contentTypes]);

  // Squelettes d'aperçu
  const skeletons = Array.from({ length: Math.min(postsPerPage, 6) });

  return (
    <>
      <InspectorControls>
        {/* ── Types de contenu ── */}
        <PanelBody title={__("Types de contenu", "g2rd")} initialOpen={true}>
          {loadingTypes ? (
            <div style={{ display: "flex", alignItems: "center", gap: "8px", padding: "8px 0" }}>
              <Spinner />
              {__("Chargement des types…", "g2rd")}
            </div>
          ) : contentTypes?.length === 0 ? (
            <Notice status="warning" isDismissible={false}>
              {__("Aucun type de contenu disponible.", "g2rd")}
            </Notice>
          ) : (
            Object.entries(typesBySource).map(([source, types]) => {
              const sourceMeta = SOURCE_LABELS[source] || { label: source, color: "#666" };
              return (
                <div key={source} style={{ marginBottom: "12px" }}>
                  <p style={{
                    fontSize: "11px",
                    fontWeight: 700,
                    textTransform: "uppercase",
                    letterSpacing: "0.5px",
                    color: sourceMeta.color,
                    margin: "0 0 6px",
                    display: "flex",
                    alignItems: "center",
                    gap: "6px",
                  }}>
                    <span style={{
                      background: sourceMeta.color,
                      borderRadius: "3px",
                      color: "#fff",
                      fontSize: "9px",
                      fontWeight: 700,
                      padding: "1px 5px",
                    }}>
                      {sourceMeta.label}
                    </span>
                  </p>
                  {types.map((t) => (
                    <CheckboxControl
                      key={t.slug}
                      label={t.label}
                      checked={selectedPostTypes.includes(t.slug)}
                      onChange={(v) => toggleType(t.slug, v)}
                      __nextHasNoMarginBottom
                    />
                  ))}
                </div>
              );
            })
          )}
          {selectedPostTypes.length === 0 && (
            <Notice status="error" isDismissible={false}>
              {__("Sélectionnez au moins un type de contenu.", "g2rd")}
            </Notice>
          )}
        </PanelBody>

        {/* ── Affichage ── */}
        <PanelBody title={__("Affichage", "g2rd")} initialOpen={false}>
          <RangeControl
            label={__("Éléments par page", "g2rd")}
            value={postsPerPage}
            onChange={(v) => setAttributes({ postsPerPage: v })}
            min={1} max={24}
          />
          <RangeControl
            label={__("Colonnes", "g2rd")}
            value={layoutColumns}
            onChange={(v) => setAttributes({ layoutColumns: v })}
            min={1} max={6}
          />
          <SelectControl
            label={__("Type de carte", "g2rd")}
            value={cardDisplay}
            options={[
              { label: __("Résumé (image + titre + extrait)", "g2rd"), value: "summary" },
              { label: __("Compact (image + titre)", "g2rd"),          value: "compact" },
              { label: __("Liste (horizontal)",  "g2rd"),              value: "list" },
            ]}
            onChange={(v) => setAttributes({ cardDisplay: v })}
          />
          <RangeControl
            label={__("Longueur de l'extrait (car.)", "g2rd")}
            value={excerptLength}
            onChange={(v) => setAttributes({ excerptLength: v })}
            min={50} max={400} step={10}
          />
        </PanelBody>

        {/* ── Champs affichés ── */}
        <PanelBody title={__("Champs affichés", "g2rd")} initialOpen={false}>
          <ToggleControl label={__("Badge / catégorie", "g2rd")} checked={showBadge}    onChange={(v) => setAttributes({ showBadge: v })}    __nextHasNoMarginBottom />
          <ToggleControl label={__("Date", "g2rd")}              checked={showDate}     onChange={(v) => setAttributes({ showDate: v })}     __nextHasNoMarginBottom />
          {hasProductType && (
            <>
              <ToggleControl label={__("Prix", "g2rd")}               checked={showPrice}    onChange={(v) => setAttributes({ showPrice: v })}    __nextHasNoMarginBottom />
              <ToggleControl label={__("Note / étoiles", "g2rd")}     checked={showRating}   onChange={(v) => setAttributes({ showRating: v })}   __nextHasNoMarginBottom />
              <ToggleControl label={__("Bouton « Ajouter au panier »", "g2rd")} checked={showAddToCart} onChange={(v) => setAttributes({ showAddToCart: v })} __nextHasNoMarginBottom />
            </>
          )}
        </PanelBody>

        {/* ── Filtres & Recherche ── */}
        <PanelBody title={__("Filtres & Recherche", "g2rd")} initialOpen={false}>
          <ToggleControl
            label={__("Barre de recherche", "g2rd")}
            checked={showSearch}
            onChange={(v) => setAttributes({ showSearch: v })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Filtre par taxonomie", "g2rd")}
            checked={showTaxonomyFilter}
            onChange={(v) => setAttributes({ showTaxonomyFilter: v })}
            __nextHasNoMarginBottom
          />
          {showTaxonomyFilter && availableTaxonomies.length > 0 && (
            <SelectControl
              label={__("Taxonomie à filtrer", "g2rd")}
              value={taxonomy}
              options={[
                { label: __("— Choisir —", "g2rd"), value: "" },
                ...availableTaxonomies.map((t) => ({ label: t.label, value: t.slug })),
              ]}
              onChange={(v) => setAttributes({ taxonomy: v })}
            />
          )}
          <ToggleControl
            label={__("Pagination", "g2rd")}
            checked={showPagination}
            onChange={(v) => setAttributes({ showPagination: v })}
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* ── Liens ── */}
        <PanelBody title={__("Liens & CTA", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Type de lien", "g2rd")}
            value={linkType}
            options={[
              { label: __("Titre cliquable uniquement", "g2rd"),   value: "title" },
              { label: __("Bouton « Lire la suite »", "g2rd"),     value: "read-more" },
              { label: __("Carte entière cliquable", "g2rd"),      value: "full-card" },
            ]}
            onChange={(v) => setAttributes({ linkType: v })}
          />
          {(linkType === "read-more" || (hasProductType && !showAddToCart)) && (
            <TextControl
              label={__("Texte du bouton", "g2rd")}
              value={readMoreText}
              onChange={(v) => setAttributes({ readMoreText: v })}
              __nextHasNoMarginBottom
            />
          )}
        </PanelBody>

        {/* ── Tri ── */}
        <PanelBody title={__("Tri", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Trier par", "g2rd")}
            value={orderby}
            options={[
              { label: __("Date (récent → ancien)", "g2rd"), value: "date" },
              { label: __("Titre A → Z", "g2rd"),           value: "title" },
              { label: __("Menu order", "g2rd"),             value: "menu_order" },
              { label: __("Commentaires", "g2rd"),           value: "comment_count" },
              { label: __("Aléatoire", "g2rd"),              value: "rand" },
              ...(hasProductType ? [{ label: __("Prix", "g2rd"), value: "price" }] : []),
            ]}
            onChange={(v) => setAttributes({ orderby: v })}
          />
          <SelectControl
            label={__("Ordre", "g2rd")}
            value={order}
            options={[
              { label: __("Décroissant", "g2rd"), value: "DESC" },
              { label: __("Croissant", "g2rd"),   value: "ASC" },
            ]}
            onChange={(v) => setAttributes({ order: v })}
          />
        </PanelBody>
      </InspectorControls>

      {/* ── Aperçu éditeur ── */}
      <div {...blockProps}>
        <div className="g2rd-filter-grid__controls">
          <span style={{ fontSize: "12px", color: "#757575" }}>
            <strong>{selectedPostTypes.join(", ") || "—"}</strong>
            {hasProductType && (
              <span style={{
                background: "#7f54b3",
                borderRadius: "3px",
                color: "#fff",
                fontSize: "10px",
                marginLeft: "6px",
                padding: "1px 6px",
              }}>
                {__("Produits", "g2rd")}
              </span>
            )}
            {` · ${postsPerPage} items · ${layoutColumns} col.`}
          </span>
        </div>

        <div
          className="g2rd-filter-grid__grid"
          style={{ "--wrb-grid-columns": Math.min(layoutColumns, 3) }}
        >
          {skeletons.map((_, i) => (
            <div key={i} className="g2rd-filter-grid__card is-skeleton">
              <div className="g2rd-filter-grid__media"></div>
              <div className="g2rd-filter-grid__content">
                <div className="g2rd-filter-grid__badge"></div>
                <div className="g2rd-filter-grid__title"></div>
                <div className="g2rd-filter-grid__excerpt"></div>
                {hasProductType && showPrice && (
                  <div className="g2rd-filter-grid__price-skeleton"></div>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </>
  );
}
