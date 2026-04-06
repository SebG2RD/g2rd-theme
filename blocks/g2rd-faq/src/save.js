import { useBlockProps } from "@wordpress/block-editor";

export default function Save({ attributes }) {
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

  const blockProps = useBlockProps.save({
    className: "g2rd-faq",
    "data-open-first": openFirst ? "true" : "false",
    "data-allow-multiple": allowMultiple ? "true" : "false",
    "data-icon-type": iconType,
  });

  const cssVars = {
    "--g2rd-faq-question-color": questionColor || "var(--wp--preset--color--primary,#2f425d)",
    "--g2rd-faq-answer-color": answerColor || "inherit",
    "--g2rd-faq-icon-color": iconColor || "var(--wp--preset--color--primary,#2f425d)",
    "--g2rd-faq-bg": backgroundColor || "transparent",
    "--g2rd-faq-border": borderColor || "var(--wp--preset--color--primary,#2f425d)",
    "--g2rd-faq-separator": separatorColor || "#e5e7eb",
    "--g2rd-faq-radius": `${borderRadius}px`,
  };

  return (
    <div {...blockProps}>
      <div className="g2rd-faq__list" style={cssVars}>
        {items.map((item, idx) => (
          <div
            key={idx}
            className={`g2rd-faq__item${openFirst && idx === 0 ? " is-open" : ""}`}
          >
            <button
              className="g2rd-faq__question"
              type="button"
              aria-expanded={openFirst && idx === 0 ? "true" : "false"}
              aria-controls={`g2rd-faq-answer-${idx}`}
            >
              <span className="g2rd-faq__question-text">{item.question}</span>
              <span className="g2rd-faq__icon" aria-hidden="true"></span>
            </button>
            <div
              className="g2rd-faq__answer"
              id={`g2rd-faq-answer-${idx}`}
              role="region"
            >
              <div className="g2rd-faq__answer-inner">
                <p>{item.answer}</p>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
