import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import "./countdown.css";
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

// v2 : valueSize/labelSize (string avec unité, ex. "2em") → valueFontSize/labelFontSize
// useBlockProps.save() présent, labels en français, data-unit sur les items
const deprecatedSaveV2 = ( { attributes } ) => {
  const {
    title, endDate,
    showYears, showMonths, showDays, showHours, showMinutes, showSeconds,
    valueSize, labelSize,
    itemSpacing, itemPadding, itemBackground, itemBorderRadius,
    timerStyle, animation, animationSpeed, layout,
  } = attributes;

  const blockProps = useBlockProps.save( {
    className: "g2rd-countdown",
    style: {
      "--item-spacing":      itemSpacing,
      "--item-padding":      itemPadding,
      "--item-background":   itemBackground,
      "--item-border-radius":itemBorderRadius,
      "--value-size":        valueSize,
      "--label-size":        labelSize,
      "--animation-duration": animationSpeed === "slow" ? "2s" : animationSpeed === "fast" ? "0.5s" : "1s",
    },
  } );

  const renderTimeUnit = ( show, value, label, unit ) => {
    if ( ! show ) return null;
    return (
      <div className={ `countdown-item ${ timerStyle } ${ animation }` } data-unit={ unit }>
        <div className="countdown-value">{ value }</div>
        <div className="countdown-label">{ label }</div>
      </div>
    );
  };

  return (
    <div { ...blockProps } data-end-date={ endDate }>
      { title && <h2 className="countdown-title">{ title }</h2> }
      <div className={ `countdown-container countdown-layout-${ layout }` }>
        { renderTimeUnit( showYears,   "00", __( "Années",   "g2rd" ), "years"   ) }
        { renderTimeUnit( showMonths,  "00", __( "Mois",     "g2rd" ), "months"  ) }
        { renderTimeUnit( showDays,    "00", __( "Jours",    "g2rd" ), "days"    ) }
        { renderTimeUnit( showHours,   "00", __( "Heures",   "g2rd" ), "hours"   ) }
        { renderTimeUnit( showMinutes, "00", __( "Minutes",  "g2rd" ), "minutes" ) }
        { renderTimeUnit( showSeconds, "00", __( "Secondes", "g2rd" ), "seconds" ) }
      </div>
    </div>
  );
};

registerBlockType("g2rd/countdown", {
  edit: Edit,
  save: Save,
  deprecated: [
    {
      attributes: {
        endDate:         { type: "string",  default: "" },
        title:           { type: "string",  default: "Countdown Timer" },
        showYears:       { type: "boolean", default: false },
        showMonths:      { type: "boolean", default: false },
        showDays:        { type: "boolean", default: true },
        showHours:       { type: "boolean", default: true },
        showMinutes:     { type: "boolean", default: true },
        showSeconds:     { type: "boolean", default: true },
        valueSize:       { type: "string",  default: "2em" },
        labelSize:       { type: "string",  default: "0.9em" },
        itemSpacing:     { type: "string",  default: "20px" },
        itemPadding:     { type: "string",  default: "15px" },
        itemBackground:  { type: "string",  default: "#f5f5f5" },
        itemBorderRadius:{ type: "string",  default: "8px" },
        timerStyle:      { type: "string",  default: "default" },
        animation:       { type: "string",  default: "none" },
        animationSpeed:  { type: "string",  default: "normal" },
        layout:          { type: "string",  default: "row" },
      },
      migrate( attributes ) {
        const { valueSize, labelSize, ...rest } = attributes;
        return { ...rest, valueFontSize: valueSize || "", labelFontSize: labelSize || "" };
      },
      save: deprecatedSaveV2,
    },
    {
      save: deprecatedSave,
    },
  ],
});
