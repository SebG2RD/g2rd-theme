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

function initClickableArticles() {
  const blocks = document.querySelectorAll(
    '.wp-block-columns[data-clickable-articles="true"], .wp-block-group[data-clickable-articles="true"]'
  );

  blocks.forEach(function (block) {
    // Évite la double-initialisation si LiteSpeed exécute le script plusieurs fois
    if (block.dataset.g2rdInit === "clickable") {
      return;
    }
    block.dataset.g2rdInit = "clickable";

    // Trouver le premier lien parmi les enfants directs du bloc.
    // Un enfant direct peut ÊTRE un lien (ex. bloc core/read-more → <a>) ou en
    // contenir un (ex. image/titre en isLink → <figure><a> / <h3><a>).
    // querySelector("a") ne matche que les descendants : on teste donc aussi
    // si l'enfant lui-même est un <a>.
    const directChildren = block.children;
    let link = null;

    for (let i = 0; i < directChildren.length; i++) {
      const child = directChildren[i];
      const foundLink = child.tagName === "A" ? child : child.querySelector("a");
      if (foundLink) {
        link = foundLink;
        break;
      }
    }

    if (!link) {
      return;
    }

    // Nettoyer les éventuelles classes héritées sur les enfants
    block.querySelectorAll(".g2rd-clickable-article").forEach(function (el) {
      if (el !== block) {
        el.classList.remove("g2rd-clickable-article");
      }
    });

    block.classList.add("g2rd-clickable-article");
    block.style.cursor = "pointer";

    block.setAttribute("role", "article");
    block.setAttribute("tabindex", "0");
    block.setAttribute(
      "aria-label",
      "Article cliquable - " + link.textContent.trim()
    );

    block.addEventListener("click", function (e) {
      if (e.target.closest("a") || e.target.closest("button")) {
        return;
      }
      e.stopPropagation();
      if (link.target === "_blank") {
        window.open(link.href, "_blank", "noopener,noreferrer");
      } else {
        window.location.href = link.href;
      }
    });

    block.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        link.click();
      }
    });

    block.addEventListener("focus", function () {
      this.style.outline = "2px solid var(--wp--preset--color--primary)";
      this.style.outlineOffset = "2px";
    });

    block.addEventListener("blur", function () {
      this.style.outline = "none";
    });
  });
}

// Robuste : fonctionne que le DOM soit déjà prêt (exécution différée LiteSpeed)
// ou en cours de chargement (DOMContentLoaded normal).
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initClickableArticles);
} else {
  initClickableArticles();
}
