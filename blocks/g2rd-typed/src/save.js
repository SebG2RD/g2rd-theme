import { useBlockProps } from "@wordpress/block-editor";

export default function Save({ attributes }) {
  const {
    typedStrings,
    textBefore,
    textAfter,
    typeSpeed,
    backSpeed,
    loop,
    startDelay,
    backDelay,
    fadeOut,
    fadeOutClass,
    fadeOutDelay,
    smartBackspace,
    shuffle,
    showCursor,
    cursorChar,
    autoInsertCss,
    attr,
    bindInputFocusEvents,
    contentType,
    alignment,
    fontSize,
    fontWeight,
    textColor,
    backgroundColor,
    padding,
    margin,
  } = attributes;

  const blockProps = useBlockProps.save({
    className: "g2rd-typed",
    style: {
      textAlign: alignment,
      fontSize: fontSize !== "inherit" ? fontSize : undefined,
      fontWeight: fontWeight !== "inherit" ? fontWeight : undefined,
      color: textColor !== "inherit" ? textColor : undefined,
      backgroundColor:
        backgroundColor !== "transparent" ? backgroundColor : undefined,
      padding: `${padding.top} ${padding.right} ${padding.bottom} ${padding.left}`,
      margin: `${margin.top} ${margin.right} ${margin.bottom} ${margin.left}`,
    },
    "data-typed-config": JSON.stringify({
      typeSpeed,
      backSpeed,
      loop,
      startDelay,
      backDelay,
      fadeOut,
      fadeOutClass,
      fadeOutDelay,
      smartBackspace,
      shuffle,
      showCursor,
      cursorChar,
      autoInsertCss,
      attr,
      bindInputFocusEvents,
      contentType,
    }),
  });

  return (
    <div {...blockProps}>
      {textBefore && <span className="typed-text-before">{textBefore}</span>}

      {typedStrings.length > 0 && (
        <div id="typed-strings" style={{ display: "none" }}>
          {typedStrings.map((string, index) => (
            <p key={index}>
              <strong>{string}</strong>
            </p>
          ))}
        </div>
      )}

      <span id="typed" className="typed-text-animated"></span>

      {textAfter && <span className="typed-text-after">{textAfter}</span>}
    </div>
  );
}
