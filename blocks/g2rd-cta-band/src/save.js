import { useBlockProps, RichText } from "@wordpress/block-editor";

export default function Save({ attributes }) {
  const {
    title, description, ctaText, ctaUrl,
    ctaSecondaryText, ctaSecondaryUrl,
    reassurance, showReassurance,
    customBg, titleColor, textColor, ctaBg, ctaColor,
    alignment, paddingVertical,
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
            fontSize: "clamp(1.6rem, 2.5vw, 2.5rem)",
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
            }}
          />
        )}

        <div className="g2rd-cta-band__btns">
          <RichText.Content
            tagName="a"
            href={ctaUrl}
            className="g2rd-cta-band__btn g2rd-cta-band__btn--primary"
            value={ctaText}
            style={{ backgroundColor: ctaBg, color: ctaColor }}
          />
          {ctaSecondaryText && (
            <RichText.Content
              tagName="a"
              href={ctaSecondaryUrl}
              className="g2rd-cta-band__btn g2rd-cta-band__btn--secondary"
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
}
