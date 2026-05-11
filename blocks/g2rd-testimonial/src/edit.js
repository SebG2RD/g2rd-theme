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
  RangeControl,
  ToggleControl,
  Button,
  TextControl,
  Spinner,
  Notice,
} from "@wordpress/components";
import { useState, useEffect } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";

/* ── Icônes de layout (SVG inline) ──────────────────────────────────────── */

function LayoutIcon( { type, active } ) {
  const c = active ? "#fff" : "#666";
  const icons = {
    grid: (
      <svg width="22" height="16" viewBox="0 0 22 16" fill="none">
        <rect x="0" y="0" width="9" height="7" rx="1.5" fill={c} />
        <rect x="13" y="0" width="9" height="7" rx="1.5" fill={c} />
        <rect x="0" y="9" width="9" height="7" rx="1.5" fill={c} />
        <rect x="13" y="9" width="9" height="7" rx="1.5" fill={c} />
      </svg>
    ),
    list: (
      <svg width="22" height="16" viewBox="0 0 22 16" fill="none">
        <rect x="0" y="0" width="22" height="4" rx="1.5" fill={c} />
        <rect x="0" y="6" width="22" height="4" rx="1.5" fill={c} />
        <rect x="0" y="12" width="22" height="4" rx="1.5" fill={c} />
      </svg>
    ),
    carousel: (
      <svg width="22" height="16" viewBox="0 0 22 16" fill="none">
        <rect x="0" y="3" width="3" height="10" rx="1" fill={c} opacity="0.4" />
        <rect x="4" y="0" width="14" height="16" rx="2" fill={c} />
        <rect x="19" y="3" width="3" height="10" rx="1" fill={c} opacity="0.4" />
      </svg>
    ),
    masonry: (
      <svg width="22" height="16" viewBox="0 0 22 16" fill="none">
        <rect x="0" y="0" width="9" height="10" rx="1.5" fill={c} />
        <rect x="13" y="0" width="9" height="6" rx="1.5" fill={c} />
        <rect x="0" y="12" width="9" height="4" rx="1.5" fill={c} />
        <rect x="13" y="8" width="9" height="8" rx="1.5" fill={c} />
      </svg>
    ),
    marquee: (
      <svg width="22" height="16" viewBox="0 0 22 16" fill="none">
        <rect x="0"  y="3" width="5" height="10" rx="1.5" fill={c} opacity="0.45" />
        <rect x="6.5" y="3" width="5" height="10" rx="1.5" fill={c} />
        <rect x="13" y="3" width="5" height="10" rx="1.5" fill={c} />
        <rect x="19.5" y="3" width="5" height="10" rx="1.5" fill={c} opacity="0.45" />
        <path d="M20.5 8 L22 6.5 L22 9.5 Z" fill={c} opacity="0.7" />
      </svg>
    ),
  };
  return icons[ type ] || null;
}

/* ── Miniature style de carte ────────────────────────────────────────────── */

function CardStyleMini( { type } ) {
  const base = {
    borderRadius: 4,
    height: 30,
    marginBottom: 5,
    display: "flex",
    alignItems: "center",
    padding: "0 8px",
    gap: 6,
    boxSizing: "border-box",
  };
  const styles = {
    shadow:   { ...base, background: "#fff", boxShadow: "0 2px 8px rgba(0,0,0,0.18)" },
    flat:     { ...base, background: "#f3f4f6" },
    bordered: { ...base, background: "#fff", border: "1.5px solid #ccc" },
    glass:    { ...base, background: "rgba(200,215,255,0.3)", border: "1px solid rgba(255,255,255,0.7)", boxShadow: "0 2px 8px rgba(0,0,0,0.06)" },
  };
  return (
    <div style={ styles[ type ] || base }>
      <div style={ { flex: 1, height: 3, borderRadius: 2, background: "rgba(0,0,0,0.15)" } } />
      <div style={ { width: 12, height: 12, borderRadius: "50%", background: "rgba(0,0,0,0.18)" } } />
    </div>
  );
}

/* ── StarRating ──────────────────────────────────────────────────────────── */

const StarRating = ( { rating, color } ) => (
  <div className="g2rd-testimonial__stars" style={ { color } }>
    { [ 1, 2, 3, 4, 5 ].map( ( star ) => (
      <span
        key={ star }
        className={ `dashicons dashicons-${ star <= rating ? "star-filled" : "star-empty" }` }
        style={ { color } }
      />
    ) ) }
  </div>
);

/* ── Prévisualisation Google (éditeur) ───────────────────────────────────── */

function GooglePreview( { placeId, minRating, max, colors, layout, columns, showHeader, showAvatar, showDate, showAuthorLink, cardStyle, maxTextLength, highlightFirst, showBusinessLink } ) {
  const [ state, setState ] = useState( { loading: false, data: null, error: null } );

  useEffect( () => {
    if ( ! placeId ) { setState( { loading: false, data: null, error: null } ); return; }
    setState( { loading: true, data: null, error: null } );
    apiFetch( {
      path: `/g2rd/v1/google-reviews?place_id=${ encodeURIComponent( placeId ) }&min_rating=${ minRating }&max=${ max }`,
    } )
      .then( ( data ) => setState( { loading: false, data, error: null } ) )
      .catch( ( err ) => setState( { loading: false, data: null, error: err.message || __( "Erreur de chargement.", "g2rd" ) } ) );
  }, [ placeId, minRating, max ] );

  if ( ! placeId ) {
    return (
      <div style={ { textAlign: "center", padding: "2rem", opacity: 0.5 } }>
        <span className="dashicons dashicons-google" style={ { fontSize: "2rem", height: "2rem", width: "2rem" } } />
        <p style={ { marginTop: "0.5rem" } }>{ __( "Renseignez votre Place ID pour prévisualiser.", "g2rd" ) }</p>
      </div>
    );
  }
  if ( state.loading ) return <div style={ { display: "flex", justifyContent: "center", padding: "2rem" } }><Spinner /></div>;
  if ( state.error )   return <Notice status="error" isDismissible={ false }>{ state.error }</Notice>;
  if ( ! state.data?.reviews?.length ) return <Notice status="warning" isDismissible={ false }>{ __( "Aucun avis trouvé.", "g2rd" ) }</Notice>;

  const { data } = state;
  const cols = columns || 3;
  const truncate = ( t ) => maxTextLength > 0 && t.length > maxTextLength ? t.slice( 0, maxTextLength ) + "…" : t;

  const gridStyle = layout === "list"
    ? { display: "grid", gridTemplateColumns: "1fr", gap: "1rem", maxWidth: 640 }
    : layout === "carousel"
    ? { display: "flex", overflowX: "auto", gap: "1rem", paddingBottom: 4 }
    : layout === "masonry"
    ? { columns: cols, columnGap: "1rem" }
    : { display: "grid", gridTemplateColumns: `repeat(${ cols }, 1fr)`, gap: "1rem" };

  const cardBase = { padding: "1rem 1.1rem", borderRadius: `${ colors.radius || 8 }px`, display: "flex", flexDirection: "column", gap: "0.5rem" };
  const cardStyles = {
    shadow:   { ...cardBase, background: colors.bg || "#fff", boxShadow: "0 4px 20px rgba(0,0,0,0.09)" },
    flat:     { ...cardBase, background: "#f7f8fa" },
    bordered: { ...cardBase, background: "#fff", border: "1px solid rgba(0,0,0,0.12)" },
    glass:    { ...cardBase, background: "rgba(235,240,255,0.5)", border: "1px solid rgba(255,255,255,0.7)", backdropFilter: "blur(8px)" },
  };

  return (
    <div>
      { showHeader !== false && (
        <div style={ { display: "flex", alignItems: "center", gap: "0.75rem", marginBottom: "1rem" } }>
          { showBusinessLink && data.place_url
            ? (
              <a
                href={ data.place_url }
                target="_blank"
                rel="noreferrer"
                style={ { display: "flex", alignItems: "center", gap: "0.75rem", textDecoration: "none", color: "inherit" } }
                title={ __( "Voir sur Google Business", "g2rd" ) }
              >
                <strong style={ { fontSize: "1.4rem" } }>★ { data.overall_rating.toFixed( 1 ) }</strong>
                <span style={ { opacity: 0.6, fontSize: "0.85rem" } }>{ data.total_ratings } { __( "avis Google", "g2rd" ) }</span>
                <span style={ { fontSize: "0.72rem", opacity: 0.5 } }>↗</span>
              </a>
            )
            : (
              <>
                <strong style={ { fontSize: "1.4rem" } }>★ { data.overall_rating.toFixed( 1 ) }</strong>
                <span style={ { opacity: 0.6, fontSize: "0.85rem" } }>{ data.total_ratings } { __( "avis Google", "g2rd" ) }</span>
              </>
            )
          }
        </div>
      ) }
      <div style={ gridStyle }>
        { data.reviews.map( ( r, i ) => (
          <div
            key={ i }
            style={ {
              ...( cardStyles[ cardStyle || "shadow" ] || cardBase ),
              ...( layout === "carousel" ? { minWidth: 260, flex: "0 0 auto" } : {} ),
              ...( layout === "masonry"  ? { breakInside: "avoid", marginBottom: "1rem" } : {} ),
              ...( i === 0 && highlightFirst ? { border: `2px solid ${ colors.accent || "#D4A373" }` } : {} ),
            } }
          >
            <div className="g2rd-testimonial__stars" style={ { color: colors.star || "#D4A373", display: "flex", gap: 2 } }>
              { [ 1, 2, 3, 4, 5 ].map( ( s ) => (
                <span key={ s } className={ `dashicons dashicons-${ s <= r.rating ? "star-filled" : "star-empty" }` } />
              ) ) }
            </div>
            <p style={ { fontStyle: "italic", color: colors.quote || "#2F425D", margin: 0, lineHeight: 1.6, fontSize: "0.875rem" } }>
              { truncate( r.text ) }
            </p>
            <div style={ { display: "flex", alignItems: "center", gap: "0.5rem", marginTop: "auto", paddingTop: "0.6rem", borderTop: "1px solid rgba(0,0,0,0.07)" } }>
              { showAvatar !== false && (
                r.avatar
                  ? <img src={ r.avatar } alt={ r.author } style={ { width: 32, height: 32, borderRadius: "50%", objectFit: "cover" } } />
                  : <div style={ { width: 32, height: 32, borderRadius: "50%", background: colors.accent || "#D4A373", display: "flex", alignItems: "center", justifyContent: "center", color: "#fff", fontWeight: 700, fontSize: "0.75rem" } }>{ ( r.author || "A" )[ 0 ] }</div>
              ) }
              <div>
                { showAuthorLink && r.author_url
                  ? <a href={ r.author_url } target="_blank" rel="noreferrer" style={ { display: "block", fontSize: "0.8rem", fontWeight: 600, color: colors.author || "#2F425D", textDecoration: "none" } }>{ r.author }</a>
                  : <strong style={ { display: "block", fontSize: "0.8rem", color: colors.author || "#2F425D" } }>{ r.author }</strong>
                }
                { showDate !== false && <span style={ { fontSize: "0.72rem", color: colors.role || "#D4A373" } }>{ r.relative_time }</span> }
              </div>
            </div>
          </div>
        ) ) }
      </div>
    </div>
  );
}

/* ── Edit principal ───────────────────────────────────────────────────────── */

export default function Edit( { attributes, setAttributes } ) {
  const {
    quote, authorName, authorRole, rating,
    avatarUrl, avatarId, avatarAlt,
    quoteColor, authorColor, roleColor, starColor, accentColor,
    backgroundColor, borderRadius, hasShadow, layout,
    googleMode, googlePlaceId, googleMinRating, googleMaxReviews,
    googleLayout, googleColumns, googleCardStyle,
    googleShowHeader, googleShowAvatar, googleShowDate,
    googleShowAuthorLink, googleMaxTextLength, googleHighlightFirst,
    googleMarqueeSpeed, googleShowBusinessLink, marqueePauseButton,
  } = attributes;

  const blockProps = useBlockProps( {
    className: googleMode
      ? "g2rd-testimonial g2rd-testimonial--google"
      : `g2rd-testimonial g2rd-testimonial--${ layout }`,
  } );

  const colorSet = {
    bg: backgroundColor, radius: borderRadius,
    shadow: hasShadow ? "0 4px 24px rgba(0,0,0,0.08)" : "none",
    star: starColor, quote: quoteColor, author: authorColor, role: roleColor, accent: accentColor,
  };

  /* Style des boutons picker actif/inactif */
  const pickerBtn = ( active ) => ( {
    display: "flex",
    flexDirection: "column",
    alignItems: "center",
    gap: 5,
    padding: "9px 4px 7px",
    border: `2px solid ${ active ? "#2f425d" : "#e0e0e0" }`,
    borderRadius: 7,
    background: active ? "#2f425d" : "#fafafa",
    color: active ? "#fff" : "#444",
    cursor: "pointer",
    fontSize: 11,
    fontWeight: 600,
    transition: "all .15s ease",
    lineHeight: 1.2,
  } );

  const sectionLabel = {
    fontSize: 11,
    fontWeight: 600,
    color: "#757575",
    textTransform: "uppercase",
    letterSpacing: ".5px",
    margin: "10px 0 8px",
    display: "block",
  };

  const divider = { borderTop: "1px solid #e8e8e8", margin: "12px 0 0" };

  return (
    <>
      <InspectorControls>

        { /* ── Panneau Avis Google ── */ }
        <PanelBody title={ __( "Avis Google Business", "g2rd" ) } initialOpen={ false }>
          <ToggleControl
            label={ __( "Afficher les avis Google", "g2rd" ) }
            help={
              googleMode
                ? __( "Les avis sont récupérés depuis Google Places API et mis en cache 12h.", "g2rd" )
                : __( "Activez pour remplacer ce témoignage par vos avis Google Business.", "g2rd" )
            }
            checked={ !! googleMode }
            onChange={ ( v ) => setAttributes( { googleMode: v } ) }
            __nextHasNoMarginBottom
          />

          { googleMode && (
            <>
              { /* ─ Identifiant ─ */ }
              <TextControl
                label={ __( "Place ID Google", "g2rd" ) }
                value={ googlePlaceId }
                onChange={ ( v ) => setAttributes( { googlePlaceId: v.trim() } ) }
                __next40pxDefaultSize
                __nextHasNoMarginBottom
                placeholder="ChIJxxxxxxxxxxxxxxxxxx"
                help={
                  <span>
                    { __( "Trouvez votre Place ID sur ", "g2rd" ) }
                    <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder" target="_blank" rel="noreferrer">
                      Google Place ID Finder
                    </a>.
                  </span>
                }
                __nextHasNoMarginBottom
              />
              <RangeControl
                label={ __( "Note minimum", "g2rd" ) }
                value={ googleMinRating }
                onChange={ ( v ) => setAttributes( { googleMinRating: v } ) }
                min={ 1 } max={ 5 }
                __nextHasNoMarginBottom
              />
              <RangeControl
                label={ __( "Nombre d'avis à afficher (max 5)", "g2rd" ) }
                value={ googleMaxReviews }
                onChange={ ( v ) => setAttributes( { googleMaxReviews: v } ) }
                min={ 1 } max={ 5 }
                __nextHasNoMarginBottom
              />
              <Notice status="info" isDismissible={ false } style={ { marginTop: 8 } }>
                { __( "L'API Google Places retourne au maximum 5 avis. Pour un mode marquee avec tous vos avis, utilisez des témoignages manuels.", "g2rd" ) }
              </Notice>

              { /* ─ Disposition ─ */ }
              <div style={ divider }>
                <span style={ sectionLabel }>Disposition</span>
                <div style={ { display: "grid", gridTemplateColumns: "1fr 1fr", gap: 6 } }>
                  { [
                    { value: "grid",     label: "Grille" },
                    { value: "list",     label: "Liste" },
                    { value: "carousel", label: "Carrousel" },
                    { value: "masonry",  label: "Maçonnerie" },
                    { value: "marquee",  label: "Marquee", fullWidth: true },
                  ].map( ( { value, label, fullWidth } ) => {
                    const active = ( googleLayout || "grid" ) === value;
                    return (
                      <button
                        key={ value }
                        type="button"
                        style={ { ...pickerBtn( active ), ...( fullWidth ? { gridColumn: "1 / -1" } : {} ) } }
                        onClick={ () => setAttributes( { googleLayout: value } ) }
                      >
                        <LayoutIcon type={ value } active={ active } />
                        { label }
                      </button>
                    );
                  } ) }
                </div>
              </div>

              { [ "grid", "carousel", "masonry", "marquee" ].includes( googleLayout || "grid" ) && googleLayout !== "list" && (
                <RangeControl
                  label={ __( "Colonnes visibles", "g2rd" ) }
                  value={ googleColumns || 3 }
                  onChange={ ( v ) => setAttributes( { googleColumns: v } ) }
                  min={ 1 } max={ 4 }
                  __nextHasNoMarginBottom
                />
              ) }

              { ( googleLayout === "marquee" ) && (
                <RangeControl
                  label={ __( "Vitesse du défilement (s)", "g2rd" ) }
                  value={ googleMarqueeSpeed || 40 }
                  onChange={ ( v ) => setAttributes( { googleMarqueeSpeed: v } ) }
                  min={ 5 } max={ 120 } step={ 5 }
                  help={ __( "Durée d'un cycle complet. Faible = rapide, élevé = lent.", "g2rd" ) }
                  __nextHasNoMarginBottom
                />
              ) }

              { ( googleLayout === "marquee" ) && (
                <ToggleControl
                  label={ __( "Afficher le bouton pause", "g2rd" ) }
                  help={ __( "RGAA 13.2 — requis si animation > 5 secondes", "g2rd" ) }
                  checked={ marqueePauseButton !== false }
                  onChange={ ( v ) => setAttributes( { marqueePauseButton: v } ) }
                  __nextHasNoMarginBottom
                />
              ) }

              { /* ─ Style des cartes ─ */ }
              <div style={ divider }>
                <span style={ sectionLabel }>Style des cartes</span>
                <div style={ { display: "grid", gridTemplateColumns: "1fr 1fr", gap: 6 } }>
                  { [
                    { value: "shadow",   label: "Ombre" },
                    { value: "flat",     label: "Plat" },
                    { value: "bordered", label: "Bordure" },
                    { value: "glass",    label: "Verre" },
                  ].map( ( { value, label } ) => {
                    const active = ( googleCardStyle || "shadow" ) === value;
                    return (
                      <button key={ value } type="button" style={ pickerBtn( active ) } onClick={ () => setAttributes( { googleCardStyle: value } ) }>
                        <CardStyleMini type={ value } />
                        { label }
                      </button>
                    );
                  } ) }
                </div>
              </div>

              { /* ─ Contenu ─ */ }
              <div style={ divider }>
                <span style={ sectionLabel }>Contenu</span>
                <ToggleControl
                  label={ __( "Note globale Google", "g2rd" ) }
                  checked={ googleShowHeader !== false }
                  onChange={ ( v ) => setAttributes( { googleShowHeader: v } ) }
                  __nextHasNoMarginBottom
                />
                { googleShowHeader !== false && (
                  <ToggleControl
                    label={ __( "Lien vers Google Business", "g2rd" ) }
                    checked={ !! googleShowBusinessLink }
                    onChange={ ( v ) => setAttributes( { googleShowBusinessLink: v } ) }
                    help={ __( "La note globale devient un lien cliquable vers votre fiche Google Business.", "g2rd" ) }
                    __nextHasNoMarginBottom
                  />
                ) }
                <ToggleControl
                  label={ __( "Avatar de l'auteur", "g2rd" ) }
                  checked={ googleShowAvatar !== false }
                  onChange={ ( v ) => setAttributes( { googleShowAvatar: v } ) }
                  __nextHasNoMarginBottom
                />
                <ToggleControl
                  label={ __( "Date de l'avis", "g2rd" ) }
                  checked={ googleShowDate !== false }
                  onChange={ ( v ) => setAttributes( { googleShowDate: v } ) }
                  __nextHasNoMarginBottom
                />
                <ToggleControl
                  label={ __( "Lien profil Google de l'auteur", "g2rd" ) }
                  checked={ !! googleShowAuthorLink }
                  onChange={ ( v ) => setAttributes( { googleShowAuthorLink: v } ) }
                  help={ __( "Rend le nom cliquable vers son profil Google Maps.", "g2rd" ) }
                  __nextHasNoMarginBottom
                />
                <ToggleControl
                  label={ __( "Mettre en avant le 1er avis", "g2rd" ) }
                  checked={ !! googleHighlightFirst }
                  onChange={ ( v ) => setAttributes( { googleHighlightFirst: v } ) }
                  help={ __( "Le premier avis s'affiche en pleine largeur (mode grille).", "g2rd" ) }
                  __nextHasNoMarginBottom
                />
              </div>

              { /* ─ Avancé ─ */ }
              <div style={ divider }>
                <span style={ sectionLabel }>Avancé</span>
                <RangeControl
                  label={ __( "Longueur max du texte", "g2rd" ) }
                  value={ googleMaxTextLength || 0 }
                  onChange={ ( v ) => setAttributes( { googleMaxTextLength: v } ) }
                  min={ 0 } max={ 500 } step={ 25 }
                  help={ __( "0 = illimité. Tronque les longs avis pour uniformiser.", "g2rd" ) }
                  __nextHasNoMarginBottom
                />
              </div>

              <p style={ { fontSize: "11px", color: "#9ca3af", marginTop: "12px", paddingTop: 10, borderTop: "1px solid #e8e8e8" } }>
                { __( "Clé API Google Maps → Options G2RD › Intégrations.", "g2rd" ) }
              </p>
            </>
          ) }
        </PanelBody>

        { /* ── Panneaux manuels (masqués en mode Google) ── */ }
        { ! googleMode && (
          <>
            <PanelBody title={ __( "Notation & Mise en page", "g2rd" ) } initialOpen>
              <RangeControl
                label={ __( "Nombre d'étoiles", "g2rd" ) }
                value={ rating }
                onChange={ ( val ) => setAttributes( { rating: val } ) }
                min={ 1 } max={ 5 }
              />
              <div style={ { marginTop: 8 } }>
                <p style={ { marginBottom: 6, fontSize: 13, fontWeight: 500 } }>{ __( "Style", "g2rd" ) }</p>
                <div style={ { display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 6 } }>
                  { [
                    { value: "card",    label: "Carte" },
                    { value: "simple",  label: "Simple" },
                    { value: "minimal", label: "Minimal" },
                  ].map( ( { value, label } ) => {
                    const active = layout === value;
                    return (
                      <button
                        key={ value }
                        type="button"
                        onClick={ () => setAttributes( { layout: value } ) }
                        style={ pickerBtn( active ) }
                      >
                        { label }
                      </button>
                    );
                  } ) }
                </div>
              </div>
              <RangeControl
                label={ __( "Rayon de bordure (px)", "g2rd" ) }
                value={ borderRadius }
                onChange={ ( val ) => setAttributes( { borderRadius: val } ) }
                min={ 0 } max={ 32 }
              />
              <ToggleControl
                label={ __( "Ombre portée", "g2rd" ) }
                checked={ hasShadow }
                onChange={ ( val ) => setAttributes( { hasShadow: val } ) }
              />
            </PanelBody>

            <PanelBody title={ __( "Avatar", "g2rd" ) } initialOpen={ false }>
              <MediaUploadCheck>
                <MediaUpload
                  onSelect={ ( media ) =>
                    setAttributes( {
                      avatarUrl: media.url,
                      avatarId:  media.id,
                      avatarAlt: media.alt || "",
                    } )
                  }
                  allowedTypes={ [ "image" ] }
                  value={ avatarId }
                  render={ ( { open } ) => (
                    <div>
                      { avatarUrl ? (
                        <div style={ { marginBottom: "8px" } }>
                          <img
                            src={ avatarUrl }
                            alt={ avatarAlt }
                            style={ { width: "60px", height: "60px", borderRadius: "50%", objectFit: "cover", display: "block", marginBottom: "8px" } }
                          />
                          <Button isDestructive size="small" onClick={ () => setAttributes( { avatarUrl: "", avatarId: 0, avatarAlt: "" } ) }>
                            { __( "Supprimer", "g2rd" ) }
                          </Button>
                        </div>
                      ) : (
                        <Button variant="secondary" size="small" onClick={ open }>
                          { __( "Choisir un avatar", "g2rd" ) }
                        </Button>
                      ) }
                    </div>
                  ) }
                />
              </MediaUploadCheck>
            </PanelBody>

          </>
        ) }

        { /* ── Couleurs (mode manuel ET Google) ── */ }
        <PanelColorSettings
          title={ __( "Couleurs", "g2rd" ) }
          initialOpen={ false }
          colorSettings={ [
            { value: backgroundColor, onChange: ( v ) => setAttributes( { backgroundColor: v || "" } ), label: __( "Fond", "g2rd" ) },
            { value: quoteColor,      onChange: ( v ) => setAttributes( { quoteColor:      v || "" } ), label: __( "Citation", "g2rd" ) },
            { value: accentColor,     onChange: ( v ) => setAttributes( { accentColor:     v || "" } ), label: __( "Accent", "g2rd" ) },
            { value: starColor,       onChange: ( v ) => setAttributes( { starColor:       v || "" } ), label: __( "Étoiles", "g2rd" ) },
            { value: authorColor,     onChange: ( v ) => setAttributes( { authorColor:     v || "" } ), label: __( "Auteur", "g2rd" ) },
            { value: roleColor,       onChange: ( v ) => setAttributes( { roleColor:       v || "" } ), label: __( "Rôle / Date", "g2rd" ) },
          ] }
        />

      </InspectorControls>

      { /* ── Canvas ── */ }
      { googleMode ? (
        <div { ...blockProps } style={ {
          padding: "1.5rem",
          "--g2rd-t-bg":     backgroundColor,
          "--g2rd-t-radius": `${ borderRadius }px`,
          "--g2rd-t-shadow": hasShadow ? "0 4px 24px rgba(0,0,0,0.08)" : "none",
          "--g2rd-t-star":   starColor,
          "--g2rd-t-quote":  quoteColor,
          "--g2rd-t-author": authorColor,
          "--g2rd-t-role":   roleColor,
          "--g2rd-t-accent": accentColor,
        } }>
          <GooglePreview
            placeId={ googlePlaceId }
            minRating={ googleMinRating }
            max={ googleMaxReviews }
            colors={ colorSet }
            layout={ googleLayout || "grid" }
            columns={ googleColumns || 3 }
            showHeader={ googleShowHeader }
            showAvatar={ googleShowAvatar }
            showDate={ googleShowDate }
            showAuthorLink={ googleShowAuthorLink }
            cardStyle={ googleCardStyle || "shadow" }
            maxTextLength={ googleMaxTextLength || 0 }
            highlightFirst={ googleHighlightFirst }
            showBusinessLink={ !! googleShowBusinessLink }
          />
        </div>
      ) : (
        <div
          { ...blockProps }
          style={ {
            backgroundColor,
            borderRadius: `${ borderRadius }px`,
            boxShadow: hasShadow ? "0 4px 24px rgba(0,0,0,0.08)" : "none",
            padding: "2rem",
          } }
        >
          <StarRating rating={ rating } color={ starColor } />

          <div
            className="g2rd-testimonial__accent"
            style={ { color: accentColor, fontSize: "3rem", lineHeight: "0.8", fontWeight: 800, marginTop: "0.5rem" } }
          >
            "
          </div>

          <RichText
            tagName="p"
            className="g2rd-testimonial__quote"
            value={ quote }
            onChange={ ( val ) => setAttributes( { quote: val } ) }
            placeholder={ __( "Témoignage du client…", "g2rd" ) }
            style={ { color: quoteColor, fontStyle: "italic", lineHeight: "1.7", marginTop: "0.5rem" } }
          />

          <div className="g2rd-testimonial__author" style={ { display: "flex", alignItems: "center", gap: "0.75rem", marginTop: "1.25rem" } }>
            { avatarUrl && (
              <img src={ avatarUrl } alt={ avatarAlt } style={ { width: "48px", height: "48px", borderRadius: "50%", objectFit: "cover" } } />
            ) }
            { ! avatarUrl && (
              <div style={ { width: "48px", height: "48px", borderRadius: "50%", backgroundColor: accentColor, display: "flex", alignItems: "center", justifyContent: "center", color: "#fff", fontWeight: 700, fontSize: "1.1rem" } }>
                { authorName?.[ 0 ] || "A" }
              </div>
            ) }
            <div>
              <RichText
                tagName="strong"
                className="g2rd-testimonial__name"
                value={ authorName }
                onChange={ ( val ) => setAttributes( { authorName: val } ) }
                placeholder={ __( "Prénom Nom", "g2rd" ) }
                style={ { color: authorColor, display: "block", fontWeight: 700 } }
              />
              <RichText
                tagName="span"
                className="g2rd-testimonial__role"
                value={ authorRole }
                onChange={ ( val ) => setAttributes( { authorRole: val } ) }
                placeholder={ __( "Rôle — Entreprise", "g2rd" ) }
                style={ { color: roleColor, fontSize: "0.875rem" } }
              />
            </div>
          </div>
        </div>
      ) }
    </>
  );
}
