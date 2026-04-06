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

/**
 * Icons organized by categories for the DropdownMenu
 */
const iconCategories = {
  "Information & Status": [
    { label: "Star Filled", value: "star-filled" },
    { label: "Star Empty", value: "star-empty" },
    { label: "Info", value: "info" },
    { label: "Warning", value: "warning" },
    { label: "Success", value: "yes" },
    { label: "Error", value: "no" },
    { label: "Thumbs Up", value: "thumbs-up" },
    { label: "Thumbs Down", value: "thumbs-down" },
  ],
  "Numbers & Statistics": [
    { label: "Chart Bar", value: "chart-bar" },
    { label: "Chart Pie", value: "chart-pie" },
    { label: "Chart Area", value: "chart-area" },
    { label: "Chart Line", value: "chart-line" },
    { label: "Analytics", value: "analytics" },
    { label: "Performance", value: "performance" },
    { label: "Trending Up", value: "trending-up" },
    { label: "Trending Down", value: "trending-down" },
  ],
  "Business & Commerce": [
    { label: "Money", value: "money" },
    { label: "Money Alt", value: "money-alt" },
    { label: "Cart", value: "cart" },
    { label: "Products", value: "products" },
    { label: "Businessman", value: "businessman" },
    { label: "Building", value: "building" },
  ],
  Communication: [
    { label: "Email", value: "email" },
    { label: "Phone", value: "phone" },
    { label: "Smartphone", value: "smartphone" },
    { label: "Megaphone", value: "megaphone" },
  ],
  "Awards & Achievement": [
    { label: "Awards", value: "awards" },
    { label: "Trophy", value: "trophy" },
    { label: "Medal", value: "medal" },
    { label: "Ribbon", value: "ribbon" },
  ],
  Technology: [
    { label: "Desktop", value: "desktop" },
    { label: "Laptop", value: "laptop" },
    { label: "Tablet", value: "tablet" },
    { label: "Cloud", value: "cloud" },
    { label: "Database", value: "database" },
  ],
  Social: [
    { label: "Groups", value: "groups" },
    { label: "Users", value: "admin-users" },
    { label: "Activity", value: "buddicons-activity" },
    { label: "Heart", value: "heart" },
  ],
  "Time & Calendar": [
    { label: "Clock", value: "clock" },
    { label: "Calendar", value: "calendar-alt" },
    { label: "Hourglass", value: "hourglass" },
  ],
  Location: [
    { label: "Location", value: "location" },
    { label: "Location Alt", value: "location-alt" },
    { label: "Store", value: "store" },
    { label: "Home", value: "admin-home" },
  ],
};

/**
 * Edit function for the G2RD Counter block
 */
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

  // Ajout des valeurs par défaut si non définies
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

  /**
   * Render the icon based on media type
   */
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

  /**
   * Render the number with prefix and suffix
   */
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

  /**
   * Render the content based on layout and icon position
   */
  const renderContent = () => {
    const icon = renderIcon();
    const number = renderNumber();
    const titleElement = (
      <RichText
        tagName="h3"
        value={title}
        onChange={(value) => setAttributes({ title: value })}
        placeholder={__("Add Your Title Here...", "g2rd")}
        className="counter-title"
        style={{ color: titleColor }}
      />
    );

    if (layout === "circle" || layout === "bar") {
      // Pour les layouts circle et bar, on affiche différemment
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

    // Layout number (default)
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
        {/* Panel General */}
        <PanelBody title={__("General", "g2rd")} initialOpen={true}>
          <SelectControl
            label={__("Layout", "g2rd")}
            value={layout}
            options={[
              { label: __("Number", "g2rd"), value: "number" },
              { label: __("Circle", "g2rd"), value: "circle" },
              { label: __("Bar", "g2rd"), value: "bar" },
            ]}
            onChange={(value) => setAttributes({ layout: value })}
          />

          <SelectControl
            label={__("Alignment", "g2rd")}
            value={alignment}
            options={[
              { label: __("Left", "g2rd"), value: "left" },
              { label: __("Center", "g2rd"), value: "center" },
              { label: __("Right", "g2rd"), value: "right" },
            ]}
            onChange={(value) => setAttributes({ alignment: value })}
          />

          <NumberControl
            label={__("Starting Number", "g2rd")}
            value={startingNumber}
            onChange={(value) =>
              setAttributes({ startingNumber: parseFloat(value) || 0 })
            }
          />

          <NumberControl
            label={__("Ending Number", "g2rd")}
            value={endingNumber}
            onChange={(value) =>
              setAttributes({ endingNumber: parseFloat(value) || 0 })
            }
          />

          <RangeControl
            label={__("Decimal Places", "g2rd")}
            value={decimalPlaces}
            onChange={(value) => setAttributes({ decimalPlaces: value })}
            min={0}
            max={5}
          />

          <TextControl
            label={__("Number Prefix", "g2rd")}
            value={numberPrefix}
            onChange={(value) => setAttributes({ numberPrefix: value })}
          />

          <TextControl
            label={__("Number Suffix", "g2rd")}
            value={numberSuffix}
            onChange={(value) => setAttributes({ numberSuffix: value })}
          />

          <NumberControl
            label={__("Animation Duration (ms)", "g2rd")}
            value={animationDuration}
            onChange={(value) =>
              setAttributes({ animationDuration: parseFloat(value) || 2000 })
            }
            min={500}
            max={10000}
            step={100}
          />

          <SelectControl
            label={__("Thousands Separator", "g2rd")}
            value={thousands}
            options={[
              { label: __("Comma", "g2rd"), value: "comma" },
              { label: __("Space", "g2rd"), value: "space" },
              { label: __("None", "g2rd"), value: "none" },
            ]}
            onChange={(value) => setAttributes({ thousands: value })}
          />
        </PanelBody>

        {/* Panel Image/Icon */}
        <PanelBody title={__("Image/Icon", "g2rd")} initialOpen={false}>
          <ToggleControl
            label={__("Enable Icon/Image", "g2rd")}
            checked={enableIcon}
            onChange={(value) => setAttributes({ enableIcon: value })}
          />

          {enableIcon && (
            <>
              <SelectControl
                label={__("Select Position", "g2rd")}
                value={iconPosition}
                options={[
                  { label: __("Top", "g2rd"), value: "top" },
                  { label: __("Bottom", "g2rd"), value: "bottom" },
                  { label: __("Left", "g2rd"), value: "left" },
                  { label: __("Right", "g2rd"), value: "right" },
                ]}
                onChange={(value) => setAttributes({ iconPosition: value })}
              />

              <SelectControl
                label={__("Select Source", "g2rd")}
                value={mediaType}
                options={[
                  { label: __("Icon", "g2rd"), value: "icon" },
                  { label: __("Image", "g2rd"), value: "image" },
                ]}
                onChange={(value) => setAttributes({ mediaType: value })}
              />

              {mediaType === "icon" && (
                <RangeControl
                  label={__("Icon Size (px)", "g2rd")}
                  value={iconSize}
                  onChange={(value) => setAttributes({ iconSize: value })}
                  min={16}
                  max={128}
                />
              )}
              {mediaType === "image" && (
                <RangeControl
                  label={__("Image Size (px)", "g2rd")}
                  value={imageSize}
                  onChange={(value) => setAttributes({ imageSize: value })}
                  min={16}
                  max={256}
                />
              )}

              {mediaType === "icon" && (
                <>
                  {/* Sélecteur d'icône custom avec visuel */}
                  <DropdownMenu
                    icon={
                      iconName ? (
                        <span
                          className={`dashicons dashicons-${iconName}`}
                        ></span>
                      ) : (
                        "admin-customizer"
                      )
                    }
                    label={__("Icon", "g2rd")}
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
                </>
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
                          ? __("Change Image", "g2rd")
                          : __("Select Image", "g2rd")}
                      </Button>
                    )}
                  />
                </MediaUploadCheck>
              )}
            </>
          )}
        </PanelBody>

        {/* Panel Number */}
        <PanelBody title={__("Number", "g2rd")} initialOpen={false}>
          <RangeControl
            label={__("Prefix Right Margin", "g2rd")}
            value={prefixRightMargin}
            onChange={(value) => setAttributes({ prefixRightMargin: value })}
            min={0}
            max={50}
          />

          <RangeControl
            label={__("Suffix Left Margin", "g2rd")}
            value={suffixLeftMargin}
            onChange={(value) => setAttributes({ suffixLeftMargin: value })}
            min={0}
            max={50}
          />
        </PanelBody>

        <PanelColorSettings
          title={__("Colors", "g2rd")}
          colorSettings={[
            {
              value: numberColor,
              onChange: (value) => setAttributes({ numberColor: value }),
              label: __("Number Color", "g2rd"),
            },
            {
              value: titleColor,
              onChange: (value) => setAttributes({ titleColor: value }),
              label: __("Title Color", "g2rd"),
            },
            ...(enableIcon && mediaType === "icon"
              ? [
                  {
                    value: iconColor,
                    onChange: (value) => setAttributes({ iconColor: value }),
                    label: __("Icon Color", "g2rd"),
                  },
                ]
              : []),
            {
              value: backgroundColor,
              onChange: (value) => setAttributes({ backgroundColor: value }),
              label: __("Background Color", "g2rd"),
            },
          ]}
        />
      </InspectorControls>

      <div {...blockProps}>{renderContent()}</div>
    </>
  );
}
