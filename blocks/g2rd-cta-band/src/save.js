import { useBlockProps } from "@wordpress/block-editor";

const BG_PRESETS = {
  primary: "#2F425D",
  secondary: "#D4A373",
  dark: "#1a1a2e",
  light: "#F5F4F2",
};

export default function Save({ attributes }) {
  const {
    title, description, ctaText, ctaUrl,
    ctaSecondaryText, ctaSecondaryUrl,
    reassurance, showReassurance,
    backgroundStyle, customBg, titleColor, textColor, ctaBg, ctaColor,
    alignment, paddingVertical,
  } = attributes;

  const bg = backgroundStyle === "custom" ? customBg : BG_PRESETS[backgroundStyle] || BG_PRESETS.primary;

  const blockProps = useBlockProps.save({
    className: `g2rd-cta-band alignfull`,
    style: { backgroundColor: bg, padding: `${paddingVertical}px 2rem`, textAlign: alignment },
  });

  return (
    <div {...blockProps}>
      <div className="g2rd-cta-band__inner">
        <h2
          className="g2rd-cta-band__title"
          style={{ color: titleColor, fontSize: "clamp(1.6rem, 2.5vw, 2.5rem)", fontWeight: 800, lineHeight: 1.2, marginBottom: "1rem" }}
        >
          {title}
        </h2>

        {description && (
          <p
            className="g2rd-cta-band__desc"
            style={{ color: textColor, opacity: 0.9, lineHeight: 1.75, maxWidth: "600px", margin: "0 auto 2rem" }}
          >
            {description}
          </p>
        )}

        <div className="g2rd-cta-band__btns">
          <a
            href={ctaUrl}
            className="g2rd-cta-band__btn g2rd-cta-band__btn--primary"
            style={{ backgroundColor: ctaBg, color: ctaColor }}
          >
            {ctaText}
          </a>
          {ctaSecondaryText && (
            <a
              href={ctaSecondaryUrl}
              className="g2rd-cta-band__btn g2rd-cta-band__btn--secondary"
              style={{ color: titleColor }}
            >
              {ctaSecondaryText}
            </a>
          )}
        </div>

        {showReassurance && reassurance && (
          <p
            className="g2rd-cta-band__reassurance"
            style={{ color: textColor, opacity: 0.6 }}
          >
            {reassurance}
          </p>
        )}
      </div>
    </div>
  );
}
