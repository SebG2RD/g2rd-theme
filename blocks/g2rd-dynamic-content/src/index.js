import { registerBlockType } from "@wordpress/blocks";
import "./style.css";
import Edit from "./edit";

// Bloc dynamique : save retourne null (rendu côté serveur via render.php)
registerBlockType("g2rd/dynamic-content", {
  edit: Edit,
  save: () => null,
});
