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
import {
  PanelBody,
  SelectControl,
  RangeControl,
  TextControl,
  ToggleControl,
  Button,
  DropdownMenu,
  MenuGroup,
  MenuItem,
  __experimentalNumberControl as NumberControl,
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
            }}
          >
            {numberPrefix}
          </span>
        )}
        <span className="counter-number" style={{ color: numberColor }}>
          {formattedNumber}
        </span>
        {numberSuffix && (
          <span
            className="counter-suffix"
            style={{
              marginLeft: `${suffixLeftMargin}px`,
              color: numberColor,
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
        style={{ color: titleColor }}
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
      <InspectorControls>
        <PanelBody title={__("Général", "g2rd")} initialOpen={true}>
          <SelectControl
            label={__("Disposition", "g2rd")}
            value={layout}
            options={[
              { label: __("Chiffre", "g2rd"), value: "number" },
              { label: __("Cercle", "g2rd"),  value: "circle" },
              { label: __("Barre", "g2rd"),   value: "bar" },
            ]}
            onChange={(value) => setAttributes({ layout: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          <SelectControl
            label={__("Alignement", "g2rd")}
            value={alignment}
            options={[
              { label: __("Gauche", "g2rd"),  value: "left" },
              { label: __("Centre", "g2rd"),  value: "center" },
              { label: __("Droite", "g2rd"),  value: "right" },
            ]}
            onChange={(value) => setAttributes({ alignment: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

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
            onChange={(value) =
            __next40pxDefaultSize
            __nextHasNoMarginBottom
onChange={(value) => setAttributes({ numberPrefix: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          <TextControl
            label={__("Suffixe du nombre", "g2rd")}
            value={numberSuffix}
            onChange={(value) =
            __next40pxDefaultSize
            __nextHasNoMarginBottom
onChange={(value) => setAttributes({ numberSuffix: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          <NumberControl
            label={__("Durée de l'animation (ms)", "g2rd")}
            value={animationDuration}
            onChange={(value) =>
              setAttributes({ animationDuration: parseFloat(value) || 2000 })
            }
            min={500}
            max={10000}
            step={100}
          />

          <SelectControl
            label={__("Séparateur de milliers", "g2rd")}
            value={thousands}
            options={[
              { label: __("Virgule", "g2rd"), value: "comma" },
              { label: __("Espace", "g2rd"),  value: "space" },
              { label: __("Aucun", "g2rd"),   value: "none" },
            ]}
            onChange={(value) => setAttributes({ thousands: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>

        <PanelBody title={__("Icône / Image", "g2rd")} initialOpen={false}>
          <ToggleControl
            label={__("Activer icône/image", "g2rd")}
            checked={enableIcon}
            onChange={(value) => setAttributes({ enableIcon: value })}
            __nextHasNoMarginBottom
          />

          {enableIcon && (
            <>
              <SelectControl
                label={__("Position", "g2rd")}
                value={iconPosition}
                options={[
                  { label: __("Haut", "g2rd"),   value: "top" },
                  { label: __("Bas", "g2rd"),    value: "bottom" },
                  { label: __("Gauche", "g2rd"), value: "left" },
                  { label: __("Droite", "g2rd"), value: "right" },
                ]}
                onChange={(value) => setAttributes({ iconPosition: value })}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />

              <SelectControl
                label={__("Type de média", "g2rd")}
                value={mediaType}
                options={[
                  { label: __("Icône", "g2rd"), value: "icon" },
                  { label: __("Image", "g2rd"), value: "image" },
                ]}
                onChange={(value) => setAttributes({ mediaType: value })}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />

              {mediaType === "icon" && (
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
              {mediaType === "image" && (
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

        <PanelBody title={__("Nombre", "g2rd")} initialOpen={false}>
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
        </PanelBody>

        <PanelColorSettings
          title={__("Couleurs", "g2rd")}
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
            {
              value: backgroundColor,
              onChange: (value) => setAttributes({ backgroundColor: value }),
              label: __("Couleur de fond", "g2rd"),
            },
          ]}
        />
      </InspectorControls>

      <div {...blockProps}>{renderContent()}</div>
    </>
  );
}
