import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
// import "./countdown.css";
import "./countdown-frontend.js";
import Edit from "./edit";
import Save from "./save";

/**
 * Sauvegarde dépréciée v1 — l'ancienne version :
 * - concaténait "px" aux valeurs qui avaient déjà leur unité (→ "20pxpx", "2empx")
 * - n'avait pas l'attribut data-unit sur les items
 * - utilisait des chaînes source anglaises pour __()
 * Cette entrée permet à Gutenberg de reconnaître les anciens blocs enregistrés
 * et de les migrer automatiquement vers le format correct.
 */
const deprecatedSave = ({ attributes }) => {
  const {
    title,
    endDate,
    showYears,
    showMonths,
    showDays,
    showHours,
    showMinutes,
    showSeconds,
    valueSize,
    labelSize,
    itemSpacing,
    itemPadding,
    itemBackground,
    itemBorderRadius,
    timerStyle,
    animation,
    animationSpeed,
    layout,
  } = attributes;

  const blockProps = useBlockProps.save({
    className: "g2rd-countdown",
    style: {
      "--item-spacing": `${itemSpacing}px`,
      "--item-padding": `${itemPadding}px`,
      "--item-background": itemBackground,
      "--item-border-radius": `${itemBorderRadius}px`,
      "--value-size": `${valueSize}px`,
      "--label-size": `${labelSize}px`,
      "--animation-duration":
        animationSpeed === "slow"
          ? "2s"
          : animationSpeed === "fast"
          ? "0.5s"
          : "1s",
    },
  });

  // Ancienne version : pas d'attribut data-unit, labels en anglais
  const renderTimeUnit = (show, value, label) => {
    if (!show) return null;
    return (
      <div className={`countdown-item ${timerStyle} ${animation}`}>
        <div className="countdown-value">{value}</div>
        <div className="countdown-label">{label}</div>
      </div>
    );
  };

  return (
    <div {...blockProps} data-end-date={endDate}>
      {title && <h2 className="countdown-title">{title}</h2>}
      <div className={`countdown-container countdown-layout-${layout}`}>
        {renderTimeUnit(showYears, "00", __("Years", "g2rd"))}
        {renderTimeUnit(showMonths, "00", __("Months", "g2rd"))}
        {renderTimeUnit(showDays, "00", __("Days", "g2rd"))}
        {renderTimeUnit(showHours, "00", __("Hours", "g2rd"))}
        {renderTimeUnit(showMinutes, "00", __("Minutes", "g2rd"))}
        {renderTimeUnit(showSeconds, "00", __("Seconds", "g2rd"))}
      </div>
    </div>
  );
};

registerBlockType("g2rd/countdown", {
  edit: Edit,
  save: Save,
  deprecated: [
    {
      save: deprecatedSave,
    },
  ],
});
