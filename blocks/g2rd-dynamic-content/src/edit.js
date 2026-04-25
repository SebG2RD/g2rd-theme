import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls, PanelColorSettings } from "@wordpress/block-editor";
import {
  PanelBody,
  SelectControl,
  RangeControl,
  ToggleControl,
  TextControl,
} from "@wordpress/components";
import { useSelect } from "@wordpress/data";

export default function Edit({ attributes, setAttributes }) {
  const {
    postType,
    postsPerPage,
    columns,
    orderby,
    order,
    categoryId,
    showImage,
    showTitle,
    showExcerpt,
    showDate,
    showCategory,
    showAuthor,
    showReadMore,
    readMoreText,
    imageRatio,
    cardRadius,
    cardShadow,
    accentColor,
    textColor,
    backgroundColor,
  } = attributes;

  const blockProps = useBlockProps({ className: "g2rd-dynamic-content" });

  // Récupérer les types de post publics
  const postTypes = useSelect((select) => {
    const types = select("core").getPostTypes({ per_page: -1 });
    if (!types) return [];
    return types
      .filter((t) => t.viewable && !["attachment", "wp_block", "wp_template", "wp_template_part", "wp_navigation", "wp_font_family", "wp_font_face"].includes(t.slug))
      .map((t) => ({ label: t.name, value: t.slug }));
  }, []);

  // Récupérer les catégories du post type sélectionné (taxonomie principale)
  const categories = useSelect(
    (select) => {
      const terms = select("core").getEntityRecords("taxonomy", "category", { per_page: 100, hide_empty: true });
      if (!terms) return [];
      return [{ label: __("Toutes les catégories", "g2rd"), value: 0 }, ...terms.map((t) => ({ label: t.name, value: t.id }))];
    },
    []
  );

  // Prévisualisation : grille de cartes squelettes
  const skeletonCards = Array.from({ length: Math.min(postsPerPage, 6) });

  return (
    <>
      <InspectorControls>
        {/* ── Source ── */}
        <PanelBody title={__("Source de contenu", "g2rd")} initialOpen={true}>
          <SelectControl
            label={__("Type de contenu", "g2rd")}
            value={postType}
            options={postTypes.length ? postTypes : [{ label: "Articles", value: "post" }]}
            onChange={(v) => setAttributes({ postType: v })}
          />
          <RangeControl
            label={__("Nombre d'éléments", "g2rd")}
            value={postsPerPage}
            onChange={(v) => setAttributes({ postsPerPage: v })}
            min={1}
            max={24}
          />
          <SelectControl
            label={__("Catégorie", "g2rd")}
            value={categoryId}
            options={categories}
            onChange={(v) => setAttributes({ categoryId: parseInt(v, 10) })}
          />
        </PanelBody>

        {/* ── Tri ── */}
        <PanelBody title={__("Ordre et tri", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Trier par", "g2rd")}
            value={orderby}
            options={[
              { label: __("Date", "g2rd"),          value: "date" },
              { label: __("Titre", "g2rd"),         value: "title" },
              { label: __("Popularité (commentaires)", "g2rd"), value: "comment_count" },
              { label: __("Aléatoire", "g2rd"),     value: "rand" },
              { label: __("Menu order", "g2rd"),    value: "menu_order" },
            ]}
            onChange={(v) => setAttributes({ orderby: v })}
          />
          <SelectControl
            label={__("Ordre", "g2rd")}
            value={order}
            options={[
              { label: __("Décroissant (plus récent d'abord)", "g2rd"), value: "DESC" },
              { label: __("Croissant (plus ancien d'abord)", "g2rd"),   value: "ASC" },
            ]}
            onChange={(v) => setAttributes({ order: v })}
          />
        </PanelBody>

        {/* ── Mise en page ── */}
        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          <RangeControl
            label={__("Colonnes", "g2rd")}
            value={columns}
            onChange={(v) => setAttributes({ columns: v })}
            min={1}
            max={6}
          />
          <SelectControl
            label={__("Ratio de l'image", "g2rd")}
            value={imageRatio}
            options={[
              { label: "16/9",  value: "16/9" },
              { label: "4/3",   value: "4/3" },
              { label: "1/1 (carré)", value: "1/1" },
              { label: "3/2",   value: "3/2" },
            ]}
            onChange={(v) => setAttributes({ imageRatio: v })}
          />
          <RangeControl
            label={__("Rayon des cartes (px)", "g2rd")}
            value={cardRadius}
            onChange={(v) => setAttributes({ cardRadius: v })}
            min={0}
            max={24}
          />
          <ToggleControl
            label={__("Ombre portée", "g2rd")}
            checked={cardShadow}
            onChange={(v) => setAttributes({ cardShadow: v })}
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* ── Éléments à afficher ── */}
        <PanelBody title={__("Éléments affichés", "g2rd")} initialOpen={false}>
          {[
            ["showImage",    __("Image mise en avant", "g2rd")],
            ["showTitle",    __("Titre", "g2rd")],
            ["showExcerpt",  __("Extrait", "g2rd")],
            ["showDate",     __("Date", "g2rd")],
            ["showCategory", __("Catégorie", "g2rd")],
            ["showAuthor",   __("Auteur", "g2rd")],
            ["showReadMore", __("Bouton « Lire la suite »", "g2rd")],
          ].map(([key, label]) => (
            <ToggleControl
              key={key}
              label={label}
              checked={attributes[key]}
              onChange={(v) => setAttributes({ [key]: v })}
              __nextHasNoMarginBottom
            />
          ))}
          {showReadMore && (
            <TextControl
              label={__("Texte du bouton", "g2rd")}
              value={readMoreText}
              onChange={(v) => setAttributes({ readMoreText: v })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          )}
        </PanelBody>

        {/* ── Couleurs ── */}
        <PanelColorSettings
          title={__("Couleurs", "g2rd")}
          colorSettings={[
            { value: accentColor,     onChange: (v) => setAttributes({ accentColor: v || "" }),     label: __("Couleur d'accentuation", "g2rd") },
            { value: textColor,       onChange: (v) => setAttributes({ textColor: v || "" }),       label: __("Couleur du texte", "g2rd") },
            { value: backgroundColor, onChange: (v) => setAttributes({ backgroundColor: v || "" }), label: __("Couleur de fond des cartes", "g2rd") },
          ]}
        />
      </InspectorControls>

      {/* ── Prévisualisation éditeur ── */}
      <div {...blockProps}>
        <div
          className="g2rd-dynamic-content__hint"
        >
          <span className="dashicons dashicons-database" style={{ fontSize: "20px", marginRight: "8px" }}></span>
          {__("Contenu dynamique", "g2rd")} — <strong>{postType}</strong>
          {categoryId > 0 && ` · cat. #${categoryId}`}
          {` · ${postsPerPage} éléments · ${columns} col.`}
        </div>
        <div
          className="g2rd-dynamic-content__grid"
          style={{ gridTemplateColumns: `repeat(${Math.min(columns, 3)}, 1fr)` }}
        >
          {skeletonCards.map((_, i) => (
            <div
              key={i}
              className="g2rd-dynamic-content__card is-skeleton"
              style={{ borderRadius: `${cardRadius}px` }}
            >
              {showImage && (
                <div
                  className="g2rd-dynamic-content__image"
                  style={{ aspectRatio: imageRatio }}
                ></div>
              )}
              <div className="g2rd-dynamic-content__body">
                {showCategory && <div className="g2rd-dynamic-content__skeleton g2rd-dynamic-content__skeleton--badge"></div>}
                {showTitle   && <div className="g2rd-dynamic-content__skeleton g2rd-dynamic-content__skeleton--title"></div>}
                {showExcerpt && <div className="g2rd-dynamic-content__skeleton g2rd-dynamic-content__skeleton--text"></div>}
                {showDate    && <div className="g2rd-dynamic-content__skeleton g2rd-dynamic-content__skeleton--meta"></div>}
                {showReadMore && <div className="g2rd-dynamic-content__skeleton g2rd-dynamic-content__skeleton--btn"></div>}
              </div>
            </div>
          ))}
        </div>
      </div>
    </>
  );
}
