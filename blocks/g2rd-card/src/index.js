import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps, RichText } from "@wordpress/block-editor";
import Edit from "./edit";
import Save from "./save";

registerBlockType("g2rd/card", {
  edit: Edit,
  save: Save,
  // Rétrocompatibilité avec l'ancienne version (simple contenu RichText)
  deprecated: [
    {
      attributes: {
        content: { type: "string", default: "" },
      },
      save({ attributes }) {
        const { content } = attributes;
        const blockProps = useBlockProps.save();
        return (
          <div {...blockProps}>
            <RichText.Content tagName="p" value={content} />
          </div>
        );
      },
    },
  ],
});
