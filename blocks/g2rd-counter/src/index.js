/**
 * Registers the G2RD Counter block
 */
import { registerBlockType } from "@wordpress/blocks";
import "./style.css";
import "./editor.css";
import edit from "./edit";
import save from "./save";
import metadata from "../block.json";

/**
 * Register the G2RD Counter block
 */
registerBlockType(metadata.name, {
  edit,
  save,
});
