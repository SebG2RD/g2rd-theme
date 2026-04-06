import { __ } from "@wordpress/i18n";
import { useState, useEffect } from "@wordpress/element";
import { Button, Spinner, Notice } from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";

/**
 * Composant partagé de sélection de posts pour l'éditeur Gutenberg.
 *
 * Usage:
 *   import PostSelector from "../../shared/PostSelector";
 *
 *   <PostSelector
 *     contentType="portfolio"
 *     selectedPosts={attributes.selectedPosts}
 *     onSelect={(posts) => setAttributes({ selectedPosts: posts })}
 *   />
 *
 * @param {string}   contentType    - Type REST : "posts", "pages", "portfolio", etc.
 * @param {Array}    selectedPosts  - Posts actuellement sélectionnés.
 * @param {Function} onSelect       - Callback appelé avec le nouveau tableau de sélection.
 */
export default function PostSelector({ contentType, selectedPosts, onSelect }) {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (contentType !== "images") {
      fetchPosts();
    }
  }, [contentType]);

  const fetchPosts = async () => {
    setLoading(true);
    setError(null);

    try {
      const endpoint = `/wp/v2/${contentType}`;
      const response = await apiFetch({
        path: `${endpoint}?per_page=50&_embed`,
      });

      const formattedPosts = response.map((post) => ({
        id: post.id,
        title: post.title.rendered,
        excerpt: post.excerpt.rendered,
        link: post.link,
        featuredImage:
          post._embedded?.["wp:featuredmedia"]?.[0]?.source_url || "",
        featuredImageAlt:
          post._embedded?.["wp:featuredmedia"]?.[0]?.alt_text || "",
        date: post.date,
        type: contentType,
      }));

      setPosts(formattedPosts);
      setLoading(false);
    } catch (err) {
      setError(
        __(
          "Erreur lors du chargement du contenu. Vérifiez que le type de contenu existe.",
          "g2rd"
        )
      );
      setLoading(false);
    }
  };

  const handlePostSelect = (post) => {
    const isSelected = selectedPosts.some((p) => p.id === post.id);
    const newSelection = isSelected
      ? selectedPosts.filter((p) => p.id !== post.id)
      : [...selectedPosts, post];
    onSelect(newSelection);
  };

  if (contentType === "images") {
    return null;
  }

  return (
    <div className="g2rd-post-selector">
      {loading && (
        <div className="g2rd-post-selector__loading">
          <Spinner />
          <p>{__("Chargement du contenu…", "g2rd")}</p>
        </div>
      )}

      {error && (
        <Notice status="warning" isDismissible={false}>
          <p>{error}</p>
          <Button isSmall onClick={fetchPosts}>
            {__("Réessayer", "g2rd")}
          </Button>
        </Notice>
      )}

      {!loading && !error && (
        <div className="g2rd-post-selector__content">
          <p className="g2rd-post-selector__description">
            {__("Sélectionnez des éléments à afficher :", "g2rd")}
          </p>

          <div className="g2rd-post-selector__grid">
            {posts.map((post) => {
              const isSelected = selectedPosts.some((p) => p.id === post.id);
              return (
                <div
                  key={post.id}
                  className={`g2rd-post-selector__item ${isSelected ? "is-selected" : ""}`}
                  onClick={() => handlePostSelect(post)}
                  role="checkbox"
                  aria-checked={isSelected}
                  tabIndex={0}
                  onKeyDown={(e) => e.key === "Enter" && handlePostSelect(post)}
                >
                  {post.featuredImage && (
                    <div className="g2rd-post-selector__image">
                      <img
                        src={post.featuredImage}
                        alt={post.featuredImageAlt || post.title}
                      />
                    </div>
                  )}
                  <div className="g2rd-post-selector__info">
                    <h4>{post.title}</h4>
                    <p dangerouslySetInnerHTML={{ __html: post.excerpt }} />
                  </div>
                </div>
              );
            })}
          </div>

          {selectedPosts.length > 0 && (
            <div className="g2rd-post-selector__selected">
              <h4>{__("Sélectionnés :", "g2rd")}</h4>
              <div className="g2rd-post-selector__tags">
                {selectedPosts.map((post) => (
                  <span key={post.id} className="g2rd-post-selector__tag">
                    {post.title}
                  </span>
                ))}
              </div>
              <Button isSmall onClick={() => onSelect([])}>
                {__("Tout désélectionner", "g2rd")}
              </Button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
