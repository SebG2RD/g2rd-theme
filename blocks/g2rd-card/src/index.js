import { registerBlockType } from "@wordpress/blocks";
import Edit from "./edit";
import Save from "./save";
import deprecated from "./deprecated";

registerBlockType("g2rd/card", {
  edit: Edit,
  save: Save,
  deprecated,
});
