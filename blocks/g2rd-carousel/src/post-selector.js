import { __ } from "@wordpress/i18n";
import { useState, useEffect } from "@wordpress/element";
import { Button, Spinner, Notice } from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";

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
      let endpoint = "/wp/v2/";

      // Déterminer l'endpoint selon le type de contenu
      switch (contentType) {
        case "posts":
          endpoint += "posts";
          break;
        case "pages":
          endpoint += "pages";
          break;
        case "portfolio":
          endpoint += "portfolio";
          break;
        case "prestations":
          endpoint += "prestations";
          break;
        case "qui-sommes-nous":
          endpoint += "qui-sommes-nous";
          break;
        default:
          endpoint += "posts";
      }

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
      console.error("Error fetching posts:", err);
      setError(
        __(
          `Erreur lors du chargement des ${contentType}. Vérifiez que le type de contenu existe.`,
          "g2rd-carousel"
        )
      );
      setLoading(false);
    }
  };

  const handlePostSelect = (post) => {
    const isSelected = selectedPosts.some((p) => p.id === post.id);
    let newSelection;

    if (isSelected) {
      newSelection = selectedPosts.filter((p) => p.id !== post.id);
    } else {
      newSelection = [...selectedPosts, post];
    }

    onSelect(newSelection);
  };

  const handleDeselectAll = () => {
    onSelect([]);
  };

  if (contentType === "images") {
    return null;
  }

  return (
    <div className="post-selector">
      {loading && (
        <div className="post-selector-loading">
          <Spinner />
          <p>{__("Chargement du contenu...", "g2rd-carousel")}</p>
        </div>
      )}

      {error && (
        <Notice status="warning" isDismissible={false}>
          <p>{error}</p>
          <Button isSmall onClick={fetchPosts}>
            {__("Réessayer", "g2rd-carousel")}
          </Button>
        </Notice>
      )}

      {!loading && !error && (
        <div className="post-selector-content">
          <p className="post-selector-description">
            {__(
              `Sélectionnez des ${contentType} à afficher dans le carousel:`,
              "g2rd-carousel"
            )}
          </p>

          <div className="post-selector-grid">
            {posts.map((post) => {
              const isSelected = selectedPosts.some((p) => p.id === post.id);
              return (
                <div
                  key={post.id}
                  className={`post-selector-item ${
                    isSelected ? "selected" : ""
                  }`}
                  onClick={() => handlePostSelect(post)}
                >
                  {post.featuredImage && (
                    <div className="post-selector-image">
                      <img
                        src={post.featuredImage}
                        alt={post.featuredImageAlt || post.title}
                      />
                    </div>
                  )}
                  <div className="post-selector-info">
                    <h4>{post.title}</h4>
                    <p dangerouslySetInnerHTML={{ __html: post.excerpt }} />
                  </div>
                </div>
              );
            })}
          </div>

          {selectedPosts.length > 0 && (
            <div className="post-selector-selected">
              <h4>{__("Contenu sélectionné:", "g2rd-carousel")}</h4>
              <div className="selected-posts">
                {selectedPosts.map((post) => (
                  <span key={post.id} className="selected-post">
                    {post.title}
                  </span>
                ))}
              </div>
              <Button isSmall onClick={handleDeselectAll}>
                {__("Tout désélectionner", "g2rd-carousel")}
              </Button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
