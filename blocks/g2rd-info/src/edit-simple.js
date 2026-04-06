import { __ } from "@wordpress/i18n";
import { useBlockProps, RichText } from "@wordpress/block-editor";

export default function Edit({ attributes, setAttributes }) {
  const { title, description, backgroundColor } = attributes;

  const blockProps = useBlockProps({
    style: {
      backgroundColor: backgroundColor || "#f8f9fa",
      padding: "20px",
      borderRadius: "8px",
    },
  });

  return (
    <div {...blockProps}>
      <div className="g2rd-info-content">
        <div className="g2rd-info-icon">
          <span
            className="dashicons dashicons-info"
            style={{ fontSize: "48px", color: "#333" }}
          ></span>
        </div>
        <div className="g2rd-info-text">
          <RichText
            tagName="h3"
            value={title}
            onChange={(value) => setAttributes({ title: value })}
            placeholder={__("Enter title...", "g2rd")}
            style={{ color: "#333", fontSize: "1.5rem" }}
          />
          <RichText
            tagName="p"
            value={description}
            onChange={(value) => setAttributes({ description: value })}
            placeholder={__("Enter description...", "g2rd")}
            style={{ color: "#666", fontSize: "1rem" }}
          />
        </div>
      </div>
    </div>
  );
}
