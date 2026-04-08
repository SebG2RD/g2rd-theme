import { registerBlockType } from "@wordpress/blocks";
// import "./countdown.css";
import "./countdown-frontend.js";
import Edit from "./edit";
import Save from "./save";

/**
 * Sauvegarde dépréciée v1 — l'ancienne version :
 * - concaténait "px" aux valeurs qui avaient déjà leur unité (→ "20pxpx", "2empx")
 * - n'avait pas l'attribut data-unit sur les items
 * - utilisait des chaînes anglaises sans traduction
 *
 * NB : on n'utilise PAS useBlockProps.save() ici — son comportement varie selon
 * la version de @wordpress/scripts et pourrait ajouter des attributs absents du
 * HTML originellement sérialisé, empêchant la correspondance de validation.
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

  // Reproduit exactement le style inline généré par l'ancienne save.js.
  const style = {
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
  };

  // Ancienne version : pas d'attribut data-unit, labels en anglais (non traduits).
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
    <div
      className="wp-block-g2rd-countdown g2rd-countdown"
      style={style}
      data-end-date={endDate}
    >
      {title && <h2 className="countdown-title">{title}</h2>}
      <div className={`countdown-container countdown-layout-${layout}`}>
        {renderTimeUnit(showYears, "00", "Years")}
        {renderTimeUnit(showMonths, "00", "Months")}
        {renderTimeUnit(showDays, "00", "Days")}
        {renderTimeUnit(showHours, "00", "Hours")}
        {renderTimeUnit(showMinutes, "00", "Minutes")}
        {renderTimeUnit(showSeconds, "00", "Seconds")}
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
