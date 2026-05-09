import { useBlockProps, RichText } from "@wordpress/block-editor";

export default function save({ attributes }) {
  const {
    mediaType,
    icon,
    imageUrl,
    imageAlt,
    title,
    description,
    backgroundColor,
    titleColor,
    descriptionColor,
    iconColor,
    iconSize,
    gap,
    layout,
  } = attributes;

  const blockProps = useBlockProps.save({
    className: "g2rd-info-block",
    style: {
      backgroundColor: backgroundColor || "#2F425D",
      padding: "20px",
      borderRadius: "8px",
    },
  });

  const getFlexDirection = () => {
    if (layout === "icon-top") return "column";
    if (layout === "icon-bottom") return "column-reverse";
    if (layout === "icon-right") return "row-reverse";
    return "row";
  };

  const renderMedia = () => {
    if (mediaType === "icon") {
      return (
        <div className="g2rd-info-icon">
          <span
            className={`dashicons ${icon || "dashicons-info"}`}
            aria-hidden="true"
            style={{
              fontSize: `${iconSize}px`,
              color: iconColor || "#FAFAFA",
            }}
          ></span>
        </div>
      );
    } else if (mediaType === "image" && imageUrl) {
      return (
        <div className="g2rd-info-image">
          <img
            src={imageUrl}
            alt={imageAlt}
            style={{ maxWidth: "100px", height: "auto" }}
          />
        </div>
      );
    }
    return null;
  };

  const renderContent = () => {
    if (layout === "icon-top") {
      return (
        <>
          {renderMedia()}
          <div className="g2rd-info-text">
            <RichText.Content
              tagName="h3"
              value={title}
              style={{ color: titleColor || "#FAFAFA", fontSize: "1.5rem" }}
            />
            <RichText.Content
              tagName="p"
              value={description}
              style={{ color: descriptionColor || "#D4A373", fontSize: "1rem" }}
            />
          </div>
        </>
      );
    }
    if (layout === "icon-bottom") {
      return (
        <>
          <div className="g2rd-info-text">
            <RichText.Content
              tagName="h3"
              value={title}
              style={{ color: titleColor || "#FAFAFA", fontSize: "1.5rem" }}
            />
            <RichText.Content
              tagName="p"
              value={description}
              style={{ color: descriptionColor || "#D4A373", fontSize: "1rem" }}
            />
          </div>
          {renderMedia()}
        </>
      );
    }
    if (layout === "icon-right") {
      return (
        <>
          <div className="g2rd-info-text">
            <RichText.Content
              tagName="h3"
              value={title}
              style={{ color: titleColor || "#FAFAFA", fontSize: "1.5rem" }}
            />
            <RichText.Content
              tagName="p"
              value={description}
              style={{ color: descriptionColor || "#D4A373", fontSize: "1rem" }}
            />
          </div>
          {renderMedia()}
        </>
      );
    }
    // icon-left (défaut)
    return (
      <>
        {renderMedia()}
        <div className="g2rd-info-text">
          <RichText.Content
            tagName="h3"
            value={title}
            style={{ color: titleColor || "#FAFAFA", fontSize: "1.5rem" }}
          />
          <RichText.Content
            tagName="p"
            value={description}
            style={{ color: descriptionColor || "#D4A373", fontSize: "1rem" }}
          />
        </div>
      </>
    );
  };

  return (
    <div {...blockProps}>
      <div
        className={`g2rd-info-content g2rd-layout-${layout}`}
        style={{ gap: gap || "16px", flexDirection: getFlexDirection() }}
      >
        {renderContent()}
      </div>
    </div>
  );
}
