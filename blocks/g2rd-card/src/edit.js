import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  PanelColorSettings,
  MediaUpload,
  MediaUploadCheck,
  RichText,
  BlockControls,
  AlignmentToolbar,
  URLInput,
} from "@wordpress/block-editor";
import { TypographySizePanel } from "../../shared/TypographySizePanel";
import {
  PanelBody,
  SelectControl,
  RangeControl,
  ToggleControl,
  TextControl,
  Button,
  ButtonGroup,
} from "@wordpress/components";

// Catégories d'icônes dashicons pour le sélecteur
const ICON_CATEGORIES = {
  "Information & Statut": [
    { label: "Info", value: "dashicons-info" },
    { label: "Avertissement", value: "dashicons-warning" },
    { label: "Succès", value: "dashicons-yes-alt" },
    { label: "Erreur", value: "dashicons-no-alt" },
    { label: "Question", value: "dashicons-editor-help" },
    { label: "Étoile pleine", value: "dashicons-star-filled" },
    { label: "Étoile vide", value: "dashicons-star-empty" },
    { label: "Drapeau", value: "dashicons-flag" },
    { label: "Bouclier", value: "dashicons-shield" },
  ],
  "Business & Commerce": [
    { label: "Argent", value: "dashicons-money-alt" },
    { label: "Panier", value: "dashicons-cart" },
    { label: "Produits", value: "dashicons-products" },
    { label: "Bâtiment", value: "dashicons-building" },
    { label: "Magasin", value: "dashicons-store" },
    { label: "Graphique", value: "dashicons-chart-bar" },
    { label: "Performance", value: "dashicons-performance" },
  ],
  Communication: [
    { label: "Email", value: "dashicons-email-alt" },
    { label: "Téléphone", value: "dashicons-phone" },
    { label: "Mégaphone", value: "dashicons-megaphone" },
    { label: "Témoignage", value: "dashicons-testimonial" },
  ],
  Technologie: [
    { label: "Bureau", value: "dashicons-desktop" },
    { label: "Cloud", value: "dashicons-cloud" },
    { label: "Base de données", value: "dashicons-database" },
    { label: "Réseau", value: "dashicons-networking" },
  ],
  "Récompenses": [
    { label: "Trophée", value: "dashicons-trophy" },
    { label: "Médaille", value: "dashicons-medal" },
    { label: "Ruban", value: "dashicons-ribbon" },
    { label: "Récompenses", value: "dashicons-awards" },
  ],
  Localisation: [
    { label: "Localisation", value: "dashicons-location" },
    { label: "Localisation alt", value: "dashicons-location-alt" },
    { label: "Domicile", value: "dashicons-admin-home" },
  ],
  "Temps & Calendrier": [
    { label: "Horloge", value: "dashicons-clock" },
    { label: "Calendrier", value: "dashicons-calendar-alt" },
  ],
  Divers: [
    { label: "Ampoule", value: "dashicons-lightbulb" },
    { label: "Cadenas", value: "dashicons-lock" },
    { label: "Cœur", value: "dashicons-heart" },
    { label: "Utilisateurs", value: "dashicons-groups" },
    { label: "Télécharger", value: "dashicons-download" },
    { label: "Partager", value: "dashicons-share-alt" },
    { label: "Paramètres", value: "dashicons-admin-generic" },
  ],
};

export default function Edit({ attributes, setAttributes }) {
  const {
    mediaType,
    icon,
    iconSize,
    iconColor,
    iconBgColor,
    iconBorderRadius,
    imageUrl,
    imageId,
    imageAlt,
    imageWidth,
    iconPosition,
    alignment,
    heading,
    headingTag,
    subHeading,
    description,
    showSeparator,
    separatorColor,
    separatorWidth,
    separatorHeight,
    ctaText,
    ctaUrl,
    ctaTarget,
    ctaAriaLabel,
    ctaStyle,
    ctaBgColor,
    ctaTextColor,
    ctaBorderRadius,
    headingColor,
    subHeadingColor,
    descriptionColor,
    backgroundColor,
    borderRadius,
    paddingTop,
    paddingRight,
    paddingBottom,
    paddingLeft,
    headingFontSize,
    subheadingFontSize,
    descriptionFontSize,
    linkMode,
    showSubHeading,
    showDescription,
    mediaGap,
    contentGap,
    alignItems,
  } = attributes;

  const blockProps = useBlockProps({
    className: `g2rd-card g2rd-card--icon-${iconPosition} g2rd-card--align-${alignment}`,
    style: {
      backgroundColor: backgroundColor || undefined,
      borderRadius: borderRadius ? `${borderRadius}px` : undefined,
      padding: `${paddingTop} ${paddingRight} ${paddingBottom} ${paddingLeft}`,
      textAlign: alignment,
      gap: mediaGap || undefined,
      "--g2rd-card-align": alignItems || undefined,
    },
  });

  const renderMedia = () => {
    if (mediaType === "icon") {
      return (
        <div
          className="g2rd-card__icon-wrap"
          style={{
            width: `${iconSize}px`,
            height: `${iconSize}px`,
            minWidth: `${iconSize}px`,
            backgroundColor: iconBgColor || undefined,
            borderRadius: `${iconBorderRadius}%`,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
          }}
        >
          <span
            className={`dashicons ${icon}`}
            style={{
              fontSize: `${Math.round(iconSize * 0.6)}px`,
              color: iconColor || "var(--wp--preset--color--primary,#2f425d)",
              width: `${Math.round(iconSize * 0.6)}px`,
              height: `${Math.round(iconSize * 0.6)}px`,
            }}
          ></span>
        </div>
      );
    }
    if (mediaType === "image") {
      return (
        <div className="g2rd-card__image-wrap">
          {imageUrl ? (
            <MediaUploadCheck>
              <MediaUpload
                onSelect={(media) =>
                  setAttributes({ imageUrl: media.url, imageId: media.id, imageAlt: media.alt || "" })
                }
                allowedTypes={["image"]}
                value={imageId}
                render={({ open }) => (
                  <img
                    src={imageUrl}
                    alt={imageAlt}
                    style={{ width: `${imageWidth}px`, height: "auto", cursor: "pointer" }}
                    onClick={open}
                  />
                )}
              />
            </MediaUploadCheck>
          ) : (
            <MediaUploadCheck>
              <MediaUpload
                onSelect={(media) =>
                  setAttributes({ imageUrl: media.url, imageId: media.id, imageAlt: media.alt || "" })
                }
                allowedTypes={["image"]}
                render={({ open }) => (
                  <Button variant="secondary" onClick={open}>
                    {__("Sélectionner une image", "g2rd")}
                  </Button>
                )}
              />
            </MediaUploadCheck>
          )}
        </div>
      );
    }
    return null;
  };

  const media = renderMedia();

  return (
    <>
      <InspectorControls group="styles">
        <PanelBody title={__("Espacement des éléments", "g2rd")} initialOpen={false}>
          <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Alignement des éléments (align-items)", "g2rd")}
            value={alignItems}
            options={[
              { label: __("Par défaut (auto)", "g2rd"), value: "" },
              { label: __("Début (flex-start)", "g2rd"), value: "flex-start" },
              { label: __("Centre (center)", "g2rd"), value: "center" },
              { label: __("Fin (flex-end)", "g2rd"), value: "flex-end" },
              { label: __("Étirer (stretch)", "g2rd"), value: "stretch" },
            ]}
            onChange={(v) => setAttributes({ alignItems: v })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Écart média / contenu", "g2rd")}
            value={mediaGap}
            onChange={(v) => setAttributes({ mediaGap: v })}
            placeholder="16px"
            help={__("Gap entre l'icône/image et le texte (ex : 12px, 1rem).", "g2rd")}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Écart entre éléments du contenu", "g2rd")}
            value={contentGap}
            onChange={(v) => setAttributes({ contentGap: v })}
            placeholder="8px"
            help={__("Espace entre titre, sous-titre, description (ex : 4px, 0.5rem).", "g2rd")}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>
      </InspectorControls>

      <TypographySizePanel
        elements={[
          {
            label: __("Taille du titre", "g2rd"),
            value: headingFontSize,
            onChange: (v) => setAttributes({ headingFontSize: v || "" }),
          },
          {
            label: __("Taille du sous-titre", "g2rd"),
            value: subheadingFontSize,
            onChange: (v) => setAttributes({ subheadingFontSize: v || "" }),
          },
          {
            label: __("Taille de la description", "g2rd"),
            value: descriptionFontSize,
            onChange: (v) => setAttributes({ descriptionFontSize: v || "" }),
          },
        ]}
      />
      <BlockControls>
        <AlignmentToolbar
          value={alignment}
          onChange={(value) => setAttributes({ alignment: value || "center" })}
        />
      </BlockControls>

      <InspectorControls>
        {/* --- Média --- */}
        <PanelBody title={__("Média", "g2rd")} initialOpen={true}>
          <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Type de média", "g2rd")}
            value={mediaType}
            options={[
              { label: __("Icône Dashicons", "g2rd"), value: "icon" },
              { label: __("Image", "g2rd"), value: "image" },
              { label: __("Aucun", "g2rd"), value: "none" },
            ]}
            onChange={(value) => setAttributes({ mediaType: value })}
          />

          {mediaType === "icon" && (
            <>
              <p className="components-base-control__label">
                {__("Icône", "g2rd")}
              </p>
              <div style={{ maxHeight: "200px", overflowY: "auto", border: "1px solid #ddd", borderRadius: "4px", padding: "8px" }}>
                {Object.entries(ICON_CATEGORIES).map(([cat, icons]) => (
                  <div key={cat}>
                    <p style={{ fontSize: "11px", fontWeight: 600, color: "#646970", margin: "8px 0 4px", textTransform: "uppercase", letterSpacing: "0.5px" }}>
                      {cat}
                    </p>
                    <div style={{ display: "flex", flexWrap: "wrap", gap: "4px" }}>
                      {icons.map((ic) => (
                        <button
                          key={ic.value}
                          type="button"
                          title={ic.label}
                          onClick={() => setAttributes({ icon: ic.value })}
                          style={{
                            width: "32px",
                            height: "32px",
                            display: "flex",
                            alignItems: "center",
                            justifyContent: "center",
                            background: icon === ic.value ? "var(--wp--preset--color--primary,#2f425d)" : "#f0f0f0",
                            color: icon === ic.value ? "#fff" : "#333",
                            border: "none",
                            borderRadius: "4px",
                            cursor: "pointer",
                          }}
                        >
                          <span className={`dashicons ${ic.value}`} style={{ fontSize: "18px", width: "18px", height: "18px" }}></span>
                        </button>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
              <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
                label={__("Taille de l'icône (px)", "g2rd")}
                value={iconSize}
                onChange={(v) => setAttributes({ iconSize: v })}
                min={24}
                max={128}
                step={4}
              />
              <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
                label={__("Rayon de l'arrière-plan (%)", "g2rd")}
                value={iconBorderRadius}
                onChange={(v) => setAttributes({ iconBorderRadius: v })}
                min={0}
                max={50}
              />
            </>
          )}

          {mediaType === "image" && (
            <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
              label={__("Largeur de l'image (px)", "g2rd")}
              value={imageWidth}
              onChange={(v) => setAttributes({ imageWidth: v })}
              min={40}
              max={300}
            />
          )}

          <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Position du média", "g2rd")}
            value={iconPosition}
            options={[
              { label: __("En haut", "g2rd"), value: "top" },
              { label: __("À gauche", "g2rd"), value: "left" },
              { label: __("À droite", "g2rd"), value: "right" },
            ]}
            onChange={(v) => setAttributes({ iconPosition: v })}
          />
          {iconPosition === "top" && (
            <p style={{ fontSize: "12px", color: "#646970", marginTop: "4px" }}>
              {__("L'alignement du média en haut suit l'alignement du texte (barre d'outils).", "g2rd")}
            </p>
          )}
        </PanelBody>

        {/* --- Contenu --- */}
        <PanelBody title={__("Contenu", "g2rd")} initialOpen={false}>
          <ToggleControl
            label={__("Afficher le sous-titre", "g2rd")}
            checked={showSubHeading}
            onChange={(v) => setAttributes({ showSubHeading: v })}
          />
          <ToggleControl
            label={__("Afficher la description", "g2rd")}
            checked={showDescription}
            onChange={(v) => setAttributes({ showDescription: v })}
          />
          <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Balise du titre", "g2rd")}
            value={headingTag}
            options={["h2","h3","h4","h5","h6","p"].map((t) => ({ label: t.toUpperCase(), value: t }))}
            onChange={(v) => setAttributes({ headingTag: v })}
          />
          <ToggleControl
            label={__("Afficher le séparateur", "g2rd")}
            checked={showSeparator}
            onChange={(v) => setAttributes({ showSeparator: v })}
          />
          {showSeparator && (
            <>
              <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
                label={__("Largeur du séparateur (px)", "g2rd")}
                value={separatorWidth}
                onChange={(v) => setAttributes({ separatorWidth: v })}
                min={10}
                max={200}
              />
              <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
                label={__("Épaisseur du séparateur (px)", "g2rd")}
                value={separatorHeight}
                onChange={(v) => setAttributes({ separatorHeight: v })}
                min={1}
                max={10}
              />
            </>
          )}
        </PanelBody>

        {/* --- Lien --- */}
        <PanelBody title={__("Lien", "g2rd")} initialOpen={false}>
          <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Mode de lien", "g2rd")}
            value={linkMode}
            options={[
              { label: __("Désactivé", "g2rd"), value: "none" },
              { label: __("Bouton CTA", "g2rd"), value: "cta" },
              { label: __("Carte entière", "g2rd"), value: "card" },
            ]}
            onChange={(v) => setAttributes({ linkMode: v })}
          />

          {linkMode !== "none" && (
            <>
              <p className="components-base-control__label">{__("URL", "g2rd")}</p>
              <URLInput
                value={ctaUrl}
                onChange={(v) => setAttributes({ ctaUrl: v })}
              />
              <ToggleControl
                label={__("Ouvrir dans un nouvel onglet", "g2rd")}
                checked={ctaTarget}
                onChange={(v) => setAttributes({ ctaTarget: v })}
              />
              <TextControl
                label={__("Étiquette ARIA (accessibilité)", "g2rd")}
                value={ctaAriaLabel}
                onChange={(v) => setAttributes({ ctaAriaLabel: v })}
                placeholder={__("Ex : En savoir plus sur notre offre SEO", "g2rd")}
                help={__("Précise la destination pour les lecteurs d'écran (RGAA 6.1).", "g2rd")}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
            </>
          )}

          {linkMode === "cta" && (
            <>
              <SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
                label={__("Style du bouton", "g2rd")}
                value={ctaStyle}
                options={[
                  { label: __("Bouton rempli", "g2rd"), value: "button" },
                  { label: __("Lien simple", "g2rd"), value: "link" },
                ]}
                onChange={(v) => setAttributes({ ctaStyle: v })}
              />
              {ctaStyle === "button" && (
                <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
                  label={__("Rayon des coins (px)", "g2rd")}
                  value={ctaBorderRadius}
                  onChange={(v) => setAttributes({ ctaBorderRadius: v })}
                  min={0}
                  max={40}
                />
              )}
            </>
          )}

          {linkMode === "card" && (
            <p style={{ fontSize: "12px", color: "#646970", margin: "8px 0 0", lineHeight: 1.5 }}>
              {__("La carte entière sera cliquable. Le bouton CTA est désactivé dans ce mode.", "g2rd")}
            </p>
          )}
        </PanelBody>

        {/* --- Espacement & Bordures --- */}
        <PanelBody title={__("Espacement & Bordures", "g2rd")} initialOpen={false}>
          <RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
            label={__("Rayon des coins de la carte (px)", "g2rd")}
            value={borderRadius}
            onChange={(v) => setAttributes({ borderRadius: v })}
            min={0}
            max={40}
          />
          <p className="components-base-control__label">{__("Rembourrage", "g2rd")}</p>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "8px" }}>
            {[
              ["paddingTop", __("Haut", "g2rd")],
              ["paddingRight", __("Droite", "g2rd")],
              ["paddingBottom", __("Bas", "g2rd")],
              ["paddingLeft", __("Gauche", "g2rd")],
            ].map(([key, label]) => (
              <TextControl
                key={key}
                label={label}
                value={attributes[key]}
                onChange={(v) => setAttributes({ [key]: v })}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
            ))}
          </div>
        </PanelBody>

        {/* --- Couleurs --- */}
        <PanelColorSettings
          title={__("Couleurs", "g2rd")}
          colorSettings={[
            ...(mediaType === "icon"
              ? [
                  { value: iconColor, onChange: (v) => setAttributes({ iconColor: v || "" }), label: __("Couleur de l'icône", "g2rd") },
                  { value: iconBgColor, onChange: (v) => setAttributes({ iconBgColor: v || "" }), label: __("Fond de l'icône", "g2rd") },
                ]
              : []),
            { value: headingColor, onChange: (v) => setAttributes({ headingColor: v || "" }), label: __("Couleur du titre", "g2rd") },
            ...(showSubHeading
              ? [{ value: subHeadingColor, onChange: (v) => setAttributes({ subHeadingColor: v || "" }), label: __("Couleur du sous-titre", "g2rd") }]
              : []),
            ...(showDescription
              ? [{ value: descriptionColor, onChange: (v) => setAttributes({ descriptionColor: v || "" }), label: __("Couleur de la description", "g2rd") }]
              : []),
            ...(showSeparator
              ? [{ value: separatorColor, onChange: (v) => setAttributes({ separatorColor: v || "" }), label: __("Couleur du séparateur", "g2rd") }]
              : []),
            ...(linkMode === "cta"
              ? [
                  { value: ctaBgColor, onChange: (v) => setAttributes({ ctaBgColor: v || "" }), label: __("Couleur du bouton (fond)", "g2rd") },
                  { value: ctaTextColor, onChange: (v) => setAttributes({ ctaTextColor: v || "" }), label: __("Couleur du bouton (texte)", "g2rd") },
                ]
              : []),
            { value: backgroundColor, onChange: (v) => setAttributes({ backgroundColor: v || "" }), label: __("Fond de la carte", "g2rd") },
          ]}
        />
      </InspectorControls>

      {/* --- Rendu éditeur --- */}
      <div {...blockProps}>
        {media && <div className="g2rd-card__media">{media}</div>}

        <div className="g2rd-card__content" style={{ gap: contentGap || undefined }}>
          <RichText
            tagName={headingTag}
            className="g2rd-card__heading"
            value={heading}
            onChange={(v) => setAttributes({ heading: v })}
            placeholder={__("Titre de la carte…", "g2rd")}
            style={{ color: headingColor || undefined, fontSize: headingFontSize || undefined, margin: 0 }}
          />

          {showSubHeading && (
            <RichText
              tagName="p"
              className="g2rd-card__subheading"
              value={subHeading}
              onChange={(v) => setAttributes({ subHeading: v })}
              placeholder={__("Sous-titre (optionnel)…", "g2rd")}
              style={{ color: subHeadingColor || undefined, fontSize: subheadingFontSize || undefined }}
            />
          )}

          {showSeparator && (
            <div
              className="g2rd-card__separator"
              style={{
                width: `${separatorWidth}px`,
                height: `${separatorHeight}px`,
                backgroundColor: separatorColor || "var(--wp--preset--color--primary,#2f425d)",
              }}
            ></div>
          )}

          {showDescription && (
            <RichText
              tagName="p"
              className="g2rd-card__description"
              value={description}
              onChange={(v) => setAttributes({ description: v })}
              placeholder={__("Description de la carte…", "g2rd")}
              style={{ color: descriptionColor || undefined, fontSize: descriptionFontSize || undefined }}
            />
          )}

          {linkMode === "cta" && (
            <div className="g2rd-card__cta">
              <RichText
                tagName="span"
                className={`g2rd-card__cta-link g2rd-card__cta-link--${ctaStyle}`}
                value={ctaText}
                onChange={(v) => setAttributes({ ctaText: v })}
                placeholder={__("Texte du bouton…", "g2rd")}
                style={
                  ctaStyle === "button"
                    ? {
                        backgroundColor: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)",
                        color: ctaTextColor || "var(--wp--preset--color--white,#fff)",
                        borderRadius: `${ctaBorderRadius}px`,
                      }
                    : {
                        color: ctaBgColor || "var(--wp--preset--color--primary,#2f425d)",
                      }
                }
              />
            </div>
          )}

          {linkMode === "card" && (
            <div className="g2rd-card__link-badge">
              {ctaUrl
                ? `🔗 ${ctaUrl}`
                : __("🔗 Carte cliquable — définissez une URL dans le panneau Lien", "g2rd")}
            </div>
          )}
        </div>
      </div>
    </>
  );
}
