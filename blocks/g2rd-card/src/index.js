import { registerBlockType } from "@wordpress/blocks";
import Edit from "./edit";
import Save from "./save";
import deprecated from "./deprecated";
import "./style.css";
import "./editor.css";

registerBlockType("g2rd/card", {
  edit: Edit,
  save: Save,
  deprecated,
});
