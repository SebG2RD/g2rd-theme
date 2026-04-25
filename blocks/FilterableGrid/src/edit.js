import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls, PanelColorSettings } from "@wordpress/block-editor";
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
import { useEffect, useState, useMemo } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";

const RATIO_MAP = {
  "auto":     "58%",
  "16-9":     "56.25%",
  "4-3":      "75%",
  "3-2":      "66.67%",
  "1-1":      "100%",
  "portrait": "133.33%",
  "2-3":      "150%",
};

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
 * Aperçu d'une carte dans l'éditeur — miroir de renderCard() du frontend.
 *
 * @param {Object} props
 * @param {Object} props.item   - Données du post (titre, thumbnail, terms, etc.)
 * @param {Object} props.opts   - Options d'affichage (cardDisplay, linkType, etc.)
 * @param {Object} props.colors - Couleurs personnalisées (titleColor, cardTextColor, excerptColor)
 */
function CardPreview({ item, opts, colors }) {
  const {
    cardDisplay   = "summary",
    linkType      = "title",
    readMoreText  = "Lire la suite",
    excerptLength = 150,
    showBadge     = true,
    showDate      = true,
  } = opts;
  const { titleColor, cardTextColor, excerptColor } = colors;

  const firstTax  = Object.values(item.terms || {})[0];
  const firstTerm = firstTax?.[0];

  const rawExcerpt = item.excerpt || "";
  const excerpt    = rawExcerpt
    ? rawExcerpt.substring(0, excerptLength) + (rawExcerpt.length > excerptLength ? "…" : "")
    : "";

  const isFullCard   = linkType === "full-card";
  const Wrapper      = isFullCard ? "a" : "div";
  const wrapperProps = isFullCard ? { href: item.link } : {};

  return (
    <Wrapper
      { ...wrapperProps }
      className={ `g2rd-filter-grid__card g2rd-fg__card--${ cardDisplay }` }
      style={ cardTextColor ? { color: cardTextColor } : {} }
    >
      { item.thumbnail && (
        <div className="g2rd-filter-grid__media">
          <img src={ item.thumbnail } alt={ item.title } loading="lazy" />
        </div>
      ) }
      <div className="g2rd-filter-grid__content">
        { showBadge && firstTerm && (
          <span className="g2rd-fg__badge">{ firstTerm.name }</span>
        ) }
        <h3
          className="g2rd-fg__title"
          style={ titleColor ? { color: titleColor } : {} }
        >
          { linkType === "title"
            ? <a href={ item.link } onClick={ (e) => e.preventDefault() }>{ item.title }</a>
            : item.title
          }
        </h3>
        { cardDisplay !== "compact" && excerpt && (
          <p
            className="g2rd-fg__excerpt"
            style={ excerptColor ? { color: excerptColor } : {} }
          >
            { excerpt }
          </p>
        ) }
        <div className="g2rd-fg__meta">
          { showDate && item.date && (
            <time className="g2rd-fg__date">{ item.date }</time>
          ) }
        </div>
        { linkType === "read-more" && (
          <a
            href={ item.link }
            className="g2rd-fg__readmore"
            onClick={ (e) => e.preventDefault() }
          >
            { readMoreText }
          </a>
        ) }
      </div>
    </Wrapper>
  );
}

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
    titleColor,
    cardTextColor,
    excerptColor,
    imageAspectRatio,
    imageObjectFit,
    showUnderline,
  } = attributes;

  // Générer un blockId unique à la création
  useEffect(() => {
    if (!attributes.blockId) {
      setAttributes({ blockId: `g2rd-fg-${ clientId.slice(0, 8) }` });
    }
  }, []);

  // Propriétés CSS custom propagées par cascade aux enfants
  const customStyle = {};
  if (titleColor)    customStyle["--g2rd-fg-title-color"]   = titleColor;
  if (cardTextColor) customStyle["--g2rd-fg-text-color"]    = cardTextColor;
  if (excerptColor)  customStyle["--g2rd-fg-excerpt-color"] = excerptColor;
  if (imageAspectRatio && imageAspectRatio !== "auto") {
    customStyle["--g2rd-fg-img-ratio"] = RATIO_MAP[imageAspectRatio] || "58%";
  }
  if (imageObjectFit && imageObjectFit !== "cover") {
    customStyle["--g2rd-fg-img-fit"] = imageObjectFit;
  }

  const blockProps = useBlockProps({
    className: `g2rd-filter-grid g2rd-filter-grid--editor${showUnderline === false ? " no-text-underline" : ""}`,
    style: Object.keys(customStyle).length ? customStyle : undefined,
  });

  // ── Chargement des types de contenu via notre endpoint ──────────────────────
  const [contentTypes, setContentTypes] = useState(null);
  const [loadingTypes, setLoadingTypes] = useState(true);

  useEffect(() => {
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

  // ── Aperçu en temps réel : chargement des vrais posts ────────────────────────
  const [previewPosts,   setPreviewPosts]   = useState([]);
  const [previewLoading, setPreviewLoading] = useState(false);

  // Re-fetch quand le type, le nombre d'éléments ou le tri changent
  const fetchKey = JSON.stringify({ selectedPostTypes, postsPerPage, orderby, order });

  useEffect(() => {
    if (selectedPostTypes.length === 0) {
      setPreviewPosts([]);
      return;
    }

    setPreviewLoading(true);
    const perType = Math.ceil(postsPerPage / selectedPostTypes.length);

    Promise.all(
      selectedPostTypes.map((postType) =>
        apiFetch({
          path: `/g2rd/v1/posts?post_type=${ encodeURIComponent(postType) }&per_page=${ perType }&orderby=${ orderby }&order=${ order }`,
        })
      )
    )
      .then((results) => {
        let items = results.flatMap((r) => r.items || []);
        if (orderby === "date") {
          items.sort((a, b) =>
            order === "DESC"
              ? new Date(b.date_iso) - new Date(a.date_iso)
              : new Date(a.date_iso) - new Date(b.date_iso)
          );
        }
        setPreviewPosts(items.slice(0, postsPerPage));
        setPreviewLoading(false);
      })
      .catch(() => {
        setPreviewPosts([]);
        setPreviewLoading(false);
      });
  }, [fetchKey]);

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

  const hasProductType = contentTypes
    ? contentTypes.some((t) => selectedPostTypes.includes(t.slug) && t.is_product)
    : false;

  const toggleType = (slug, checked) => {
    if (checked) {
      setAttributes({ selectedPostTypes: [...selectedPostTypes, slug] });
    } else {
      setAttributes({ selectedPostTypes: selectedPostTypes.filter((s) => s !== slug) });
    }
  };

  const typesBySource = useMemo(() => {
    if (!Array.isArray(contentTypes)) return {};
    return contentTypes.reduce((acc, t) => {
      const src = t.source || "wordpress";
      if (!acc[src]) acc[src] = [];
      acc[src].push(t);
      return acc;
    }, {});
  }, [contentTypes]);

  // Options transmises à CardPreview
  const cardOpts   = { cardDisplay, linkType, readMoreText, excerptLength, showBadge, showDate };
  const cardColors = { titleColor, cardTextColor, excerptColor };

  // Squelettes affichés pendant le chargement ou si aucun post
  const skeletons = Array.from({ length: Math.min(postsPerPage, 6) });

  return (
    <>
      <InspectorControls>
        {/* ── Types de contenu ── */}
        <PanelBody title={ __("Types de contenu", "g2rd") } initialOpen={ true }>
          { loadingTypes ? (
            <div style={{ display: "flex", alignItems: "center", gap: "8px", padding: "8px 0" }}>
              <Spinner />
              { __("Chargement des types…", "g2rd") }
            </div>
          ) : contentTypes?.length === 0 ? (
            <Notice status="warning" isDismissible={ false }>
              { __("Aucun type de contenu disponible.", "g2rd") }
            </Notice>
          ) : (
            Object.entries(typesBySource).map(([source, types]) => {
              const sourceMeta = SOURCE_LABELS[source] || { label: source, color: "#666" };
              return (
                <div key={ source } style={{ marginBottom: "12px" }}>
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
                      { sourceMeta.label }
                    </span>
                  </p>
                  { types.map((t) => (
                    <CheckboxControl
                      key={ t.slug }
                      label={ t.label }
                      checked={ selectedPostTypes.includes(t.slug) }
                      onChange={ (v) => toggleType(t.slug, v) }
                      __nextHasNoMarginBottom
                    />
                  )) }
                </div>
              );
            })
          ) }
          { selectedPostTypes.length === 0 && (
            <Notice status="error" isDismissible={ false }>
              { __("Sélectionnez au moins un type de contenu.", "g2rd") }
            </Notice>
          ) }
        </PanelBody>

        {/* ── Affichage ── */}
        <PanelBody title={ __("Affichage", "g2rd") } initialOpen={ false }>
          <RangeControl
            label={ __("Éléments par page", "g2rd") }
            value={ postsPerPage }
            onChange={ (v) => setAttributes({ postsPerPage: v }) }
            min={ 1 } max={ 24 }
          />
          <RangeControl
            label={ __("Colonnes", "g2rd") }
            value={ layoutColumns }
            onChange={ (v) => setAttributes({ layoutColumns: v }) }
            min={ 1 } max={ 6 }
          />
          <SelectControl
            label={ __("Type de carte", "g2rd") }
            value={ cardDisplay }
            options={ [
              { label: __("Résumé (image + titre + extrait)", "g2rd"), value: "summary" },
              { label: __("Compact (image + titre)", "g2rd"),          value: "compact" },
              { label: __("Liste (horizontal)",  "g2rd"),              value: "list" },
            ] }
            onChange={ (v) => setAttributes({ cardDisplay: v }) }
          />
          <RangeControl
            label={ __("Longueur de l'extrait (car.)", "g2rd") }
            value={ excerptLength }
            onChange={ (v) => setAttributes({ excerptLength: v }) }
            min={ 50 } max={ 400 } step={ 10 }
          />
        </PanelBody>

        {/* ── Champs affichés ── */}
        <PanelBody title={ __("Champs affichés", "g2rd") } initialOpen={ false }>
          <ToggleControl label={ __("Badge / catégorie", "g2rd") } checked={ showBadge }      onChange={ (v) => setAttributes({ showBadge: v }) }      __nextHasNoMarginBottom />
          <ToggleControl label={ __("Date", "g2rd") }              checked={ showDate }       onChange={ (v) => setAttributes({ showDate: v }) }       __nextHasNoMarginBottom />
          <ToggleControl label={ __("Souligner les liens", "g2rd") } checked={ showUnderline } onChange={ (v) => setAttributes({ showUnderline: v }) } __nextHasNoMarginBottom />
          { hasProductType && (
            <>
              <ToggleControl label={ __("Prix", "g2rd") }                         checked={ showPrice }     onChange={ (v) => setAttributes({ showPrice: v }) }     __nextHasNoMarginBottom />
              <ToggleControl label={ __("Note / étoiles", "g2rd") }               checked={ showRating }    onChange={ (v) => setAttributes({ showRating: v }) }    __nextHasNoMarginBottom />
              <ToggleControl label={ __("Bouton « Ajouter au panier »", "g2rd") } checked={ showAddToCart } onChange={ (v) => setAttributes({ showAddToCart: v }) } __nextHasNoMarginBottom />
            </>
          ) }
        </PanelBody>

        {/* ── Couleurs des cartes ── */}
        <PanelColorSettings
          title={ __("Couleurs des cartes", "g2rd") }
          initialOpen={ false }
          colorSettings={ [
            {
              value:    titleColor,
              onChange: (v) => setAttributes({ titleColor: v || "" }),
              label:    __("Couleur du titre", "g2rd"),
            },
            {
              value:    cardTextColor,
              onChange: (v) => setAttributes({ cardTextColor: v || "" }),
              label:    __("Couleur du texte", "g2rd"),
            },
            {
              value:    excerptColor,
              onChange: (v) => setAttributes({ excerptColor: v || "" }),
              label:    __("Couleur de l'extrait", "g2rd"),
            },
          ] }
        />

        {/* ── Image des cartes ── */}
        <PanelBody title={ __("Image des cartes", "g2rd") } initialOpen={ false }>
          <SelectControl
            label={ __("Format (ratio)", "g2rd") }
            value={ imageAspectRatio }
            options={ [
              { label: __("Auto (58%)", "g2rd"),      value: "auto" },
              { label: __("16:9",       "g2rd"),      value: "16-9" },
              { label: __("4:3",        "g2rd"),      value: "4-3" },
              { label: __("3:2",        "g2rd"),      value: "3-2" },
              { label: __("Carré 1:1",  "g2rd"),      value: "1-1" },
              { label: __("Portrait 3:4", "g2rd"),    value: "portrait" },
              { label: __("Portrait 2:3", "g2rd"),    value: "2-3" },
            ] }
            onChange={ (v) => setAttributes({ imageAspectRatio: v }) }
          />
          <SelectControl
            label={ __("Ajustement de l'image", "g2rd") }
            value={ imageObjectFit }
            options={ [
              { label: __("Cover — recadré, remplit",  "g2rd"), value: "cover" },
              { label: __("Contain — image complète",  "g2rd"), value: "contain" },
              { label: __("Fill — étiré si besoin",    "g2rd"), value: "fill" },
              { label: __("None — taille originale",   "g2rd"), value: "none" },
            ] }
            onChange={ (v) => setAttributes({ imageObjectFit: v }) }
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* ── Filtres & Recherche ── */}
        <PanelBody title={ __("Filtres & Recherche", "g2rd") } initialOpen={ false }>
          <ToggleControl
            label={ __("Barre de recherche", "g2rd") }
            checked={ showSearch }
            onChange={ (v) => setAttributes({ showSearch: v }) }
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={ __("Filtre par taxonomie", "g2rd") }
            checked={ showTaxonomyFilter }
            onChange={ (v) => setAttributes({ showTaxonomyFilter: v }) }
            __nextHasNoMarginBottom
          />
          { showTaxonomyFilter && availableTaxonomies.length > 0 && (
            <SelectControl
              label={ __("Taxonomie à filtrer", "g2rd") }
              value={ taxonomy }
              options={ [
                { label: __("— Choisir —", "g2rd"), value: "" },
                ...availableTaxonomies.map((t) => ({ label: t.label, value: t.slug })),
              ] }
              onChange={ (v) => setAttributes({ taxonomy: v }) }
            />
          ) }
          <ToggleControl
            label={ __("Pagination", "g2rd") }
            checked={ showPagination }
            onChange={ (v) => setAttributes({ showPagination: v }) }
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* ── Liens ── */}
        <PanelBody title={ __("Liens & CTA", "g2rd") } initialOpen={ false }>
          <SelectControl
            label={ __("Type de lien", "g2rd") }
            value={ linkType }
            options={ [
              { label: __("Titre cliquable uniquement", "g2rd"), value: "title" },
              { label: __("Bouton « Lire la suite »", "g2rd"),  value: "read-more" },
              { label: __("Carte entière cliquable", "g2rd"),   value: "full-card" },
            ] }
            onChange={ (v) => setAttributes({ linkType: v }) }
          />
          { (linkType === "read-more" || (hasProductType && !showAddToCart)) && (
            <TextControl
              label={ __("Texte du bouton", "g2rd") }
              value={ readMoreText }
              onChange={ (v) =
              __next40pxDefaultSize
              __nextHasNoMarginBottom
onChange={ (v) => setAttributes({ readMoreText: v }) }
              __nextHasNoMarginBottom
            />
          ) }
        </PanelBody>

        {/* ── Tri ── */}
        <PanelBody title={ __("Tri", "g2rd") } initialOpen={ false }>
          <SelectControl
            label={ __("Trier par", "g2rd") }
            value={ orderby }
            options={ [
              { label: __("Date (récent → ancien)", "g2rd"), value: "date" },
              { label: __("Titre A → Z", "g2rd"),           value: "title" },
              { label: __("Menu order", "g2rd"),             value: "menu_order" },
              { label: __("Commentaires", "g2rd"),           value: "comment_count" },
              { label: __("Aléatoire", "g2rd"),              value: "rand" },
              ...(hasProductType ? [{ label: __("Prix", "g2rd"), value: "price" }] : []),
            ] }
            onChange={ (v) => setAttributes({ orderby: v }) }
          />
          <SelectControl
            label={ __("Ordre", "g2rd") }
            value={ order }
            options={ [
              { label: __("Décroissant", "g2rd"), value: "DESC" },
              { label: __("Croissant", "g2rd"),   value: "ASC" },
            ] }
            onChange={ (v) => setAttributes({ order: v }) }
          />
        </PanelBody>
      </InspectorControls>

      {/* ── Aperçu éditeur ── */}
      <div { ...blockProps }>
        <div className="g2rd-filter-grid__controls">
          <span style={{ fontSize: "12px", color: "#757575", display: "flex", alignItems: "center", gap: "6px" }}>
            <strong>{ selectedPostTypes.join(", ") || "—" }</strong>
            { hasProductType && (
              <span style={{
                background: "#7f54b3",
                borderRadius: "3px",
                color: "#fff",
                fontSize: "10px",
                padding: "1px 6px",
              }}>
                { __("Produits", "g2rd") }
              </span>
            ) }
            { ` · ${ postsPerPage } items · ${ layoutColumns } col.` }
            { previewLoading && <Spinner style={{ width: "14px", height: "14px", margin: 0 }} /> }
          </span>
        </div>

        <div
          className="g2rd-filter-grid__grid"
          style={{ "--wrb-grid-columns": Math.min(layoutColumns, 3) }}
        >
          { previewLoading || previewPosts.length === 0
            ? skeletons.map((_, i) => (
                <div key={ i } className="g2rd-filter-grid__card is-skeleton">
                  <div className="g2rd-filter-grid__media"></div>
                  <div className="g2rd-filter-grid__content">
                    <div className="g2rd-filter-grid__badge"></div>
                    <div className="g2rd-filter-grid__title"></div>
                    <div className="g2rd-filter-grid__excerpt"></div>
                    { hasProductType && showPrice && (
                      <div className="g2rd-filter-grid__price-skeleton"></div>
                    ) }
                  </div>
                </div>
              ))
            : previewPosts.map((item) => (
                <CardPreview
                  key={ item.id }
                  item={ item }
                  opts={ cardOpts }
                  colors={ cardColors }
                />
              ))
          }
        </div>
      </div>
    </>
  );
}
