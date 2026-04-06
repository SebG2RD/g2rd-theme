/**
 * Enregistrement du bloc G2RD Graphiques
 */
import { registerBlockType } from "@wordpress/blocks";
import "./style.css";
import "./editor.css";
import edit from "./edit";
import save from "./save";
import metadata from "../block.json";

registerBlockType( metadata.name, { edit, save } );
