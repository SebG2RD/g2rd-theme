import { __ } from "@wordpress/i18n";
import {
  useBlockProps,
  InspectorControls,
  RichText,
  MediaUpload,
  MediaUploadCheck,
} from "@wordpress/block-editor";
import {
  PanelBody,
  RangeControl,
  ToggleControl,
  SelectControl,
  Button,
  ColorPicker,
} from "@wordpress/components";

const StarRating = ({ rating, color }) => (
  <div className="g2rd-testimonial__stars" style={{ color }}>
    {[1, 2, 3, 4, 5].map((star) => (
      <span
        key={star}
        className={`dashicons dashicons-${star <= rating ? "star-filled" : "star-empty"}`}
        style={{ color }}
      />
    ))}
  </div>
);

export default function Edit({ attributes, setAttributes }) {
  const {
    quote, authorName, authorRole, rating,
    avatarUrl, avatarId, avatarAlt,
    quoteColor, authorColor, roleColor, starColor, accentColor,
    backgroundColor, borderRadius, hasShadow, layout,
  } = attributes;

  const blockProps = useBlockProps({
    className: `g2rd-testimonial g2rd-testimonial--${layout}`,
  });

  return (
    <>
      <InspectorControls>
        <PanelBody title={__("Notation & Mise en page", "g2rd")} initialOpen>
          <RangeControl
            label={__("Nombre d'étoiles", "g2rd")}
            value={rating}
            onChange={(val) => setAttributes({ rating: val })}
            min={1}
            max={5}
          />
          <SelectControl
            label={__("Style", "g2rd")}
            value={layout}
            options={[
              { label: __("Carte", "g2rd"), value: "card" },
              { label: __("Simple", "g2rd"), value: "simple" },
              { label: __("Minimal", "g2rd"), value: "minimal" },
            ]}
            onChange={(val) => setAttributes({ layout: val })}
          />
          <RangeControl
            label={__("Rayon de bordure (px)", "g2rd")}
            value={borderRadius}
            onChange={(val) => setAttributes({ borderRadius: val })}
            min={0}
            max={32}
          />
          <ToggleControl
            label={__("Ombre portée", "g2rd")}
            checked={hasShadow}
            onChange={(val) => setAttributes({ hasShadow: val })}
          />
        </PanelBody>

        <PanelBody title={__("Avatar", "g2rd")} initialOpen={false}>
          <MediaUploadCheck>
            <MediaUpload
              onSelect={(media) =>
                setAttributes({
                  avatarUrl: media.url,
                  avatarId: media.id,
                  avatarAlt: media.alt || "",
                })
              }
              allowedTypes={["image"]}
              value={avatarId}
              render={({ open }) => (
                <div>
                  {avatarUrl ? (
                    <div style={{ marginBottom: "8px" }}>
                      <img
                        src={avatarUrl}
                        alt={avatarAlt}
                        style={{
                          width: "60px",
                          height: "60px",
                          borderRadius: "50%",
                          objectFit: "cover",
                          display: "block",
                          marginBottom: "8px",
                        }}
                      />
                      <Button
                        isDestructive
                        isSmall
                        onClick={() =>
                          setAttributes({ avatarUrl: "", avatarId: 0, avatarAlt: "" })
                        }
                      >
                        {__("Supprimer", "g2rd")}
                      </Button>
                    </div>
                  ) : (
                    <Button isSecondary isSmall onClick={open}>
                      {__("Choisir un avatar", "g2rd")}
                    </Button>
                  )}
                </div>
              )}
            />
          </MediaUploadCheck>
        </PanelBody>

        <PanelBody title={__("Couleurs", "g2rd")} initialOpen={false}>
          <p style={{ fontWeight: 600, marginBottom: 4 }}>{__("Fond", "g2rd")}</p>
          <ColorPicker
            color={backgroundColor}
            onChangeComplete={(val) => setAttributes({ backgroundColor: val.hex })}
            disableAlpha
          />
          <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>
            {__("Citation", "g2rd")}
          </p>
          <ColorPicker
            color={quoteColor}
            onChangeComplete={(val) => setAttributes({ quoteColor: val.hex })}
            disableAlpha
          />
          <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>
            {__("Accent (guillemets)", "g2rd")}
          </p>
          <ColorPicker
            color={accentColor}
            onChangeComplete={(val) => setAttributes({ accentColor: val.hex })}
            disableAlpha
          />
          <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>
            {__("Étoiles", "g2rd")}
          </p>
          <ColorPicker
            color={starColor}
            onChangeComplete={(val) => setAttributes({ starColor: val.hex })}
            disableAlpha
          />
          <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>
            {__("Auteur", "g2rd")}
          </p>
          <ColorPicker
            color={authorColor}
            onChangeComplete={(val) => setAttributes({ authorColor: val.hex })}
            disableAlpha
          />
          <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>
            {__("Rôle / Entreprise", "g2rd")}
          </p>
          <ColorPicker
            color={roleColor}
            onChangeComplete={(val) => setAttributes({ roleColor: val.hex })}
            disableAlpha
          />
        </PanelBody>
      </InspectorControls>

      <div
        {...blockProps}
        style={{
          backgroundColor,
          borderRadius: `${borderRadius}px`,
          boxShadow: hasShadow ? "0 4px 24px rgba(0,0,0,0.08)" : "none",
          padding: "2rem",
        }}
      >
        <StarRating rating={rating} color={starColor} />

        <div
          className="g2rd-testimonial__accent"
          style={{ color: accentColor, fontSize: "3rem", lineHeight: "0.8", fontWeight: 800, marginTop: "0.5rem" }}
        >
          "
        </div>

        <RichText
          tagName="p"
          className="g2rd-testimonial__quote"
          value={quote}
          onChange={(val) => setAttributes({ quote: val })}
          placeholder={__("Témoignage du client…", "g2rd")}
          style={{ color: quoteColor, fontStyle: "italic", lineHeight: "1.7", marginTop: "0.5rem" }}
        />

        <div className="g2rd-testimonial__author" style={{ display: "flex", alignItems: "center", gap: "0.75rem", marginTop: "1.25rem" }}>
          {avatarUrl && (
            <img
              src={avatarUrl}
              alt={avatarAlt}
              style={{ width: "48px", height: "48px", borderRadius: "50%", objectFit: "cover" }}
            />
          )}
          {!avatarUrl && (
            <div
              style={{
                width: "48px", height: "48px", borderRadius: "50%",
                backgroundColor: accentColor, display: "flex", alignItems: "center",
                justifyContent: "center", color: "#fff", fontWeight: 700, fontSize: "1.1rem",
              }}
            >
              {authorName?.[0] || "A"}
            </div>
          )}
          <div>
            <RichText
              tagName="strong"
              className="g2rd-testimonial__name"
              value={authorName}
              onChange={(val) => setAttributes({ authorName: val })}
              placeholder={__("Prénom Nom", "g2rd")}
              style={{ color: authorColor, display: "block", fontWeight: 700 }}
            />
            <RichText
              tagName="span"
              className="g2rd-testimonial__role"
              value={authorRole}
              onChange={(val) => setAttributes({ authorRole: val })}
              placeholder={__("Rôle — Entreprise", "g2rd")}
              style={{ color: roleColor, fontSize: "0.875rem" }}
            />
          </div>
        </div>
      </div>
    </>
  );
}
