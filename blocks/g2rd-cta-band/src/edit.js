import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  PanelColorSettings,
  RichText,
} from "@wordpress/block-editor";
import { TypographySizePanel } from "../../shared/TypographySizePanel";
import {
  PanelBody,
  TextControl,
  ToggleControl,
  RangeControl,
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from "@wordpress/components";
import { G2RDAiInspectorPanel } from '../../shared/ai/G2RDAiInspectorPanel';

export default function Edit({ attributes, setAttributes }) {
  const {
    title, description, ctaText, ctaUrl, ctaAriaLabel,
    ctaSecondaryText, ctaSecondaryUrl, ctaSecondaryAriaLabel,
    reassurance, showReassurance,
    customBg, titleColor, textColor, ctaBg, ctaColor,
    alignment, paddingVertical,
    titleFontSize, textFontSize,
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
      <TypographySizePanel
        elements={ [
          {
            label:    __( 'Titre', 'g2rd' ),
            value:    titleFontSize,
            onChange: ( v ) => setAttributes( { titleFontSize: v || '' } ),
          },
          {
            label:    __( 'Texte', 'g2rd' ),
            value:    textFontSize,
            onChange: ( v ) => setAttributes( { textFontSize: v || '' } ),
          },
        ] }
      />
      {/* ── Onglet « Réglages » ── */}
      <InspectorControls>
        <PanelBody title={__("Contenu", "g2rd")} initialOpen>
          <TextControl
            label={__("URL bouton principal", "g2rd")}
            value={ctaUrl}
            onChange={(v) => setAttributes({ ctaUrl: v })}
            type="url"
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Étiquette ARIA bouton principal", "g2rd")}
            value={ctaAriaLabel}
            onChange={(v) => setAttributes({ ctaAriaLabel: v })}
            placeholder={__("Ex : Demander un devis gratuit pour votre site", "g2rd")}
            help={__("Précise la destination pour les lecteurs d'écran (RGAA 6.1).", "g2rd")}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("URL bouton secondaire (optionnel)", "g2rd")}
            value={ctaSecondaryUrl}
            onChange={(v) => setAttributes({ ctaSecondaryUrl: v })}
            type="url"
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Étiquette ARIA bouton secondaire", "g2rd")}
            value={ctaSecondaryAriaLabel}
            onChange={(v) => setAttributes({ ctaSecondaryAriaLabel: v })}
            placeholder={__("Ex : Voir nos offres et tarifs", "g2rd")}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher la réassurance", "g2rd")}
            checked={showReassurance}
            onChange={(v) => setAttributes({ showReassurance: v })}
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* Choix parmi 2 → ToggleGroupControl natif (remplace le ButtonGroup maison :
            même attribut « alignment », mêmes valeurs). */}
        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          <ToggleGroupControl
            label={__("Alignement", "g2rd")}
            value={alignment}
            onChange={(v) => setAttributes({ alignment: v })}
            isBlock
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          >
            <ToggleGroupControlOption value="left" label={__("Gauche", "g2rd")} />
            <ToggleGroupControlOption value="center" label={__("Centré", "g2rd")} />
          </ToggleGroupControl>
        </PanelBody>
      </InspectorControls>

      {/* ── Onglet « Styles » ── */}
      <InspectorControls group="styles">
        <PanelColorSettings
          title={__("Couleur", "g2rd")}
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

        {/* Dimensions = marge intérieure (padding vertical du bandeau). */}
        <PanelBody title={__("Dimensions", "g2rd")} initialOpen={false}>
          <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
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
      </InspectorControls>
      <G2RDAiInspectorPanel blockType="g2rd/cta-band" attributes={attributes} setAttributes={setAttributes} />

      <div {...blockProps}>
        <div className="g2rd-cta-band__inner">
          <RichText
            tagName="h2"
            className="g2rd-cta-band__title"
            value={title}
            onChange={(v) => setAttributes({ title: v })}
            placeholder={__("Titre du bandeau…", "g2rd")}
            style={{
              color: titleColor,
              fontSize: titleFontSize || "clamp(1.6rem, 2.5vw, 2.5rem)",
              fontWeight: 800,
              lineHeight: 1.2,
              marginBottom: "1rem",
            }}
          />
          <RichText
            tagName="p"
            className="g2rd-cta-band__desc"
            value={description}
            onChange={(v) => setAttributes({ description: v })}
            placeholder={__("Description…", "g2rd")}
            style={{
              color: textColor,
              opacity: 0.9,
              lineHeight: 1.75,
              maxWidth: "600px",
              margin: "0 auto 2rem",
              ...(textFontSize ? { fontSize: textFontSize } : {}),
            }}
          />
          <div className="g2rd-cta-band__btns">
            <RichText
              tagName="a"
              className="g2rd-cta-band__btn g2rd-cta-band__btn--primary"
              value={ctaText}
              onChange={(v) => setAttributes({ ctaText: v })}
              placeholder={__("Texte du bouton…", "g2rd")}
              style={{ backgroundColor: ctaBg, color: ctaColor }}
              href={ctaUrl}
            />
            <RichText
              tagName="a"
              className="g2rd-cta-band__btn g2rd-cta-band__btn--secondary"
              value={ctaSecondaryText}
              onChange={(v) => setAttributes({ ctaSecondaryText: v })}
              placeholder={__("Bouton secondaire (optionnel)…", "g2rd")}
              style={{ color: titleColor }}
              href={ctaSecondaryUrl}
            />
          </div>
          {showReassurance && (
            <RichText
              tagName="p"
              className="g2rd-cta-band__reassurance"
              value={reassurance}
              onChange={(v) => setAttributes({ reassurance: v })}
              placeholder={__("Texte de réassurance…", "g2rd")}
              style={{ color: textColor, opacity: 0.6 }}
            />
          )}
        </div>
      </div>
    </>
  );
}
