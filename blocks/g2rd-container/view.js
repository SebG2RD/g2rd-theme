/**
 * Conteneur G2RD — animations d'entrée (frontend).
 *
 * Le CSS (style.css) met [data-g2rd-animate] à opacity:0 et ne révèle le
 * contenu (opacity:1 + transform final) que via la classe .g2rd-animated.
 * Ce script ajoute cette classe quand l'élément entre dans le viewport
 * (IntersectionObserver) et applique durée / délai / easing depuis les
 * data-attributes posés par render.php. Sans lui, tout conteneur avec une
 * animation reste invisible sur le front.
 *
 * Robuste : s'exécute que le DOM soit prêt ou en cours de chargement
 * (exécution différée type LiteSpeed) ; MutationObserver pour le contenu
 * injecté (canvas Gutenberg) ; repli immédiat si prefers-reduced-motion ou
 * IntersectionObserver indisponible → le contenu reste toujours visible.
 */
(function () {
	var SEL = "[data-g2rd-animate]";

	function reveal(el) {
		var duration = parseInt(el.getAttribute("data-g2rd-animate-duration") || "600", 10);
		var delay = parseInt(el.getAttribute("data-g2rd-animate-delay") || "0", 10);
		var easing = el.getAttribute("data-g2rd-animate-easing") || "ease";
		el.style.transitionDuration = duration + "ms";
		el.style.transitionDelay = delay + "ms";
		el.style.transitionTimingFunction = easing;
		el.classList.add("g2rd-animated");
	}

	function init() {
		var els = document.querySelectorAll(SEL + ":not([data-g2rd-anim-init])");
		if (!els.length) {
			return;
		}

		var reduce =
			window.matchMedia &&
			window.matchMedia("(prefers-reduced-motion: reduce)").matches;
		var noIO = !("IntersectionObserver" in window);

		var obs =
			!reduce && !noIO
				? new IntersectionObserver(
						function (entries, o) {
							for (var i = 0; i < entries.length; i++) {
								if (entries[i].isIntersecting) {
									reveal(entries[i].target);
									o.unobserve(entries[i].target);
								}
							}
						},
						{ threshold: 0.1, rootMargin: "0px 0px -8% 0px" }
				  )
				: null;

		for (var i = 0; i < els.length; i++) {
			var el = els[i];
			el.setAttribute("data-g2rd-anim-init", "1");
			if (obs) {
				obs.observe(el);
			} else {
				// prefers-reduced-motion ou pas d'IntersectionObserver :
				// révélation immédiate (le contenu reste visible).
				reveal(el);
			}
		}
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}

	// Contenu injecté après coup (canvas Gutenberg, chargements différés).
	if (typeof MutationObserver !== "undefined") {
		var mo = new MutationObserver(function () {
			init();
		});
		if (document.body) {
			mo.observe(document.body, { childList: true, subtree: true });
		} else {
			document.addEventListener("DOMContentLoaded", function () {
				mo.observe(document.body, { childList: true, subtree: true });
			});
		}
	}
})();
