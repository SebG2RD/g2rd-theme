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
  SelectControl,
  RangeControl,
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from "@wordpress/components";
import { TypographySizePanel } from "../../shared/TypographySizePanel";
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
    valueFontSize,
    labelFontSize,
    itemSpacing,
    itemPadding,
    itemBackground,
    itemBorderRadius,
    timerStyle,
    animation,
    animationSpeed,
    layout,
  } = attributes;

  /* Parité éditeur/front : mêmes classes et mêmes variables CSS que `save()`,
     afin que countdown.css pilote seul le rendu dans les deux contextes. */
  const blockProps = useBlockProps({
    className: "g2rd-countdown",
    style: {
      "--item-spacing": itemSpacing,
      "--item-padding": itemPadding,
      "--item-background": itemBackground,
      "--item-border-radius": itemBorderRadius,
      ...(valueFontSize ? { "--value-size": valueFontSize } : {}),
      ...(labelFontSize ? { "--label-size": labelFontSize } : {}),
      "--animation-duration":
        animationSpeed === "slow"
          ? "2s"
          : animationSpeed === "fast"
          ? "0.5s"
          : "1s",
    },
  });
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

      let years = end.getFullYear() - now.getFullYear();
      let months = end.getMonth() - now.getMonth();
      if (months < 0) {
        years--;
        months += 12;
      }

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
      /* Aucun style inline : le décor vient de countdown.css, qui lit les
         variables posées sur le conteneur — exactement comme sur le front. */
      <div className={`countdown-item ${timerStyle} ${animation}`}>
        <div className="countdown-value">{value}</div>
        <div className="countdown-label">{label}</div>
      </div>
    );
  };

  return (
    <>
      {/* ── Onglet « Réglages » ─────────────────────────────────────────── */}
      <InspectorControls>
        {/* Contenu : titre, date de fin et unités affichées */}
        <PanelBody title={__("Contenu", "g2rd")} initialOpen={true}>
          <TextControl
            label={__("Titre", "g2rd")}
            value={title}
            placeholder={__("Titre du compte à rebours", "g2rd")}
            onChange={(value) => setAttributes({ title: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <p className="g2rd-control-label">{__("Date de fin", "g2rd")}</p>
          <DateTimePicker
            currentDate={endDate}
            onChange={(date) => setAttributes({ endDate: format("c", date) })}
            is12Hour={true}
          />
          <ToggleControl
            label={__("Afficher les années", "g2rd")}
            checked={showYears}
            onChange={() => setAttributes({ showYears: !showYears })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher les mois", "g2rd")}
            checked={showMonths}
            onChange={() => setAttributes({ showMonths: !showMonths })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher les jours", "g2rd")}
            checked={showDays}
            onChange={() => setAttributes({ showDays: !showDays })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher les heures", "g2rd")}
            checked={showHours}
            onChange={() => setAttributes({ showHours: !showHours })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher les minutes", "g2rd")}
            checked={showMinutes}
            onChange={() => setAttributes({ showMinutes: !showMinutes })}
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__("Afficher les secondes", "g2rd")}
            checked={showSeconds}
            onChange={() => setAttributes({ showSeconds: !showSeconds })}
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* Mise en page : disposition des items */}
        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          {/* Choix parmi 2 → ToggleGroupControl (même attribut et mêmes
              valeurs que l'ancien SelectControl) */}
          <ToggleGroupControl
            label={__("Disposition", "g2rd")}
            value={layout}
            onChange={(value) => setAttributes({ layout: value })}
            isBlock
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          >
            <ToggleGroupControlOption
              value="row"
              label={__("Ligne", "g2rd")}
            />
            <ToggleGroupControlOption
              value="column"
              label={__("Colonne", "g2rd")}
            />
          </ToggleGroupControl>
        </PanelBody>

        {/* Comportement : préréglage visuel du minuteur (spécifique au bloc) */}
        <PanelBody title={__("Comportement", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Style du minuteur", "g2rd")}
            value={timerStyle}
            options={[
              { label: __("Défaut", "g2rd"),      value: "default" },
              { label: __("Numérique", "g2rd"),   value: "digital" },
              { label: __("Néon", "g2rd"),         value: "neon" },
              { label: __("Rétro", "g2rd"),        value: "retro" },
              { label: __("Minimal", "g2rd"),      value: "minimal" },
              { label: __("Gras", "g2rd"),         value: "bold" },
            ]}
            onChange={(value) => setAttributes({ timerStyle: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>
      </InspectorControls>

      {/* ── Onglet « Styles » ───────────────────────────────────────────── */}
      <InspectorControls group="styles">
        {/* Arrière-plan : fond des items */}
        <PanelColorSettings
          title={__("Arrière-plan", "g2rd")}
          colorSettings={[
            {
              value: itemBackground,
              onChange: (value) => setAttributes({ itemBackground: value }),
              label: __("Fond des items", "g2rd"),
            },
          ]}
        />
      </InspectorControls>

      {/* Typographie (panneau partagé, rendu dans group="styles") */}
      <TypographySizePanel
        elements={[
          {
            label: __("Taille des valeurs", "g2rd"),
            value: valueFontSize,
            onChange: (value) => setAttributes({ valueFontSize: value || "" }),
          },
          {
            label: __("Taille des étiquettes", "g2rd"),
            value: labelFontSize,
            onChange: (value) => setAttributes({ labelFontSize: value || "" }),
          },
        ]}
      />

      <InspectorControls group="styles">
        {/* Dimensions : espacement et marge intérieure des items.
            Les attributs stockent une chaîne « Npx » : on conserve le
            RangeControl (nombre borné) pour garantir le même format. */}
        <PanelBody title={__("Dimensions", "g2rd")} initialOpen={false}>
          <RangeControl
            label={__("Espacement entre les items", "g2rd")}
            value={parseInt(itemSpacing)}
            onChange={(value) => setAttributes({ itemSpacing: `${value}px` })}
            min={0}
            max={50}
            step={1}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <RangeControl
            label={__("Marge intérieure des items", "g2rd")}
            value={parseInt(itemPadding)}
            onChange={(value) => setAttributes({ itemPadding: `${value}px` })}
            min={5}
            max={50}
            step={1}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* Bordure : arrondi des items */}
        <PanelBody title={__("Bordure", "g2rd")} initialOpen={false}>
          <RangeControl
            label={__("Arrondi des coins", "g2rd")}
            value={parseInt(itemBorderRadius)}
            onChange={(value) =>
              setAttributes({ itemBorderRadius: `${value}px` })
            }
            min={0}
            max={20}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>

        {/* Animation : effet et vitesse */}
        <PanelBody title={__("Animation", "g2rd")} initialOpen={false}>
          <SelectControl
            label={__("Animation", "g2rd")}
            value={animation}
            options={[
              { label: __("Aucune", "g2rd"),        value: "none" },
              { label: __("Pulsation", "g2rd"),     value: "pulse" },
              { label: __("Retournement", "g2rd"),  value: "flip" },
              { label: __("Fondu", "g2rd"),         value: "fade" },
              { label: __("Rebond", "g2rd"),        value: "bounce" },
            ]}
            onChange={(value) => setAttributes({ animation: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          {/* Choix parmi 3 → ToggleGroupControl (même attribut et mêmes
              valeurs que l'ancien SelectControl) */}
          <ToggleGroupControl
            label={__("Vitesse d'animation", "g2rd")}
            value={animationSpeed}
            onChange={(value) => setAttributes({ animationSpeed: value })}
            isBlock
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          >
            <ToggleGroupControlOption
              value="slow"
              label={__("Lent", "g2rd")}
            />
            <ToggleGroupControlOption
              value="normal"
              label={__("Normal", "g2rd")}
            />
            <ToggleGroupControlOption
              value="fast"
              label={__("Rapide", "g2rd")}
            />
          </ToggleGroupControl>
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>
        {title && <h2 className="countdown-title">{title}</h2>}
        <div className={`countdown-container countdown-layout-${layout}`}>
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
