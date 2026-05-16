import { useBlockProps, RichText } from "@wordpress/block-editor";

export default function Save({ attributes }) {
  const {
    mediaType,
    icon,
    iconSize,
    iconColor,
    iconBgColor,
    iconBorderRadius,
    imageUrl,
    imageAlt,
    imageWidth,
    iconPosition,
    alignment,
    heading,
    headingTag,
    subHeading,
    description,
    showSeparator,
    separatorColor,
    separatorWidth,
    separatorHeight,
    ctaText,
    ctaUrl,
    ctaTarget,
    ctaAriaLabel,
    ctaStyle,
    ctaBgColor,
    ctaTextColor,
    ctaBorderRadius,
    headingColor,
    subHeadingColor,
    descriptionColor,
    backgroundColor,
    borderRadius,
    paddingTop,
    paddingRight,
    paddingBottom,
    paddingLeft,
    headingFontSize,
    subheadingFontSize,
    descriptionFontSize,
    linkMode,
    showSubHeading,
    showDescription,
  } = attributes;

  const blockProps = useBlockProps.save({
    className: `g2rd-card g2rd-card--icon-${iconPosition} g2rd-card--align-${alignment}`,
    style: {
      backgroundColor: backgroundColor || undefined,
      borderRadius: borderRadius ? `${borderRadius}px` : undefined,
      padding: `${paddingTop} ${paddingRight} ${paddingBottom} ${paddingLeft}`,
      textAlign: alignment,
    },
  });

  const renderMedia = () => {
    if (mediaType === "icon" && icon) {
      return (
        <div
          className="g2rd-card__icon-wrap"
          style={{
            width: `${iconSize}px`,
            height: `${iconSize}px`,
            minWidth: `${iconSize}px`,
            backgroundColor: iconBgColor || undefined,
            borderRadius: `${iconBorderRadius}%`,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
          }}
        >
          <span
            className={`dashicons ${icon}`}
            aria-hidden="true"
            style={{
              fontSize: `${Math.round(iconSize * 0.6)}px`,
              color: iconColor || "var(--wp--preset--color--primary,#2f425d)",
              width: `${Math.round(iconSize * 0.6)}px`,
              height: `${Math.round(iconSize * 0.6)}px`,
            }}
          ></span>
        </div>
      );
    }
    if (mediaType === "image" && imageUrl) {
      return (
        <div className="g2rd-card__image-wrap">
          <img
            src={imageUrl}
            alt={imageAlt}
            style={{ width: `${imageWidth}px`, height: "auto" }}
          />
        </div>
      );
    }
    return null;
  };

  const media = renderMedia();

  return (
    <div {...blockProps}>
      {linkMode === "card" && ctaUrl && (
        <a
          className="g2rd-card__overlay-link"
          href={ctaUrl}
          target={ctaTarget ? "_blank" : undefined}
          rel={ctaTarget ? "noopener noreferrer" : undefined}
          aria-label={ctaAriaLabel || undefined}
        ></a>
      )}

      {media && <div className="g2rd-card__media">{media}</div>}

      <div className="g2rd-card__content">
        {heading && (
          <RichText.Content
            tagName={headingTag}
            className="g2rd-card__heading"
            value={heading}
            style={{ color: headingColor || undefined, fontSize: headingFontSize || undefined, margin: 0 }}
          />
        )}

        {showSubHeading && subHeading && (
          <RichText.Content
            tagName="p"
            className="g2rd-card__subheading"
            value={subHeading}
            style={{ color: subHeadingColor || undefined, fontSize: subheadingFontSize || undefined }}
          />
        )}

        {showSeparator && (
          <div
            className="g2rd-card__separator"
            style={{
              width: `${separatorWidth}px`,
              height: `${separatorHeight}px`,
              backgroundColor: separatorColor || "var(--wp--preset--color--primary,#2f425d)",
            }}
          ></div>
        )}

        {showDescription && description && (
          <RichText.Content
            tagName="p"
            className="g2rd-card__description"
            value={description}
            style={{ color: descriptionColor || undefined, fontSize: descriptionFontSize || undefined }}
          />
        )}

        {linkMode === "cta" && ctaText && (
          <div className="g2rd-card__cta">
            <RichText.Content
              tagName="a"
              href={ctaUrl || "#"}
              className={`g2rd-card__cta-link g2rd-card__cta-link--${ctaStyle}`}
              target={ctaTarget ? "_blank" : undefined}
              rel={ctaTarget ? "noopener noreferrer" : undefined}
              aria-label={ctaAriaLabel || undefined}
              value={ctaText}
              style={
                ctaStyle === "button"
                  ? {
                      backgroundColor: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)",
                      color: ctaTextColor || "var(--wp--preset--color--white,#fff)",
                      borderRadius: `${ctaBorderRadius}px`,
                    }
                  : {
                      color: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)",
                    }
              }
            />
          </div>
        )}
      </div>
    </div>
  );
}
