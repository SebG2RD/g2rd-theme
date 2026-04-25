import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls, PanelColorSettings } from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  TextareaControl,
  ToggleControl,
  RangeControl,
  Button,
  ButtonGroup,
} from "@wordpress/components";

export default function Edit({ attributes, setAttributes }) {
  const {
    title, description, ctaText, ctaUrl,
    ctaSecondaryText, ctaSecondaryUrl,
    reassurance, showReassurance,
    customBg, titleColor, textColor, ctaBg, ctaColor,
    alignment, paddingVertical,
  } = attributes;

  const blockProps = useBlockProps({
    className: `g2rd-cta-band alignfull`,
    style: {
      backgroundColor: customBg || "var(--wp--preset--color--primary)",
      padding: `${paddingVertical}px 2rem`,
      textAlign: alignment,
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
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextareaControl
            label={__("Description", "g2rd")}
            value={description}
            onChange={(v) => setAttributes({ description: v })}
            __nextHasNoMarginBottom
            rows={3}
          />
          <TextControl
            label={__("Texte bouton principal", "g2rd")}
            value={ctaText}
            onChange={(v) => setAttributes({ ctaText: v })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("URL bouton principal", "g2rd")}
            value={ctaUrl}
            onChange={(v) => setAttributes({ ctaUrl: v })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            type="url"
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Texte bouton secondaire (optionnel)", "g2rd")}
            value={ctaSecondaryText}
            onChange={(v) => setAttributes({ ctaSecondaryText: v })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          {ctaSecondaryText && (
            <TextControl
              label={__("URL bouton secondaire", "g2rd")}
              value={ctaSecondaryUrl}
              onChange={(v) => setAttributes({ ctaSecondaryUrl: v })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
              type="url"
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          )}
          <ToggleControl
            label={__("Afficher la réassurance", "g2rd")}
            checked={showReassurance}
            onChange={(v) => setAttributes({ showReassurance: v })}
            __nextHasNoMarginBottom
          />
          {showReassurance && (
            <TextControl
              label={__("Texte de réassurance", "g2rd")}
              value={reassurance}
              onChange={(v) => setAttributes({ reassurance: v })}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          )}
        </PanelBody>

        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          <p style={{ fontSize: "11px", fontWeight: 600, textTransform: "uppercase", marginBottom: "8px", color: "#1e1e1e" }}>
            {__("Alignement", "g2rd")}
          </p>
          <ButtonGroup style={{ marginBottom: "16px", display: "flex" }}>
            {[
              { value: "left",   label: __("Gauche", "g2rd") },
              { value: "center", label: __("Centré", "g2rd") },
            ].map(({ value, label }) => (
              <Button
                key={value}
                variant={alignment === value ? "primary" : "secondary"}
                onClick={() => setAttributes({ alignment: value })}
                __next40pxDefaultSize
              >
                {label}
              </Button>
            ))}
          </ButtonGroup>
          <RangeControl
            label={__("Espacement vertical (px)", "g2rd")}
            value={paddingVertical}
            onChange={(v) => setAttributes({ paddingVertical: v })}
            min={20}
            max={160}
            step={8}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>

        <PanelColorSettings
          title={__("Couleurs", "g2rd")}
          initialOpen={false}
          colorSettings={[
            {
              value: customBg,
              onChange: (v) => setAttributes({ customBg: v }),
              label: __("Fond", "g2rd"),
            },
            {
              value: titleColor,
              onChange: (v) => setAttributes({ titleColor: v }),
              label: __("Titre", "g2rd"),
            },
            {
              value: textColor,
              onChange: (v) => setAttributes({ textColor: v }),
              label: __("Texte", "g2rd"),
            },
            {
              value: ctaBg,
              onChange: (v) => setAttributes({ ctaBg: v }),
              label: __("Fond du bouton", "g2rd"),
            },
            {
              value: ctaColor,
              onChange: (v) => setAttributes({ ctaColor: v }),
              label: __("Texte du bouton", "g2rd"),
            },
          ]}
        />
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
