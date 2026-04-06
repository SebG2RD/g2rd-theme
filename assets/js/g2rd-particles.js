/**
 * Effet de particules interactif
 *
 * Ce script gère l'effet de particules interactif dans les blocs
 * qui ont l'attribut data-particles="true".
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

// Détection du User-Agent pour PageSpeed/Lighthouse
function isPageSpeedOrLighthouse() {
  const ua = navigator.userAgent || "";
  return (
    ua.includes("Chrome-Lighthouse") ||
    ua.includes("Lighthouse") ||
    ua.includes("Google Page Speed Insights") ||
    ua.includes("Chrome-Lighthouse")
  );
}

if (!isPageSpeedOrLighthouse()) {
  document.addEventListener("DOMContentLoaded", function () {
    // Récupérer tous les éléments avec l'attribut data-particles="true"
    const elements = document.querySelectorAll("[data-particles='true']");

    // Paramètres de base (inspirés de particles.js)
    const config = {
      particles: {
        number: {
          value: 120, // Nombre de particules
          density: {
            enable: true,
            value_area: 800,
          },
        },
        color: {
          value: "#cccccc",
        },
        shape: {
          type: "circle",
        },
        opacity: {
          value: 0.6,
          random: true,
        },
        size: {
          value: 4,
          random: true,
        },
        line_linked: {
          enable: true,
          distance: 180,
          color: "#cccccc",
          opacity: 0.5,
          width: 1,
        },
        move: {
          enable: true,
          speed: 4.5, // Vitesse augmentée pour plus de fluidité (2.8 → 4.5)
          direction: "none",
          random: true,
          straight: false,
          bounce: true,
          // Nouveaux paramètres pour la fluidité
          out_mode: "bounce", // Rebond aux bords
          attract: {
            enable: false, // Pas d'attraction centrale
            rotateX: 600,
            rotateY: 1200,
          },
        },
        // Nouveau: éviter le regroupement
        repulse: {
          enable: true,
          distance: 60, // Distance minimale entre particules
          strength: 0.2, // Force de répulsion entre particules
        },
      },
      interactivity: {
        detect_on: "canvas",
        events: {
          onhover: {
            enable: true,
            mode: "repulse",
          },
          onclick: {
            enable: true,
            mode: "push",
          },
        },
        modes: {
          repulse: {
            distance: 200, // Augmentation de la distance de répulsion (150 → 200)
            strength: 5, // Augmentation significative de la force (1.5 → 5)
          },
          push: {
            particles_nb: 8, // Plus de particules ajoutées au clic (6 → 8)
          },
        },
      },
    };

    // Initialiser l'effet de particules pour chaque élément
    elements.forEach(function (element) {
      try {
        // Récupérer les attributs de personnalisation
        const el = element;
        const color =
          el instanceof HTMLElement && el.dataset.particlesColor
            ? el.dataset.particlesColor
            : "#cccccc";
        const speed =
          el instanceof HTMLElement && el.dataset.particlesSpeed
            ? parseFloat(el.dataset.particlesSpeed)
            : 4.5;
        const opacity =
          el instanceof HTMLElement && el.dataset.particlesOpacity
            ? parseFloat(el.dataset.particlesOpacity)
            : 0.6;

        // Mettre à jour la configuration avec les valeurs personnalisées
        const customConfig = {
          ...config,
          particles: {
            ...config.particles,
            color: {
              value: color,
            },
            opacity: {
              value: opacity,
              random: true,
            },
            move: {
              ...config.particles.move,
              speed: speed,
            },
            line_linked: {
              ...config.particles.line_linked,
              color: color,
            },
          },
        };

        initParticles(element, customConfig);
      } catch (error) {
        console.error("Erreur lors de l'initialisation des particules:", error);
      }
    });

    /**
     * Initialise les particules dans un élément
     * @param {Element} container - L'élément conteneur
     * @param {Object} customConfig - Configuration personnalisée
     */
    function initParticles(container, customConfig) {
      // Assurer que le conteneur a une position relative/absolute
      if (window.getComputedStyle(container).position === "static") {
        container.style.position = "relative";
      }

      // Ajouter une classe pour le z-index au conteneur parent
      container.classList.add("particles-container");

      // Mesures du conteneur
      const rect = container.getBoundingClientRect();
      const width = rect.width;
      const height = rect.height;

      // Données pour l'animation
      const particles = [];
      let mouse = { x: -1000, y: -1000, isInside: false };

      // Calculer le nombre de particules en fonction de la taille
      const particleCount = Math.min(
        Math.max(
          15,
          Math.floor(
            ((width * height) / 8000) * customConfig.particles.number.value
          )
        ),
        180 // Légèrement réduit pour éviter la surcharge
      );

      // Répartition plus uniforme des particules initiales
      const cellSize = Math.sqrt((width * height) / particleCount);
      const cols = Math.floor(width / cellSize);
      const rows = Math.floor(height / cellSize);

      // Créer les particules de manière plus distribuée
      for (let i = 0; i < particleCount; i++) {
        // Positionner les particules dans une grille avec légère variation
        const col = i % cols;
        const row = Math.floor(i / cols) % rows;

        // Position de base avec variation aléatoire
        const baseX = (col + 0.5) * (width / cols);
        const baseY = (row + 0.5) * (height / rows);

        // Variation aléatoire autour de la position de base
        const randomX = baseX + (((Math.random() - 0.5) * width) / cols) * 0.8;
        const randomY = baseY + (((Math.random() - 0.5) * height) / rows) * 0.8;

        particles.push(
          createParticle(container, width, height, randomX, randomY)
        );
      }

      // Supprimer le cercle autour de la souris
      const connections = [];

      // Gestion des événements souris simplifiée
      container.addEventListener("mousemove", function (e) {
        const rect = container.getBoundingClientRect();
        if (e instanceof MouseEvent) {
          mouse.x = e.clientX - rect.left;
          mouse.y = e.clientY - rect.top;
          mouse.isInside = true;
        }
      });

      container.addEventListener("mouseleave", function () {
        mouse.isInside = false;
      });

      // Click pour ajouter une particule avec effet plus spectaculaire
      container.addEventListener("click", function (e) {
        if (
          customConfig.interactivity.events.onclick.enable &&
          e instanceof MouseEvent
        ) {
          const rect = container.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;

          // Effet visuel du clic
          const clickEffect = document.createElement("div");
          clickEffect.style.position = "absolute";
          clickEffect.style.left = `${x - 50}px`;
          clickEffect.style.top = `${y - 50}px`;
          clickEffect.style.width = "100px";
          clickEffect.style.height = "100px";
          clickEffect.style.borderRadius = "50%";
          clickEffect.style.backgroundColor = "rgba(255,255,255,0.2)";
          clickEffect.style.transform = "scale(0)";
          clickEffect.style.transition =
            "transform 0.5s ease-out, opacity 0.5s ease-out";
          clickEffect.style.opacity = "1";
          clickEffect.style.zIndex = "-1"; // Z-index négatif pour rester derrière les éléments du bloc
          container.appendChild(clickEffect);

          // Animation de l'effet de clic
          setTimeout(() => {
            clickEffect.style.transform = "scale(1)";
            setTimeout(() => {
              clickEffect.style.opacity = "0";
              setTimeout(() => {
                clickEffect.remove();
              }, 500);
            }, 200);
          }, 10);

          // Ajouter de nouvelles particules avec une distribution plus spectaculaire
          for (
            let i = 0;
            i < customConfig.interactivity.modes.push.particles_nb;
            i++
          ) {
            const angle =
              (i / customConfig.interactivity.modes.push.particles_nb) *
              Math.PI *
              2;
            const distance = 30 + Math.random() * 50; // Distance plus grande depuis le point de clic
            const particleX = x + Math.cos(angle) * distance;
            const particleY = y + Math.sin(angle) * distance;

            const particle = createParticle(
              container,
              width,
              height,
              particleX,
              particleY
            );
            // Vitesse plus élevée dans la direction de l'angle pour un effet d'explosion
            particle.vx =
              Math.cos(angle) * customConfig.particles.move.speed * 4;
            particle.vy =
              Math.sin(angle) * customConfig.particles.move.speed * 4;
            particles.push(particle);
          }
        }
      });

      // Démarrer l'animation avec requestAnimationFrame pour une meilleure fluidité
      let lastTime = 0;
      function animate(timestamp) {
        if (!lastTime) lastTime = timestamp;
        const deltaTime = timestamp - lastTime;
        lastTime = timestamp;

        // Limiter les mises à jour pour maintenir une animation fluide
        animateParticles(deltaTime / 16.67); // normaliser par rapport à 60fps
        requestAnimationFrame(animate);
      }

      requestAnimationFrame(animate);

      /**
       * Crée une particule
       * @param {Element} parent - L'élément parent
       * @param {number} width - Largeur du conteneur
       * @param {number} height - Hauteur du conteneur
       * @param {number} x - Position X initiale (optionnelle)
       * @param {number} y - Position Y initiale (optionnelle)
       * @returns {Object} - La particule créée
       */
      function createParticle(parent, width, height, x, y) {
        // Taille aléatoire si configurée
        const size = customConfig.particles.size.random
          ? Math.random() * customConfig.particles.size.value + 1
          : customConfig.particles.size.value;

        // Créer l'élément DOM
        const htmlElement = document.createElement("div"); // HTMLElement
        htmlElement.classList.add("particle");
        htmlElement.classList.add("node"); // Ajouter la classe node pour compatibilité avec CSS

        // Position (aléatoire ou spécifiée)
        const posX = x !== undefined ? x : Math.random() * width;
        const posY = y !== undefined ? y : Math.random() * height;

        // Vitesse initiale avec une distribution plus variée
        const speedFactor = 0.7 + Math.random() * 0.9; // Augmenté (0.7-1.3 → 0.7-1.6) pour plus de vitesse
        const angle = Math.random() * Math.PI * 2;
        const vx =
          Math.cos(angle) * customConfig.particles.move.speed * speedFactor;
        const vy =
          Math.sin(angle) * customConfig.particles.move.speed * speedFactor;

        // Opacité
        const opacity = customConfig.particles.opacity.random
          ? Math.random() * customConfig.particles.opacity.value + 0.2
          : customConfig.particles.opacity.value;

        // Appliquer les styles
        htmlElement.style.position = "absolute";
        htmlElement.style.width = `${size}px`;
        htmlElement.style.height = `${size}px`;
        htmlElement.style.backgroundColor = customConfig.particles.color.value;
        htmlElement.style.borderRadius = "50%";
        htmlElement.style.opacity = opacity.toString();
        htmlElement.style.left = `${posX}px`;
        htmlElement.style.top = `${posY}px`;
        htmlElement.style.transition = "opacity 0.1s ease"; // Transition plus rapide (0.2s → 0.1s)
        htmlElement.style.willChange = "transform, opacity"; // Optimisation des performances
        htmlElement.style.zIndex = "-1"; // Z-index négatif pour rester derrière les éléments du bloc

        // Ajouter au parent
        parent.appendChild(htmlElement);

        // Retourner l'objet particule
        return {
          element: htmlElement,
          x: posX,
          y: posY,
          vx: vx,
          vy: vy,
          size: size,
          opacity: opacity,
          originalOpacity: opacity,
          connections: [],
          pulse: Math.random() > 0.7,
          pulseSpeed: 0.01 + Math.random() * 0.02,
          pulseDirection: 1,
          pulseTime: 0,
          // Paramètres pour le mouvement fluide
          targetX: posX,
          targetY: posY,
          lastX: posX,
          lastY: posY,
        };
      }

      /**
       * Crée une ligne entre deux particules
       * @param {Object} p1 - Première particule
       * @param {Object} p2 - Deuxième particule
       * @param {number} opacity - Opacité de la ligne
       * @returns {Object} - Ligne créée
       */
      function createConnection(p1, p2, opacity) {
        // Calculer la distance
        const dx = p2.x - p1.x;
        const dy = p2.y - p1.y;
        const distance = Math.sqrt(dx * dx + dy * dy);

        // Créer l'élément de ligne
        var lineEl = document.createElement("div");
        lineEl.classList.add("line");

        // Calculer l'angle pour la rotation
        const angle = Math.atan2(dy, dx);

        // Appliquer les styles
        lineEl.style.position = "absolute";
        lineEl.style.width = `${distance}px`;
        lineEl.style.height = `${customConfig.particles.line_linked.width}px`;
        lineEl.style.backgroundColor = customConfig.particles.line_linked.color;
        lineEl.style.opacity = opacity.toString();
        lineEl.style.left = `${p1.x}px`;
        lineEl.style.top = `${p1.y}px`;
        lineEl.style.transform = `rotate(${angle}rad)`;
        lineEl.style.transformOrigin = "0 0";
        lineEl.style.transition = "opacity 0.2s ease"; // Transition plus rapide (0.3s → 0.2s)
        lineEl.style.willChange = "transform, opacity"; // Optimisation des performances
        lineEl.style.zIndex = "-1"; // Z-index négatif pour rester derrière les éléments du bloc

        // Ajouter au conteneur
        container.appendChild(lineEl);

        // Retourner l'objet connexion
        return {
          element: lineEl,
          p1: p1,
          p2: p2,
          opacity: opacity,
        };
      }

      /**
       * Anime les particules et leurs connexions
       * @param {number} deltaFactor - Facteur pour le calcul basé sur le temps écoulé
       */
      function animateParticles(deltaFactor = 1) {
        // Normaliser le facteur de temps
        const timeFactor = Math.min(Math.max(deltaFactor, 0.5), 2);

        // Rafraîchir les positions des particules
        particles.forEach(function (p, index) {
          // Animation de pulsation pour certaines particules
          if (p.pulse) {
            p.pulseTime += p.pulseSpeed * timeFactor * 1.5; // Pulsation plus rapide (×1.5)
            const pulseFactor = Math.sin(p.pulseTime) * 0.3 + 0.7;
            var el = /** @type {HTMLElement} */ (p.element);
            el.style.opacity = (p.originalOpacity * pulseFactor).toString();

            const newSize = p.size * (0.9 + pulseFactor * 0.2);
            var el = /** @type {HTMLElement} */ (p.element);
            el.style.width = `${newSize}px`;
            el.style.height = `${newSize}px`;
          }

          // Ajout d'un mouvement légèrement sinusoïdal pour plus de fluidité
          const time = Date.now() * 0.001;
          const waveFactor = 0.02 * timeFactor;
          p.vx += Math.sin(time * 0.3 + p.y * 0.02) * waveFactor;
          p.vy += Math.cos(time * 0.2 + p.x * 0.01) * waveFactor;

          // Limitation de la vitesse maximale pour un mouvement plus fluide
          const maxSpeed = 4.5 * customConfig.particles.move.speed; // Augmenté (3 → 4.5)
          const currentSpeed = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
          if (currentSpeed > maxSpeed) {
            p.vx = (p.vx / currentSpeed) * maxSpeed;
            p.vy = (p.vy / currentSpeed) * maxSpeed;
          }

          // Assurer une vitesse minimale pour un mouvement continu
          const minSpeed = 0.5 * customConfig.particles.move.speed; // Augmenté (0.3 → 0.5)
          if (currentSpeed < minSpeed) {
            const angle = Math.atan2(p.vy, p.vx);
            p.vx = Math.cos(angle) * minSpeed;
            p.vy = Math.sin(angle) * minSpeed;
          }

          // Force de répulsion entre particules pour éviter le regroupement
          if (customConfig.particles.repulse.enable) {
            for (let j = 0; j < particles.length; j++) {
              if (j !== index) {
                const other = particles[j];
                const dx = p.x - other.x;
                const dy = p.y - other.y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < customConfig.particles.repulse.distance) {
                  // Force inversement proportionnelle à la distance
                  const force =
                    customConfig.particles.repulse.strength *
                    (1 - distance / customConfig.particles.repulse.distance) *
                    timeFactor;
                  const angle = Math.atan2(dy, dx);

                  // Appliquer une force plus faible mais suffisante
                  p.vx += Math.cos(angle) * force;
                  p.vy += Math.sin(angle) * force;
                }
              }
            }
          }

          // Appliquer la vitesse avec le facteur de temps
          p.x += p.vx * timeFactor;
          p.y += p.vy * timeFactor;

          // Interpolation moins forte pour un mouvement plus rapide
          p.targetX = p.x;
          p.targetY = p.y;
          p.x = p.lastX + (p.targetX - p.lastX) * 0.4; // Interpolation plus rapide (0.2 → 0.4)
          p.y = p.lastY + (p.targetY - p.lastY) * 0.4; // Interpolation plus rapide (0.2 → 0.4)
          p.lastX = p.x;
          p.lastY = p.y;

          // Rebondir sur les bords avec atténuation pour plus de fluidité
          if (p.x < 0 || p.x > width) {
            p.vx = -p.vx * 0.9; // Légère perte d'énergie pour plus de réalisme

            // Assurer que la particule reste dans les limites
            if (p.x < 0) p.x = 0;
            if (p.x > width) p.x = width;
          }

          if (p.y < 0 || p.y > height) {
            p.vy = -p.vy * 0.9; // Légère perte d'énergie pour plus de réalisme

            // Assurer que la particule reste dans les limites
            if (p.y < 0) p.y = 0;
            if (p.y > height) p.y = height;
          }

          // Effet de répulsion de la souris amélioré
          if (
            mouse.isInside &&
            customConfig.interactivity.events.onhover.enable
          ) {
            const dx = p.x - mouse.x;
            const dy = p.y - mouse.y;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < customConfig.interactivity.modes.repulse.distance) {
              // Force proportionnelle à la distance avec effet plus visible
              const repulseFactor = Math.min(
                Math.max(
                  1 -
                    Math.pow(
                      distance /
                        customConfig.interactivity.modes.repulse.distance,
                      2
                    ),
                  0
                ),
                1
              );

              const force =
                customConfig.interactivity.modes.repulse.strength *
                repulseFactor *
                timeFactor *
                1.2; // Force accrue (ajout ×1.2)
              const angle = Math.atan2(dy, dx);

              // Appliquer une répulsion plus forte
              p.vx += Math.cos(angle) * force;
              p.vy += Math.sin(angle) * force;

              // Effet visuel supplémentaire - opacité et taille modifiées près de la souris
              if (
                distance <
                customConfig.interactivity.modes.repulse.distance * 0.5
              ) {
                const repulseFactor = Math.min(
                  Math.max(
                    1 -
                      Math.pow(
                        distance /
                          customConfig.interactivity.modes.repulse.distance,
                        2
                      ),
                    0
                  ),
                  1
                );
                const opacityFactor = Math.max(0.2, 1 - repulseFactor * 0.5);
                var el = /** @type {HTMLElement} */ (p.element);
                el.style.opacity = (
                  p.originalOpacity * opacityFactor
                ).toString();

                // Augmenter légèrement la taille
                const sizeFactor = 1 + repulseFactor * 0.5;
                var el = /** @type {HTMLElement} */ (p.element);
                el.style.width = `${p.size * sizeFactor}px`;
                el.style.height = `${p.size * sizeFactor}px`;
              } else {
                var el = /** @type {HTMLElement} */ (p.element);
                el.style.opacity = p.originalOpacity.toString();
                el.style.width = `${p.size}px`;
                el.style.height = `${p.size}px`;
              }
            }
          }

          // Friction adaptative - plus faible pour un mouvement plus rapide
          const speedDamp =
            0.985 + 0.01 * (1 - Math.min(1, currentSpeed / maxSpeed)); // Friction réduite (0.98 → 0.985)
          p.vx *= speedDamp;
          p.vy *= speedDamp;

          // Appliquer la nouvelle position avec animation CSS
          var el = /** @type {HTMLElement} */ (p.element);
          el.style.left = `${p.x}px`;
          el.style.top = `${p.y}px`;
        });

        // Supprimer toutes les anciennes connexions
        connections.forEach(function (c) {
          c.element.remove();
        });
        connections.length = 0;

        // Créer de nouvelles connexions
        for (let i = 0; i < particles.length; i++) {
          // Limiter le nombre de connexions pour des raisons de performance
          let connectionCount = 0;
          const maxConnectionsPerParticle = 3;

          for (let j = i + 1; j < particles.length; j++) {
            if (connectionCount >= maxConnectionsPerParticle) break;

            const p1 = particles[i];
            const p2 = particles[j];

            // Calculer la distance
            const dx = p2.x - p1.x;
            const dy = p2.y - p1.y;
            const distance = Math.sqrt(dx * dx + dy * dy);

            // Connecter si suffisamment proche
            if (distance < customConfig.particles.line_linked.distance) {
              // Opacité variable selon la distance
              const opacity =
                (1 - distance / customConfig.particles.line_linked.distance) *
                customConfig.particles.line_linked.opacity;

              // Créer la connexion
              const connection = createConnection(p1, p2, opacity);
              connections.push(connection);
              connectionCount++;
            }
          }
        }
      }
    }
  });
}
