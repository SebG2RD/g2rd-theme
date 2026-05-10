import { useBlockProps, RichText } from "@wordpress/block-editor";

export default function Save({ attributes }) {
  const {
    kicker, heading, subheading,
    ctaPrimaryText, ctaPrimaryUrl, ctaPrimaryAriaLabel,
    ctaSecondaryText, ctaSecondaryUrl, ctaSecondaryAriaLabel, showSecondary,
    socialProof, showSocialProof,
    backgroundType, backgroundColor, imageUrl,
    overlayColor, overlayOpacity,
    headingColor, accentColor, textColor, ctaPrimaryBg, ctaPrimaryColor,
    alignment, minHeight, paddingVertical,
  } = attributes;

  const bgStyle =
    backgroundType === "image" && imageUrl
      ? { backgroundImage: `url(${imageUrl})`, backgroundSize: "cover", backgroundPosition: "center" }
      : { backgroundColor };

  const blockProps = useBlockProps.save({
    className: `g2rd-hero g2rd-hero--${alignment} alignfull`,
    style: { ...bgStyle, minHeight: `${minHeight}px`, padding: `${paddingVertical}px 2rem`, position: "relative" },
  });

  const markedHeading = heading
    .replace(/<mark>/g, `<mark style="background:none;color:${accentColor}">`)
    .replace(/<mark ([^>]*)>/g, (_, attrs) => `<mark style="background:none;color:${accentColor}" ${attrs}>`);

  return (
    <div {...blockProps}>
      {backgroundType === "image" && imageUrl && (
        <div
          className="g2rd-hero__overlay"
          aria-hidden="true"
          style={{ position: "absolute", inset: 0, backgroundColor: overlayColor, opacity: overlayOpacity / 100 }}
        />
      )}

      <div
        className="g2rd-hero__inner"
        style={{
          position: "relative",
          zIndex: 1,
          maxWidth: "720px",
          margin: alignment === "center" ? "0 auto" : "0",
          textAlign: alignment === "center" ? "center" : "left",
        }}
      >
        {kicker && (
          <RichText.Content
            tagName="p"
            className="g2rd-hero__kicker"
            value={kicker}
            style={{ color: accentColor, fontWeight: 600, letterSpacing: "0.1em", textTransform: "uppercase", fontSize: "0.875rem", marginBottom: "0.75rem" }}
          />
        )}

        <h1
          className="g2rd-hero__heading"
          style={{ color: headingColor, fontSize: "clamp(2rem, 4vw, 3.5rem)", fontWeight: 800, lineHeight: 1.1, marginBottom: "1.25rem" }}
          dangerouslySetInnerHTML={{ __html: markedHeading }}
        />

        {subheading && (
          <RichText.Content
            tagName="p"
            className="g2rd-hero__subheading"
            value={subheading}
            style={{ color: textColor, opacity: 0.9, lineHeight: 1.75, fontSize: "1.1rem", marginBottom: "2rem" }}
          />
        )}

        <div
          className="g2rd-hero__ctas"
          style={{ display: "flex", gap: "1rem", flexWrap: "wrap", justifyContent: alignment === "center" ? "center" : "flex-start" }}
        >
          {ctaPrimaryText && (
            <RichText.Content
              tagName="a"
              href={ctaPrimaryUrl}
              className="g2rd-hero__btn g2rd-hero__btn--primary"
              aria-label={ctaPrimaryAriaLabel || undefined}
              value={ctaPrimaryText}
              style={{ backgroundColor: ctaPrimaryBg, color: ctaPrimaryColor, padding: "0.9rem 2rem", borderRadius: "4px", fontWeight: 700, textDecoration: "none", display: "inline-block" }}
            />
          )}

          {showSecondary && ctaSecondaryText && (
            <RichText.Content
              tagName="a"
              href={ctaSecondaryUrl}
              className="g2rd-hero__btn g2rd-hero__btn--secondary"
              aria-label={ctaSecondaryAriaLabel || undefined}
              value={ctaSecondaryText}
              style={{ border: "1px solid rgba(250,250,250,0.6)", color: headingColor, padding: "0.9rem 2rem", borderRadius: "4px", fontWeight: 600, textDecoration: "none", display: "inline-block" }}
            />
          )}
        </div>

        {showSocialProof && socialProof && (
          <RichText.Content
            tagName="p"
            className="g2rd-hero__social-proof"
            value={socialProof}
            style={{ color: textColor, opacity: 0.65, fontSize: "0.875rem", marginTop: "1.5rem" }}
          />
        )}
      </div>
    </div>
  );
}
