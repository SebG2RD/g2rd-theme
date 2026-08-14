/**
 * G2RD Grille filtrable — script frontend
 *
 * Appelle l'endpoint REST unifié /g2rd/v1/posts qui normalise les données
 * de n'importe quel CPT (articles, pages, WooCommerce, SureCart, FluentCart…).
 *
 * Chaque grille est initialisée via MutationObserver + data-g2rd-init
 * pour assurer la compatibilité avec le canvas Gutenberg.
 *
 * Sécurité :
 * - Toutes les valeurs insérées dans le DOM passent par esc() (createTextNode)
 *   ou attr() — aucune valeur brute ne transite via innerHTML.
 * - Le nonce WordPress est transmis sur chaque requête via X-WP-Nonce.
 */

const API_BASE = window.wpApiSettings?.root
  ? window.wpApiSettings.root.replace(/\/$/, "")
  : "/wp-json";

const NONCE = window.wpApiSettings?.nonce || "";

/**
 * Effectue une requête REST GET avec nonce WordPress.
 * Lève une Error si la réponse HTTP n'est pas OK.
 *
 * @param {string} url URL complète de l'endpoint
 * @returns {Promise<any>}
 */
async function apiFetch(url) {
  const headers = NONCE ? { "X-WP-Nonce": NONCE } : {};
  const resp    = await fetch(url, { headers });
  if (!resp.ok) {
    throw new Error(`REST error ${resp.status} — ${url}`);
  }
  return resp.json();
}

// ── Utilitaires ───────────────────────────────────────────────────────────────

function esc(str) {
  const d = document.createElement("div");
  d.appendChild(document.createTextNode(String(str || "")));
  return d.innerHTML;
}

function attr(str) {
  return String(str || "").replace(/"/g, "&quot;");
}

function debounce(fn, ms) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}

// ── Rendu des étoiles ─────────────────────────────────────────────────────────

function renderStars(rating) {
  const full  = Math.floor(rating);
  const half  = rating - full >= 0.5 ? 1 : 0;
  const empty = 5 - full - half;
  let html = '<span class="g2rd-fg__stars" aria-label="' + rating + '/5">';
  for (let i = 0; i < full;  i++) html += '<span class="g2rd-fg__star g2rd-fg__star--full">★</span>';
  if (half)                       html += '<span class="g2rd-fg__star g2rd-fg__star--half">★</span>';
  for (let i = 0; i < empty; i++) html += '<span class="g2rd-fg__star g2rd-fg__star--empty">☆</span>';
  html += "</span>";
  return html;
}

// ── Rendu d'une carte ─────────────────────────────────────────────────────────

function renderCard(item, opts) {
  const {
    cardDisplay, linkType, readMoreText, excerptLength,
    showBadge, showDate, showPrice, showRating, showAddToCart, ctaButtonStyle,
  } = opts;

  const p      = item.product;
  const isCard = linkType === "full-card";
  const tag    = isCard ? "a" : "div";
  const tagAttrs = isCard
    ? ` href="${attr(item.link)}" class="g2rd-filter-grid__card g2rd-fg__card--${cardDisplay}" aria-label="${attr(item.title)}"`
    : ` class="g2rd-filter-grid__card g2rd-fg__card--${cardDisplay}"`;

  // Badge : premier terme de la première taxonomie disponible
  let badgeHtml = "";
  if (showBadge) {
    const firstTax  = Object.values(item.terms || {})[0];
    const firstTerm = firstTax?.[0];
    if (firstTerm) {
      badgeHtml = `<span class="g2rd-fg__badge">${esc(firstTerm.name)}</span>`;
    }
    // Badges ecommerce
    if (p?.on_sale)                          badgeHtml += '<span class="g2rd-fg__badge g2rd-fg__badge--sale">Promo</span>';
    if (p?.stock_status === "outofstock")    badgeHtml += '<span class="g2rd-fg__badge g2rd-fg__badge--out">Épuisé</span>';
    if (p?.stock_status === "onbackorder")   badgeHtml += '<span class="g2rd-fg__badge g2rd-fg__badge--back">Sur commande</span>';
  }

  // Image
  let mediaHtml = "";
  if (item.thumbnail) {
    mediaHtml = `<div class="g2rd-filter-grid__media">
      <img src="${attr(item.thumbnail)}" alt="${attr(item.title)}" loading="lazy" />
    </div>`;
  }

  // Titre
  const titleHtml = linkType === "title"
    ? `<h3 class="g2rd-fg__title"><a href="${attr(item.link)}">${esc(item.title)}</a></h3>`
    : `<h3 class="g2rd-fg__title">${esc(item.title)}</h3>`;

  // Extrait
  let excerptHtml = "";
  if (cardDisplay !== "compact") {
    const ex = item.excerpt
      ? item.excerpt.substring(0, excerptLength) + (item.excerpt.length > excerptLength ? "…" : "")
      : "";
    if (ex) excerptHtml = `<p class="g2rd-fg__excerpt">${esc(ex)}</p>`;
  }

  // Date
  let dateHtml = "";
  if (showDate && item.date) {
    dateHtml = `<time class="g2rd-fg__date" datetime="${attr(item.date_iso)}">${esc(item.date)}</time>`;
  }

  // Prix (produits)
  let priceHtml = "";
  if (showPrice && p) {
    if (p.price_html) {
      // price_html est généré par WooCommerce (get_price_html()) ou FluentCart côté serveur —
      // HTML de confiance same-origin. On retire tout de même les <script> par défense en profondeur.
      const safePriceHtml = p.price_html.replace( /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, "" );
      priceHtml = `<div class="g2rd-fg__price">${safePriceHtml}</div>`;
    } else if (p.price != null) {
      const formatted = Number(p.price).toLocaleString("fr-FR", { style: "currency", currency: "EUR" });
      priceHtml = `<div class="g2rd-fg__price">${esc(formatted)}</div>`;
    }
  }

  // Note (WooCommerce)
  let ratingHtml = "";
  if (showRating && p?.average_rating > 0) {
    ratingHtml = `<div class="g2rd-fg__rating">${renderStars(p.average_rating)}
      <span class="g2rd-fg__rating-count">(${p.rating_count})</span></div>`;
  }

  // CTA — bouton natif WordPress (markup wp-block-button) pour hériter des styles de boutons du site.
  let ctaHtml = "";
  const ctaStyleClass = ctaButtonStyle ? ` is-style-${attr(ctaButtonStyle)}` : "";
  const ctaButton = (href, label, text) =>
    `<div class="wp-block-button g2rd-fg__cta${ctaStyleClass}"><a href="${attr(href)}" class="wp-block-button__link wp-element-button" aria-label="${label}">${text}</a></div>`;
  if (linkType === "read-more") {
    const rmText = esc(readMoreText || "Lire la suite");
    ctaHtml = ctaButton(item.link, `${rmText} : ${attr(item.title)}`, rmText);
  }
  if (showAddToCart && p?.add_to_cart_url) {
    ctaHtml = ctaButton(p.add_to_cart_url, `Ajouter au panier : ${attr(item.title)}`, "Ajouter au panier");
  } else if (showAddToCart && p) {
    ctaHtml = ctaButton(item.link, `Voir le produit : ${attr(item.title)}`, "Voir le produit");
  }

  return `<${tag}${tagAttrs}>
    ${mediaHtml}
    <div class="g2rd-filter-grid__content">
      ${badgeHtml}
      ${titleHtml}
      ${excerptHtml}
      ${priceHtml}
      ${ratingHtml}
      <div class="g2rd-fg__meta">${dateHtml}</div>
      ${ctaHtml}
    </div>
  </${tag}>`;
}

// ── Contrôleur principal ──────────────────────────────────────────────────────

class FilterableGrid {
  constructor(el) {
    this.el   = el;
    this.grid = el.querySelector(".g2rd-filter-grid__grid");
    this.pag  = el.querySelector(".g2rd-filter-grid__pagination");

    // Lire la config depuis les data-attributes
    this.cfg = {
      postTypes:    JSON.parse(el.dataset.postTypes   || '["post"]'),
      perPage:      parseInt(el.dataset.perPage       || "6", 10),
      showSearch:   el.dataset.showSearch             === "true",
      showTax:      el.dataset.showTax                === "true",
      taxonomy:     el.dataset.taxonomy               || "",
      columns:      parseInt(el.dataset.columns       || "3", 10),
      cardDisplay:  el.dataset.cardDisplay            || "summary",
      linkType:     el.dataset.linkType               || "title",
      readMoreText: el.dataset.readMore               || "Lire la suite",
      excerptLen:   parseInt(el.dataset.excerptLen    || "150", 10),
      pagination:   el.dataset.pagination             !== "false",
      showPrice:    el.dataset.showPrice              === "true",
      showBadge:    el.dataset.showBadge              !== "false",
      showDate:     el.dataset.showDate               !== "false",
      showRating:   el.dataset.showRating             === "true",
      showAddToCart:el.dataset.showCart               === "true",
      orderby:      el.dataset.orderby                || "date",
      order:        el.dataset.order                  || "DESC",
      ctaButtonStyle: el.dataset.ctaStyle             || "",
      // Catégories proposées dans le filtre : « all » par défaut. Les grilles
      // qui restreignent la liste portent data-term-mode + data-terms.
      termMode:     el.dataset.termMode               || "all",
      terms:        (() => {
        try {
          const parsed = JSON.parse(el.dataset.terms || "[]");
          return Array.isArray(parsed) ? parsed.filter((n) => Number.isInteger(n)) : [];
        } catch (_) {
          return [];
        }
      })(),
    };

    this.state = {
      search:   "",
      term:     0,
      page:     1,
      loading:  false,
      termSlug: "",
    };

    this.grid.style.setProperty("--wrb-grid-columns", this.cfg.columns);

    this._buildControls();
    this._fetch();
  }

  // ── Construire les contrôles de filtre ──────────────────────────────────────

  _buildControls() {
    const controlsEl = this.el.querySelector(".g2rd-filter-grid__controls");
    if (!controlsEl) return;

    // Recherche
    if (this.cfg.showSearch) {
      const searchEl = controlsEl.querySelector(".g2rd-filter-grid__search-form input");
      if (searchEl) {
        searchEl.disabled = false;
        searchEl.addEventListener(
          "input",
          debounce((e) => {
            this.state.search = e.target.value;
            this.state.page   = 1;
            this._fetch();
          }, 400)
        );
      }
    }

    // Filtre taxonomie
    if (this.cfg.showTax && this.cfg.taxonomy) {
      this._loadTaxTerms();
    }
  }

  async _loadTaxTerms() {
    const taxEl = this.el.querySelector(".g2rd-filter-grid__taxonomy select");
    if (!taxEl) return;
    taxEl.disabled = false;

    try {
      // La route REST d'une taxonomie utilise son rest_base, pas son slug :
      // `category` s'interroge sur /wp/v2/categories. Interroger le slug
      // renvoyait un 404 avalé par le catch — le filtre restait vide.
      let restBase = this.cfg.taxonomy;
      try {
        const taxInfo = await apiFetch(
          `${API_BASE}/wp/v2/taxonomies/${encodeURIComponent(this.cfg.taxonomy)}?_fields=rest_base`
        );
        if (taxInfo?.rest_base) restBase = taxInfo.rest_base;
      } catch (_) {
        // On retombe sur le slug : correct pour les taxonomies dont le
        // rest_base n'a pas été personnalisé (product_cat, la plupart des CPT).
      }

      // Sélection de catégories : on ne demande que celles retenues.
      const params = new URLSearchParams({
        per_page:   "100",
        hide_empty: "true",
      });
      if (this.cfg.termMode === "selected" && this.cfg.terms.length) {
        params.set("include", this.cfg.terms.join(","));
      }

      const terms = await apiFetch(
        `${API_BASE}/wp/v2/${encodeURIComponent(restBase)}?${params.toString()}`
      );

      if (!Array.isArray(terms)) return;

      taxEl.innerHTML = '<option value="0">Tous</option>';
      terms.forEach((t) => {
        if (!t || typeof t.id !== "number" || typeof t.name !== "string") return;
        const opt       = document.createElement("option");
        opt.value       = String(t.id);
        opt.textContent = t.name; // textContent est sûr contre les XSS
        taxEl.appendChild(opt);
      });

      taxEl.addEventListener("change", (e) => {
        this.state.term = parseInt(e.target.value, 10);
        this.state.page = 1;
        this._fetch();
      });
    } catch (_) {
      // Échec silencieux : la grille reste fonctionnelle sans filtre taxonomie
    }
  }

  // ── Fetch ────────────────────────────────────────────────────────────────────

  async _fetch() {
    if (this.state.loading) return;
    this.state.loading = true;
    this._setLoading(true);

    // Si plusieurs types sont sélectionnés, on fait des requêtes parallèles
    const types = this.cfg.postTypes;

    try {
      const requests = types.map((postType) => {
        const params = new URLSearchParams({
          post_type: postType,
          per_page:  Math.ceil(this.cfg.perPage / types.length),
          page:      this.state.page,
          orderby:   this.cfg.orderby,
          order:     this.cfg.order,
        });
        if (this.state.search) params.set("search", this.state.search);
        if (this.state.term > 0) {
          params.set("taxonomy", this.cfg.taxonomy);
          params.set("term",     this.state.term);
        }
        return apiFetch(`${API_BASE}/g2rd/v1/posts?${params}`);
      });

      const results = await Promise.all(requests);

      // Fusionner et trier les résultats de plusieurs types
      let allItems = results.flatMap((r) => r.items || []);
      if (this.cfg.orderby === "date") {
        allItems.sort((a, b) =>
          this.cfg.order === "DESC"
            ? new Date(b.date_iso) - new Date(a.date_iso)
            : new Date(a.date_iso) - new Date(b.date_iso)
        );
      }
      allItems = allItems.slice(0, this.cfg.perPage);

      const total      = results.reduce((s, r) => s + (r.total || 0), 0);
      const totalPages = results.reduce((max, r) => Math.max(max, r.total_pages || 1), 1);

      this._renderCards(allItems);
      if (this.cfg.pagination) {
        this._renderPagination(this.state.page, totalPages, total);
      }
    } catch (e) {
      this._renderError();
    } finally {
      this.state.loading = false;
      this._setLoading(false);
    }
  }

  // ── Rendu ─────────────────────────────────────────────────────────────────

  _setLoading(on) {
    this.grid.setAttribute("aria-busy", on ? "true" : "false");
    if (on) this.grid.style.opacity = "0.5";
    else    this.grid.style.opacity = "1";
  }

  _renderCards(items) {
    if (!items.length) {
      this.grid.innerHTML = '<p class="g2rd-fg__empty">Aucun résultat trouvé.</p>';
      return;
    }

    const opts = {
      cardDisplay:  this.cfg.cardDisplay,
      linkType:     this.cfg.linkType,
      readMoreText: this.cfg.readMoreText,
      excerptLength:this.cfg.excerptLen,
      showBadge:    this.cfg.showBadge,
      showDate:     this.cfg.showDate,
      showPrice:    this.cfg.showPrice,
      showRating:   this.cfg.showRating,
      showAddToCart:this.cfg.showAddToCart,
      ctaButtonStyle: this.cfg.ctaButtonStyle,
    };

    this.grid.innerHTML = items.map((item) => renderCard(item, opts)).join("");
  }

  _renderPagination(page, totalPages, total) {
    if (!this.pag) return;
    if (totalPages <= 1) {
      this.pag.innerHTML = "";
      return;
    }

    let html = '<nav class="g2rd-fg__nav" aria-label="Pagination">';

    if (page > 1) {
      html += `<button type="button" class="g2rd-fg__page-btn" data-page="${page - 1}">←</button>`;
    }

    for (let i = 1; i <= totalPages; i++) {
      html += `<button type="button" class="g2rd-fg__page-btn${i === page ? " is-active" : ""}" data-page="${i}" ${i === page ? 'aria-current="page"' : ""}>${i}</button>`;
    }

    if (page < totalPages) {
      html += `<button type="button" class="g2rd-fg__page-btn" data-page="${page + 1}">→</button>`;
    }

    html += `<span class="g2rd-fg__total">${total} résultat${total > 1 ? "s" : ""}</span>`;
    html += "</nav>";

    this.pag.innerHTML = html;
    this.pag.querySelectorAll(".g2rd-fg__page-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        this.state.page = parseInt(btn.dataset.page, 10);
        this._fetch();
        this.el.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });
  }

  _renderError() {
    this.grid.innerHTML =
      '<p class="g2rd-fg__error">Une erreur est survenue lors du chargement du contenu.</p>';
  }
}

// ── Init ──────────────────────────────────────────────────────────────────────

/**
 * Initialise une seule grille filtrable.
 *
 * @param {HTMLElement} el
 */
function initFilterableGrid( el ) {
  if ( el.dataset.g2rdInit ) return;
  el.dataset.g2rdInit = "1";
  new FilterableGrid( el );
}

/**
 * Initialise toutes les grilles non encore initialisées.
 *
 * @param {Document|HTMLElement} [root]
 */
function initAllFilterableGrids( root ) {
  ( root || document )
    .querySelectorAll( ".g2rd-filter-grid[data-post-types]:not([data-g2rd-init])" )
    .forEach( ( el ) => initFilterableGrid( /** @type {HTMLElement} */ ( el ) ) );
}

// Frontend : init au chargement du DOM
document.addEventListener( "DOMContentLoaded", () => initAllFilterableGrids() );

// Éditeur Gutenberg (canvas iframe) : MutationObserver pour les blocs rendus par React
if ( typeof MutationObserver !== "undefined" ) {
  var _fgObserver = new MutationObserver( function () {
    initAllFilterableGrids();
  } );

  if ( document.body ) {
    _fgObserver.observe( document.body, { childList: true, subtree: true } );
  } else {
    document.addEventListener( "DOMContentLoaded", function () {
      _fgObserver.observe( document.body, { childList: true, subtree: true } );
    } );
  }
}
