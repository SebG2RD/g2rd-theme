import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls, PanelColorSettings } from "@wordpress/block-editor";
import {
  PanelBody,
  SelectControl,
  RangeControl,
  ToggleControl,
  TextControl,
  TextareaControl,
  Button,
} from "@wordpress/components";
import { useState, useCallback } from "@wordpress/element";

const ICON_MAP = {
  "plus-minus": { open: "−", closed: "+" },
  chevron: { open: "▲", closed: "▼" },
  arrow: { open: "▲", closed: "▶" },
};

const ICON_OPTIONS = [
  { label: "Plus / Moins", value: "plus-minus" },
  { label: "Chevron",      value: "chevron" },
  { label: "Flèche",       value: "arrow" },
];

export default function Edit({ attributes, setAttributes }) {
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
    optimizeForGEO,
    showHeader,
    headerText,
    headerIcon,
  } = attributes;

  const [openIndex, setOpenIndex] = useState(openFirst ? 0 : -1);

  const blockProps = useBlockProps({ className: "g2rd-faq" });

  const addItem = useCallback(() => {
    setAttributes({
      items: [...items, { question: __("Nouvelle question ?", "g2rd"), answer: __("Votre réponse ici.", "g2rd") }],
    });
    setOpenIndex(items.length);
  }, [items, setAttributes]);

  const removeItem = useCallback((idx) => {
    setAttributes({ items: items.filter((_, i) => i !== idx) });
    setOpenIndex(-1);
  }, [items, setAttributes]);

  const updateItem = useCallback((idx, field, value) => {
    const updated = items.map((item, i) =>
      i === idx ? { ...item, [field]: value } : item
    );
    setAttributes({ items: updated });
  }, [items, setAttributes]);

  const moveUp = useCallback((idx) => {
    if (idx === 0) return;
    const updated = [...items];
    [updated[idx - 1], updated[idx]] = [updated[idx], updated[idx - 1]];
    setAttributes({ items: updated });
    setOpenIndex(idx - 1);
  }, [items, setAttributes]);

  const moveDown = useCallback((idx) => {
    if (idx === items.length - 1) return;
    const updated = [...items];
    [updated[idx], updated[idx + 1]] = [updated[idx + 1], updated[idx]];
    setAttributes({ items: updated });
    setOpenIndex(idx + 1);
  }, [items, setAttributes]);

  const icons = ICON_MAP[iconType] || ICON_MAP["plus-minus"];

  return (
    <>
      <InspectorControls>
        <PanelBody title={__("Optimisation SEO / GEO", "g2rd")} initialOpen={false}>
          <ToggleControl
            label={__("Activer le mode GEO", "g2rd")}
            help={
              optimizeForGEO
                ? __("Schema FAQPage JSON-LD actif — questions indexables par les IA.", "g2rd")
                : __("Génère un schema.org FAQPage + JSON-LD pour les moteurs et les IA.", "g2rd")
            }
            checked={!!optimizeForGEO}
            onChange={(v) => setAttributes({ optimizeForGEO: v })}
            __nextHasNoMarginBottom
          />
          {optimizeForGEO && (
            <p style={{ marginTop: "8px", fontSize: "12px", color: "#3c434a" }}>
              {"✓ Microdata schema.org + JSON-LD FAQPage générés côté serveur."}
            </p>
          )}
        </PanelBody>

        <PanelBody title={__("En-tête", "g2rd")} initialOpen={false}>
          <ToggleControl
            label={__("Afficher un en-tête", "g2rd")}
            help={showHeader
              ? __("Un titre est affiché au-dessus de la FAQ.", "g2rd")
              : __("Optionnel : ajoute un titre avec icône au-dessus de la FAQ.", "g2rd")}
            checked={!!showHeader}
            onChange={(v) => setAttributes({ showHeader: v })}
            __nextHasNoMarginBottom
          />
          {showHeader && (
            <>
              <TextControl
                label={__("Icône", "g2rd")}
                value={headerIcon}
                onChange={(v) => setAttributes({ headerIcon: v })}
                help={__("Emoji ou caractère (ex. ❓ 💬 🙋)", "g2rd")}
                __nextHasNoMarginBottom
              />
              <TextControl
                label={__("Texte de l'en-tête", "g2rd")}
                value={headerText}
                onChange={(v) => setAttributes({ headerText: v })}
                __nextHasNoMarginBottom
              />
            </>
          )}
        </PanelBody>

        <PanelBody title={__("Comportement", "g2rd")} initialOpen={true}>
          <ToggleControl
            label={__("Ouvrir le premier item par défaut", "g2rd")}
            checked={openFirst}
            onChange={(v) => setAttributes({ openFirst: v })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Autoriser plusieurs ouverts simultanément", "g2rd")}
            checked={allowMultiple}
            onChange={(v) => setAttributes({ allowMultiple: v })}
            __nextHasNoMarginBottom
          />
          <SelectControl
            label={__("Type d'icône", "g2rd")}
            value={iconType}
            options={ICON_OPTIONS}
            onChange={(v) => setAttributes({ iconType: v })}
          />
          <RangeControl
            label={__("Rayon des coins (px)", "g2rd")}
            value={borderRadius}
            onChange={(v) => setAttributes({ borderRadius: v })}
            min={0}
            max={24}
          />
        </PanelBody>

        <PanelColorSettings
          title={__("Couleurs", "g2rd")}
          colorSettings={[
            { value: questionColor, onChange: (v) => setAttributes({ questionColor: v || "" }), label: __("Couleur des questions", "g2rd") },
            { value: answerColor, onChange: (v) => setAttributes({ answerColor: v || "" }), label: __("Couleur des réponses", "g2rd") },
            { value: iconColor, onChange: (v) => setAttributes({ iconColor: v || "" }), label: __("Couleur des icônes", "g2rd") },
            { value: backgroundColor, onChange: (v) => setAttributes({ backgroundColor: v || "" }), label: __("Couleur de fond", "g2rd") },
            { value: borderColor, onChange: (v) => setAttributes({ borderColor: v || "" }), label: __("Couleur des bordures", "g2rd") },
            { value: separatorColor, onChange: (v) => setAttributes({ separatorColor: v || "" }), label: __("Couleur des séparateurs", "g2rd") },
          ]}
        />
      </InspectorControls>

      <div {...blockProps}>
        {showHeader && (
          <div className="g2rd-faq__header">
            {headerIcon && (
              <span className="g2rd-faq__header-icon" aria-hidden="true">{headerIcon}</span>
            )}
            <span className="g2rd-faq__header-title">{headerText}</span>
            {optimizeForGEO && <span className="g2rd-faq__badge">schema.org</span>}
          </div>
        )}
        <div
          className="g2rd-faq__list"
          style={{
            "--g2rd-faq-question-color": questionColor || "var(--wp--preset--color--primary,#2f425d)",
            "--g2rd-faq-answer-color": answerColor || "inherit",
            "--g2rd-faq-icon-color": iconColor || "var(--wp--preset--color--primary,#2f425d)",
            "--g2rd-faq-bg": backgroundColor || "transparent",
            "--g2rd-faq-border": borderColor || "var(--wp--preset--color--primary,#2f425d)",
            "--g2rd-faq-separator": separatorColor || "#e5e7eb",
            "--g2rd-faq-radius": `${borderRadius}px`,
          }}
        >
          {items.map((item, idx) => {
            const isOpen = openIndex === idx;
            return (
              <div
                key={idx}
                className={`g2rd-faq__item${isOpen ? " is-open" : ""}`}
              >
                {/* Entête de l'item */}
                <div
                  className="g2rd-faq__question"
                  onClick={() => setOpenIndex(isOpen ? -1 : idx)}
                  style={{ cursor: "pointer" }}
                >
                  <TextControl
                    value={item.question}
                    onChange={(v) => updateItem(idx, "question", v)}
                    onClick={(e) => e.stopPropagation()}
                    className="g2rd-faq__question-input"
                    __nextHasNoMarginBottom
                  />
                  <span className="g2rd-faq__icon">
                    {isOpen ? icons.open : icons.closed}
                  </span>
                </div>

                {/* Corps de la réponse */}
                {isOpen && (
                  <div className="g2rd-faq__answer-editor">
                    <TextareaControl
                      label={__("Réponse", "g2rd")}
                      value={item.answer}
                      onChange={(v) => updateItem(idx, "answer", v)}
                      rows={4}
                      __nextHasNoMarginBottom
                    />
                    <div className="g2rd-faq__item-actions">
                      <Button
                        variant="tertiary"
                        icon="arrow-up"
                        disabled={idx === 0}
                        onClick={() => moveUp(idx)}
                        label={__("Monter", "g2rd")}
                        isSmall
                      />
                      <Button
                        variant="tertiary"
                        icon="arrow-down"
                        disabled={idx === items.length - 1}
                        onClick={() => moveDown(idx)}
                        label={__("Descendre", "g2rd")}
                        isSmall
                      />
                      <Button
                        variant="tertiary"
                        icon="trash"
                        isDestructive
                        onClick={() => removeItem(idx)}
                        label={__("Supprimer", "g2rd")}
                        isSmall
                      />
                    </div>
                  </div>
                )}
              </div>
            );
          })}

          <Button
            variant="secondary"
            onClick={addItem}
            icon="plus"
            style={{ marginTop: "8px" }}
          >
            {__("Ajouter une question", "g2rd")}
          </Button>
        </div>
      </div>
    </>
  );
}
