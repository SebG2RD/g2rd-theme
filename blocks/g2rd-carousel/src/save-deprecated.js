import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

export default function SaveDeprecated({ attributes, className }) {
  const {
    images,
    autoplayDelay,
    showPagination,
    showNavigation,
    effect,
    slidesPerView,
    spaceBetween,
    centeredSlides,
    loop,
    grabCursor,
    coverflowRotate,
    coverflowStretch,
    coverflowDepth,
    coverflowModifier,
    title,
    description,
    showBadge,
    badgeText,
    showCaptions,
    style,
    visibleSlides,
    contentType,
    selectedPosts,
    showBoxShadow,
    height,
  } = attributes;

  const blockProps = useBlockProps.save({ className, style });

  const currentContentType = contentType || "images";
  const displayContent =
    currentContentType === "images" ? images : selectedPosts || [];

  const responsiveConfig = {
    320: {
      slidesPerView: 2,
      slidesPerGroup: 4,
      spaceBetween: 10,
      centeredSlides: false,
      effect: "slide",
      loop: false,
      autoplay: false,
      allowTouchMove: false,
      grid: { rows: 2, fill: "row" },
    },
    768: {
      slidesPerView: 2,
      slidesPerGroup: 2,
      spaceBetween: 30,
      centeredSlides: true,
      effect: effect || "slide",
      loop: true,
      allowTouchMove: true,
      autoplay: autoplayDelay ? true : false,
    },
    1024: {
      slidesPerView: Math.min(3, visibleSlides || 3),
      slidesPerGroup: 1,
      spaceBetween: spaceBetween || 30,
      centeredSlides: centeredSlides !== false,
      effect: effect || "slide",
      loop: true,
      allowTouchMove: true,
      autoplay: autoplayDelay ? true : false,
    },
    1200: {
      slidesPerView: visibleSlides || 3,
      slidesPerGroup: 1,
      spaceBetween: spaceBetween || 50,
      centeredSlides: centeredSlides !== false,
      effect: effect || "coverflow",
      loop: true,
      allowTouchMove: true,
      autoplay: autoplayDelay ? true : false,
    },
  };

  const config = {
    autoplayDelay,
    showPagination,
    showNavigation,
    effect,
    slidesPerView: visibleSlides.toString(),
    spaceBetween,
    centeredSlides,
    loop,
    grabCursor,
    coverflowRotate,
    coverflowStretch,
    coverflowDepth,
    coverflowModifier,
    showBoxShadow,
    height,
    responsiveConfig,
    ...(contentType && { contentType }),
  };

  const slidesToShow = displayContent;

  return (
    <div {...blockProps}>
      <div className="g2rd-carousel" data-config={JSON.stringify(config)}>
        <div className="carousel-header">
          {showBadge && (
            <div className="carousel-badge">
              <span className="badge-icon">✨</span>
              {badgeText}
            </div>
          )}
          <div className="carousel-title">
            <h2 className="wp-block-heading">{title}</h2>
            <p>{description}</p>
          </div>
        </div>

        <div className="carousel-container" style={{ height: `${height}px` }}>
          <div
            className="swiper"
            data-slides-per-view={visibleSlides}
            data-responsive-config={JSON.stringify(responsiveConfig)}
          >
            <div className="swiper-wrapper"></div>
          </div>
        </div>
      </div>
    </div>
  );
}
