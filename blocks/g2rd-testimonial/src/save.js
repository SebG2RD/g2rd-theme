import { useBlockProps, RichText } from "@wordpress/block-editor";

const StarRating = ({ rating, color }) => (
  <div className="g2rd-testimonial__stars" style={{ color }}>
    {[1, 2, 3, 4, 5].map((star) => (
      <span
        key={star}
        className={`dashicons dashicons-${star <= rating ? "star-filled" : "star-empty"}`}
        aria-hidden="true"
      />
    ))}
    <span className="screen-reader-text">{rating}/5</span>
  </div>
);

export default function Save({ attributes }) {
  const {
    quote, authorName, authorRole, rating,
    avatarUrl, avatarAlt,
    quoteColor, authorColor, roleColor, starColor, accentColor,
    backgroundColor, borderRadius, hasShadow, layout,
    googleMode, googlePlaceId, googleMinRating, googleMaxReviews,
    googleLayout, googleColumns, googleCardStyle,
    googleShowHeader, googleShowAvatar, googleShowDate,
    googleShowAuthorLink, googleMaxTextLength, googleHighlightFirst,
    googleMarqueeSpeed,
  } = attributes;

  /* ── Mode Google Reviews ─────────────────────────────────────────────── */
  if ( googleMode ) {
    const blockProps = useBlockProps.save({
      className: "g2rd-testimonial g2rd-testimonial--google",
      "data-google-place-id":          googlePlaceId || "",
      "data-google-min-rating":        String( googleMinRating ),
      "data-google-max-reviews":       String( googleMaxReviews ),
      "data-google-layout":            googleLayout            || "grid",
      "data-google-columns":           String( googleColumns   || 3 ),
      "data-google-card-style":        googleCardStyle         || "shadow",
      "data-google-show-header":       String( googleShowHeader  !== false ),
      "data-google-show-avatar":       String( googleShowAvatar  !== false ),
      "data-google-show-date":         String( googleShowDate    !== false ),
      "data-google-show-author-link":  String( !! googleShowAuthorLink ),
      "data-google-max-text":          String( googleMaxTextLength || 0 ),
      "data-google-highlight-first":   String( !! googleHighlightFirst ),
      "data-google-marquee-speed":     String( googleMarqueeSpeed || 40 ),
      "aria-busy": "true",
      style: {
        "--g2rd-t-bg":     backgroundColor,
        "--g2rd-t-radius": `${ borderRadius }px`,
        "--g2rd-t-shadow": hasShadow ? "0 4px 24px rgba(0,0,0,0.08)" : "none",
        "--g2rd-t-star":   starColor,
        "--g2rd-t-quote":  quoteColor,
        "--g2rd-t-author": authorColor,
        "--g2rd-t-role":   roleColor,
        "--g2rd-t-accent": accentColor,
      },
    });

    return (
      <div { ...blockProps }>
        { /* Squelettes de chargement — remplacés par view.js */ }
        <div className="g2rd-testimonial__google-header is-skeleton" aria-hidden="true" />
        { Array.from({ length: Math.min( googleMaxReviews, 5 ) }).map( ( _, i ) => (
          <div key={ i } className="g2rd-testimonial__card is-skeleton" aria-hidden="true" />
        ) ) }
      </div>
    );
  }

  /* ── Mode manuel (comportement original inchangé) ────────────────────── */
  const blockProps = useBlockProps.save({
    className: `g2rd-testimonial g2rd-testimonial--${ layout }`,
    style: {
      backgroundColor,
      borderRadius: `${ borderRadius }px`,
      boxShadow: hasShadow ? "0 4px 24px rgba(0,0,0,0.08)" : "none",
      padding: "2rem",
    },
  });

  return (
    <div {...blockProps}>
      <StarRating rating={rating} color={starColor} />

      <div
        className="g2rd-testimonial__accent"
        style={{ color: accentColor, fontSize: "3rem", lineHeight: "0.8", fontWeight: 800, marginTop: "0.5rem" }}
        aria-hidden="true"
      >
        "
      </div>

      <RichText.Content
        tagName="p"
        className="g2rd-testimonial__quote"
        value={quote}
        style={{ color: quoteColor, fontStyle: "italic", lineHeight: "1.7", marginTop: "0.5rem" }}
      />

      <div className="g2rd-testimonial__author">
        {avatarUrl && (
          <img
            src={avatarUrl}
            alt={avatarAlt}
            className="g2rd-testimonial__avatar"
            width="48"
            height="48"
            loading="lazy"
          />
        )}
        {!avatarUrl && (
          <div
            className="g2rd-testimonial__avatar-placeholder"
            style={{ backgroundColor: accentColor }}
            aria-hidden="true"
          >
            {authorName?.[0] || "A"}
          </div>
        )}
        <div className="g2rd-testimonial__author-info">
          <RichText.Content
            tagName="strong"
            className="g2rd-testimonial__name"
            value={authorName}
            style={{ color: authorColor, display: "block", fontWeight: 700 }}
          />
          <RichText.Content
            tagName="span"
            className="g2rd-testimonial__role"
            value={authorRole}
            style={{ color: roleColor, fontSize: "0.875rem" }}
          />
        </div>
      </div>
    </div>
  );
}
