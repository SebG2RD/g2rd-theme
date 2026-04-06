import { registerBlockType } from "@wordpress/blocks";
import "./carousel.css";
import "./carousel-frontend.js";
import Edit from "./edit";
import Save from "./save";
import SaveDeprecated from "./save-deprecated";

registerBlockType("g2rd/carousel", {
  edit: Edit,
  save: Save,
  deprecated: [
    {
      save: SaveDeprecated,
      attributes: {
        images: { type: "array", default: [] },
        autoplayDelay: { type: "number", default: 3000 },
        showPagination: { type: "boolean", default: true },
        showNavigation: { type: "boolean", default: true },
        effect: { type: "string", default: "coverflow" },
        slidesPerView: { type: "string", default: "3" },
        spaceBetween: { type: "number", default: 50 },
        centeredSlides: { type: "boolean", default: true },
        loop: { type: "boolean", default: true },
        grabCursor: { type: "boolean", default: true },
        coverflowRotate: { type: "number", default: 50 },
        coverflowStretch: { type: "number", default: 0 },
        coverflowDepth: { type: "number", default: 200 },
        coverflowModifier: { type: "number", default: 1 },
        title: { type: "string", default: "Image Carousel" },
        description: {
          type: "string",
          default: "Beautiful image carousel with smooth animations",
        },
        showBadge: { type: "boolean", default: true },
        badgeText: { type: "string", default: "Latest component" },
        showCaptions: { type: "boolean", default: true },
        style: { type: "object" },
        visibleSlides: { type: "number", default: 3 },
        contentType: { type: "string", default: "images" },
        selectedPosts: { type: "array", default: [] },
        showBoxShadow: { type: "boolean", default: true },
        height: { type: "number", default: 400 },
      },
    },
  ],
});
