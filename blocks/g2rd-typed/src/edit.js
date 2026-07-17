import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  RichText,
  BlockControls,
  AlignmentToolbar,
  PanelColorSettings,
} from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  RangeControl,
  ToggleControl,
  Button,
  TextareaControl,
  SelectControl,
} from "@wordpress/components";
import { useState } from "@wordpress/element";

export default function Edit({ attributes, setAttributes }) {
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
    showPauseButton,
  } = attributes;

  const blockProps = useBlockProps({
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
  });

  const [newString, setNewString] = useState("");

  const addString = () => {
    if (newString.trim()) {
      setAttributes({
        typedStrings: [...typedStrings, newString.trim()],
      });
      setNewString("");
    }
  };

  const removeString = (index) => {
    const newStrings = typedStrings.filter((_, i) => i !== index);
    setAttributes({ typedStrings: newStrings });
  };

  const updateString = (index, value) => {
    const newStrings = [...typedStrings];
    newStrings[index] = value;
    setAttributes({ typedStrings: newStrings });
  };

  return (
    <>
      <BlockControls>
        <AlignmentToolbar
          value={alignment}
          onChange={(value) => setAttributes({ alignment: value })}
        />
      </BlockControls>

      <InspectorControls>
        {/* Onglet « Réglages » › Contenu : textes animés + texte avant / après */}
        <PanelBody title={__("Contenu", "g2rd")} initialOpen={true}>
          <p className="g2rd-control-label">{__("Textes animés", "g2rd")}</p>
          <TextareaControl
            label={__("Ajouter un nouveau texte", "g2rd")}
            value={newString}
            onChange={setNewString}
            placeholder={__("Entrez un nouveau texte à animer...", "g2rd")}
            __nextHasNoMarginBottom
          />
          <Button
            variant="primary"
            onClick={addString}
            disabled={!newString.trim()}
            style={{ marginTop: "8px" }}
          >
            {__("Ajouter", "g2rd")}
          </Button>

          <div style={{ marginTop: "16px" }}>
            <strong>{__("Textes actuels :", "g2rd")}</strong>
            {typedStrings.map((string, index) => (
              <div
                key={index}
                style={{
                  display: "flex",
                  alignItems: "center",
                  marginTop: "8px",
                  padding: "8px",
                  border: "1px solid #ddd",
                  borderRadius: "4px",
                }}
              >
                <TextControl
                  value={string}
                  onChange={(value) => updateString(index, value)}
                  __next40pxDefaultSize
                  __nextHasNoMarginBottom
                  style={{ flex: 1, marginRight: "8px" }}
                />
                <Button
                  isDestructive
                  size="small"
                  onClick={() => removeString(index)}
                >
                  {__("Supprimer", "g2rd")}
                </Button>
              </div>
            ))}
          </div>

          <p className="g2rd-control-label">{__("Texte avant / après", "g2rd")}</p>
          <TextControl
            label={__("Texte avant l'animation", "g2rd")}
            value={textBefore}
            onChange={(value) => setAttributes({ textBefore: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            placeholder={__("Texte qui apparaît avant l'animation...", "g2rd")}
          />
          <TextControl
            label={__("Texte après l'animation", "g2rd")}
            value={textAfter}
            onChange={(value) => setAttributes({ textAfter: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            placeholder={__("Texte qui apparaît après l'animation...", "g2rd")}
          />
        </PanelBody>

        {/* Onglet « Réglages » › Comportement : animation, curseur et options avancées */}
        <PanelBody title={__("Comportement", "g2rd")} initialOpen={false}>
          <p className="g2rd-control-label">{__("Animation", "g2rd")}</p>
          <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Vitesse de frappe", "g2rd")}
            value={typeSpeed}
            onChange={(value) => setAttributes({ typeSpeed: value })}
            min={10}
            max={200}
            step={5}
          />
          <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Vitesse d'effacement", "g2rd")}
            value={backSpeed}
            onChange={(value) => setAttributes({ backSpeed: value })}
            min={10}
            max={200}
            step={5}
          />
          <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Délai de départ", "g2rd")}
            value={startDelay}
            onChange={(value) => setAttributes({ startDelay: value })}
            min={0}
            max={5000}
            step={100}
          />
          <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Délai avant effacement", "g2rd")}
            value={backDelay}
            onChange={(value) => setAttributes({ backDelay: value })}
            min={0}
            max={5000}
            step={100}
          />
          <ToggleControl
            label={__("Boucle infinie", "g2rd")}
            checked={loop}
            onChange={() => setAttributes({ loop: !loop })}
          />
          { loop && (
            <ToggleControl
              label={__("Afficher le bouton pause", "g2rd")}
              help={__("RGAA 13.2 — requis pour les animations en boucle infinie", "g2rd")}
              checked={showPauseButton === true}
              onChange={(v) => setAttributes({ showPauseButton: v })}
              __nextHasNoMarginBottom
            />
          ) }
          <ToggleControl
            label={__("Mélanger les textes", "g2rd")}
            checked={shuffle}
            onChange={() => setAttributes({ shuffle: !shuffle })}
          />

          <p className="g2rd-control-label">{__("Curseur", "g2rd")}</p>
          <ToggleControl
            label={__("Afficher le curseur", "g2rd")}
            checked={showCursor}
            onChange={() => setAttributes({ showCursor: !showCursor })}
          />
          <TextControl
            label={__("Caractère du curseur", "g2rd")}
            value={cursorChar}
            onChange={(value) => setAttributes({ cursorChar: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            disabled={!showCursor}
          />

          <p className="g2rd-control-label">{__("Options avancées", "g2rd")}</p>
          <ToggleControl
            label={__("Effacement intelligent", "g2rd")}
            checked={smartBackspace}
            onChange={() => setAttributes({ smartBackspace: !smartBackspace })}
          />
          <ToggleControl
            label={__("Fondu de sortie", "g2rd")}
            checked={fadeOut}
            onChange={() => setAttributes({ fadeOut: !fadeOut })}
          />
          <TextControl
            label={__("Classe de fondu", "g2rd")}
            value={fadeOutClass}
            onChange={(value) => setAttributes({ fadeOutClass: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            disabled={!fadeOut}
          />
          <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Délai de fondu", "g2rd")}
            value={fadeOutDelay}
            onChange={(value) => setAttributes({ fadeOutDelay: value })}
            min={0}
            max={5000}
            step={100}
            disabled={!fadeOut}
          />
          <ToggleControl
            label={__("CSS automatique", "g2rd")}
            checked={autoInsertCss}
            onChange={() => setAttributes({ autoInsertCss: !autoInsertCss })}
          />
          <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Type de contenu", "g2rd")}
            value={contentType}
            options={[
              { label: __("HTML", "g2rd"), value: "html" },
              { label: __("Texte", "g2rd"), value: "null" },
            ]}
            onChange={(value) => setAttributes({ contentType: value })}
          />
        </PanelBody>

      </InspectorControls>

      {/* Onglet « Styles » : couleurs personnalisées (distinctes du support natif). */}
      <InspectorControls group="styles">
        <PanelColorSettings
          title={__("Couleur", "g2rd")}
          initialOpen={false}
          colorSettings={[
            {
              value: textColor !== "inherit" ? textColor : undefined,
              onChange: (value) => setAttributes({ textColor: value || "inherit" }),
              label: __("Couleur du texte", "g2rd"),
            },
          ]}
        />
        <PanelColorSettings
          title={__("Arrière-plan", "g2rd")}
          initialOpen={false}
          colorSettings={[
            {
              value: backgroundColor !== "transparent" ? backgroundColor : undefined,
              onChange: (value) => setAttributes({ backgroundColor: value || "transparent" }),
              label: __("Couleur de fond", "g2rd"),
            },
          ]}
        />
      </InspectorControls>

      <div {...blockProps}>
        <div className="g2rd-typed-editor">
          {textBefore && (
            <span className="typed-text-before">{textBefore}</span>
          )}
          <span className="typed-text-animated">
            {typedStrings.length > 0
              ? typedStrings[0]
              : __("Aucun texte défini", "g2rd")}
          </span>
          {textAfter && <span className="typed-text-after">{textAfter}</span>}
          {showCursor && <span className="typed-cursor">{cursorChar}</span>}
        </div>

        {typedStrings.length === 0 && (
          <div
            style={{
              padding: "16px",
              backgroundColor: "#f0f0f0",
              borderRadius: "4px",
              marginTop: "8px",
              fontSize: "14px",
              color: "#666",
            }}
          >
            {__(
              "Aucun texte animé défini. Ajoutez des textes dans les paramètres.",
              "g2rd"
            )}
          </div>
        )}
      </div>
    </>
  );
}
