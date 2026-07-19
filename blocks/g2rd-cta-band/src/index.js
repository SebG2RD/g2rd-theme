import { registerBlockType } from "@wordpress/blocks";
import metadata from "../block.json";
import Edit from "./edit";
import Save from "./save";
import deprecated from "./deprecated";
import "./style.css";
import "./editor.css";

registerBlockType(metadata.name, {
  edit: Edit,
  save: Save,
  deprecated,
});
