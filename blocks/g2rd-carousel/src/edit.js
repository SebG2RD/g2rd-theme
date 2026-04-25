import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  MediaUpload,
  MediaUploadCheck,
} from "@wordpress/block-editor";
import {
  PanelBody,
  Button,
  TextControl,
  TextareaControl,
  ToggleControl,
  RangeControl,
  SelectControl,
} from "@wordpress/components";
import { useState, useCallback } from "@wordpress/element";
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
      <InspectorControls>
        <PanelBody title={__("Content Selection", "g2rd-carousel")}>
          <SelectControl
            label={__("Content Type", "g2rd-carousel")}
            value={currentContentType}
            options={[
              { label: __("Images", "g2rd-carousel"), value: "images" },
              { label: __("Posts", "g2rd-carousel"), value: "posts" },
              { label: __("Pages", "g2rd-carousel"), value: "pages" },
              { label: __("Portfolio", "g2rd-carousel"), value: "portfolio" },
              { label: __("Prestations", "g2rd-carousel"), value: "prestations" },
              { label: __("Qui sommes-nous", "g2rd-carousel"), value: "qui-sommes-nous" },
            ]}
            onChange={(value) => setAttributes({ contentType: value })}
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
                      ? __("Select Images", "g2rd-carousel")
                      : __("Replace Images", "g2rd-carousel")}
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

        <PanelBody title={__("Carousel Settings", "g2rd-carousel")}>
          <TextControl
            label={__("Title", "g2rd-carousel")}
            value={title}
            onChange={(value) => setAttributes({ title: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextareaControl
            label={__("Description", "g2rd-carousel")}
            value={description}
            onChange={(value) => setAttributes({ description: value })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Show Badge", "g2rd-carousel")}
            checked={showBadge}
            onChange={() => setAttributes({ showBadge: !showBadge })}
          />
          {showBadge && (
            <TextControl
              label={__("Badge Text", "g2rd-carousel")}
              value={badgeText}
              onChange={(value) => setAttributes({ badgeText: value })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          )}
          <ToggleControl
            label={__("Show Captions", "g2rd-carousel")}
            checked={showCaptions}
            onChange={() => setAttributes({ showCaptions: !showCaptions })}
          />
          <ToggleControl
            label={__("Show Box Shadow", "g2rd-carousel")}
            checked={showBoxShadow}
            onChange={() => setAttributes({ showBoxShadow: !showBoxShadow })}
            help={__(
              "Add shadow effect around the carousel images",
              "g2rd-carousel"
            )}
          />
          <RangeControl
            label={__("Height (px)", "g2rd-carousel")}
            value={height}
            onChange={(value) => setAttributes({ height: value })}
            min={200}
            max={800}
            step={50}
            help={__(
              "Set the height of the carousel container",
              "g2rd-carousel"
            )}
          />
        </PanelBody>

        <PanelBody title={__("Animation Settings", "g2rd-carousel")}>
          <SelectControl
            label={__("Effect", "g2rd-carousel")}
            value={effect}
            options={[
              { label: __("Coverflow", "g2rd-carousel"), value: "coverflow" },
              { label: __("Slide", "g2rd-carousel"), value: "slide" },
              { label: __("Fade", "g2rd-carousel"), value: "fade" },
              { label: __("Cube", "g2rd-carousel"), value: "cube" },
              { label: __("Flip", "g2rd-carousel"), value: "flip" },
            ]}
            onChange={(value) => setAttributes({ effect: value })}
          />
          <SelectControl
            label={__("Visible Slides", "g2rd-carousel")}
            value={visibleSlides.toString()}
            options={[
              { label: __("1 slide", "g2rd-carousel"), value: "1" },
              { label: __("3 slides", "g2rd-carousel"), value: "3" },
              { label: __("5 slides", "g2rd-carousel"), value: "5" },
              { label: __("7 slides", "g2rd-carousel"), value: "7" },
            ]}
            onChange={(value) =>
              setAttributes({ visibleSlides: parseInt(value) })
            }
            help={__("Number of slides visible at once", "g2rd-carousel")}
          />
          <RangeControl
            label={__("Autoplay Delay (ms)", "g2rd-carousel")}
            value={autoplayDelay}
            onChange={(value) => setAttributes({ autoplayDelay: value })}
            min={1000}
            max={10000}
            step={500}
          />
          <RangeControl
            label={__("Space Between", "g2rd-carousel")}
            value={spaceBetween}
            onChange={(value) => setAttributes({ spaceBetween: value })}
            min={0}
            max={100}
            step={10}
          />
          <ToggleControl
            label={__("Show Pagination", "g2rd-carousel")}
            checked={showPagination}
            onChange={() => setAttributes({ showPagination: !showPagination })}
          />
          <ToggleControl
            label={__("Show Navigation", "g2rd-carousel")}
            checked={showNavigation}
            onChange={() => setAttributes({ showNavigation: !showNavigation })}
          />
          <ToggleControl
            label={__("Centered Slides", "g2rd-carousel")}
            checked={centeredSlides}
            onChange={() => setAttributes({ centeredSlides: !centeredSlides })}
          />
          <ToggleControl
            label={__("Loop", "g2rd-carousel")}
            checked={loop}
            onChange={() => setAttributes({ loop: !loop })}
          />
          <ToggleControl
            label={__("Grab Cursor", "g2rd-carousel")}
            checked={grabCursor}
            onChange={() => setAttributes({ grabCursor: !grabCursor })}
          />
        </PanelBody>

        {effect === "coverflow" && (
          <PanelBody title={__("Coverflow Settings", "g2rd-carousel")}>
            <RangeControl
              label={__("Rotate", "g2rd-carousel")}
              value={coverflowRotate}
              onChange={(value) => setAttributes({ coverflowRotate: value })}
              min={0}
              max={100}
              step={1}
            />
            <RangeControl
              label={__("Stretch", "g2rd-carousel")}
              value={coverflowStretch}
              onChange={(value) => setAttributes({ coverflowStretch: value })}
              min={0}
              max={100}
              step={1}
            />
            <RangeControl
              label={__("Depth", "g2rd-carousel")}
              value={coverflowDepth}
              onChange={(value) => setAttributes({ coverflowDepth: value })}
              min={0}
              max={500}
              step={10}
            />
            <RangeControl
              label={__("Modifier", "g2rd-carousel")}
              value={coverflowModifier}
              onChange={(value) => setAttributes({ coverflowModifier: value })}
              min={0}
              max={5}
              step={0.1}
            />
          </PanelBody>
        )}
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
                <h2 className="wp-block-heading">{title}</h2>
                <p>{description}</p>
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
