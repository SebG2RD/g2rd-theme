import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  BlockControls,
  InspectorControls,
  PanelColorSettings,
  MediaUpload,
  MediaUploadCheck,
  RichText,
} from "@wordpress/block-editor";
import {
  PanelBody,
  SelectControl,
  TextControl,
  RangeControl,
  Button,
  DropdownMenu,
  MenuGroup,
  MenuItem,
  ToolbarGroup,
  ToolbarDropdownMenu,
} from "@wordpress/components";

// Préréglages de style — miroir des variations de src/index.js
const STYLE_PRESETS = [
  {
    title: __("Info standard", "g2rd"),
    attrs: {
      icon:             "dashicons-info",
      backgroundColor: "#2F425D",
      titleColor:      "#FAFAFA",
      descriptionColor: "#D4A373",
      iconColor:       "#FAFAFA",
    },
  },
  {
    title: __("Succès", "g2rd"),
    attrs: {
      icon:             "dashicons-yes-alt",
      backgroundColor: "#14532d",
      titleColor:      "#f0fdf4",
      descriptionColor: "#bbf7d0",
      iconColor:       "#4ade80",
    },
  },
  {
    title: __("Avertissement", "g2rd"),
    attrs: {
      icon:             "dashicons-warning",
      backgroundColor: "#78350f",
      titleColor:      "#fffbeb",
      descriptionColor: "#fde68a",
      iconColor:       "#fbbf24",
    },
  },
  {
    title: __("Danger / Erreur", "g2rd"),
    attrs: {
      icon:             "dashicons-dismiss",
      backgroundColor: "#7f1d1d",
      titleColor:      "#fef2f2",
      descriptionColor: "#fca5a5",
      iconColor:       "#f87171",
    },
  },
  {
    title: __("Conseil / Astuce", "g2rd"),
    attrs: {
      icon:             "dashicons-lightbulb",
      backgroundColor: "#1e3a5f",
      titleColor:      "#eff6ff",
      descriptionColor: "#93c5fd",
      iconColor:       "#60a5fa",
    },
  },
];

/**
 * Bloc Info G2RD - Composant principal d'édition
 */
export default function Edit({ attributes, setAttributes }) {
  // Déstructuration des attributs du bloc
  const {
    mediaType,
    icon,
    imageUrl,
    imageId,
    imageAlt,
    title,
    description,
    backgroundColor,
    titleColor,
    descriptionColor,
    iconColor,
    iconSize,
    gap,
    layout,
  } = attributes;

  // Propriétés du wrapper du bloc (même className que save.js pour la parité CSS)
  const blockProps = useBlockProps({
    className: "g2rd-info-block",
    style: {
      backgroundColor: backgroundColor || "#f8f9fa",
      padding: "20px",
      borderRadius: "8px",
    },
  });

  // Icônes organisées par catégories pour le DropdownMenu
  const iconCategories = {
    "Information & Status": [
      { label: "Info", value: "dashicons-info" },
      { label: "Warning", value: "dashicons-warning" },
      { label: "Success", value: "dashicons-yes-alt" },
      { label: "Error", value: "dashicons-no-alt" },
      { label: "Question", value: "dashicons-editor-help" },
      { label: "Check", value: "dashicons-yes" },
      { label: "Cross", value: "dashicons-no" },
      { label: "Plus", value: "dashicons-plus" },
      { label: "Minus", value: "dashicons-minus" },
      { label: "Star Filled", value: "dashicons-star-filled" },
      { label: "Star Empty", value: "dashicons-star-empty" },
      { label: "Flag", value: "dashicons-flag" },
      { label: "Shield", value: "dashicons-shield" },
      { label: "Shield Alt", value: "dashicons-shield-alt" },
    ],
    "Numbers & Statistics": [
      { label: "Chart Bar", value: "dashicons-chart-bar" },
      { label: "Chart Pie", value: "dashicons-chart-pie" },
      { label: "Chart Area", value: "dashicons-chart-area" },
      { label: "Chart Line", value: "dashicons-chart-line" },
      { label: "Analytics", value: "dashicons-analytics" },
      { label: "Performance", value: "dashicons-performance" },
      { label: "Calculator", value: "dashicons-calculator" },
      { label: "Dashboard", value: "dashicons-dashboard" },
    ],
    "Business & Commerce": [
      { label: "Money", value: "dashicons-money" },
      { label: "Money Alt", value: "dashicons-money-alt" },
      { label: "Cart", value: "dashicons-cart" },
      { label: "Products", value: "dashicons-products" },
      { label: "Businessman", value: "dashicons-businessman" },
      { label: "Building", value: "dashicons-building" },
      { label: "Store", value: "dashicons-store" },
      { label: "Bank", value: "dashicons-bank" },
    ],
    Communication: [
      { label: "Email", value: "dashicons-email" },
      { label: "Email Alt", value: "dashicons-email-alt" },
      { label: "Phone", value: "dashicons-phone" },
      { label: "Megaphone", value: "dashicons-megaphone" },
      { label: "Testimonial", value: "dashicons-testimonial" },
      { label: "Microphone", value: "dashicons-microphone" },
    ],
    "Awards & Achievement": [
      { label: "Awards", value: "dashicons-awards" },
      { label: "Trophy", value: "dashicons-trophy" },
      { label: "Medal", value: "dashicons-medal" },
      { label: "Ribbon", value: "dashicons-ribbon" },
    ],
    Technology: [
      { label: "Desktop", value: "dashicons-desktop" },
      { label: "Laptop", value: "dashicons-laptop" },
      { label: "Tablet", value: "dashicons-tablet" },
      { label: "Cloud", value: "dashicons-cloud" },
      { label: "Cloud Saved", value: "dashicons-cloud-saved" },
      { label: "Cloud Upload", value: "dashicons-cloud-upload" },
      { label: "Database", value: "dashicons-database" },
      { label: "Database Add", value: "dashicons-database-add" },
      { label: "Database Export", value: "dashicons-database-export" },
      { label: "Database Import", value: "dashicons-database-import" },
      { label: "Database Remove", value: "dashicons-database-remove" },
      { label: "Database View", value: "dashicons-database-view" },
      { label: "Networking", value: "dashicons-networking" },
    ],
    Social: [
      { label: "Groups", value: "dashicons-groups" },
      { label: "Users", value: "dashicons-admin-users" },
      { label: "Heart", value: "dashicons-heart" },
      { label: "ID", value: "dashicons-id" },
      { label: "ID Alt", value: "dashicons-id-alt" },
    ],
    "Time & Calendar": [
      { label: "Clock", value: "dashicons-clock" },
      { label: "Calendar", value: "dashicons-calendar" },
      { label: "Calendar Alt", value: "dashicons-calendar-alt" },
    ],
    Location: [
      { label: "Location", value: "dashicons-location" },
      { label: "Location Alt", value: "dashicons-location-alt" },
      { label: "Home", value: "dashicons-admin-home" },
    ],
    "Media & Content": [
      { label: "Book", value: "dashicons-book" },
      { label: "Book Alt", value: "dashicons-book-alt" },
      { label: "Camera", value: "dashicons-camera" },
      { label: "Camera Alt", value: "dashicons-camera-alt" },
      { label: "Images Alt", value: "dashicons-images-alt" },
      { label: "Images Alt2", value: "dashicons-images-alt2" },
      { label: "Video Alt", value: "dashicons-video-alt" },
      { label: "Video Alt2", value: "dashicons-video-alt2" },
      { label: "Video Alt3", value: "dashicons-video-alt3" },
      { label: "Format Image", value: "dashicons-format-image" },
      { label: "Format Video", value: "dashicons-format-video" },
      { label: "Format Audio", value: "dashicons-format-audio" },
      { label: "Format Gallery", value: "dashicons-format-gallery" },
      { label: "Format Quote", value: "dashicons-format-quote" },
      { label: "Format Chat", value: "dashicons-format-chat" },
      { label: "Format Aside", value: "dashicons-format-aside" },
      { label: "Format Status", value: "dashicons-format-status" },
      { label: "Format Standard", value: "dashicons-format-standard" },
      { label: "Format Link", value: "dashicons-format-link" },
    ],
    Actions: [
      { label: "Download", value: "dashicons-download" },
      { label: "Upload", value: "dashicons-upload" },
      { label: "Share", value: "dashicons-share" },
      { label: "Share Alt", value: "dashicons-share-alt" },
      { label: "Share Alt2", value: "dashicons-share-alt2" },
      { label: "External", value: "dashicons-external" },
      { label: "Link", value: "dashicons-admin-links" },
      { label: "Paperclip", value: "dashicons-paperclip" },
    ],
    Management: [
      { label: "Settings", value: "dashicons-admin-generic" },
      { label: "Controls Play", value: "dashicons-controls-play" },
      { label: "Controls Pause", value: "dashicons-controls-pause" },
      { label: "Controls Forward", value: "dashicons-controls-forward" },
      { label: "Controls Back", value: "dashicons-controls-back" },
      { label: "Controls Repeat", value: "dashicons-controls-repeat" },
      {
        label: "Controls Skip Forward",
        value: "dashicons-controls-skipforward",
      },
      { label: "Controls Skip Back", value: "dashicons-controls-skipback" },
    ],
    Security: [
      { label: "Visibility", value: "dashicons-visibility" },
      { label: "Hidden", value: "dashicons-hidden" },
      { label: "Lock", value: "dashicons-lock" },
      { label: "Unlock", value: "dashicons-unlock" },
      { label: "Privacy", value: "dashicons-privacy" },
    ],
    Editing: [
      { label: "Edit", value: "dashicons-edit" },
      { label: "Edit Large", value: "dashicons-edit-large" },
      { label: "Edit Page", value: "dashicons-edit-page" },
      { label: "Trash", value: "dashicons-trash" },
      { label: "Backup", value: "dashicons-backup" },
      { label: "Migrate", value: "dashicons-migrate" },
      { label: "Redo", value: "dashicons-redo" },
      { label: "Undo", value: "dashicons-undo" },
    ],
    Navigation: [
      { label: "Arrow Up", value: "dashicons-arrow-up" },
      { label: "Arrow Down", value: "dashicons-arrow-down" },
      { label: "Arrow Left", value: "dashicons-arrow-left" },
      { label: "Arrow Right", value: "dashicons-arrow-right" },
      { label: "Arrow Up Alt", value: "dashicons-arrow-up-alt" },
      { label: "Arrow Down Alt", value: "dashicons-arrow-down-alt" },
      { label: "Arrow Left Alt", value: "dashicons-arrow-left-alt" },
      { label: "Arrow Right Alt", value: "dashicons-arrow-right-alt" },
      { label: "Sort", value: "dashicons-sort" },
      { label: "Randomize", value: "dashicons-randomize" },
    ],
    Miscellaneous: [
      { label: "Lightbulb", value: "dashicons-lightbulb" },
      { label: "Carrot", value: "dashicons-carrot" },
      { label: "Hammer", value: "dashicons-hammer" },
      { label: "Palmtree", value: "dashicons-palmtree" },
      { label: "Tickets", value: "dashicons-tickets" },
      { label: "Tickets Alt", value: "dashicons-tickets-alt" },
      { label: "Coffee", value: "dashicons-coffee" },
      { label: "Food", value: "dashicons-food" },
      { label: "Universal Access", value: "dashicons-universal-access" },
      {
        label: "Universal Access Alt",
        value: "dashicons-universal-access-alt",
      },
    ],
  };

  // Layouts disponibles
  const layoutOptions = [
    { label: __("Icône à gauche (ligne)", "g2rd"), value: "icon-left" },
    { label: __("Icône à droite (ligne)", "g2rd"), value: "icon-right" },
    { label: __("Icône en haut (colonne)", "g2rd"), value: "icon-top" },
    { label: __("Icône en bas (colonne)", "g2rd"), value: "icon-bottom" },
  ];

  /**
   * Rendu de l'icône ou de l'image
   */
  const renderMedia = () => {
    if (mediaType === "icon") {
      return (
        <div className="g2rd-info-icon">
          <span
            className={`dashicons ${icon || "dashicons-info"}`}
            style={{ fontSize: `${iconSize}px`, color: iconColor || "#333" }}
          ></span>
        </div>
      );
    } else if (mediaType === "image" && imageUrl) {
      return (
        <div className="g2rd-info-image">
          <img
            src={imageUrl}
            alt={imageAlt}
            style={{ maxWidth: "100px", height: "auto" }}
          />
        </div>
      );
    }
    return null;
  };

  /**
   * Styles flex dynamiques selon le layout
   */
  const getFlexStyles = () => {
    if (layout === "icon-right") {
      return { gap: gap || "16px", flexDirection: "row", alignItems: "center" };
    }
    if (layout === "icon-top") {
      return {
        gap: gap || "16px",
        flexDirection: "column",
        alignItems: "center",
      };
    }
    if (layout === "icon-bottom") {
      return {
        gap: gap || "16px",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        width: "auto",
      };
    }
    // icon-left (défaut)
    return {
      gap: gap || "16px",
      flexDirection: "row",
      alignItems: "flex-start",
    };
  };

  /**
   * Alignement du texte selon le layout
   */
  const getTextAlign = () => {
    if (layout === "icon-top" || layout === "icon-bottom") return "center";
    return "left";
  };

  /**
   * Rendu du texte (titre + description)
   */
  const renderText = (textAlign = "left") => (
    <div className="g2rd-info-text" style={{ textAlign }}>
      <RichText
        tagName="h3"
        value={title}
        onChange={(value) => setAttributes({ title: value })}
        placeholder={__("Enter title...", "g2rd")}
        style={{ color: titleColor || "#333", fontSize: "1.5rem" }}
      />
      <RichText
        tagName="p"
        value={description}
        onChange={(value) => setAttributes({ description: value })}
        placeholder={__("Enter description...", "g2rd")}
        style={{ color: descriptionColor || "#666", fontSize: "1rem" }}
      />
    </div>
  );

  /**
   * Rendu du contenu (icône/image + texte) selon le layout
   */
  const renderContent = (textAlign = "left") => {
    switch (layout) {
      case "icon-top":
        return (
          <>
            {renderMedia()}
            {renderText(textAlign)}
          </>
        );
      case "icon-bottom":
        return (
          <>
            {renderText(textAlign)}
            {renderMedia()}
          </>
        );
      case "icon-right":
        return (
          <>
            {renderText(textAlign)}
            {renderMedia()}
          </>
        );
      case "icon-left":
      default:
        return (
          <>
            {renderMedia()}
            {renderText(textAlign)}
          </>
        );
    }
  };

  // --- Rendu principal du bloc ---
  return (
    <>
      {/* Barre d'outils : sélecteur de style prédéfini */}
      <BlockControls>
        <ToolbarGroup>
          <ToolbarDropdownMenu
            icon={
              <span style={{
                display: "inline-block",
                width: "16px",
                height: "16px",
                borderRadius: "3px",
                background: backgroundColor || "#2F425D",
                border: "2px solid rgba(255,255,255,.75)",
                flexShrink: 0,
              }} />
            }
            label={ __("Style de l'encart", "g2rd") }
            controls={ STYLE_PRESETS.map((preset) => ({
              title: preset.title,
              icon: (
                <span style={{
                  display: "inline-block",
                  width: "12px",
                  height: "12px",
                  borderRadius: "2px",
                  background: preset.attrs.backgroundColor,
                  border: "1px solid rgba(0,0,0,.2)",
                  flexShrink: 0,
                }} />
              ),
              isActive: backgroundColor === preset.attrs.backgroundColor,
              onClick: () => setAttributes({ ...preset.attrs }),
            })) }
          />
        </ToolbarGroup>
      </BlockControls>

      {/* Panneau latéral de configuration */}
      <InspectorControls>
        <PanelBody title={__("Media Settings", "g2rd")} initialOpen={true}>
          <SelectControl
            label={__("Media Type", "g2rd")}
            value={mediaType}
            options={[
              { label: "Icon", value: "icon" },
              { label: "Image", value: "image" },
            ]}
            onChange={(value) => setAttributes({ mediaType: value })}
          />
          {mediaType === "icon" && (
            <>
              {/* Sélecteur d'icône custom avec visuel */}
              <DropdownMenu
                icon={
                  icon ? (
                    <span className={`dashicons ${icon}`}></span>
                  ) : (
                    "admin-customizer"
                  )
                }
                label={__("Icon", "g2rd")}
                toggleProps={{ variant: "secondary" }}
              >
                {({ onClose }) => (
                  <div style={{ maxHeight: "400px", overflowY: "auto" }}>
                    {Object.entries(iconCategories).map(([category, icons]) => (
                      <MenuGroup key={category} label={category}>
                        {icons.map((iconData) => (
                          <MenuItem
                            key={iconData.value}
                            icon={
                              <span
                                className={`dashicons ${iconData.value}`}
                              ></span>
                            }
                            isSelected={icon === iconData.value}
                            onClick={() => {
                              setAttributes({ icon: iconData.value });
                              onClose();
                            }}
                          >
                            {iconData.label}
                          </MenuItem>
                        ))}
                      </MenuGroup>
                    ))}
                  </div>
                )}
              </DropdownMenu>
              <RangeControl
                label={__("Icon Size", "g2rd")}
                value={iconSize}
                onChange={(value) => setAttributes({ iconSize: value })}
                min={16}
                max={128}
                step={1}
              />
            </>
          )}
          {mediaType === "image" && (
            <>
              <MediaUploadCheck>
                <MediaUpload
                  onSelect={(media) => {
                    setAttributes({
                      imageUrl: media.url,
                      imageId: media.id,
                      imageAlt: media.alt || "",
                    });
                  }}
                  allowedTypes={["image"]}
                  value={imageId}
                  render={({ open }) => (
                    <Button onClick={open} variant="secondary">
                      {imageUrl
                        ? __("Replace Image", "g2rd")
                        : __("Select Image", "g2rd")}
                    </Button>
                  )}
                />
              </MediaUploadCheck>
              {imageUrl && (
                <TextControl
                  label={__("Alt Text", "g2rd")}
                  value={imageAlt}
                  onChange={(value) => setAttributes({ imageAlt: value })}
                />
              )}
            </>
          )}
        </PanelBody>
        <PanelColorSettings
          title={__("Colors", "g2rd")}
          colorSettings={[
            {
              value: backgroundColor,
              onChange: (color) => setAttributes({ backgroundColor: color }),
              label: __("Background Color", "g2rd"),
            },
            {
              value: titleColor,
              onChange: (color) => setAttributes({ titleColor: color }),
              label: __("Title Color", "g2rd"),
            },
            {
              value: descriptionColor,
              onChange: (color) => setAttributes({ descriptionColor: color }),
              label: __("Description Color", "g2rd"),
            },
            ...(mediaType === "icon"
              ? [
                  {
                    value: iconColor,
                    onChange: (color) => setAttributes({ iconColor: color }),
                    label: __("Icon Color", "g2rd"),
                  },
                ]
              : []),
          ]}
        />
        <PanelBody title={__("Layout", "g2rd")} initialOpen={false}>
          <TextControl
            label={__("Espacement entre l'icône et le texte (gap)", "g2rd")}
            value={gap}
            onChange={(value) => setAttributes({ gap: value })}
            help={__("Exemple : 8px, 1rem, 2em...", "g2rd")}
          />
          <SelectControl
            label={__("Disposition", "g2rd")}
            value={layout}
            options={layoutOptions}
            onChange={(value) => setAttributes({ layout: value })}
          />
        </PanelBody>
      </InspectorControls>
      {/* Rendu du bloc dans l'éditeur */}
      <div {...blockProps}>
        <div
          className={`g2rd-info-content g2rd-layout-${layout}`}
          style={getFlexStyles()}
        >
          {renderContent(getTextAlign())}
        </div>
      </div>
    </>
  );
}
