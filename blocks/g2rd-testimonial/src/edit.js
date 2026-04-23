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
  TextControl,
  Spinner,
  Notice,
} from "@wordpress/components";
import { useState, useEffect } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";

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

/* ── Composant prévisualisation Google dans l'éditeur ─────────────────────── */
function GooglePreview({ placeId, minRating, max, colors }) {
  const [state, setState] = useState({ loading: false, data: null, error: null });

  useEffect(() => {
    if (!placeId) { setState({ loading: false, data: null, error: null }); return; }
    setState({ loading: true, data: null, error: null });
    apiFetch({
      path: `/g2rd/v1/google-reviews?place_id=${encodeURIComponent(placeId)}&min_rating=${minRating}&max=${max}`,
    })
      .then((data) => setState({ loading: false, data, error: null }))
      .catch((err) => setState({ loading: false, data: null, error: err.message || __("Erreur de chargement.", "g2rd") }));
  }, [placeId, minRating, max]);

  if (!placeId) {
    return (
      <div style={{ textAlign: "center", padding: "2rem", opacity: 0.6 }}>
        <span className="dashicons dashicons-google" style={{ fontSize: "2rem", height: "2rem", width: "2rem" }} />
        <p style={{ marginTop: "0.5rem" }}>{__("Renseignez votre Place ID pour prévisualiser les avis.", "g2rd")}</p>
      </div>
    );
  }

  if (state.loading) {
    return (
      <div style={{ display: "flex", justifyContent: "center", padding: "2rem" }}>
        <Spinner />
      </div>
    );
  }

  if (state.error) {
    return (
      <Notice status="error" isDismissible={false}>
        {state.error}
      </Notice>
    );
  }

  if (!state.data || !state.data.reviews?.length) {
    return (
      <Notice status="warning" isDismissible={false}>
        {__("Aucun avis trouvé pour ce Place ID avec les filtres actuels.", "g2rd")}
      </Notice>
    );
  }

  const { data } = state;
  return (
    <div>
      <div className="g2rd-testimonial__google-header" style={{ display: "flex", alignItems: "center", gap: "0.75rem", marginBottom: "1rem" }}>
        <strong style={{ fontSize: "1.5rem" }}>★ {data.overall_rating.toFixed(1)}</strong>
        <span style={{ opacity: 0.7, fontSize: "0.875rem" }}>{data.total_ratings} {__("avis Google", "g2rd")}</span>
      </div>
      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))", gap: "1rem" }}>
        {data.reviews.map((r, i) => (
          <div
            key={i}
            className="g2rd-testimonial__card"
            style={{
              background: colors.bg || "#fff",
              borderRadius: `${colors.radius || 8}px`,
              boxShadow: colors.shadow || "0 4px 24px rgba(0,0,0,0.08)",
              padding: "1.25rem",
              display: "flex",
              flexDirection: "column",
              gap: "0.5rem",
            }}
          >
            <StarRating rating={r.rating} color={colors.star || "#D4A373"} />
            <p style={{ fontStyle: "italic", color: colors.quote || "#2F425D", margin: 0, lineHeight: 1.6, fontSize: "0.9rem" }}>
              {r.text}
            </p>
            <div style={{ display: "flex", alignItems: "center", gap: "0.5rem", marginTop: "auto", paddingTop: "0.75rem", borderTop: "1px solid rgba(0,0,0,0.08)" }}>
              {r.avatar
                ? <img src={r.avatar} alt={r.author} style={{ width: 36, height: 36, borderRadius: "50%", objectFit: "cover" }} />
                : <div style={{ width: 36, height: 36, borderRadius: "50%", background: colors.accent || "#D4A373", display: "flex", alignItems: "center", justifyContent: "center", color: "#fff", fontWeight: 700 }}>{(r.author || "A")[0]}</div>
              }
              <div>
                <strong style={{ display: "block", fontSize: "0.85rem", color: colors.author || "#2F425D" }}>{r.author}</strong>
                <span style={{ fontSize: "0.75rem", color: colors.role || "#D4A373" }}>{r.relative_time}</span>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

/* ── Edit principal ───────────────────────────────────────────────────────── */
export default function Edit({ attributes, setAttributes }) {
  const {
    quote, authorName, authorRole, rating,
    avatarUrl, avatarId, avatarAlt,
    quoteColor, authorColor, roleColor, starColor, accentColor,
    backgroundColor, borderRadius, hasShadow, layout,
    googleMode, googlePlaceId, googleMinRating, googleMaxReviews,
  } = attributes;

  const blockProps = useBlockProps({
    className: googleMode
      ? "g2rd-testimonial g2rd-testimonial--google"
      : `g2rd-testimonial g2rd-testimonial--${layout}`,
  });

  const colorSet = {
    bg: backgroundColor, radius: borderRadius, shadow: hasShadow ? "0 4px 24px rgba(0,0,0,0.08)" : "none",
    star: starColor, quote: quoteColor, author: authorColor, role: roleColor, accent: accentColor,
  };

  return (
    <>
      <InspectorControls>

        {/* ── Panneau Avis Google ── */}
        <PanelBody title={__("Avis Google Business", "g2rd")} initialOpen={false}>
          <ToggleControl
            label={__("Afficher les avis Google", "g2rd")}
            help={
              googleMode
                ? __("Les avis sont récupérés depuis Google Places API et mis en cache 12h.", "g2rd")
                : __("Activez pour remplacer ce témoignage par vos avis Google Business.", "g2rd")
            }
            checked={!!googleMode}
            onChange={(v) => setAttributes({ googleMode: v })}
            __nextHasNoMarginBottom
          />
          {googleMode && (
            <>
              <TextControl
                label={__("Place ID Google", "g2rd")}
                value={googlePlaceId}
                onChange={(v) => setAttributes({ googlePlaceId: v.trim() })}
                placeholder="ChIJxxxxxxxxxxxxxxxxxx"
                help={
                  <span>
                    {__("Trouvez votre Place ID sur ", "g2rd")}
                    <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder" target="_blank" rel="noreferrer">
                      Google Place ID Finder
                    </a>
                    .
                  </span>
                }
                __nextHasNoMarginBottom
              />
              <RangeControl
                label={__("Note minimum à afficher", "g2rd")}
                value={googleMinRating}
                onChange={(v) => setAttributes({ googleMinRating: v })}
                min={1}
                max={5}
                __nextHasNoMarginBottom
              />
              <RangeControl
                label={__("Nombre d'avis maximum", "g2rd")}
                value={googleMaxReviews}
                onChange={(v) => setAttributes({ googleMaxReviews: v })}
                min={1}
                max={5}
                __nextHasNoMarginBottom
              />
              <p style={{ fontSize: "11px", color: "#757575", marginTop: "8px" }}>
                {__("La clé API Google Maps se configure dans Options G2RD → Intégrations.", "g2rd")}
              </p>
            </>
          )}
        </PanelBody>

        {/* ── Panneaux manuels (masqués en mode Google) ── */}
        {!googleMode && (
          <>
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
              <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>{__("Citation", "g2rd")}</p>
              <ColorPicker
                color={quoteColor}
                onChangeComplete={(val) => setAttributes({ quoteColor: val.hex })}
                disableAlpha
              />
              <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>{__("Accent (guillemets)", "g2rd")}</p>
              <ColorPicker
                color={accentColor}
                onChangeComplete={(val) => setAttributes({ accentColor: val.hex })}
                disableAlpha
              />
              <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>{__("Étoiles", "g2rd")}</p>
              <ColorPicker
                color={starColor}
                onChangeComplete={(val) => setAttributes({ starColor: val.hex })}
                disableAlpha
              />
              <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>{__("Auteur", "g2rd")}</p>
              <ColorPicker
                color={authorColor}
                onChangeComplete={(val) => setAttributes({ authorColor: val.hex })}
                disableAlpha
              />
              <p style={{ fontWeight: 600, marginTop: 12, marginBottom: 4 }}>{__("Rôle / Entreprise", "g2rd")}</p>
              <ColorPicker
                color={roleColor}
                onChangeComplete={(val) => setAttributes({ roleColor: val.hex })}
                disableAlpha
              />
            </PanelBody>
          </>
        )}

      </InspectorControls>

      {/* ── Canvas ── */}
      {googleMode ? (
        <div {...blockProps} style={{ padding: "1.5rem" }}>
          <GooglePreview
            placeId={googlePlaceId}
            minRating={googleMinRating}
            max={googleMaxReviews}
            colors={colorSet}
          />
        </div>
      ) : (
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
      )}
    </>
  );
}
