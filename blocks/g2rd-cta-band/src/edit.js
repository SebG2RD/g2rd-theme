import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  TextareaControl,
  ToggleControl,
  SelectControl,
  RangeControl,
  ColorPicker,
} from "@wordpress/components";

const BG_PRESETS = {
  primary: "#2F425D",
  secondary: "#D4A373",
  dark: "#1a1a2e",
  light: "#F5F4F2",
};

export default function Edit({ attributes, setAttributes }) {
  const {
    title, description, ctaText, ctaUrl,
    ctaSecondaryText, ctaSecondaryUrl,
    reassurance, showReassurance,
    backgroundStyle, customBg, titleColor, textColor, ctaBg, ctaColor,
    alignment, paddingVertical,
  } = attributes;

  const bg = backgroundStyle === "custom" ? customBg : BG_PRESETS[backgroundStyle] || BG_PRESETS.primary;
  const textAlign = alignment;

  const blockProps = useBlockProps({
    className: `g2rd-cta-band alignfull`,
    style: {
      backgroundColor: bg,
      padding: `${paddingVertical}px 2rem`,
      textAlign,
    },
  });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__("Contenu", "g2rd")} initialOpen>
          <TextControl
            label={__("Titre", "g2rd")}
            value={title}
            onChange={(v) => setAttributes({ title: v })}
          />
          <TextareaControl
            label={__("Description", "g2rd")}
            value={description}
            onChange={(v) => setAttributes({ description: v })}
            rows={3}
          />
          <TextControl
            label={__("Texte bouton principal", "g2rd")}
            value={ctaText}
            onChange={(v) => setAttributes({ ctaText: v })}
          />
          <TextControl
            label={__("URL bouton principal", "g2rd")}
            value={ctaUrl}
            onChange={(v) => setAttributes({ ctaUrl: v })}
            type="url"
          />
          <TextControl
            label={__("Texte bouton secondaire (optionnel)", "g2rd")}
            value={ctaSecondaryText}
            onChange={(v) => setAttributes({ ctaSecondaryText: v })}
          />
          {ctaSecondaryText && (
            <TextControl
              label={__("URL bouton secondaire", "g2rd")}
              value={ctaSecondaryUrl}
              onChange={(v) => setAttributes({ ctaSecondaryUrl: v })}
              type="url"
            />
          )}
          <ToggleControl
            label={__("Afficher la réassurance", "g2rd")}
            checked={showReassurance}
            onChange={(v) => setAttributes({ showReassurance: v })}
          />
          {showReassurance && (
            <TextControl
              label={__("Texte de réassurance", "g2rd")}
              value={reassurance}
              onChange={(v) => setAttributes({ reassurance: v })}
            />
          )}
        </PanelBody>

        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Alignement", "g2rd")}
            value={alignment}
            options={[
              { label: __("Centré", "g2rd"), value: "center" },
              { label: __("Gauche", "g2rd"), value: "left" },
            ]}
            onChange={(v) => setAttributes({ alignment: v })}
          />
          <RangeControl
            label={__("Espacement vertical (px)", "g2rd")}
            value={paddingVertical}
            onChange={(v) => setAttributes({ paddingVertical: v })}
            min={20}
            max={160}
            step={8}
          />
        </PanelBody>

        <PanelBody title={__("Couleurs", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Fond prédéfini", "g2rd")}
            value={backgroundStyle}
            options={[
              { label: __("Bleu profond (primary)", "g2rd"), value: "primary" },
              { label: __("Beige doré (secondary)", "g2rd"), value: "secondary" },
              { label: __("Nuit", "g2rd"), value: "dark" },
              { label: __("Clair", "g2rd"), value: "light" },
              { label: __("Personnalisé", "g2rd"), value: "custom" },
            ]}
            onChange={(v) => setAttributes({ backgroundStyle: v })}
          />
          {backgroundStyle === "custom" && (
            <>
              <p style={{ fontWeight: 600, marginBottom: 4 }}>{__("Couleur personnalisée", "g2rd")}</p>
              <ColorPicker color={customBg} onChangeComplete={(v) => setAttributes({ customBg: v.hex })} disableAlpha />
            </>
          )}
          <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>{__("Couleur du titre", "g2rd")}</p>
          <ColorPicker color={titleColor} onChangeComplete={(v) => setAttributes({ titleColor: v.hex })} disableAlpha />
          <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>{__("Couleur du texte", "g2rd")}</p>
          <ColorPicker color={textColor} onChangeComplete={(v) => setAttributes({ textColor: v.hex })} disableAlpha />
          <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>{__("Fond bouton", "g2rd")}</p>
          <ColorPicker color={ctaBg} onChangeComplete={(v) => setAttributes({ ctaBg: v.hex })} disableAlpha />
          <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>{__("Texte bouton", "g2rd")}</p>
          <ColorPicker color={ctaColor} onChangeComplete={(v) => setAttributes({ ctaColor: v.hex })} disableAlpha />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        <div className="g2rd-cta-band__inner">
          <h2
            className="g2rd-cta-band__title"
            style={{ color: titleColor, fontSize: "clamp(1.6rem, 2.5vw, 2.5rem)", fontWeight: 800, lineHeight: 1.2, marginBottom: "1rem" }}
          >
            {title}
          </h2>
          {description && (
            <p className="g2rd-cta-band__desc" style={{ color: textColor, opacity: 0.9, lineHeight: 1.75, maxWidth: "600px", margin: "0 auto 2rem" }}>
              {description}
            </p>
          )}
          <div className="g2rd-cta-band__btns">
            <a className="g2rd-cta-band__btn" style={{ backgroundColor: ctaBg, color: ctaColor }} href={ctaUrl}>
              {ctaText}
            </a>
            {ctaSecondaryText && (
              <a className="g2rd-cta-band__btn g2rd-cta-band__btn--secondary" style={{ color: titleColor }} href={ctaSecondaryUrl}>
                {ctaSecondaryText}
              </a>
            )}
          </div>
          {showReassurance && reassurance && (
            <p className="g2rd-cta-band__reassurance" style={{ color: textColor, opacity: 0.6 }}>
              {reassurance}
            </p>
          )}
        </div>
      </div>
    </>
  );
}
