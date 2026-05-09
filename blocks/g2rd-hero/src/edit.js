import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  MediaUpload,
  MediaUploadCheck,
  PanelColorSettings,
  RichText,
} from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  TextareaControl,
  ToggleControl,
  SelectControl,
  RangeControl,
  Button,
} from "@wordpress/components";

const CSS_COLOR_RE = /^#[0-9a-fA-F]{3,8}$|^rgb\(\s*\d|^rgba\(\s*\d|^hsl\(\s*\d|^var\(--[\w-]+\)$/;
const sanitizeCssColor = ( color ) => CSS_COLOR_RE.test( color || '' ) ? color : '';

export default function Edit({ attributes, setAttributes }) {
  const {
    kicker, heading, subheading,
    ctaPrimaryText, ctaPrimaryUrl, ctaSecondaryText, ctaSecondaryUrl, showSecondary,
    socialProof, showSocialProof,
    backgroundType, backgroundColor, imageId, imageUrl, overlayColor, overlayOpacity,
    headingColor, accentColor, textColor, ctaPrimaryBg, ctaPrimaryColor,
    alignment, minHeight, paddingVertical,
  } = attributes;

  const safeAccentColor = sanitizeCssColor( accentColor );
  const textAlign = alignment === "center" ? "center" : "left";

  const bgStyle =
    backgroundType === "image" && imageUrl
      ? { backgroundImage: `url(${imageUrl})`, backgroundSize: "cover", backgroundPosition: "center" }
      : { backgroundColor };

  const blockProps = useBlockProps({
    className: `g2rd-hero g2rd-hero--${alignment} alignfull`,
    style: {
      ...bgStyle,
      minHeight: `${minHeight}px`,
      padding: `${paddingVertical}px 2rem`,
      position: "relative",
    },
  });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__("Titre principal", "g2rd")} initialOpen>
          <TextareaControl
            label={__("Titre (HTML autorisé)", "g2rd")}
            value={heading}
            onChange={(val) => setAttributes({ heading: val })}
            __nextHasNoMarginBottom
            help={__("Utilisez <mark> pour colorer un mot. Les autres champs sont éditables directement sur le bloc.", "g2rd")}
            rows={3}
          />
        </PanelBody>

        <PanelBody title={__("Boutons CTA", "g2rd")} initialOpen={false}>
          <TextControl
            label={__("URL bouton principal", "g2rd")}
            value={ctaPrimaryUrl}
            onChange={(val) => setAttributes({ ctaPrimaryUrl: val })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            type="url"
          />
          <ToggleControl
            label={__("Afficher le bouton secondaire", "g2rd")}
            checked={showSecondary}
            onChange={(val) => setAttributes({ showSecondary: val })}
            __nextHasNoMarginBottom
          />
          {showSecondary && (
            <TextControl
              label={__("URL bouton secondaire", "g2rd")}
              value={ctaSecondaryUrl}
              onChange={(val) => setAttributes({ ctaSecondaryUrl: val })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
              type="url"
            />
          )}
          <ToggleControl
            label={__("Afficher la preuve sociale", "g2rd")}
            checked={showSocialProof}
            onChange={(val) => setAttributes({ showSocialProof: val })}
            __nextHasNoMarginBottom
          />
        </PanelBody>

        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Alignement du contenu", "g2rd")}
            value={alignment}
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
            value={minHeight}
            onChange={(val) => setAttributes({ minHeight: val })}
            min={300}
            max={1000}
            step={20}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <RangeControl
            label={__("Espacement vertical (px)", "g2rd")}
            value={paddingVertical}
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
                value={overlayOpacity}
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
              value: backgroundColor,
              onChange: (v) => setAttributes({ backgroundColor: v }),
              label: __("Fond", "g2rd"),
            }] : []),
            ...(backgroundType === "image" ? [{
              value: overlayColor,
              onChange: (v) => setAttributes({ overlayColor: v }),
              label: __("Couleur de l'overlay", "g2rd"),
            }] : []),
            {
              value: headingColor,
              onChange: (v) => setAttributes({ headingColor: v }),
              label: __("Titre", "g2rd"),
            },
            {
              value: accentColor,
              onChange: (v) => setAttributes({ accentColor: v }),
              label: __("Accent / <mark>", "g2rd"),
            },
            {
              value: textColor,
              onChange: (v) => setAttributes({ textColor: v }),
              label: __("Sous-titre / texte", "g2rd"),
            },
            {
              value: ctaPrimaryBg,
              onChange: (v) => setAttributes({ ctaPrimaryBg: v }),
              label: __("Bouton principal — fond", "g2rd"),
            },
            {
              value: ctaPrimaryColor,
              onChange: (v) => setAttributes({ ctaPrimaryColor: v }),
              label: __("Bouton principal — texte", "g2rd"),
            },
          ]}
        />
      </InspectorControls>

      <div {...blockProps}>
        {backgroundType === "image" && imageUrl && (
          <div
            className="g2rd-hero__overlay"
            aria-hidden="true"
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
          <RichText
            tagName="p"
            className="g2rd-hero__kicker"
            value={kicker}
            onChange={(val) => setAttributes({ kicker: val })}
            placeholder={__("Accroche (kicker)…", "g2rd")}
            style={{
              color: accentColor,
              fontWeight: 600,
              letterSpacing: "0.1em",
              textTransform: "uppercase",
              fontSize: "0.875rem",
              marginBottom: "0.75rem",
            }}
          />

          <h1
            className="g2rd-hero__heading"
            style={{
              color: headingColor,
              fontSize: "clamp(2rem, 4vw, 3.5rem)",
              fontWeight: 800,
              lineHeight: 1.1,
              marginBottom: "1.25rem",
            }}
            dangerouslySetInnerHTML={{
              __html: heading.replace(/<mark>/g, `<mark style="background:none;color:${safeAccentColor}">`).replace(/<mark /g, `<mark style="background:none;color:${safeAccentColor}" `),
            }}
          />

          <RichText
            tagName="p"
            className="g2rd-hero__subheading"
            value={subheading}
            onChange={(val) => setAttributes({ subheading: val })}
            placeholder={__("Sous-titre…", "g2rd")}
            style={{
              color: textColor,
              opacity: 0.9,
              lineHeight: 1.75,
              fontSize: "1.1rem",
              marginBottom: "2rem",
            }}
          />

          <div
            className="g2rd-hero__ctas"
            style={{
              display: "flex",
              gap: "1rem",
              flexWrap: "wrap",
              justifyContent: alignment === "center" ? "center" : "flex-start",
            }}
          >
            <RichText
              tagName="a"
              className="g2rd-hero__btn g2rd-hero__btn--primary"
              value={ctaPrimaryText}
              onChange={(val) => setAttributes({ ctaPrimaryText: val })}
              placeholder={__("Texte du bouton principal…", "g2rd")}
              href={ctaPrimaryUrl}
              style={{
                backgroundColor: ctaPrimaryBg,
                color: ctaPrimaryColor,
                padding: "0.9rem 2rem",
                borderRadius: "4px",
                fontWeight: 700,
                textDecoration: "none",
                display: "inline-block",
              }}
            />

            {showSecondary && (
              <RichText
                tagName="a"
                className="g2rd-hero__btn g2rd-hero__btn--secondary"
                value={ctaSecondaryText}
                onChange={(val) => setAttributes({ ctaSecondaryText: val })}
                placeholder={__("Texte du bouton secondaire…", "g2rd")}
                href={ctaSecondaryUrl}
                style={{
                  border: "1px solid rgba(250,250,250,0.6)",
                  color: headingColor,
                  padding: "0.9rem 2rem",
                  borderRadius: "4px",
                  fontWeight: 600,
                  textDecoration: "none",
                  display: "inline-block",
                }}
              />
            )}
          </div>

          {showSocialProof && (
            <RichText
              tagName="p"
              className="g2rd-hero__social-proof"
              value={socialProof}
              onChange={(val) => setAttributes({ socialProof: val })}
              placeholder={__("Texte de preuve sociale…", "g2rd")}
              style={{
                color: textColor,
                opacity: 0.65,
                fontSize: "0.875rem",
                marginTop: "1.5rem",
              }}
            />
          )}
        </div>
      </div>
    </>
  );
}
