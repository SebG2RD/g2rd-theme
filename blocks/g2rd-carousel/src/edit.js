import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  MediaUpload,
  MediaUploadCheck,
} from "@wordpress/block-editor";
import { TypographySizePanel } from "../../shared/TypographySizePanel";
import {
  PanelBody,
  Button,
  TextControl,
  TextareaControl,
  ToggleControl,
  RangeControl,
  SelectControl,
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from "@wordpress/components";
import { useCallback } from "@wordpress/element";
import PostSelector from "../../shared/PostSelector";

export default function Edit({ attributes, setAttributes }) {
  const {
    images,
    autoplayDelay,
    showPagination,
    showNavigation,
    effect,
    slidesPerView,
    spaceBetween,
    centeredSlides,
    loop,
    grabCursor,
    coverflowRotate,
    coverflowStretch,
    coverflowDepth,
    coverflowModifier,
    title,
    description,
    showBadge,
    badgeText,
    showCaptions,
    visibleSlides,
    contentType,
    selectedPosts,
    showBoxShadow,
    height,
    showPauseButton,
    titleFontSize,
    descriptionFontSize,
  } = attributes;

  const blockProps = useBlockProps();

  const onSelectImages = useCallback(
    (newImages) => {
      const formattedImages = newImages.map((image) => ({
        id: image.id,
        url: image.url,
        alt: image.alt || "",
        caption: image.caption || "",
      }));
      setAttributes({ images: formattedImages });
    },
    [setAttributes]
  );

  const removeImage = (index) => {
    const newImages = [...images];
    newImages.splice(index, 1);
    setAttributes({ images: newImages });
  };

  // Déterminer le contenu à afficher (avec fallback pour les anciens blocks)
  const currentContentType = contentType || "images";
  const displayContent =
    currentContentType === "images" ? images : selectedPosts || [];
  const hasContent = displayContent && displayContent.length > 0;

  return (
    <>
      {/* ── Onglet « Réglages » ─────────────────────────────────────────── */}
      <InspectorControls>
        {/* Contenu : textes et éléments affichés */}
        <PanelBody title={__("Contenu", "g2rd")} initialOpen={true}>
          <TextControl
            label={__("Titre", "g2rd")}
            value={title}
            onChange={(value) => setAttributes({ title: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextareaControl
            label={__("Description", "g2rd")}
            value={description}
            onChange={(value) => setAttributes({ description: value })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher le badge", "g2rd")}
            checked={showBadge}
            onChange={() => setAttributes({ showBadge: !showBadge })}
            __nextHasNoMarginBottom
          />
          {showBadge && (
            <TextControl
              label={__("Texte du badge", "g2rd")}
              value={badgeText}
              onChange={(value) => setAttributes({ badgeText: value })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          )}
          <ToggleControl
            label={__("Afficher les légendes", "g2rd")}
            checked={showCaptions}
            onChange={() => setAttributes({ showCaptions: !showCaptions })}
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* Mise en page : disposition des diapositives */}
        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          {/* Choix parmi 4 → ToggleGroupControl (même attribut et mêmes
              valeurs que l'ancien SelectControl) */}
          <ToggleGroupControl
            label={__("Diapositives visibles", "g2rd")}
            value={visibleSlides.toString()}
            onChange={(value) =>
              setAttributes({ visibleSlides: parseInt(value) })
            }
            isBlock
            help={__("Nombre de diapositives visibles à la fois", "g2rd")}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          >
            <ToggleGroupControlOption value="1" label="1" />
            <ToggleGroupControlOption value="3" label="3" />
            <ToggleGroupControlOption value="5" label="5" />
            <ToggleGroupControlOption value="7" label="7" />
          </ToggleGroupControl>
          <RangeControl
            label={__("Espace entre les diapositives", "g2rd")}
            value={spaceBetween}
            onChange={(value) => setAttributes({ spaceBetween: value })}
            min={0}
            max={100}
            step={10}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Diapositives centrées", "g2rd")}
            checked={centeredSlides}
            onChange={() => setAttributes({ centeredSlides: !centeredSlides })}
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* Comportement : lecture automatique et interactions */}
        <PanelBody title={__("Comportement", "g2rd")} initialOpen={false}>
          <RangeControl
            label={__("Délai de lecture automatique (ms)", "g2rd")}
            value={autoplayDelay}
            onChange={(value) => setAttributes({ autoplayDelay: value })}
            min={1000}
            max={10000}
            step={500}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          {autoplayDelay > 0 && (
            <ToggleControl
              label={__("Afficher le bouton pause", "g2rd")}
              help={__(
                "RGAA 13.2 — requis pour les animations continues > 5 s",
                "g2rd"
              )}
              checked={showPauseButton === true}
              onChange={(v) => setAttributes({ showPauseButton: v })}
              __nextHasNoMarginBottom
            />
          )}
          <ToggleControl
            label={__("Lecture en boucle", "g2rd")}
            checked={loop}
            onChange={() => setAttributes({ loop: !loop })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Curseur de saisie (grab)", "g2rd")}
            checked={grabCursor}
            onChange={() => setAttributes({ grabCursor: !grabCursor })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher la navigation", "g2rd")}
            checked={showNavigation}
            onChange={() => setAttributes({ showNavigation: !showNavigation })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher la pagination", "g2rd")}
            checked={showPagination}
            onChange={() => setAttributes({ showPagination: !showPagination })}
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* Source de données : origine des diapositives */}
        <PanelBody title={__("Source de données", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Type de contenu", "g2rd")}
            value={currentContentType}
            options={[
              { label: __("Images", "g2rd"), value: "images" },
              { label: __("Articles", "g2rd"), value: "posts" },
              { label: __("Pages", "g2rd"), value: "pages" },
              { label: __("Portfolio", "g2rd"), value: "portfolio" },
              { label: __("Prestations", "g2rd"), value: "prestations" },
              {
                label: __("Qui sommes-nous", "g2rd"),
                value: "qui-sommes-nous",
              },
            ]}
            onChange={(value) => setAttributes({ contentType: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          {currentContentType === "images" && (
            <MediaUploadCheck>
              <MediaUpload
                onSelect={onSelectImages}
                allowedTypes={["image"]}
                multiple={true}
                gallery={true}
                value={images.map((img) => img.id)}
                render={({ open }) => (
                  <Button
                    onClick={open}
                    variant="primary"
                    className="editor-post-featured-image__toggle"
                  >
                    {images.length === 0
                      ? __("Sélectionner des images", "g2rd")
                      : __("Remplacer les images", "g2rd")}
                  </Button>
                )}
              />
            </MediaUploadCheck>
          )}

          {currentContentType !== "images" && (
            <PostSelector
              contentType={contentType}
              selectedPosts={selectedPosts}
              onSelect={(posts) => setAttributes({ selectedPosts: posts })}
            />
          )}
        </PanelBody>
      </InspectorControls>

      {/* ── Onglet « Styles » ───────────────────────────────────────────── */}
      {/* Typographie (panneau partagé, rendu dans group="styles") */}
      <TypographySizePanel
        elements={[
          {
            label: __("Taille du titre", "g2rd"),
            value: titleFontSize,
            onChange: (value) => setAttributes({ titleFontSize: value || "" }),
          },
          {
            label: __("Taille de la description", "g2rd"),
            value: descriptionFontSize,
            onChange: (value) =>
              setAttributes({ descriptionFontSize: value || "" }),
          },
        ]}
      />
      <InspectorControls group="styles">
        {/* Dimensions : hauteur du carrousel */}
        <PanelBody title={__("Dimensions", "g2rd")} initialOpen={false}>
          <RangeControl
            label={__("Hauteur (px)", "g2rd")}
            value={height}
            onChange={(value) => setAttributes({ height: value })}
            min={200}
            max={800}
            step={50}
            help={__("Hauteur du conteneur du carrousel", "g2rd")}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* Ombre : portée des diapositives */}
        <PanelBody title={__("Ombre", "g2rd")} initialOpen={false}>
          <ToggleControl
            label={__("Afficher une ombre", "g2rd")}
            checked={showBoxShadow}
            onChange={() => setAttributes({ showBoxShadow: !showBoxShadow })}
            help={__(
              "Ajoute une ombre autour des images du carrousel",
              "g2rd"
            )}
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* Animation : effet de transition entre diapositives */}
        <PanelBody title={__("Animation", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Effet de transition", "g2rd")}
            value={effect}
            options={[
              { label: __("Coverflow", "g2rd"), value: "coverflow" },
              { label: __("Glissement (slide)", "g2rd"), value: "slide" },
              { label: __("Fondu (fade)", "g2rd"), value: "fade" },
              { label: __("Cube", "g2rd"), value: "cube" },
              { label: __("Retournement (flip)", "g2rd"), value: "flip" },
            ]}
            onChange={(value) => setAttributes({ effect: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          {effect === "coverflow" && (
            <>
              <RangeControl
                label={__("Rotation", "g2rd")}
                value={coverflowRotate}
                onChange={(value) => setAttributes({ coverflowRotate: value })}
                min={0}
                max={100}
                step={1}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <RangeControl
                label={__("Étirement", "g2rd")}
                value={coverflowStretch}
                onChange={(value) => setAttributes({ coverflowStretch: value })}
                min={0}
                max={100}
                step={1}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <RangeControl
                label={__("Profondeur", "g2rd")}
                value={coverflowDepth}
                onChange={(value) => setAttributes({ coverflowDepth: value })}
                min={0}
                max={500}
                step={10}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <RangeControl
                label={__("Modificateur", "g2rd")}
                value={coverflowModifier}
                onChange={(value) =>
                  setAttributes({ coverflowModifier: value })
                }
                min={0}
                max={5}
                step={0.1}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
            </>
          )}
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        {!hasContent ? (
          <div
            style={{
              padding: "40px",
              textAlign: "center",
              border: "2px dashed #ddd",
              borderRadius: "8px",
              backgroundColor: "#f9f9f9",
            }}
          >
            <p>
              {currentContentType === "images"
                ? __(
                    "No images selected. Please add images to create the carousel.",
                    "g2rd-carousel"
                  )
                : __(
                    "No content selected. Please select posts, pages or CPT to create the carousel.",
                    "g2rd-carousel"
                  )}
            </p>
            {currentContentType === "images" ? (
              <MediaUploadCheck>
                <MediaUpload
                  onSelect={onSelectImages}
                  allowedTypes={["image"]}
                  multiple={true}
                  gallery={true}
                  render={({ open }) => (
                    <Button onClick={open} variant="primary">
                      {__("Select Images", "g2rd-carousel")}
                    </Button>
                  )}
                />
              </MediaUploadCheck>
            ) : (
              <PostSelector
                contentType={contentType}
                selectedPosts={selectedPosts}
                onSelect={(posts) => setAttributes({ selectedPosts: posts })}
              />
            )}
          </div>
        ) : (
          <div className="g2rd-carousel-preview">
            <div className="carousel-header">
              {showBadge && (
                <div className="carousel-badge">
                  <span className="badge-icon">✨</span>
                  {badgeText}
                </div>
              )}
              <div className="carousel-title">
                <h2 className="wp-block-heading" style={titleFontSize ? { fontSize: titleFontSize } : undefined}>{title}</h2>
                <p style={descriptionFontSize ? { fontSize: descriptionFontSize } : undefined}>{description}</p>
              </div>
            </div>
            <div
              className="carousel-container"
              style={{ height: `${height}px` }}
            >
              <div className="carousel-slides">
                {displayContent.map((item, index) => (
                  <div
                    key={index}
                    className={`carousel-slide ${
                      !showBoxShadow ? "no-shadow" : ""
                    }`}
                  >
                    {currentContentType === "images" ? (
                      <>
                        <img
                          src={item.url}
                          alt={item.alt}
                          style={{
                            width: "100%",
                            height: "200px",
                            objectFit: "cover",
                            borderRadius: "12px",
                          }}
                        />
                        {showCaptions && item.caption && (
                          <p className="carousel-caption">{item.caption}</p>
                        )}
                      </>
                    ) : (
                      <>
                        <img
                          src={item.featuredImage || ""}
                          alt={item.featuredImageAlt || item.title}
                          style={{
                            width: "100%",
                            height: "200px",
                            objectFit: "cover",
                            borderRadius: "12px",
                          }}
                        />
                        <div className="carousel-post-info">
                          <h4 className="carousel-post-title">{item.title}</h4>
                          {showCaptions && item.excerpt && (
                            <p className="carousel-post-excerpt">
                              {item.excerpt}
                            </p>
                          )}
                        </div>
                      </>
                    )}
                  </div>
                ))}
              </div>
              {showNavigation && (
                <div className="carousel-navigation">
                  <button className="carousel-nav-prev">‹</button>
                  <button className="carousel-nav-next">›</button>
                </div>
              )}
              {showPagination && (
                <div className="carousel-pagination">
                  {displayContent.map((_, index) => (
                    <span key={index} className="pagination-dot"></span>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </>
  );
}
