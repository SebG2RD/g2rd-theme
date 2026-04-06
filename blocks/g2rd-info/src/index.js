import { registerBlockType } from "@wordpress/blocks";
import Edit from "./edit";
import save from "./save";
import "./info.css";

registerBlockType("g2rd/info", {
  edit: Edit,
  save,
});
