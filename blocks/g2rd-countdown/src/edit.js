import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  PanelColorSettings,
} from "@wordpress/block-editor";
import {
  PanelBody,
  TextControl,
  DateTimePicker,
  ToggleControl,
  RangeControl,
  SelectControl,
} from "@wordpress/components";
import { useState, useEffect } from "@wordpress/element";
import { format } from "@wordpress/date";

export default function Edit({ attributes, setAttributes }) {
  const {
    endDate,
    title,
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

  const blockProps = useBlockProps();
  const [timeLeft, setTimeLeft] = useState({
    years: 0,
    months: 0,
    days: 0,
    hours: 0,
    minutes: 0,
    seconds: 0,
  });

  useEffect(() => {
    if (!endDate) return;

    const timer = setInterval(() => {
      const now = new Date();
      const end = new Date(endDate);
      const distance = end - now;

      if (distance < 0) {
        clearInterval(timer);
        setTimeLeft({ years: 0, months: 0, days: 0, hours: 0, minutes: 0, seconds: 0 });
        return;
      }

      // Calcul précis des années et mois
      let years = end.getFullYear() - now.getFullYear();
      let months = end.getMonth() - now.getMonth();
      if (months < 0) {
        years--;
        months += 12;
      }

      // Jours restants après soustraction des mois complets
      const dateAfterMonths = new Date(now);
      dateAfterMonths.setFullYear(dateAfterMonths.getFullYear() + years);
      dateAfterMonths.setMonth(dateAfterMonths.getMonth() + months);
      const remainingMs = end - dateAfterMonths;
      const days = Math.max(0, Math.floor(remainingMs / (1000 * 60 * 60 * 24)));

      setTimeLeft({
        years,
        months,
        days,
        hours: Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((distance % (1000 * 60)) / 1000),
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [endDate]);

  const renderTimeUnit = (label, value, show) => {
    if (!show) return null;
    return (
      <div
        className={`countdown-item ${timerStyle} ${animation}`}
        style={{
          minWidth: "80px",
          padding: itemPadding,
          margin: `0 ${itemSpacing}`,
          backgroundColor: itemBackground,
          borderRadius: itemBorderRadius,
          textAlign: "center",
          boxShadow: "0 2px 4px rgba(0,0,0,0.1)",
          transition: "all 0.3s ease",
        }}
      >
        <div className="countdown-value" style={{ fontSize: valueSize }}>
          {value}
        </div>
        <div className="countdown-label" style={{ fontSize: labelSize }}>
          {label}
        </div>
      </div>
    );
  };

  return (
    <>
      <InspectorControls>
        <PanelBody title={__("Timer Settings", "g2rd")}>
          <DateTimePicker
            currentDate={endDate}
            onChange={(date) => setAttributes({ endDate: format("c", date) })}
            is12Hour={true}
          />
          <TextControl
            label={__("Title", "g2rd")}
            value={title}
            placeholder="Test modifiable"
            onChange={(value) => setAttributes({ title: value })}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
        </PanelBody>

        <PanelBody title={__("Display Units", "g2rd")}>
          <ToggleControl
            label={__("Show Years", "g2rd")}
            checked={showYears}
            onChange={() => setAttributes({ showYears: !showYears })}
            __nextHasNoMarginBottom={true}
          />
          <ToggleControl
            label={__("Show Months", "g2rd")}
            checked={showMonths}
            onChange={() => setAttributes({ showMonths: !showMonths })}
            __nextHasNoMarginBottom={true}
          />
          <ToggleControl
            label={__("Show Days", "g2rd")}
            checked={showDays}
            onChange={() => setAttributes({ showDays: !showDays })}
            __nextHasNoMarginBottom={true}
          />
          <ToggleControl
            label={__("Show Hours", "g2rd")}
            checked={showHours}
            onChange={() => setAttributes({ showHours: !showHours })}
            __nextHasNoMarginBottom={true}
          />
          <ToggleControl
            label={__("Show Minutes", "g2rd")}
            checked={showMinutes}
            onChange={() => setAttributes({ showMinutes: !showMinutes })}
            __nextHasNoMarginBottom={true}
          />
          <ToggleControl
            label={__("Show Seconds", "g2rd")}
            checked={showSeconds}
            onChange={() => setAttributes({ showSeconds: !showSeconds })}
            __nextHasNoMarginBottom={true}
          />
        </PanelBody>

        <PanelColorSettings
          title={__("Couleur", "g2rd")}
          colorSettings={[
            {
              value: itemBackground,
              onChange: (value) => setAttributes({ itemBackground: value }),
              label: __("Fond des items", "g2rd"),
            },
          ]}
        />

        <PanelBody title={__("Style Settings", "g2rd")}>
          <SelectControl
            label={__("Layout", "g2rd")}
            value={layout}
            options={[
              { label: __("Row (Horizontal)", "g2rd"), value: "row" },
              { label: __("Column (Vertical)", "g2rd"), value: "column" },
            ]}
            onChange={(value) => setAttributes({ layout: value })}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
          <SelectControl
            label={__("Timer Style", "g2rd")}
            value={timerStyle}
            options={[
              { label: __("Default", "g2rd"), value: "default" },
              { label: __("Digital", "g2rd"), value: "digital" },
              { label: __("Neon", "g2rd"), value: "neon" },
              { label: __("Retro", "g2rd"), value: "retro" },
              { label: __("Minimal", "g2rd"), value: "minimal" },
              { label: __("Bold", "g2rd"), value: "bold" },
            ]}
            onChange={(value) => setAttributes({ timerStyle: value })}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
          <SelectControl
            label={__("Animation", "g2rd")}
            value={animation}
            options={[
              { label: __("None", "g2rd"), value: "none" },
              { label: __("Pulse", "g2rd"), value: "pulse" },
              { label: __("Flip", "g2rd"), value: "flip" },
              { label: __("Fade", "g2rd"), value: "fade" },
              { label: __("Bounce", "g2rd"), value: "bounce" },
            ]}
            onChange={(value) => setAttributes({ animation: value })}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
          <SelectControl
            label={__("Animation Speed", "g2rd")}
            value={animationSpeed}
            options={[
              { label: __("Slow", "g2rd"), value: "slow" },
              { label: __("Normal", "g2rd"), value: "normal" },
              { label: __("Fast", "g2rd"), value: "fast" },
            ]}
            onChange={(value) => setAttributes({ animationSpeed: value })}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
          <RangeControl
            label={__("Value Size", "g2rd")}
            value={parseInt(valueSize)}
            onChange={(value) => setAttributes({ valueSize: `${value}em` })}
            min={1}
            max={5}
            step={0.1}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
          <RangeControl
            label={__("Label Size", "g2rd")}
            value={parseInt(labelSize)}
            onChange={(value) => setAttributes({ labelSize: `${value}em` })}
            min={0.5}
            max={2}
            step={0.1}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
          <RangeControl
            label={__("Item Spacing", "g2rd")}
            value={parseInt(itemSpacing)}
            onChange={(value) => setAttributes({ itemSpacing: `${value}px` })}
            min={0}
            max={50}
            step={1}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
          <RangeControl
            label={__("Item Padding", "g2rd")}
            value={parseInt(itemPadding)}
            onChange={(value) => setAttributes({ itemPadding: `${value}px` })}
            min={5}
            max={50}
            step={1}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
          <RangeControl
            label={__("Border Radius", "g2rd")}
            value={parseInt(itemBorderRadius)}
            onChange={(value) =>
              setAttributes({ itemBorderRadius: `${value}px` })
            }
            min={0}
            max={20}
            __next40pxDefaultSize={true}
            __nextHasNoMarginBottom={true}
          />
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        {title && <h2 className="countdown-title">{title}</h2>}
        <div
          className={`countdown-container countdown-layout-${layout}`}
          style={{
            display: "flex",
            flexDirection: layout === "column" ? "column" : "row",
            justifyContent: "center",
            flexWrap: layout === "column" ? "nowrap" : "wrap",
            gap: itemSpacing,
          }}
        >
          {renderTimeUnit(__("Années", "g2rd"), timeLeft.years.toString().padStart(2, "0"), showYears)}
          {renderTimeUnit(__("Mois", "g2rd"), timeLeft.months.toString().padStart(2, "0"), showMonths)}
          {renderTimeUnit(__("Jours", "g2rd"), timeLeft.days.toString().padStart(2, "0"), showDays)}
          {renderTimeUnit(__("Heures", "g2rd"), timeLeft.hours.toString().padStart(2, "0"), showHours)}
          {renderTimeUnit(__("Minutes", "g2rd"), timeLeft.minutes.toString().padStart(2, "0"), showMinutes)}
          {renderTimeUnit(__("Secondes", "g2rd"), timeLeft.seconds.toString().padStart(2, "0"), showSeconds)}
        </div>
      </div>
    </>
  );
}
