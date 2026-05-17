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
import { TypographySizePanel } from "../../shared/TypographySizePanel";
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

export default function Edit({ attributes, setAttributes }) {
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
    titleFontSize,
    descriptionFontSize,
  } = attributes;

  const blockProps = useBlockProps({
    className: "g2rd-info-block",
    style: {
      backgroundColor: backgroundColor || "#f8f9fa",
      padding: "20px",
      borderRadius: "8px",
    },
  });

  const iconCategories = {
    [__("Informations & Statut", "g2rd")]: [
      { label: __("Info", "g2rd"), value: "dashicons-info" },
      { label: __("Avertissement", "g2rd"), value: "dashicons-warning" },
      { label: __("Succès", "g2rd"), value: "dashicons-yes-alt" },
      { label: __("Erreur", "g2rd"), value: "dashicons-no-alt" },
      { label: __("Question", "g2rd"), value: "dashicons-editor-help" },
      { label: __("Cocher", "g2rd"), value: "dashicons-yes" },
      { label: __("Croix", "g2rd"), value: "dashicons-no" },
      { label: __("Plus", "g2rd"), value: "dashicons-plus" },
      { label: __("Moins", "g2rd"), value: "dashicons-minus" },
      { label: __("Étoile pleine", "g2rd"), value: "dashicons-star-filled" },
      { label: __("Étoile vide", "g2rd"), value: "dashicons-star-empty" },
      { label: __("Drapeau", "g2rd"), value: "dashicons-flag" },
      { label: __("Bouclier", "g2rd"), value: "dashicons-shield" },
      { label: __("Bouclier alt", "g2rd"), value: "dashicons-shield-alt" },
    ],
    [__("Chiffres & Statistiques", "g2rd")]: [
      { label: __("Graphique barres", "g2rd"), value: "dashicons-chart-bar" },
      { label: __("Graphique camembert", "g2rd"), value: "dashicons-chart-pie" },
      { label: __("Graphique aire", "g2rd"), value: "dashicons-chart-area" },
      { label: __("Graphique ligne", "g2rd"), value: "dashicons-chart-line" },
      { label: __("Analytics", "g2rd"), value: "dashicons-analytics" },
      { label: __("Performance", "g2rd"), value: "dashicons-performance" },
      { label: __("Calculateur", "g2rd"), value: "dashicons-calculator" },
      { label: __("Tableau de bord", "g2rd"), value: "dashicons-dashboard" },
    ],
    [__("Business & Commerce", "g2rd")]: [
      { label: __("Argent", "g2rd"), value: "dashicons-money" },
      { label: __("Argent alt", "g2rd"), value: "dashicons-money-alt" },
      { label: __("Panier", "g2rd"), value: "dashicons-cart" },
      { label: __("Produits", "g2rd"), value: "dashicons-products" },
      { label: __("Homme d'affaires", "g2rd"), value: "dashicons-businessman" },
      { label: __("Bâtiment", "g2rd"), value: "dashicons-building" },
      { label: __("Boutique", "g2rd"), value: "dashicons-store" },
      { label: __("Banque", "g2rd"), value: "dashicons-bank" },
    ],
    [__("Communication", "g2rd")]: [
      { label: __("Email", "g2rd"), value: "dashicons-email" },
      { label: __("Email alt", "g2rd"), value: "dashicons-email-alt" },
      { label: __("Téléphone", "g2rd"), value: "dashicons-phone" },
      { label: __("Mégaphone", "g2rd"), value: "dashicons-megaphone" },
      { label: __("Témoignage", "g2rd"), value: "dashicons-testimonial" },
      { label: __("Microphone", "g2rd"), value: "dashicons-microphone" },
    ],
    [__("Récompenses", "g2rd")]: [
      { label: __("Prix", "g2rd"), value: "dashicons-awards" },
      { label: __("Trophée", "g2rd"), value: "dashicons-trophy" },
      { label: __("Médaille", "g2rd"), value: "dashicons-medal" },
      { label: __("Ruban", "g2rd"), value: "dashicons-ribbon" },
    ],
    [__("Technologie", "g2rd")]: [
      { label: __("Bureau", "g2rd"), value: "dashicons-desktop" },
      { label: __("Laptop", "g2rd"), value: "dashicons-laptop" },
      { label: __("Tablette", "g2rd"), value: "dashicons-tablet" },
      { label: __("Cloud", "g2rd"), value: "dashicons-cloud" },
      { label: __("Cloud sauvegardé", "g2rd"), value: "dashicons-cloud-saved" },
      { label: __("Cloud envoi", "g2rd"), value: "dashicons-cloud-upload" },
      { label: __("Base de données", "g2rd"), value: "dashicons-database" },
      { label: __("Réseau", "g2rd"), value: "dashicons-networking" },
    ],
    [__("Social", "g2rd")]: [
      { label: __("Groupes", "g2rd"), value: "dashicons-groups" },
      { label: __("Utilisateurs", "g2rd"), value: "dashicons-admin-users" },
      { label: __("Cœur", "g2rd"), value: "dashicons-heart" },
      { label: __("Identité", "g2rd"), value: "dashicons-id" },
    ],
    [__("Temps & Calendrier", "g2rd")]: [
      { label: __("Horloge", "g2rd"), value: "dashicons-clock" },
      { label: __("Calendrier", "g2rd"), value: "dashicons-calendar" },
      { label: __("Calendrier alt", "g2rd"), value: "dashicons-calendar-alt" },
    ],
    [__("Localisation", "g2rd")]: [
      { label: __("Localisation", "g2rd"), value: "dashicons-location" },
      { label: __("Localisation alt", "g2rd"), value: "dashicons-location-alt" },
      { label: __("Domicile", "g2rd"), value: "dashicons-admin-home" },
    ],
    [__("Médias & Contenu", "g2rd")]: [
      { label: __("Livre", "g2rd"), value: "dashicons-book" },
      { label: __("Livre alt", "g2rd"), value: "dashicons-book-alt" },
      { label: __("Appareil photo", "g2rd"), value: "dashicons-camera" },
      { label: __("Images", "g2rd"), value: "dashicons-images-alt" },
      { label: __("Vidéo", "g2rd"), value: "dashicons-video-alt" },
      { label: __("Image", "g2rd"), value: "dashicons-format-image" },
      { label: __("Galerie", "g2rd"), value: "dashicons-format-gallery" },
      { label: __("Citation", "g2rd"), value: "dashicons-format-quote" },
    ],
    [__("Actions", "g2rd")]: [
      { label: __("Télécharger", "g2rd"), value: "dashicons-download" },
      { label: __("Envoyer", "g2rd"), value: "dashicons-upload" },
      { label: __("Partager", "g2rd"), value: "dashicons-share" },
      { label: __("Lien externe", "g2rd"), value: "dashicons-external" },
      { label: __("Lien", "g2rd"), value: "dashicons-admin-links" },
    ],
    [__("Sécurité", "g2rd")]: [
      { label: __("Visible", "g2rd"), value: "dashicons-visibility" },
      { label: __("Masqué", "g2rd"), value: "dashicons-hidden" },
      { label: __("Verrou", "g2rd"), value: "dashicons-lock" },
      { label: __("Déverrouillé", "g2rd"), value: "dashicons-unlock" },
    ],
    [__("Navigation", "g2rd")]: [
      { label: __("Flèche haut", "g2rd"), value: "dashicons-arrow-up" },
      { label: __("Flèche bas", "g2rd"), value: "dashicons-arrow-down" },
      { label: __("Flèche gauche", "g2rd"), value: "dashicons-arrow-left" },
      { label: __("Flèche droite", "g2rd"), value: "dashicons-arrow-right" },
    ],
    [__("Divers", "g2rd")]: [
      { label: __("Ampoule", "g2rd"), value: "dashicons-lightbulb" },
      { label: __("Marteau", "g2rd"), value: "dashicons-hammer" },
      { label: __("Café", "g2rd"), value: "dashicons-coffee" },
      { label: __("Ticket", "g2rd"), value: "dashicons-tickets" },
    ],
  };

  const layoutOptions = [
    { label: __("Icône à gauche (ligne)", "g2rd"), value: "icon-left" },
    { label: __("Icône à droite (ligne)", "g2rd"), value: "icon-right" },
    { label: __("Icône en haut (colonne)", "g2rd"), value: "icon-top" },
    { label: __("Icône en bas (colonne)", "g2rd"), value: "icon-bottom" },
  ];

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

  const getFlexStyles = () => {
    if (layout === "icon-right") {
      return { gap: gap || "16px", flexDirection: "row", alignItems: "center" };
    }
    if (layout === "icon-top") {
      return { gap: gap || "16px", flexDirection: "column", alignItems: "center" };
    }
    if (layout === "icon-bottom") {
      return { gap: gap || "16px", flexDirection: "column", alignItems: "center", justifyContent: "center", width: "auto" };
    }
    return { gap: gap || "16px", flexDirection: "row", alignItems: "flex-start" };
  };

  const getTextAlign = () => {
    if (layout === "icon-top" || layout === "icon-bottom") return "center";
    return "left";
  };

  const renderText = (textAlign = "left") => (
    <div className="g2rd-info-text" style={{ textAlign }}>
      <RichText
        tagName="h3"
        value={title}
        onChange={(value) => setAttributes({ title: value })}
        placeholder={__("Saisir un titre...", "g2rd")}
        style={{ color: titleColor || "#333", fontSize: "1.5rem", ...(titleFontSize ? { fontSize: titleFontSize } : {}) }}
      />
      <RichText
        tagName="p"
        value={description}
        onChange={(value) => setAttributes({ description: value })}
        placeholder={__("Saisir une description...", "g2rd")}
        style={{ color: descriptionColor || "#666", fontSize: "1rem", ...(descriptionFontSize ? { fontSize: descriptionFontSize } : {}) }}
      />
    </div>
  );

  const renderContent = (textAlign = "left") => {
    switch (layout) {
      case "icon-top":
        return (<>{renderMedia()}{renderText(textAlign)}</>);
      case "icon-bottom":
        return (<>{renderText(textAlign)}{renderMedia()}</>);
      case "icon-right":
        return (<>{renderText(textAlign)}{renderMedia()}</>);
      case "icon-left":
      default:
        return (<>{renderMedia()}{renderText(textAlign)}</>);
    }
  };

  return (
    <>
      <TypographySizePanel
        elements={[
          {
            label: __("Taille du titre", "g2rd"),
            value: titleFontSize,
            onChange: (value) => setAttributes({ titleFontSize: value || "" }),
          },
          {
            label: __("Taille de la description", "g2rd"),
            value: descriptionFontSize,
            onChange: (value) => setAttributes({ descriptionFontSize: value || "" }),
          },
        ]}
      />
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
            label={__("Style de l'encart", "g2rd")}
            controls={STYLE_PRESETS.map((preset) => ({
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
            }))}
          />
        </ToolbarGroup>
      </BlockControls>

      <InspectorControls>
        <PanelBody title={__("Média", "g2rd")} initialOpen={true}>
          <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
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
            <>
              <DropdownMenu
                icon={
                  icon ? (
                    <span className={`dashicons ${icon}`}></span>
                  ) : (
                    "admin-customizer"
                  )
                }
                label={__("Icône", "g2rd")}
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
                              <span className={`dashicons ${iconData.value}`}></span>
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
              <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
                label={__("Taille de l'icône", "g2rd")}
                value={iconSize}
                onChange={(value) => setAttributes({ iconSize: value })}
                min={16}
                max={128}
                step={1}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
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
                        ? __("Remplacer l'image", "g2rd")
                        : __("Sélectionner une image", "g2rd")}
                    </Button>
                  )}
                />
              </MediaUploadCheck>
              {imageUrl && (
                <TextControl
                  label={__("Texte alternatif", "g2rd")}
                  value={imageAlt}
                  onChange={(value) => setAttributes({ imageAlt: value })}
                  __next40pxDefaultSize
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                  __nextHasNoMarginBottom
                />
              )}
            </>
          )}
        </PanelBody>
        <PanelColorSettings
          title={__("Couleurs", "g2rd")}
          colorSettings={[
            {
              value: backgroundColor,
              onChange: (color) => setAttributes({ backgroundColor: color }),
              label: __("Couleur de fond", "g2rd"),
            },
            {
              value: titleColor,
              onChange: (color) => setAttributes({ titleColor: color }),
              label: __("Couleur du titre", "g2rd"),
            },
            {
              value: descriptionColor,
              onChange: (color) => setAttributes({ descriptionColor: color }),
              label: __("Couleur de la description", "g2rd"),
            },
            ...(mediaType === "icon"
              ? [
                  {
                    value: iconColor,
                    onChange: (color) => setAttributes({ iconColor: color }),
                    label: __("Couleur de l'icône", "g2rd"),
                  },
                ]
              : []),
          ]}
        />
        <PanelBody title={__("Disposition", "g2rd")} initialOpen={false}>
          <TextControl
            label={__("Espacement entre l'icône et le texte (gap)", "g2rd")}
            value={gap}
            onChange={(value) => setAttributes({ gap: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
            help={__("Exemple : 8px, 1rem, 2em...", "g2rd")}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Disposition", "g2rd")}
            value={layout}
            options={layoutOptions}
            onChange={(value) => setAttributes({ layout: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>
      </InspectorControls>
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
