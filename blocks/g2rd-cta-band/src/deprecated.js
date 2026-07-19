import { useBlockProps, RichText } from "@wordpress/block-editor";

/**
 * Déprécation v1 — couleurs de l'ancienne palette (navy/tan) figées en dur.
 *
 * Les blocs cta-band existants ont ces valeurs hex dans leur HTML sauvegardé.
 * Cette déprécation reproduit à l'identique l'ancien `save` (+ anciens défauts)
 * pour que WordPress valide ces blocs, puis les migre vers les tokens
 * (nouveaux défauts de block.json) dès qu'ils sont ré-édités.
 */
const v1 = {
  attributes: {
    title: { type: "string", default: "Prêt à lancer votre projet ?" },
    description: {
      type: "string",
      default:
        "Discutons de votre projet lors d'un appel de 30 minutes sans engagement.",
    },
    ctaText: { type: "string", default: "Demander un devis gratuit" },
    ctaUrl: { type: "string", default: "#contact" },
    ctaAriaLabel: { type: "string", default: "" },
    ctaSecondaryText: { type: "string", default: "" },
    ctaSecondaryUrl: { type: "string", default: "" },
    ctaSecondaryAriaLabel: { type: "string", default: "" },
    reassurance: {
      type: "string",
      default: "Réponse sous 24h · Sans engagement · Devis offert",
    },
    showReassurance: { type: "boolean", default: true },
    backgroundStyle: { type: "string", default: "primary" },
    customBg: { type: "string", default: "#2F425D" },
    titleColor: { type: "string", default: "#FAFAFA" },
    textColor: { type: "string", default: "#FAFAFA" },
    ctaBg: { type: "string", default: "#D4A373" },
    ctaColor: { type: "string", default: "#2F425D" },
    alignment: { type: "string", default: "center" },
    paddingVertical: { type: "number", default: 80 },
    titleFontSize: { type: "string", default: "" },
    textFontSize: { type: "string", default: "" },
  },
  save({ attributes }) {
    const {
      title, description, ctaText, ctaUrl, ctaAriaLabel,
      ctaSecondaryText, ctaSecondaryUrl, ctaSecondaryAriaLabel,
      reassurance, showReassurance,
      customBg, titleColor, textColor, ctaBg, ctaColor,
      alignment, paddingVertical,
      titleFontSize, textFontSize,
    } = attributes;

    const bg = customBg || "#2F425D";

    const blockProps = useBlockProps.save({
      className: `g2rd-cta-band alignfull`,
      style: { backgroundColor: bg, padding: `${paddingVertical}px 2rem`, textAlign: alignment },
    });

    return (
      <div {...blockProps}>
        <div className="g2rd-cta-band__inner">
          <RichText.Content
            tagName="h2"
            className="g2rd-cta-band__title"
            value={title}
            style={{
              color: titleColor,
              fontSize: titleFontSize || "clamp(1.6rem, 2.5vw, 2.5rem)",
              fontWeight: 800,
              lineHeight: 1.2,
              marginBottom: "1rem",
            }}
          />

          {description && (
            <RichText.Content
              tagName="p"
              className="g2rd-cta-band__desc"
              value={description}
              style={{
                color: textColor,
                opacity: 0.9,
                lineHeight: 1.75,
                maxWidth: "600px",
                margin: "0 auto 2rem",
                ...(textFontSize ? { fontSize: textFontSize } : {}),
              }}
            />
          )}

          <div className="g2rd-cta-band__btns">
            <RichText.Content
              tagName="a"
              href={ctaUrl}
              className="g2rd-cta-band__btn g2rd-cta-band__btn--primary"
              aria-label={ctaAriaLabel || undefined}
              value={ctaText}
              style={{ backgroundColor: ctaBg, color: ctaColor }}
            />
            {ctaSecondaryText && (
              <RichText.Content
                tagName="a"
                href={ctaSecondaryUrl}
                className="g2rd-cta-band__btn g2rd-cta-band__btn--secondary"
                aria-label={ctaSecondaryAriaLabel || undefined}
                value={ctaSecondaryText}
                style={{ color: titleColor }}
              />
            )}
          </div>

          {showReassurance && reassurance && (
            <RichText.Content
              tagName="p"
              className="g2rd-cta-band__reassurance"
              value={reassurance}
              style={{ color: textColor, opacity: 0.6 }}
            />
          )}
        </div>
      </div>
    );
  },
};

export default [ v1 ];
