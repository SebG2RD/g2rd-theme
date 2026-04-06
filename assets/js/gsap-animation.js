/**
 * Animations GSAP
 *
 * Ce script gère les animations GSAP pour les éléments
 * avec des classes d'animation spécifiques.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

document.addEventListener("DOMContentLoaded", function () {
  // Vérifier les préférences de réduction de mouvement
  const prefersReducedMotion = window.matchMedia(
    "(prefers-reduced-motion: reduce)"
  ).matches;

  if (
    "undefined" != typeof gsap &&
    "undefined" != typeof ScrollTrigger &&
    !prefersReducedMotion
  ) {
    gsap.registerPlugin(ScrollTrigger);
    const o = {
      ".gsap-fade-in": {
        from: { opacity: 0 },
        to: { opacity: 1, duration: 1 },
      },
      ".gsap-slide-up": {
        from: { y: 50, opacity: 0 },
        to: { y: 0, opacity: 1, duration: 0.8 },
      },
      ".gsap-slide-down": {
        from: { y: -50, opacity: 0 },
        to: { y: 0, opacity: 1, duration: 0.8 },
      },
      ".gsap-slide-left": {
        from: { x: -50, opacity: 0 },
        to: { x: 0, opacity: 1, duration: 0.8 },
      },
      ".gsap-slide-right": {
        from: { x: 50, opacity: 0 },
        to: { x: 0, opacity: 1, duration: 0.8 },
      },
      ".gsap-zoom-in": {
        from: { scale: 0.8, opacity: 0 },
        to: { scale: 1, opacity: 1, duration: 0.8 },
      },
      ".gsap-zoom-out": {
        from: { scale: 1.2, opacity: 0 },
        to: { scale: 1, opacity: 1, duration: 0.8 },
      },
      ".gsap-flip": {
        from: { rotationY: 90, opacity: 0 },
        to: { rotationY: 0, opacity: 1, duration: 1 },
      },
      ".gsap-bounce": {
        from: { y: -50, opacity: 0 },
        to: { y: 0, opacity: 1, duration: 1, ease: "bounce.out" },
      },
      ".gsap-stagger": {
        from: { opacity: 0, y: 20 },
        to: { opacity: 1, y: 0, duration: 0.5, stagger: 0.1 },
      },
      ".gsap-blur": {
        from: { filter: "blur(10px)", opacity: 0 },
        to: { filter: "blur(0px)", opacity: 1, duration: 1 },
      },
      ".gsap-skew": {
        from: { skewX: 20, opacity: 0 },
        to: { skewX: 0, opacity: 1, duration: 0.8 },
      },
      ".gsap-shake": {
        from: { x: 0 },
        to: {
          x: [-10, 10, -10, 10, 0],
          duration: 0.5,
          ease: "power1.inOut",
        },
      },
      ".gsap-pulse": {
        from: { scale: 1 },
        to: {
          scale: [1, 1.1, 1],
          duration: 1,
          ease: "power1.inOut",
          repeat: -1,
        },
      },
      ".gsap-wave": {
        from: { y: 0 },
        to: {
          y: [-10, 0, -10, 0],
          duration: 1,
          ease: "sine.inOut",
          repeat: -1,
        },
      },
      ".gsap-swing": {
        from: { rotation: 0 },
        to: {
          rotation: [-5, 5, -5, 5, 0],
          duration: 1,
          ease: "power1.inOut",
        },
      },
      ".gsap-tada": {
        from: { scale: 1, rotation: 0 },
        to: {
          scale: [1, 1.1, 1.1, 1.1, 1],
          rotation: [0, -3, 3, -3, 0],
          duration: 1,
          ease: "power1.inOut",
        },
      },
      ".gsap-wobble": {
        from: { x: 0, rotation: 0 },
        to: {
          x: [-10, 10, -10, 10, 0],
          rotation: [-5, 5, -5, 5, 0],
          duration: 1,
          ease: "power1.inOut",
        },
      },
    };
    Object.entries(o).forEach(([o, t]) => {
      const a = document.querySelectorAll(o);
      0 !== a.length &&
        a.forEach((o) => {
          gsap.set(o, t.from),
            gsap.to(o, {
              ...t.to,
              scrollTrigger: {
                trigger: o,
                start: "top bottom-=10%",
                end: "bottom center",
                scrub: !1,
                markers: !1,
                toggleActions: "play none none none",
              },
            });
        });
    });
  } else {
    // Si les animations sont désactivées, on s'assure que les éléments sont visibles
    document
      .querySelectorAll(
        ".gsap-fade-in, .gsap-slide-up, .gsap-slide-down, .gsap-slide-left, .gsap-slide-right, .gsap-zoom-in, .gsap-zoom-out, .gsap-flip, .gsap-bounce"
      )
      .forEach((element) => {
        element.style.opacity = "1";
        element.style.transform = "none";
      });
  }
});
