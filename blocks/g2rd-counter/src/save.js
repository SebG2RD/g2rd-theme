/**
 * WordPress dependencies
 */
import { useBlockProps, RichText } from "@wordpress/block-editor";

/**
 * Save function for the G2RD Counter block
 */
export default function save({ attributes }) {
  const {
    layout,
    alignment,
    startingNumber,
    endingNumber,
    decimalPlaces,
    numberPrefix,
    numberSuffix,
    animationDuration,
    thousands,
    enableIcon,
    mediaType,
    iconName,
    imageUrl,
    imageAlt,
    iconPosition,
    numberColor,
    iconColor,
    backgroundColor,
    title,
    titleColor,
    prefixRightMargin,
    suffixLeftMargin,
    margin,
    numberFontSize,
    titleFontSize,
  } = attributes;

  const iconSize = attributes.iconSize || 48;
  const imageSize = attributes.imageSize || 64;

  const blockProps = useBlockProps.save({
    style: {
      textAlign: alignment,
      backgroundColor: backgroundColor || undefined,
      margin: `${margin.top} ${margin.right} ${margin.bottom} ${margin.left}`,
    },
    className: `g2rd-counter layout-${layout} icon-${iconPosition}`,
    "data-start": startingNumber,
    "data-end": endingNumber,
    "data-decimals": decimalPlaces,
    "data-prefix": numberPrefix,
    "data-suffix": numberSuffix,
    "data-duration": animationDuration,
    "data-thousands": thousands,
  });

  /**
   * Format number with decimals and thousands separator
   * Nota : rend le nombre de DÉPART statique (avant animation JS). L'inférence
   * de décimales de la valeur finale est faite côté front (view.js) pour ne pas
   * modifier le HTML sauvegardé (compat. validation des blocs existants).
   */
  const formatNumber = (number) => {
    let formatted = number.toFixed(decimalPlaces);

    if (thousands === "comma") {
      formatted = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    } else if (thousands === "space") {
      formatted = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    return formatted;
  };

  /**
   * Render the icon based on media type
   */
  const renderIcon = () => {
    if (!enableIcon) return null;

    if (mediaType === "image" && imageUrl) {
      return (
        <img
          src={imageUrl}
          alt={imageAlt || title}
          className="counter-image"
          style={{
            maxWidth: `${imageSize}px`,
            maxHeight: `${imageSize}px`,
            height: "auto",
          }}
        />
      );
    }

    if (mediaType === "icon" && iconName) {
      return (
        <span
          className={`dashicons dashicons-${iconName} counter-icon`}
          aria-hidden="true"
          style={{
            color: iconColor,
            fontSize: `${iconSize}px`,
            width: `${iconSize}px`,
            height: `${iconSize}px`,
          }}
        />
      );
    }

    return null;
  };

  /**
   * Render the number with prefix and suffix (shows starting number for animation)
   */
  const renderNumber = () => {
    const formattedNumber = formatNumber(startingNumber || 0);

    return (
      <div className="counter-number-wrapper">
        {numberPrefix && (
          <span
            className="counter-prefix"
            style={{
              marginRight: `${prefixRightMargin}px`,
              color: numberColor,
              fontSize: numberFontSize || undefined,
            }}
          >
            {numberPrefix}
          </span>
        )}
        <span className="counter-number" style={{ color: numberColor, fontSize: numberFontSize || undefined }}>
          {formattedNumber}
        </span>
        {numberSuffix && (
          <span
            className="counter-suffix"
            style={{
              marginLeft: `${suffixLeftMargin}px`,
              color: numberColor,
              fontSize: numberFontSize || undefined,
            }}
          >
            {numberSuffix}
          </span>
        )}
      </div>
    );
  };

  /**
   * Render the content based on layout and icon position
   */
  const renderContent = () => {
    const icon = renderIcon();
    const number = renderNumber();
    const titleElement = (
      <RichText.Content
        tagName="h3"
        value={title}
        className="counter-title"
        style={{ color: titleColor, fontSize: titleFontSize || undefined }}
      />
    );

    if (layout === "circle") {
      return (
        <div className="counter-content">
          {icon && iconPosition === "top" && (
            <div className="counter-icon-wrapper">{icon}</div>
          )}
          <div className="counter-circle">
            <svg width="120" height="120" className="counter-svg">
              <circle
                cx="60"
                cy="60"
                r="50"
                fill="none"
                stroke="#e6e6e6"
                strokeWidth="8"
              />
              <circle
                cx="60"
                cy="60"
                r="50"
                fill="none"
                stroke={numberColor}
                strokeWidth="8"
                strokeDasharray="0 314"
                strokeLinecap="round"
                transform="rotate(-90 60 60)"
                className="counter-circle-progress"
              />
            </svg>
            <div className="counter-circle-content">{number}</div>
          </div>
          {titleElement}
          {icon && iconPosition === "bottom" && (
            <div className="counter-icon-wrapper">{icon}</div>
          )}
        </div>
      );
    }

    if (layout === "bar") {
      return (
        <div className="counter-content">
          {icon && iconPosition === "top" && (
            <div className="counter-icon-wrapper">{icon}</div>
          )}
          <div className="counter-bar">
            <div
              className="counter-bar-fill"
              style={{
                width: "0%",
                backgroundColor: numberColor,
              }}
            >
              <div className="counter-bar-content">{number}</div>
            </div>
          </div>
          {titleElement}
          {icon && iconPosition === "bottom" && (
            <div className="counter-icon-wrapper">{icon}</div>
          )}
        </div>
      );
    }

    // Layout number (default)
    switch (iconPosition) {
      case "top":
        return (
          <div className="counter-content">
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
            {number}
            {titleElement}
          </div>
        );
      case "bottom":
        return (
          <div className="counter-content">
            {number}
            {titleElement}
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
          </div>
        );
      case "left":
        return (
          <div className="counter-content counter-horizontal">
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
            <div className="counter-text-wrapper">
              {number}
              {titleElement}
            </div>
          </div>
        );
      case "right":
        return (
          <div className="counter-content counter-horizontal">
            <div className="counter-text-wrapper">
              {number}
              {titleElement}
            </div>
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
          </div>
        );
      default:
        return (
          <div className="counter-content">
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
            {number}
            {titleElement}
          </div>
        );
    }
  };

  return <div {...blockProps}>{renderContent()}</div>;
}
