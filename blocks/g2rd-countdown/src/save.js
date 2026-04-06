import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

export default function Save({ attributes }) {
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
      "--item-spacing": itemSpacing,
      "--item-padding": itemPadding,
      "--item-background": itemBackground,
      "--item-border-radius": itemBorderRadius,
      "--value-size": valueSize,
      "--label-size": labelSize,
      "--animation-duration":
        animationSpeed === "slow"
          ? "2s"
          : animationSpeed === "fast"
          ? "0.5s"
          : "1s",
    },
  });

  const renderTimeUnit = (show, value, label, unit) => {
    if (!show) return null;
    return (
      <div
        className={`countdown-item ${timerStyle} ${animation}`}
        data-unit={unit}
      >
        <div className="countdown-value">{value}</div>
        <div className="countdown-label">{label}</div>
      </div>
    );
  };

  return (
    <div {...blockProps} data-end-date={endDate}>
      {title && <h2 className="countdown-title">{title}</h2>}
      <div className={`countdown-container countdown-layout-${layout}`}>
        {renderTimeUnit(showYears, "00", __("Années", "g2rd"), "years")}
        {renderTimeUnit(showMonths, "00", __("Mois", "g2rd"), "months")}
        {renderTimeUnit(showDays, "00", __("Jours", "g2rd"), "days")}
        {renderTimeUnit(showHours, "00", __("Heures", "g2rd"), "hours")}
        {renderTimeUnit(showMinutes, "00", __("Minutes", "g2rd"), "minutes")}
        {renderTimeUnit(showSeconds, "00", __("Secondes", "g2rd"), "seconds")}
      </div>
    </div>
  );
}
