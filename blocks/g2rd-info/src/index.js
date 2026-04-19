import { registerBlockType } from "@wordpress/blocks";
import Edit from "./edit";
import save from "./save";
import "./info.css";

registerBlockType("g2rd/info", {
  edit: Edit,
  save,
  variations: [
    {
      name:        "info-standard",
      title:       "Info standard",
      description: "Encart informatif avec icône et texte",
      isDefault:   true,
      attributes:  {
        icon:             "dashicons-info",
        backgroundColor: "#2F425D",
        titleColor:      "#FAFAFA",
        descriptionColor: "#D4A373",
        iconColor:       "#FAFAFA",
        layout:          "icon-left",
      },
    },
    {
      name:        "info-success",
      title:       "Succès",
      description: "Encart de confirmation ou de résultat positif",
      attributes:  {
        icon:             "dashicons-yes-alt",
        backgroundColor: "#14532d",
        titleColor:      "#f0fdf4",
        descriptionColor: "#bbf7d0",
        iconColor:       "#4ade80",
        layout:          "icon-left",
      },
      icon: { src: "yes-alt", foreground: "#4ade80", background: "#14532d" },
    },
    {
      name:        "info-warning",
      title:       "Avertissement",
      description: "Encart d'alerte ou de mise en garde",
      attributes:  {
        icon:             "dashicons-warning",
        backgroundColor: "#78350f",
        titleColor:      "#fffbeb",
        descriptionColor: "#fde68a",
        iconColor:       "#fbbf24",
        layout:          "icon-left",
      },
      icon: { src: "warning", foreground: "#fbbf24", background: "#78350f" },
    },
    {
      name:        "info-danger",
      title:       "Danger / Erreur",
      description: "Encart d'erreur ou d'action critique",
      attributes:  {
        icon:             "dashicons-dismiss",
        backgroundColor: "#7f1d1d",
        titleColor:      "#fef2f2",
        descriptionColor: "#fca5a5",
        iconColor:       "#f87171",
        layout:          "icon-left",
      },
      icon: { src: "dismiss", foreground: "#f87171", background: "#7f1d1d" },
    },
    {
      name:        "info-tip",
      title:       "Conseil / Astuce",
      description: "Encart de conseil ou de bonne pratique",
      attributes:  {
        icon:             "dashicons-lightbulb",
        backgroundColor: "#1e3a5f",
        titleColor:      "#eff6ff",
        descriptionColor: "#93c5fd",
        iconColor:       "#60a5fa",
        layout:          "icon-left",
      },
      icon: { src: "lightbulb", foreground: "#60a5fa", background: "#1e3a5f" },
    },
  ],
});
