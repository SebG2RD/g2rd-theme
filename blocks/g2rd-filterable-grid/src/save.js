import { useBlockProps } from "@wordpress/block-editor";

const RATIO_MAP = {
  "auto":     "58%",
  "16-9":     "56.25%",
  "4-3":      "75%",
  "3-2":      "66.67%",
  "1-1":      "100%",
  "portrait": "133.33%",
  "2-3":      "150%",
};

export default function Save({ attributes }) {
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
    blockId,
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
    cardTitleFontSize,
    excerptFontSize,
    ctaBgColor,
    ctaTextColor,
    ctaHoverBgColor,
    ctaHoverTextColor,
    ctaPaddingY,
    ctaPaddingX,
    ctaBorderWidth,
    ctaBorderStyle,
    ctaBorderColor,
    ctaBorderRadius,
  } = attributes;

  const blockProps = useBlockProps.save({
    className: `g2rd-filter-grid${showUnderline === false ? " no-text-underline" : ""}`,
    "data-post-types":   JSON.stringify(selectedPostTypes),
    "data-per-page":     String(postsPerPage),
    "data-show-search":  String(showSearch),
    "data-show-tax":     String(showTaxonomyFilter),
    "data-taxonomy":     taxonomy || "",
    "data-columns":      String(layoutColumns),
    "data-card-display": cardDisplay,
    "data-link-type":    linkType,
    "data-read-more":    readMoreText,
    "data-excerpt-len":  String(excerptLength),
    "data-pagination":   String(showPagination),
    "data-block-id":     blockId || "",
    "data-show-price":   String(showPrice),
    "data-show-badge":   String(showBadge),
    "data-show-date":    String(showDate),
    "data-show-rating":  String(showRating),
    "data-show-cart":    String(showAddToCart),
    "data-orderby":             orderby || "date",
    "data-order":               order || "DESC",
    "data-card-title-fontsize": cardTitleFontSize || "",
    "data-excerpt-fontsize":    excerptFontSize   || "",
  });

  const cssVars = {};
  if (titleColor)    cssVars["--g2rd-fg-title-color"]   = titleColor;
  if (cardTextColor) cssVars["--g2rd-fg-text-color"]    = cardTextColor;
  if (excerptColor)  cssVars["--g2rd-fg-excerpt-color"] = excerptColor;
  if (imageAspectRatio && imageAspectRatio !== "auto") {
    cssVars["--g2rd-fg-img-ratio"] = RATIO_MAP[imageAspectRatio] || "58%";
  }
  if (imageObjectFit && imageObjectFit !== "cover") {
    cssVars["--g2rd-fg-img-fit"] = imageObjectFit;
  }
  if (cardTitleFontSize) cssVars["--g2rd-fg-title-font-size"]   = cardTitleFontSize;
  if (excerptFontSize)   cssVars["--g2rd-fg-excerpt-font-size"] = excerptFontSize;
  if (ctaBgColor)        cssVars["--g2rd-fg-cta-bg"]          = ctaBgColor;
  if (ctaTextColor)      cssVars["--g2rd-fg-cta-color"]        = ctaTextColor;
  if (ctaHoverBgColor)   cssVars["--g2rd-fg-cta-hover-bg"]     = ctaHoverBgColor;
  if (ctaHoverTextColor) cssVars["--g2rd-fg-cta-hover-color"]  = ctaHoverTextColor;
  if (ctaPaddingY)     cssVars["--g2rd-fg-cta-pad-y"]        = ctaPaddingY;
  if (ctaPaddingX)     cssVars["--g2rd-fg-cta-pad-x"]        = ctaPaddingX;
  if (ctaBorderWidth)  cssVars["--g2rd-fg-cta-border-width"] = ctaBorderWidth;
  if (ctaBorderStyle)  cssVars["--g2rd-fg-cta-border-style"] = ctaBorderStyle;
  if (ctaBorderColor)  cssVars["--g2rd-fg-cta-border-color"] = ctaBorderColor;
  if (ctaBorderRadius) cssVars["--g2rd-fg-cta-radius"]       = ctaBorderRadius;

  if (Object.keys(cssVars).length) {
    blockProps.style = { ...(blockProps.style || {}), ...cssVars };
  }

  // Squelettes de chargement initial (accessibilité : aria-hidden)
  const skeletons = Array.from({ length: Math.min(postsPerPage, 6) });

  return (
    <div { ...blockProps }>
      { showSearch && (
        <div className="g2rd-filter-grid__controls" aria-label="Filtres">
          <div className="g2rd-filter-grid__search-form">
            <input
              type="search"
              placeholder="Rechercher…"
              aria-label="Recherche"
              disabled
            />
          </div>
          { showTaxonomyFilter && (
            <div className="g2rd-filter-grid__taxonomy">
              <select disabled aria-label="Filtrer par catégorie">
                <option>Toutes les catégories</option>
              </select>
            </div>
          ) }
        </div>
      ) }

      <div
        className="g2rd-filter-grid__grid"
        style={{ "--wrb-grid-columns": layoutColumns }}
        aria-live="polite"
        aria-busy="true"
      >
        { skeletons.map((_, i) => (
          <div key={ i } className="g2rd-filter-grid__card is-skeleton" aria-hidden="true">
            <div className="g2rd-filter-grid__media"></div>
            <div className="g2rd-filter-grid__content">
              <div className="g2rd-filter-grid__badge"></div>
              <div className="g2rd-filter-grid__title"></div>
              <div className="g2rd-filter-grid__excerpt"></div>
            </div>
          </div>
        )) }
      </div>

      { showPagination && (
        <div className="g2rd-filter-grid__pagination is-preview" aria-hidden="true">
          <button type="button" disabled>←</button>
          <button type="button" disabled>1</button>
          <button type="button" disabled>→</button>
        </div>
      ) }
    </div>
  );
}
