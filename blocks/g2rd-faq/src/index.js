import { registerBlockType, createBlock } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import Edit from "./edit";
import Save from "./save";

// Deprecated v1 — sauvegarde statique avant passage en bloc dynamique (v1.0.0)
const deprecated = [
  {
    attributes: {
      items: { type: "array", default: [] },
      iconType: { type: "string", default: "plus-minus" },
      questionColor: { type: "string", default: "" },
      answerColor: { type: "string", default: "" },
      iconColor: { type: "string", default: "" },
      backgroundColor: { type: "string", default: "" },
      borderColor: { type: "string", default: "" },
      borderRadius: { type: "number", default: 8 },
      separatorColor: { type: "string", default: "" },
      openFirst: { type: "boolean", default: false },
      allowMultiple: { type: "boolean", default: false },
    },
    save( { attributes } ) {
      const {
        items,
        iconType,
        questionColor,
        answerColor,
        iconColor,
        backgroundColor,
        borderColor,
        borderRadius,
        separatorColor,
        openFirst,
        allowMultiple,
      } = attributes;

      const blockProps = useBlockProps.save( {
        className: "g2rd-faq",
        "data-open-first": openFirst ? "true" : "false",
        "data-allow-multiple": allowMultiple ? "true" : "false",
        "data-icon-type": iconType,
      } );

      const cssVars = {
        "--g2rd-faq-question-color": questionColor || "var(--wp--preset--color--primary,#2f425d)",
        "--g2rd-faq-answer-color": answerColor || "inherit",
        "--g2rd-faq-icon-color": iconColor || "var(--wp--preset--color--primary,#2f425d)",
        "--g2rd-faq-bg": backgroundColor || "transparent",
        "--g2rd-faq-border": borderColor || "var(--wp--preset--color--primary,#2f425d)",
        "--g2rd-faq-separator": separatorColor || "#e5e7eb",
        "--g2rd-faq-radius": `${ borderRadius }px`,
      };

      return (
        <div { ...blockProps }>
          <div className="g2rd-faq__list" style={ cssVars }>
            { items.map( ( item, idx ) => (
              <div
                key={ idx }
                className={ `g2rd-faq__item${ openFirst && idx === 0 ? " is-open" : "" }` }
              >
                <button
                  className="g2rd-faq__question"
                  type="button"
                  aria-expanded={ openFirst && idx === 0 ? "true" : "false" }
                  aria-controls={ `g2rd-faq-answer-${ idx }` }
                >
                  <span className="g2rd-faq__question-text">{ item.question }</span>
                  <span className="g2rd-faq__icon" aria-hidden="true"></span>
                </button>
                <div
                  className="g2rd-faq__answer"
                  id={ `g2rd-faq-answer-${ idx }` }
                  role="region"
                >
                  <div className="g2rd-faq__answer-inner">
                    <p>{ item.answer }</p>
                  </div>
                </div>
              </div>
            ) ) }
          </div>
        </div>
      );
    },
  },
];

registerBlockType( "g2rd/faq", {
  edit: Edit,
  save: Save,
  deprecated,
  variations: [
    {
      name:        "faq-standard",
      title:       "FAQ Standard",
      description: "Accordéon classique avec boutons ARIA — idéal pour les pages de contenu",
      isDefault:   true,
      attributes:  { optimizeForGEO: false, openFirst: true, iconType: "plus-minus" },
    },
    {
      name:        "faq-geo",
      title:       "FAQ GEO (schema.org)",
      description: "FAQ optimisée pour les moteurs IA — schema FAQPage + JSON-LD automatiques",
      attributes:  { optimizeForGEO: true, openFirst: true, iconType: "chevron" },
      icon:        { src: "search", foreground: "#FAFAFA", background: "#22c55e" },
    },
  ],
  transforms: {
    from: [
      {
        type: "block",
        blocks: [ "g2rd/geo-faq" ],
        transform( attributes ) {
          return createBlock( "g2rd/faq", {
            items: attributes.items || [],
            optimizeForGEO: true,
          } );
        },
      },
    ],
  },
} );
