/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  PanelColorSettings,
  RichText,
  MediaUpload,
  MediaUploadCheck,
} from "@wordpress/block-editor";
import { TypographySizePanel } from "../../shared/TypographySizePanel";
import {
  PanelBody,
  RangeControl,
  TextControl,
  ToggleControl,
  Button,
  DropdownMenu,
  MenuGroup,
  MenuItem,
  __experimentalNumberControl as NumberControl,
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from "@wordpress/components";

const iconCategories = {
  "Informations": [
    { label: "Star Filled", value: "star-filled" },
    { label: "Star Empty", value: "star-empty" },
    { label: "Info", value: "info" },
    { label: "Warning", value: "warning" },
    { label: "Succès", value: "yes" },
    { label: "Erreur", value: "no" },
    { label: "Pouce haut", value: "thumbs-up" },
    { label: "Pouce bas", value: "thumbs-down" },
  ],
  "Statistiques": [
    { label: "Graphique barres", value: "chart-bar" },
    { label: "Graphique camembert", value: "chart-pie" },
    { label: "Graphique aire", value: "chart-area" },
    { label: "Graphique ligne", value: "chart-line" },
    { label: "Analytics", value: "analytics" },
    { label: "Performance", value: "performance" },
    { label: "Tendance hausse", value: "trending-up" },
    { label: "Tendance baisse", value: "trending-down" },
  ],
  "Business": [
    { label: "Argent", value: "money" },
    { label: "Argent alt", value: "money-alt" },
    { label: "Panier", value: "cart" },
    { label: "Produits", value: "products" },
    { label: "Homme d'affaires", value: "businessman" },
    { label: "Bâtiment", value: "building" },
  ],
  "Communication": [
    { label: "Email", value: "email" },
    { label: "Téléphone", value: "phone" },
    { label: "Smartphone", value: "smartphone" },
    { label: "Mégaphone", value: "megaphone" },
  ],
  "Récompenses": [
    { label: "Prix", value: "awards" },
    { label: "Trophée", value: "trophy" },
    { label: "Médaille", value: "medal" },
    { label: "Ruban", value: "ribbon" },
  ],
  "Technologie": [
    { label: "Bureau", value: "desktop" },
    { label: "Laptop", value: "laptop" },
    { label: "Tablette", value: "tablet" },
    { label: "Cloud", value: "cloud" },
    { label: "Base de données", value: "database" },
  ],
  "Social": [
    { label: "Groupes", value: "groups" },
    { label: "Utilisateurs", value: "admin-users" },
    { label: "Activité", value: "buddicons-activity" },
    { label: "Cœur", value: "heart" },
  ],
  "Temps & Calendrier": [
    { label: "Horloge", value: "clock" },
    { label: "Calendrier", value: "calendar-alt" },
    { label: "Sablier", value: "hourglass" },
  ],
  "Localisation": [
    { label: "Localisation", value: "location" },
    { label: "Localisation alt", value: "location-alt" },
    { label: "Boutique", value: "store" },
    { label: "Domicile", value: "admin-home" },
  ],
};

export default function Edit({ attributes, setAttributes }) {
  const {
    layout,
    alignment,
    startingNumber,
    endingNumber,
    decimalPlaces,
    numberPrefix,
    numberSuffix,
    animationDuration,
    thousands,
    enableIcon,
    mediaType,
    iconName,
    imageUrl,
    imageAlt,
    iconPosition,
    numberColor,
    iconColor,
    backgroundColor,
    title,
    titleColor,
    prefixRightMargin,
    suffixLeftMargin,
    margin,
    numberFontSize,
    titleFontSize,
  } = attributes;

  const iconSize = attributes.iconSize || 48;
  const imageSize = attributes.imageSize || 64;

  const blockProps = useBlockProps({
    style: {
      textAlign: alignment,
      backgroundColor: backgroundColor || undefined,
      margin: `${margin.top} ${margin.right} ${margin.bottom} ${margin.left}`,
    },
    className: `g2rd-counter layout-${layout} icon-${iconPosition}`,
  });

  const renderIcon = () => {
    if (!enableIcon) return null;

    if (mediaType === "image" && imageUrl) {
      return (
        <img
          src={imageUrl}
          alt={imageAlt || title}
          className="counter-image"
          style={{
            maxWidth: `${imageSize}px`,
            maxHeight: `${imageSize}px`,
            height: "auto",
          }}
        />
      );
    }

    if (mediaType === "icon" && iconName) {
      return (
        <span
          className={`dashicons dashicons-${iconName} counter-icon`}
          style={{
            color: iconColor,
            fontSize: `${iconSize}px`,
            width: `${iconSize}px`,
            height: `${iconSize}px`,
          }}
        />
      );
    }

    return null;
  };

  const renderNumber = () => {
    let formattedNumber = endingNumber.toFixed(decimalPlaces);

    if (thousands === "comma") {
      formattedNumber = formattedNumber.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    } else if (thousands === "space") {
      formattedNumber = formattedNumber.replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    return (
      <div className="counter-number-wrapper">
        {numberPrefix && (
          <span
            className="counter-prefix"
            style={{
              marginRight: `${prefixRightMargin}px`,
              color: numberColor,
              fontSize: numberFontSize || undefined,
            }}
          >
            {numberPrefix}
          </span>
        )}
        <span className="counter-number" style={{ color: numberColor, fontSize: numberFontSize || undefined }}>
          {formattedNumber}
        </span>
        {numberSuffix && (
          <span
            className="counter-suffix"
            style={{
              marginLeft: `${suffixLeftMargin}px`,
              color: numberColor,
              fontSize: numberFontSize || undefined,
            }}
          >
            {numberSuffix}
          </span>
        )}
      </div>
    );
  };

  const renderContent = () => {
    const icon = renderIcon();
    const number = renderNumber();
    const titleElement = (
      <RichText
        tagName="h3"
        value={title}
        onChange={(value) => setAttributes({ title: value })}
        placeholder={__("Saisir un titre...", "g2rd")}
        className="counter-title"
        style={{ color: titleColor, fontSize: titleFontSize || undefined }}
      />
    );

    if (layout === "circle" || layout === "bar") {
      return (
        <div className="counter-content">
          {icon && iconPosition === "top" && (
            <div className="counter-icon-wrapper">{icon}</div>
          )}
          <div className="counter-display">
            {layout === "circle" && (
              <div className="counter-circle">
                <svg width="120" height="120" className="counter-svg">
                  <circle
                    cx="60"
                    cy="60"
                    r="50"
                    fill="none"
                    stroke="#e6e6e6"
                    strokeWidth="8"
                  />
                  <circle
                    cx="60"
                    cy="60"
                    r="50"
                    fill="none"
                    stroke={numberColor}
                    strokeWidth="8"
                    strokeDasharray={`${(endingNumber / 100) * 314} 314`}
                    strokeLinecap="round"
                    transform="rotate(-90 60 60)"
                  />
                </svg>
                <div className="counter-circle-content">{number}</div>
              </div>
            )}
            {layout === "bar" && (
              <div className="counter-bar">
                <div
                  className="counter-bar-fill"
                  style={{
                    width: `${endingNumber}%`,
                    backgroundColor: numberColor,
                  }}
                >
                  <div className="counter-bar-content">{number}</div>
                </div>
              </div>
            )}
          </div>
          {titleElement}
          {icon && iconPosition === "bottom" && (
            <div className="counter-icon-wrapper">{icon}</div>
          )}
        </div>
      );
    }

    switch (iconPosition) {
      case "top":
        return (
          <div className="counter-content">
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
            {number}
            {titleElement}
          </div>
        );
      case "bottom":
        return (
          <div className="counter-content">
            {number}
            {titleElement}
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
          </div>
        );
      case "left":
        return (
          <div className="counter-content counter-horizontal">
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
            <div className="counter-text-wrapper">
              {number}
              {titleElement}
            </div>
          </div>
        );
      case "right":
        return (
          <div className="counter-content counter-horizontal">
            <div className="counter-text-wrapper">
              {number}
              {titleElement}
            </div>
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
          </div>
        );
      default:
        return (
          <div className="counter-content">
            {icon && <div className="counter-icon-wrapper">{icon}</div>}
            {number}
            {titleElement}
          </div>
        );
    }
  };

  return (
    <>
      {/* ── Onglet « Réglages » ─────────────────────────────────────────── */}
      <InspectorControls>
        {/* Contenu : valeurs, formatage et média affiché */}
        <PanelBody title={__("Contenu", "g2rd")} initialOpen={true}>
          <NumberControl
            label={__("Nombre de départ", "g2rd")}
            value={startingNumber}
            onChange={(value) =>
              setAttributes({ startingNumber: parseFloat(value) || 0 })
            }
          />

          <NumberControl
            label={__("Nombre d'arrivée", "g2rd")}
            value={endingNumber}
            onChange={(value) =>
              setAttributes({ endingNumber: parseFloat(value) || 0 })
            }
          />

          <RangeControl
            label={__("Décimales", "g2rd")}
            value={decimalPlaces}
            onChange={(value) => setAttributes({ decimalPlaces: value })}
            min={0}
            max={5}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          <TextControl
            label={__("Préfixe du nombre", "g2rd")}
            value={numberPrefix}
            onChange={(value) => setAttributes({ numberPrefix: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          <TextControl
            label={__("Suffixe du nombre", "g2rd")}
            value={numberSuffix}
            onChange={(value) => setAttributes({ numberSuffix: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          {/* Choix parmi 3 → ToggleGroupControl (même attribut et mêmes
              valeurs que l'ancien SelectControl) */}
          <ToggleGroupControl
            label={__("Séparateur de milliers", "g2rd")}
            value={thousands}
            onChange={(value) => setAttributes({ thousands: value })}
            isBlock
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          >
            <ToggleGroupControlOption
              value="comma"
              label={__("Virgule", "g2rd")}
            />
            <ToggleGroupControlOption
              value="space"
              label={__("Espace", "g2rd")}
            />
            <ToggleGroupControlOption
              value="none"
              label={__("Aucun", "g2rd")}
            />
          </ToggleGroupControl>

          <ToggleControl
            label={__("Activer icône/image", "g2rd")}
            checked={enableIcon}
            onChange={(value) => setAttributes({ enableIcon: value })}
            __nextHasNoMarginBottom
          />

          {enableIcon && (
            <>
              {/* Choix parmi 2 → ToggleGroupControl */}
              <ToggleGroupControl
                label={__("Type de média", "g2rd")}
                value={mediaType}
                onChange={(value) => setAttributes({ mediaType: value })}
                isBlock
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              >
                <ToggleGroupControlOption
                  value="icon"
                  label={__("Icône", "g2rd")}
                />
                <ToggleGroupControlOption
                  value="image"
                  label={__("Image", "g2rd")}
                />
              </ToggleGroupControl>

              {/* Sélecteur d'icône : DropdownMenu conservé (plus de 40 icônes
                  avec aperçu visuel par catégorie — un SelectControl perdrait
                  l'aperçu des dashicons) */}
              {mediaType === "icon" && (
                <DropdownMenu
                  icon={
                    iconName ? (
                      <span className={`dashicons dashicons-${iconName}`}></span>
                    ) : (
                      "admin-customizer"
                    )
                  }
                  label={__("Icône", "g2rd")}
                  toggleProps={{ variant: "secondary" }}
                >
                  {({ onClose }) => (
                    <div style={{ maxHeight: "400px", overflowY: "auto" }}>
                      {Object.entries(iconCategories).map(
                        ([category, icons]) => (
                          <MenuGroup key={category} label={category}>
                            {icons.map((iconData) => (
                              <MenuItem
                                key={iconData.value}
                                icon={
                                  <span
                                    className={`dashicons dashicons-${iconData.value}`}
                                  ></span>
                                }
                                isSelected={iconName === iconData.value}
                                onClick={() => {
                                  setAttributes({ iconName: iconData.value });
                                  onClose();
                                }}
                              >
                                {iconData.label}
                              </MenuItem>
                            ))}
                          </MenuGroup>
                        )
                      )}
                    </div>
                  )}
                </DropdownMenu>
              )}

              {mediaType === "image" && (
                <MediaUploadCheck>
                  <MediaUpload
                    onSelect={(media) =>
                      setAttributes({
                        imageUrl: media.url,
                        imageAlt: media.alt,
                      })
                    }
                    allowedTypes={["image"]}
                    value={imageUrl}
                    render={({ open }) => (
                      <Button onClick={open} variant="secondary">
                        {imageUrl
                          ? __("Changer l'image", "g2rd")
                          : __("Sélectionner une image", "g2rd")}
                      </Button>
                    )}
                  />
                </MediaUploadCheck>
              )}
            </>
          )}
        </PanelBody>

        {/* Mise en page : disposition, alignement, position du média */}
        <PanelBody title={__("Mise en page", "g2rd")} initialOpen={false}>
          {/* Choix parmi 3 → ToggleGroupControl */}
          <ToggleGroupControl
            label={__("Disposition", "g2rd")}
            value={layout}
            onChange={(value) => setAttributes({ layout: value })}
            isBlock
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          >
            <ToggleGroupControlOption
              value="number"
              label={__("Chiffre", "g2rd")}
            />
            <ToggleGroupControlOption
              value="circle"
              label={__("Cercle", "g2rd")}
            />
            <ToggleGroupControlOption
              value="bar"
              label={__("Barre", "g2rd")}
            />
          </ToggleGroupControl>

          {/* Choix parmi 3 → ToggleGroupControl */}
          <ToggleGroupControl
            label={__("Alignement", "g2rd")}
            value={alignment}
            onChange={(value) => setAttributes({ alignment: value })}
            isBlock
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          >
            <ToggleGroupControlOption
              value="left"
              label={__("Gauche", "g2rd")}
            />
            <ToggleGroupControlOption
              value="center"
              label={__("Centre", "g2rd")}
            />
            <ToggleGroupControlOption
              value="right"
              label={__("Droite", "g2rd")}
            />
          </ToggleGroupControl>

          {/* Choix parmi 4 → ToggleGroupControl */}
          {enableIcon && (
            <ToggleGroupControl
              label={__("Position de l'icône/image", "g2rd")}
              value={iconPosition}
              onChange={(value) => setAttributes({ iconPosition: value })}
              isBlock
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            >
              <ToggleGroupControlOption
                value="top"
                label={__("Haut", "g2rd")}
              />
              <ToggleGroupControlOption
                value="bottom"
                label={__("Bas", "g2rd")}
              />
              <ToggleGroupControlOption
                value="left"
                label={__("Gauche", "g2rd")}
              />
              <ToggleGroupControlOption
                value="right"
                label={__("Droite", "g2rd")}
              />
            </ToggleGroupControl>
          )}
        </PanelBody>
      </InspectorControls>

      {/* ── Onglet « Styles » ───────────────────────────────────────────── */}
      <InspectorControls group="styles">
        {/* Couleur : nombre, titre, icône */}
        <PanelColorSettings
          title={__("Couleur", "g2rd")}
          colorSettings={[
            {
              value: numberColor,
              onChange: (value) => setAttributes({ numberColor: value }),
              label: __("Couleur du nombre", "g2rd"),
            },
            {
              value: titleColor,
              onChange: (value) => setAttributes({ titleColor: value }),
              label: __("Couleur du titre", "g2rd"),
            },
            ...(enableIcon && mediaType === "icon"
              ? [
                  {
                    value: iconColor,
                    onChange: (value) => setAttributes({ iconColor: value }),
                    label: __("Couleur de l'icône", "g2rd"),
                  },
                ]
              : []),
          ]}
        />
        {/* Arrière-plan : fond du bloc */}
        <PanelColorSettings
          title={__("Arrière-plan", "g2rd")}
          colorSettings={[
            {
              value: backgroundColor,
              onChange: (value) => setAttributes({ backgroundColor: value }),
              label: __("Couleur de fond", "g2rd"),
            },
          ]}
        />
      </InspectorControls>

      {/* Typographie (panneau partagé, rendu dans group="styles") */}
      <TypographySizePanel
        elements={ [
          {
            label: __( 'Chiffre', 'g2rd' ),
            value: numberFontSize,
            onChange: ( v ) => setAttributes( { numberFontSize: v } ),
          },
          {
            label: __( 'Titre', 'g2rd' ),
            value: titleFontSize,
            onChange: ( v ) => setAttributes( { titleFontSize: v } ),
          },
        ] }
      />

      <InspectorControls group="styles">
        {/* Dimensions : marges du préfixe/suffixe et taille du média */}
        <PanelBody title={__("Dimensions", "g2rd")} initialOpen={false}>
          <RangeControl
            label={__("Marge droite du préfixe", "g2rd")}
            value={prefixRightMargin}
            onChange={(value) => setAttributes({ prefixRightMargin: value })}
            min={0}
            max={50}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          <RangeControl
            label={__("Marge gauche du suffixe", "g2rd")}
            value={suffixLeftMargin}
            onChange={(value) => setAttributes({ suffixLeftMargin: value })}
            min={0}
            max={50}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          {enableIcon && mediaType === "icon" && (
            <RangeControl
              label={__("Taille de l'icône (px)", "g2rd")}
              value={iconSize}
              onChange={(value) => setAttributes({ iconSize: value })}
              min={16}
              max={128}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          )}
          {enableIcon && mediaType === "image" && (
            <RangeControl
              label={__("Taille de l'image (px)", "g2rd")}
              value={imageSize}
              onChange={(value) => setAttributes({ imageSize: value })}
              min={16}
              max={256}
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          )}
        </PanelBody>

        {/* Animation : durée du comptage */}
        <PanelBody title={__("Animation", "g2rd")} initialOpen={false}>
          {/* Nombre borné → RangeControl (mêmes bornes que l'ancien
              NumberControl, même fallback à 2000) */}
          <RangeControl
            label={__("Durée de l'animation (ms)", "g2rd")}
            value={animationDuration}
            onChange={(value) =>
              setAttributes({ animationDuration: parseFloat(value) || 2000 })
            }
            min={500}
            max={10000}
            step={100}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>
      </InspectorControls>

      <div {...blockProps}>{renderContent()}</div>
    </>
  );
}
