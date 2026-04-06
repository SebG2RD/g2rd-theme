/**
 * Enregistrement du bloc G2RD Titre avancé
 */
import { registerBlockType } from "@wordpress/blocks";
import "./style.css";
import "./editor.css";
import edit from "./edit";
import save from "./save";
import metadata from "../block.json";

registerBlockType( metadata.name, { edit, save } );
