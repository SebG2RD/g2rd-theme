/**
 * Articles cliquables
 *
 * Ce script gère le comportement des articles cliquables
 * sur le front-end du site.
 *
 * @package G2RD
 * @since 1.0.0
 * @license EUPL-1.2
 * @copyright (c) 2024 Sebastien GERARD
 * @link https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 */

document.addEventListener("DOMContentLoaded", function () {
  // Sélectionne uniquement les éléments parents les plus hauts avec l'attribut data-clickable-articles
  const blocks = document.querySelectorAll(
    '.wp-block-columns[data-clickable-articles="true"], .wp-block-group[data-clickable-articles="true"]'
  );

  blocks.forEach(function (block) {
    // Trouver le premier lien dans le bloc parent uniquement
    const directChildren = block.children;
    let link = null;

    // Chercher le premier lien dans les enfants directs
    for (let i = 0; i < directChildren.length; i++) {
      const child = directChildren[i];
      const foundLink = child.querySelector("a");
      if (foundLink) {
        link = foundLink;
        break;
      }
    }

    if (link) {
      // Supprimer la classe g2rd-clickable-article de tous les enfants
      block
        .querySelectorAll(".g2rd-clickable-article")
        .forEach(function (element) {
          if (element !== block) {
            element.classList.remove("g2rd-clickable-article");
          }
        });

      // Ajouter la classe g2rd-clickable-article uniquement au bloc parent
      block.classList.add("g2rd-clickable-article");

      // Ajouter les attributs ARIA
      block.setAttribute("role", "article");
      block.setAttribute("tabindex", "0");
      block.setAttribute(
        "aria-label",
        "Article cliquable - " + link.textContent.trim()
      );

      // Ajoute un écouteur de clic au bloc parent uniquement
      block.addEventListener("click", function (e) {
        // Empêcher la propagation du clic pour éviter les doubles déclenchements
        e.stopPropagation();

        // Si on n'a pas cliqué directement sur un lien ou un bouton
        if (!e.target.closest("a") && !e.target.closest("button")) {
          // On simule un clic sur le premier lien
          link.click();
        }
      });

      // Ajouter la navigation au clavier
      block.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          link.click();
        }
      });

      // Ajouter les styles de focus
      block.addEventListener("focus", function () {
        this.style.outline = "2px solid var(--wp--preset--color--primary)";
        this.style.outlineOffset = "2px";
      });

      block.addEventListener("blur", function () {
        this.style.outline = "none";
      });
    }
  });
});
