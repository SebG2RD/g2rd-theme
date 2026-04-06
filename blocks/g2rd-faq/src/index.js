import { registerBlockType } from "@wordpress/blocks";
import Edit from "./edit";
import Save from "./save";

registerBlockType("g2rd/faq", {
  edit: Edit,
  save: Save,
});
