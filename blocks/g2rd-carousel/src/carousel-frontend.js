// G2RD Carousel Frontend JavaScript
// Initialise Swiper.js pour tous les carrousels sur la page

// Compteur de tentatives pour éviter une boucle infinie si Swiper n'est pas disponible
var _swiperRetryCount = 0;
var _swiperMaxRetries = 20;

function initializeCarousels() {
  // Vérifier si Swiper est disponible
  if (typeof Swiper === "undefined") {
    if (_swiperRetryCount < _swiperMaxRetries) {
      _swiperRetryCount++;
      setTimeout(initializeCarousels, 500);
    }
    return;
  }
  _swiperRetryCount = 0;

  // Trouver tous les carrousels G2RD
  const carousels = document.querySelectorAll(".g2rd-carousel");

  carousels.forEach(function (carousel, index) {
    // Récupérer la configuration depuis les data attributes
    let config = {};
    try {
      const configData = carousel.getAttribute("data-config");
      if (configData) {
        config = JSON.parse(configData);
      }
    } catch (e) {
      console.error("G2RD Carousel: Error parsing config", e);
    }

    const swiperContainer = carousel.querySelector(".swiper");
    if (!swiperContainer) {
      console.error("G2RD Carousel: Swiper container not found");
      return;
    }

    // Helpers pour construire des slides et assurer leur présence
    const ensureWrapper = () => {
      return (
        swiperContainer.querySelector(".swiper-wrapper") ||
        (() => {
          const w = document.createElement("div");
          w.className = "swiper-wrapper";
          swiperContainer.appendChild(w);
          return w;
        })()
      );
    };

    const buildFromItems = (items, options = {}) => {
      const {
        showCaptions = true,
        showBoxShadow = true,
        contentType = "images",
      } = options;
      const wrapper = ensureWrapper();

      items.forEach((item, index) => {
        // Sur mobile seulement, ne créer que 4 slides maximum pour la grille 2x2
        const isMobile = window.innerWidth < 768;
        if (isMobile && index >= 4) {
          return;
        }

        const slide = document.createElement("div");
        slide.className = "swiper-slide";

        const slideInner = document.createElement("div");
        slideInner.className =
          "carousel-slide" + (showBoxShadow ? "" : " no-shadow");

        const makeImage = (src, alt, link) => {
          const img = document.createElement("img");
          img.className = "carousel-image";
          img.loading = "lazy";
          img.src = src || "";
          img.alt = alt || "";
          if (link) {
            const a = document.createElement("a");
            a.href = link;
            a.className = "carousel-image-link";
            a.appendChild(img);
            return a;
          }
          return img;
        };

        if (item.type === "image" || contentType === "images") {
          const node = makeImage(item.url, item.alt, item.link);
          slideInner.appendChild(node);
          if (showCaptions && item.caption) {
            const p = document.createElement("p");
            p.className = "carousel-caption";
            p.textContent = item.caption;
            slideInner.appendChild(p);
          }
        } else {
          const node = makeImage(
            item.featuredImage,
            item.featuredImageAlt,
            item.link
          );
          slideInner.appendChild(node);
          if (showCaptions) {
            const info = document.createElement("div");
            info.className = "carousel-post-info";
            const title = document.createElement("h4");
            title.className = "carousel-post-title";
            title.textContent = item.title || "";
            info.appendChild(title);
            if (item.excerpt) {
              const p = document.createElement("p");
              p.className = "carousel-post-excerpt";
              p.innerHTML = item.excerpt;
              info.appendChild(p);
            }
            slideInner.appendChild(info);
          }
        }

        slide.appendChild(slideInner);
        wrapper.appendChild(slide);
      });
    };

    const ensureSlidesReady = async () => {
      const initialSlides = swiperContainer.querySelectorAll(".swiper-slide");

      if (initialSlides.length > 0) {
        // Slides déjà rendues côté serveur : ne pas modifier le DOM ici
        return;
      }

      const {
        contentData = [],
        contentIds = [],
        contentType = "images",
        showCaptions = true,
        showBoxShadow = true,
      } = config || {};

      // 1) contentData immédiat
      if (Array.isArray(contentData) && contentData.length > 0) {
        const uniqueItems = [];
        const seenIds = new Set();

        contentData.forEach((item) => {
          if (!seenIds.has(item.id)) {
            seenIds.add(item.id);
            uniqueItems.push(item);
          }
        });

        const isMobile = window.innerWidth < 768;
        const items = isMobile ? uniqueItems.slice(0, 4) : uniqueItems;
        buildFromItems(items, { showCaptions, showBoxShadow, contentType });
        return;
      }

      // 2) contentIds + wpApiSettings.root
      if (
        contentType !== "images" &&
        Array.isArray(contentIds) &&
        contentIds.length > 0 &&
        typeof window?.wpApiSettings?.root === "string"
      ) {
        try {
          const endpointType = contentType;
          const url = `${
            window.wpApiSettings.root
          }wp/v2/${endpointType}?include=${contentIds.join(",")}&per_page=${
            contentIds.length
          }&_embed`;
          const r = await fetch(url);
          const data = await r.json();

          const uniqueItems = [];
          const seenIds = new Set();

          (data || []).forEach((post) => {
            if (!seenIds.has(post.id)) {
              seenIds.add(post.id);
              uniqueItems.push({
                type: "post",
                id: post.id,
                title: post.title?.rendered || "",
                excerpt: post.excerpt?.rendered || "",
                link: post.link || "",
                featuredImage:
                  post._embedded?.["wp:featuredmedia"]?.[0]?.source_url || "",
                featuredImageAlt:
                  post._embedded?.["wp:featuredmedia"]?.[0]?.alt_text || "",
                date: post.date || "",
              });
            }
          });

          const isMobile = window.innerWidth < 768;
          const items = isMobile ? uniqueItems.slice(0, 4) : uniqueItems;
          buildFromItems(items, { showCaptions, showBoxShadow, contentType });
          return;
        } catch (err) {
          console.warn(
            "G2RD Carousel: REST fetch fallback (include) failed",
            err
          );
        }
      }

      // 3) Dernier fallback: derniers items du CPT
      if (contentType !== "images") {
        try {
          const endpointType = contentType;
          // Sur mobile, récupérer exactement 4 publications pour la grille 2x2
          // Sur desktop, récupérer plus d'items pour avoir un carousel complet
          const perPage = window.innerWidth < 768 ? 8 : 12; // Récupérer plus pour avoir assez d'items uniques

          // Utiliser l'URL correcte de l'API WordPress
          const apiRoot = window?.wpApiSettings?.root || "/wp-json/";
          const url = `${apiRoot}wp/v2/${endpointType}?per_page=${perPage}&_embed&orderby=date&order=desc`;

          const r = await fetch(url, {
            headers: {
              Accept: "application/json",
              "Content-Type": "application/json",
            },
          });

          if (!r.ok) {
            throw new Error(`HTTP error! status: ${r.status}`);
          }

          const data = await r.json();

          const uniqueItems = [];
          const seenIds = new Set();

          (data || []).forEach((post) => {
            if (!seenIds.has(post.id)) {
              seenIds.add(post.id);
              uniqueItems.push({
                type: "post",
                id: post.id,
                title: post.title?.rendered || "",
                excerpt: post.excerpt?.rendered || "",
                link: post.link || "",
                featuredImage:
                  post._embedded?.["wp:featuredmedia"]?.[0]?.source_url || "",
                featuredImageAlt:
                  post._embedded?.["wp:featuredmedia"]?.[0]?.alt_text || "",
                date: post.date || "",
              });
            }
          });

          const isMobile = window.innerWidth < 768;
          const items = isMobile ? uniqueItems.slice(0, 4) : uniqueItems;
          buildFromItems(items, { showCaptions, showBoxShadow, contentType });
          return;
        } catch (err) {
          console.warn("G2RD Carousel: CPT fetch fallback failed", err);
        }
      }

      console.warn("G2RD Carousel: No slides available after fallbacks");
    };

    const initSwiper = () => {
      // Configuration par défaut
      const defaultConfig = {
        autoplayDelay: 3000,
        showPagination: true,
        showNavigation: true,
        effect: "slide", // Changé de coverflow à slide par défaut
        slidesPerView: "3",
        spaceBetween: 50,
        centeredSlides: true,
        loop: true,
        grabCursor: true,
        contentType: "images", // Type de contenu par défaut
        height: 400, // Hauteur par défaut
      };

      // Fusionner avec la configuration du block
      const finalConfig = { ...defaultConfig, ...config };

      // Appliquer la hauteur au conteneur si définie
      if (finalConfig.height) {
        const container = carousel.querySelector(".carousel-container");
        if (container) {
          container.style.height = `${finalConfig.height}px`;
        }
      }

      // Sécuriser pagination/navigation si les éléments DOM manquent
      const paginationEl = carousel.querySelector(".swiper-pagination");
      const navPrevEl = carousel.querySelector(".swiper-button-prev");
      const navNextEl = carousel.querySelector(".swiper-button-next");
      const showPaginationEnabled = !!(
        finalConfig.showPagination && paginationEl
      );
      const showNavigationEnabled = !!(
        finalConfig.showNavigation &&
        navPrevEl &&
        navNextEl
      );
      if (!showPaginationEnabled) {
        finalConfig.showPagination = false;
      }
      if (!showNavigationEnabled) {
        finalConfig.showNavigation = false;
      }

      // Configuration responsive pour Swiper
      const getResponsiveConfig = () => {
        const baseSlidesPerView = parseInt(finalConfig.slidesPerView) || 3;

        return {
          // Mobile (320px et plus) - GRILLE 2x2 FIXE (4 images seulement)
          320: {
            slidesPerView: 2, // 2 images par ligne
            slidesPerGroup: 2, // Groupe de 2 images pour éviter les conflits
            spaceBetween: 10, // Petit espacement sur mobile
            centeredSlides: false, // Désactivé pour affichage en grille
            effect: "slide", // Effet slide sur mobile pour de meilleures performances
            loop: false, // Pas de boucle sur mobile - affichage fixe
            autoplay: false, // Désactiver l'autoplay sur mobile
            allowTouchMove: false, // Désactiver le défilement tactile sur mobile
            simulateTouch: false, // Désactiver complètement le défilement
            touchEventsTarget: "none", // Désactiver les événements tactiles
            speed: 0, // Pas d'animation sur mobile
            grid: {
              rows: 2, // 2 rangées
              fill: "row", // Remplir par rangée
            },
          },
          // Tablette (768px et plus) - DEUX IMAGES
          768: {
            slidesPerView: 2,
            slidesPerGroup: 2, // Défile par groupe de 2 images
            spaceBetween: 30,
            centeredSlides: true, // Centrer la slide principale
            effect: finalConfig.effect,
            loop: true, // Boucle activée sur tablette
            allowTouchMove: true, // Activer le défilement tactile
            autoplay: finalConfig.autoplayDelay
              ? {
                  delay: finalConfig.autoplayDelay,
                  disableOnInteraction: false,
                  pauseOnMouseEnter: true,
                  waitForTransition: true,
                  stopOnLastSlide: false,
                  reverseDirection: false,
                }
              : false,
          },
          // Desktop (1024px et plus) - TROIS IMAGES
          1024: {
            slidesPerView: Math.min(3, baseSlidesPerView),
            slidesPerGroup: 1, // Défile une image à la fois
            spaceBetween: finalConfig.spaceBetween,
            centeredSlides: false, // Désactiver le centrage pour éviter le décalage
            effect: finalConfig.effect,
            loop: false, // Désactiver le loop pour éviter le décalage
            allowTouchMove: true, // Activer le défilement tactile
            autoplay: finalConfig.autoplayDelay
              ? {
                  delay: finalConfig.autoplayDelay,
                  disableOnInteraction: false,
                  pauseOnMouseEnter: true,
                  waitForTransition: true,
                  stopOnLastSlide: true, // Arrêter sur la dernière slide
                  reverseDirection: false,
                }
              : false,
          },
          // Grand écran (1200px et plus) - CONFIGURATION COMPLÈTE
          1200: {
            slidesPerView: baseSlidesPerView,
            slidesPerGroup: 1, // Défile une image à la fois
            spaceBetween: finalConfig.spaceBetween,
            centeredSlides: false, // Désactiver le centrage pour éviter le décalage
            effect: finalConfig.effect,
            loop: false, // Désactiver le loop pour éviter le décalage
            allowTouchMove: true, // Activer le défilement tactile
            autoplay: finalConfig.autoplayDelay
              ? {
                  delay: finalConfig.autoplayDelay,
                  disableOnInteraction: false,
                  pauseOnMouseEnter: true,
                  waitForTransition: true,
                  stopOnLastSlide: true, // Arrêter sur la dernière slide
                  reverseDirection: false,
                }
              : false,
          },
        };
      };

      // Vérifier le nombre de slides disponibles
      const slidesCount =
        swiperContainer.querySelectorAll(".swiper-slide").length;

      // Désactiver le loop s'il n'y a pas assez de slides
      if (
        slidesCount <= parseInt(finalConfig.slidesPerView) ||
        slidesCount < 3
      ) {
        finalConfig.loop = false;
      }

      // En mobile, forcer désactivation nav/pagination/autoplay/touch + loop false
      if (window.innerWidth < 768) {
        finalConfig.showNavigation = false;
        finalConfig.showPagination = false;
        finalConfig.loop = false; // Toujours désactiver le loop sur mobile
      }

      // Préparer les modules Swiper disponibles
      const preInitModules = [];
      if (Swiper.Autoplay && window.innerWidth >= 768)
        preInitModules.push(Swiper.Autoplay);
      if (Swiper.Navigation && showNavigationEnabled)
        preInitModules.push(Swiper.Navigation);
      if (Swiper.Pagination && showPaginationEnabled)
        preInitModules.push(Swiper.Pagination);
      if (Swiper.EffectCoverflow) preInitModules.push(Swiper.EffectCoverflow);
      if (Swiper.Grid) preInitModules.push(Swiper.Grid);
      const modulesForSwiper = preInitModules;

      // Initialiser Swiper
      try {
        const swiperConfig = {
          modules: modulesForSwiper,
          effect: window.innerWidth < 768 ? "slide" : finalConfig.effect,
          speed: window.innerWidth < 768 ? 0 : 800, // Pas d'animation sur mobile
          easing: "ease-out",
          slidesPerView: parseInt(finalConfig.slidesPerView) || 3,
          spaceBetween: finalConfig.spaceBetween,
          centeredSlides: window.innerWidth < 768 ? false : false, // Désactiver le centrage sur desktop
          loop: window.innerWidth < 768 ? false : finalConfig.loop,
          grabCursor: finalConfig.grabCursor,
          // Configuration simplifiée pour éviter le décalage
          loopAdditionalSlides: 0, // Pas de slides supplémentaires
          loopedSlides: 0, // Pas de slides dupliquées
          breakpoints: getResponsiveConfig(),
          pagination: (window.innerWidth < 768 ? false : showPaginationEnabled)
            ? {
                el: paginationEl,
                clickable: true,
              }
            : false,
          navigation: (window.innerWidth < 768 ? false : showNavigationEnabled)
            ? {
                nextEl: navNextEl,
                prevEl: navPrevEl,
              }
            : false,
          autoplay:
            finalConfig.autoplayDelay && window.innerWidth >= 768
              ? {
                  delay: finalConfig.autoplayDelay,
                  disableOnInteraction: false,
                  pauseOnMouseEnter: true,
                  waitForTransition: true,
                  stopOnLastSlide: false,
                  reverseDirection: false,
                }
              : false,
        };

        if (finalConfig.effect === "coverflow" && Swiper.EffectCoverflow) {
          swiperConfig.coverflowEffect = {
            rotate: finalConfig.coverflowRotate || 50,
            stretch: finalConfig.coverflowStretch || 0,
            depth: finalConfig.coverflowDepth || 200,
            modifier: finalConfig.coverflowModifier || 1,
            slideShadows: false,
            scale: 0.85,
            perspective: 1000,
          };
        }

        // Initialiser Swiper
        const swiper = new Swiper(swiperContainer, swiperConfig);

        // Vérifier que l'initialisation a réussi
        if (!swiper || !swiper.slides || swiper.slides.length === 0) {
          console.error(
            "G2RD Carousel: Swiper initialization failed or no slides found"
          );
          return;
        }

        // Stocker l'instance Swiper sur l'élément pour un accès futur
        carousel.swiperInstance = swiper;

        // RGAA 13.1 — désactiver l'autoplay si prefers-reduced-motion
        const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
        if ( reducedMotion && swiper.autoplay ) {
          swiper.autoplay.stop();
        }

        // RGAA 13.2 — bouton pause accessible si activé dans le BO
        if ( finalConfig.autoplayDelay && finalConfig.showPauseButton === true && window.innerWidth >= 768 && ! reducedMotion ) {
          const pauseBtn = document.createElement( 'button' );
          pauseBtn.type = 'button';
          pauseBtn.className = 'g2rd-carousel__pause';
          pauseBtn.setAttribute( 'aria-label', 'Mettre le défilement en pause' );
          pauseBtn.setAttribute( 'aria-pressed', 'false' );
          pauseBtn.textContent = '⏸';
          pauseBtn.addEventListener( 'click', function () {
            const paused = 'true' === pauseBtn.getAttribute( 'aria-pressed' );
            if ( paused ) {
              if ( swiper.autoplay && swiper.autoplay.start ) swiper.autoplay.start();
              else if ( carousel._manualResume ) carousel._manualResume();
              pauseBtn.setAttribute( 'aria-pressed', 'false' );
              pauseBtn.setAttribute( 'aria-label', 'Mettre le défilement en pause' );
            } else {
              if ( swiper.autoplay && swiper.autoplay.stop ) swiper.autoplay.stop();
              else if ( carousel._manualPause ) carousel._manualPause();
              pauseBtn.setAttribute( 'aria-pressed', 'true' );
              pauseBtn.setAttribute( 'aria-label', 'Reprendre le défilement' );
            }
          } );
          carousel.appendChild( pauseBtn );
        }

        // Navigation manuelle si les modules ne sont pas disponibles
        if (finalConfig.showNavigation && !Swiper.Navigation) {
          const prevButton = carousel.querySelector(".swiper-button-prev");
          const nextButton = carousel.querySelector(".swiper-button-next");

          if (prevButton) {
            prevButton.addEventListener("click", function (e) {
              e.preventDefault();
              swiper.slidePrev();
            });
          }

          if (nextButton) {
            nextButton.addEventListener("click", function (e) {
              e.preventDefault();
              swiper.slideNext();
            });
          }
        }

        // Pagination manuelle si le module n'est pas disponible
        if (finalConfig.showPagination && !Swiper.Pagination) {
          const pagination = carousel.querySelector(".swiper-pagination");
          if (pagination) {
            // Créer les bullets de pagination
            const slides = swiperContainer.querySelectorAll(".swiper-slide");
            pagination.innerHTML = "";

            slides.forEach((slide, index) => {
              const bullet = document.createElement("span");
              bullet.className = "swiper-pagination-bullet";
              bullet.addEventListener("click", function () {
                swiper.slideTo(index);
              });
              pagination.appendChild(bullet);
            });

            // Mettre à jour la pagination active
            const updatePagination = () => {
              const bullets = pagination.querySelectorAll(
                ".swiper-pagination-bullet"
              );
              bullets.forEach((bullet, index) => {
                bullet.classList.toggle(
                  "swiper-pagination-bullet-active",
                  index === swiper.activeIndex
                );
              });
            };

            swiper.on("slideChange", updatePagination);
            updatePagination(); // Initialisation
          }
        }

        // Autoplay manuel si le module n'est pas disponible et PAS sur mobile
        if (
          finalConfig.autoplayDelay &&
          !Swiper.Autoplay &&
          window.innerWidth >= 768
        ) {
          // Désactiver sur mobile
          let autoplayInterval;
          let isAutoplayActive = true;

          const startAutoplay = () => {
            if (!isAutoplayActive) return;

            autoplayInterval = setInterval(() => {
              // Vérifier si la transition est en cours et si l'autoplay est actif
              if (!swiper.animating && isAutoplayActive && !swiper.destroyed) {
                swiper.slideNext();
              }
            }, finalConfig.autoplayDelay);
          };

          const stopAutoplay = () => {
            if (autoplayInterval) {
              clearInterval(autoplayInterval);
              autoplayInterval = null;
            }
          };

          const pauseAutoplay = () => {
            isAutoplayActive = false;
            stopAutoplay();
          };

          const resumeAutoplay = () => {
            isAutoplayActive = true;
            startAutoplay();
          };

          /* Expose pour le bouton pause RGAA 13.2 (fallback manuel) */
          carousel._manualPause = pauseAutoplay;
          carousel._manualResume = resumeAutoplay;

          // Démarrer l'autoplay
          startAutoplay();

          // Arrêter l'autoplay au survol
          swiperContainer.addEventListener("mouseenter", pauseAutoplay);
          swiperContainer.addEventListener("mouseleave", resumeAutoplay);

          // Arrêter l'autoplay lors d'une interaction tactile
          swiperContainer.addEventListener("touchstart", pauseAutoplay);
          swiperContainer.addEventListener("touchend", () => {
            setTimeout(resumeAutoplay, 1000);
          });

          // Gestion des transitions pour éviter les conflits
          swiper.on("slideChangeTransitionStart", pauseAutoplay);
          swiper.on("slideChangeTransitionEnd", () => {
            setTimeout(resumeAutoplay, 100);
          });

          // Nettoyer l'autoplay lors de la destruction
          swiper.on("destroy", () => {
            isAutoplayActive = false;
            stopAutoplay();
          });
        }

        // Ajouter des événements personnalisés si nécessaire
        swiper.on("slideChange", function () {
          // Événement personnalisé pour le changement de slide
          const event = new CustomEvent("g2rdCarouselSlideChange", {
            detail: {
              carousel: carousel,
              swiper: swiper,
              activeIndex: swiper.activeIndex,
            },
          });
          document.dispatchEvent(event);
        });

        // Gestion simple du redimensionnement
        const handleResize = () => {
          if (swiper && !swiper.destroyed) {
            try {
              swiper.update();
            } catch (error) {
              // Ignorer les erreurs de mise à jour
            }
          }
        };

        // Écouter les changements de taille d'écran avec debouncing
        let resizeTimeout;
        window.addEventListener("resize", () => {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(handleResize, 500);
        });

        // Optimisation pour mobile : désactiver certains effets sur les appareils tactiles
        const isTouchDevice =
          "ontouchstart" in window || navigator.maxTouchPoints > 0;
        if (isTouchDevice) {
          // Désactiver l'effet coverflow sur mobile si nécessaire
          if (finalConfig.effect === "coverflow" && window.innerWidth < 768) {
            swiper.params.effect = "slide";
          }

          // Améliorer la stabilité tactile
          swiper.params.touchRatio = 1;
          swiper.params.touchAngle = 45;
          swiper.params.simulateTouch = true;
        }
      } catch (error) {
        console.error("G2RD Carousel: Error initializing Swiper", error);
      }
    };

    // Attendre que les slides soient prêtes (fallbacks inclus) puis initialiser Swiper
    ensureSlidesReady().then(initSwiper);
  });
}

// Initialisation : attend le DOM si nécessaire, sinon exécute directement
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () =>
    setTimeout(initializeCarousels, 100)
  );
} else {
  setTimeout(initializeCarousels, 100);
}

// Éditeur Gutenberg (canvas iframe) : MutationObserver pour les blocs rendus par React
if (typeof MutationObserver !== "undefined") {
  var _carouselObserver = new MutationObserver(function () {
    // Chercher des carrousels non encore initialisés
    var uninitiated = document.querySelectorAll(
      ".g2rd-carousel:not([data-g2rd-init])"
    );
    if (uninitiated.length > 0) {
      uninitiated.forEach(function (el) {
        el.setAttribute("data-g2rd-init", "1");
      });
      setTimeout(initializeCarousels, 150);
    }
  });

  if (document.body) {
    _carouselObserver.observe(document.body, {
      childList: true,
      subtree: true,
    });
  } else {
    document.addEventListener("DOMContentLoaded", function () {
      _carouselObserver.observe(document.body, {
        childList: true,
        subtree: true,
      });
    });
  }
}

// Fonction utilitaire pour accéder aux instances Swiper
window.G2RDCarousel = {
  getInstance: function (carouselElement) {
    return carouselElement.swiperInstance;
  },

  getAllInstances: function () {
    const carousels = document.querySelectorAll(".g2rd-carousel");
    return Array.from(carousels)
      .map((carousel) => carousel.swiperInstance)
      .filter(Boolean);
  },

  destroy: function (carouselElement) {
    if (carouselElement.swiperInstance) {
      carouselElement.swiperInstance.destroy();
      delete carouselElement.swiperInstance;
    }
  },

  // Fonction simple pour mettre à jour la configuration responsive
  updateResponsiveConfig: function (carouselElement) {
    const swiper = carouselElement.swiperInstance;
    if (swiper && !swiper.destroyed) {
      try {
        swiper.update();
      } catch (error) {
        // Ignorer les erreurs
      }
    }
  },

  // Fonction pour mettre à jour tous les carousels
  updateAllResponsive: function () {
    const carousels = document.querySelectorAll(".g2rd-carousel");
    carousels.forEach((carousel) => {
      this.updateResponsiveConfig(carousel);
    });
  },
};

// Écouter les changements de taille d'écran pour tous les carousels
let resizeTimeout;
window.addEventListener("resize", function () {
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(function () {
    if (window.G2RDCarousel) {
      window.G2RDCarousel.updateAllResponsive();
    }
  }, 1000); // Délai plus long pour éviter trop d'appels
});
