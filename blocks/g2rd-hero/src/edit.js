import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  MediaUpload,
  MediaUploadCheck,
  PanelColorSettings,
} from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  TextareaControl,
  ToggleControl,
  SelectControl,
  RangeControl,
  Button,
  __experimentalHStack as HStack,
} from "@wordpress/components";

function HeroPreview({ attributes }) {
  const {
    kicker, heading, subheading,
    ctaPrimaryText, ctaSecondaryText, showSecondary, socialProof, showSocialProof,
    backgroundType, backgroundColor, imageUrl, overlayColor, overlayOpacity,
    headingColor, accentColor, textColor, ctaPrimaryBg, ctaPrimaryColor,
    alignment, minHeight, paddingVertical,
  } = attributes;

  const textAlign = alignment === "center" ? "center" : "left";

  const bgStyle =
    backgroundType === "image" && imageUrl
      ? {
          backgroundImage: `url(${imageUrl})`,
          backgroundSize: "cover",
          backgroundPosition: "center",
        }
      : { backgroundColor };

  return (
    <div
      className={`g2rd-hero g2rd-hero--${alignment}`}
      style={{
        ...bgStyle,
        minHeight: `${minHeight}px`,
        padding: `${paddingVertical}px 2rem`,
        position: "relative",
        display: "flex",
        alignItems: "center",
      }}
    >
      {backgroundType === "image" && imageUrl && (
        <div
          className="g2rd-hero__overlay"
          style={{
            position: "absolute",
            inset: 0,
            backgroundColor: overlayColor,
            opacity: overlayOpacity / 100,
          }}
        />
      )}

      <div
        className="g2rd-hero__inner"
        style={{
          position: "relative",
          zIndex: 1,
          maxWidth: "720px",
          margin: alignment === "center" ? "0 auto" : "0",
          textAlign,
        }}
      >
        {kicker && (
          <p
            className="g2rd-hero__kicker"
            style={{
              color: accentColor,
              fontWeight: 600,
              letterSpacing: "0.1em",
              textTransform: "uppercase",
              fontSize: "0.875rem",
              marginBottom: "0.75rem",
            }}
          >
            {kicker}
          </p>
        )}

        <h1
          className="g2rd-hero__heading"
          style={{
            color: headingColor,
            fontSize: "clamp(2rem, 4vw, 3.5rem)",
            fontWeight: 800,
            lineHeight: 1.1,
            marginBottom: "1.25rem",
          }}
          dangerouslySetInnerHTML={{ __html: heading.replace(/<mark>/g, `<mark style="background:none;color:${accentColor}">`).replace(/<mark /g, `<mark style="background:none;color:${accentColor}" `) }}
        />

        <p
          className="g2rd-hero__subheading"
          style={{ color: textColor, opacity: 0.9, lineHeight: 1.75, fontSize: "1.1rem", marginBottom: "2rem" }}
        >
          {subheading}
        </p>

        <div className="g2rd-hero__ctas" style={{ display: "flex", gap: "1rem", flexWrap: "wrap", justifyContent: alignment === "center" ? "center" : "flex-start" }}>
          <a
            href={attributes.ctaPrimaryUrl}
            className="g2rd-hero__btn g2rd-hero__btn--primary"
            style={{
              backgroundColor: ctaPrimaryBg,
              color: ctaPrimaryColor,
              padding: "0.9rem 2rem",
              borderRadius: "4px",
              fontWeight: 700,
              textDecoration: "none",
              display: "inline-block",
            }}
          >
            {ctaPrimaryText}
          </a>

          {showSecondary && ctaSecondaryText && (
            <a
              href={attributes.ctaSecondaryUrl}
              className="g2rd-hero__btn g2rd-hero__btn--secondary"
              style={{
                border: "1px solid rgba(250,250,250,0.6)",
                color: headingColor,
                padding: "0.9rem 2rem",
                borderRadius: "4px",
                fontWeight: 600,
                textDecoration: "none",
                display: "inline-block",
              }}
            >
              {ctaSecondaryText}
            </a>
          )}
        </div>

        {showSocialProof && socialProof && (
          <p
            className="g2rd-hero__social-proof"
            style={{ color: textColor, opacity: 0.65, fontSize: "0.875rem", marginTop: "1.5rem" }}
          >
            {socialProof}
          </p>
        )}
      </div>
    </div>
  );
}

export default function Edit({ attributes, setAttributes }) {
  const { backgroundType, imageId, imageUrl } = attributes;
  const blockProps = useBlockProps({ className: "g2rd-hero-editor-wrap" });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__("Contenu", "g2rd")} initialOpen>
          <TextControl
            label={__("Accroche (kicker)", "g2rd")}
            value={attributes.kicker}
            onChange={(val) => setAttributes({ kicker: val })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            help={__("Petit texte au-dessus du titre", "g2rd")}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextareaControl
            label={__("Titre (HTML autorisé)", "g2rd")}
            value={attributes.heading}
            onChange={(val) => setAttributes({ heading: val })}
            __nextHasNoMarginBottom
            help={__("Utilisez <mark> pour colorer un mot", "g2rd")}
            rows={3}
          />
          <TextareaControl
            label={__("Sous-titre", "g2rd")}
            value={attributes.subheading}
            onChange={(val) => setAttributes({ subheading: val })}
            __nextHasNoMarginBottom
            rows={3}
          />
        </PanelBody>

        <PanelBody title={__("Boutons CTA", "g2rd")} initialOpen={false}>
          <TextControl
            label={__("Texte bouton principal", "g2rd")}
            value={attributes.ctaPrimaryText}
            onChange={(val) => setAttributes({ ctaPrimaryText: val })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("URL bouton principal", "g2rd")}
            value={attributes.ctaPrimaryUrl}
            onChange={(val) => setAttributes({ ctaPrimaryUrl: val })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            type="url"
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher le bouton secondaire", "g2rd")}
            checked={attributes.showSecondary}
            onChange={(val) => setAttributes({ showSecondary: val })}
            __nextHasNoMarginBottom
          />
          {attributes.showSecondary && (
            <>
              <TextControl
                label={__("Texte bouton secondaire", "g2rd")}
                value={attributes.ctaSecondaryText}
                onChange={(val) => setAttributes({ ctaSecondaryText: val })}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <TextControl
                label={__("URL bouton secondaire", "g2rd")}
                value={attributes.ctaSecondaryUrl}
                onChange={(val) => setAttributes({ ctaSecondaryUrl: val })}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
                type="url"
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
            </>
          )}
          <ToggleControl
            label={__("Afficher la preuve sociale", "g2rd")}
            checked={attributes.showSocialProof}
            onChange={(val) => setAttributes({ showSocialProof: val })}
            __nextHasNoMarginBottom
          />
          {attributes.showSocialProof && (
            <TextControl
              label={__("Texte preuve sociale", "g2rd")}
              value={attributes.socialProof}
              onChange={(val) => setAttributes({ socialProof: val })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          )}
        </PanelBody>

        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Alignement du contenu", "g2rd")}
            value={attributes.alignment}
            options={[
              { label: __("Gauche", "g2rd"), value: "left" },
              { label: __("Centré", "g2rd"), value: "center" },
            ]}
            onChange={(val) => setAttributes({ alignment: val })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <RangeControl
            label={__("Hauteur minimale (px)", "g2rd")}
            value={attributes.minHeight}
            onChange={(val) => setAttributes({ minHeight: val })}
            min={300}
            max={1000}
            step={20}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <RangeControl
            label={__("Espacement vertical (px)", "g2rd")}
            value={attributes.paddingVertical}
            onChange={(val) => setAttributes({ paddingVertical: val })}
            min={20}
            max={160}
            step={8}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>

        <PanelBody title={__("Arrière-plan", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Type de fond", "g2rd")}
            value={backgroundType}
            options={[
              { label: __("Couleur", "g2rd"), value: "color" },
              { label: __("Image", "g2rd"), value: "image" },
            ]}
            onChange={(val) => setAttributes({ backgroundType: val })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          {backgroundType === "image" && (
            <>
              <MediaUploadCheck>
                <MediaUpload
                  onSelect={(media) =>
                    setAttributes({ imageUrl: media.url, imageId: media.id, imageAlt: media.alt || "" })
                  }
                  allowedTypes={["image"]}
                  value={imageId}
                  render={({ open }) => (
                    <div style={{ marginBottom: "12px", marginTop: "8px" }}>
                      {imageUrl ? (
                        <>
                          <img src={imageUrl} alt="" style={{ width: "100%", height: "80px", objectFit: "cover", borderRadius: "4px", marginBottom: "6px" }} />
                          <Button isDestructive size="small" onClick={() => setAttributes({ imageUrl: "", imageId: 0 })}>
                            {__("Supprimer", "g2rd")}
                          </Button>
                        </>
                      ) : (
                        <Button variant="secondary" onClick={open}>{__("Choisir l'image", "g2rd")}</Button>
                      )}
                    </div>
                  )}
                />
              </MediaUploadCheck>
              <RangeControl
                label={__("Opacité overlay (%)", "g2rd")}
                value={attributes.overlayOpacity}
                onChange={(val) => setAttributes({ overlayOpacity: val })}
                min={0}
                max={100}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
            </>
          )}
        </PanelBody>

        <PanelColorSettings
          title={__("Couleurs", "g2rd")}
          initialOpen={false}
          colorSettings={[
            ...(backgroundType === "color" ? [{
              value: attributes.backgroundColor,
              onChange: (v) => setAttributes({ backgroundColor: v }),
              label: __("Fond", "g2rd"),
            }] : []),
            ...(backgroundType === "image" ? [{
              value: attributes.overlayColor,
              onChange: (v) => setAttributes({ overlayColor: v }),
              label: __("Couleur de l'overlay", "g2rd"),
            }] : []),
            {
              value: attributes.headingColor,
              onChange: (v) => setAttributes({ headingColor: v }),
              label: __("Titre", "g2rd"),
            },
            {
              value: attributes.accentColor,
              onChange: (v) => setAttributes({ accentColor: v }),
              label: __("Accent / <mark>", "g2rd"),
            },
            {
              value: attributes.textColor,
              onChange: (v) => setAttributes({ textColor: v }),
              label: __("Sous-titre / texte", "g2rd"),
            },
            {
              value: attributes.ctaPrimaryBg,
              onChange: (v) => setAttributes({ ctaPrimaryBg: v }),
              label: __("Bouton principal — fond", "g2rd"),
            },
            {
              value: attributes.ctaPrimaryColor,
              onChange: (v) => setAttributes({ ctaPrimaryColor: v }),
              label: __("Bouton principal — texte", "g2rd"),
            },
          ]}
        />
      </InspectorControls>

      <div {...blockProps}>
        <HeroPreview attributes={attributes} />
      </div>
    </>
  );
}
