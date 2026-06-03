<?php
/**
 * Endpoint de téléchargement du thème G2RD — côté g2rd.fr uniquement.
 *
 * Trois responsabilités :
 *   1. Mise en cache du ZIP de production à chaque release (hook webhook).
 *   2. Endpoint de téléchargement license-gated qui streame le ZIP depuis un
 *      cache local hors webroot (le ZIP n'est jamais servi en direct).
 *   3. Fourniture de l'URL de téléchargement (utilisée par l'email + le portail).
 *
 * IMPORTANT : ne s'active que si FluentCart est présent (donc uniquement sur
 * g2rd.fr). Sur les sites clients, cette classe reste totalement inerte.
 *
 * @package    G2RD
 * @since      1.20.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Classe ThemeDownload
 *
 * URL publique : https://g2rd.fr/download/g2rd-theme.zip?license=XXXX-XXXX-XXXX-XXXX
 */
class ThemeDownload {

	/** @var string Dossier de cache, sous WP_CONTENT_DIR (hors webroot logique). */
	private const CACHE_SUBDIR = 'g2rd-private';

	/** @var string Query var interne déclenchant le téléchargement. */
	private const QUERY_VAR = 'g2rd_download';

	/** @var int Nombre maximum de téléchargements par IP par fenêtre. */
	private const RATE_LIMIT_MAX = 30;

	/** @var int Durée de la fenêtre de rate limit (secondes). */
	private const RATE_LIMIT_WINDOW = 300;

	/** @var int Nombre maximum d'entrées conservées dans le journal. */
	private const LOG_MAX_ENTRIES = 200;

	/**
	 * Enregistre les hooks — uniquement si FluentCart est actif (g2rd.fr).
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if (!$this->is_fluent_cart_active()) {
			return;
		}

		// Mise en cache du ZIP à chaque release (hook déclenché par LicenseServer).
		\add_action('g2rd_release_webhook_received', [$this, 'cacheRelease'], 10, 2);

		// Endpoint de téléchargement.
		\add_action('init', [$this, 'addRewriteRule']);
		\add_filter('query_vars', [$this, 'addQueryVar']);
		\add_action('template_redirect', [$this, 'maybeHandleDownload']);

		// Rafraîchir les règles de réécriture au changement de thème.
		\add_action('after_switch_theme', static function (): void {
			\flush_rewrite_rules();
		});
	}

	/**
	 * Construit l'URL publique de téléchargement (avec clé optionnelle).
	 *
	 * @param string $license_key Clé de licence à inclure dans l'URL.
	 * @return string
	 */
	public static function get_download_url( string $license_key = '' ): string {
		$url = \home_url('/download/g2rd-theme.zip');
		if ('' !== $license_key) {
			$url = \add_query_arg('license', \rawurlencode($license_key), $url);
		}
		return $url;
	}

	// ── Réécriture / routing ──────────────────────────────────────────────────

	/**
	 * Enregistre la règle de réécriture pour l'URL jolie de téléchargement.
	 *
	 * @return void
	 */
	public function addRewriteRule(): void {
		\add_rewrite_rule(
			'^download/g2rd-theme\.zip/?$',
			'index.php?' . self::QUERY_VAR . '=1',
			'top'
		);
	}

	/**
	 * Déclare la query var interne.
	 *
	 * @param array<int, string> $vars Query vars publiques.
	 * @return array<int, string>
	 */
	public function addQueryVar( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	// ── Endpoint de téléchargement ────────────────────────────────────────────

	/**
	 * Intercepte la requête de téléchargement, valide la licence et streame le ZIP.
	 *
	 * @return void
	 */
	public function maybeHandleDownload(): void {
		if ('1' !== (string) \get_query_var(self::QUERY_VAR)) {
			return;
		}

		$ip = $this->client_ip();

		if ($this->is_rate_limited($ip)) {
			$this->abort(429, \__('Trop de requêtes. Réessayez dans quelques minutes.', 'g2rd'));
		}

		// La clé de licence dans l'URL fait office d'authentification (endpoint public).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- la clé de licence (validée côté serveur) est l'authentification ; pas de session WP sur un lien d'email.
		$license_key = isset($_GET['license']) ? \sanitize_text_field(\wp_unslash($_GET['license'])) : '';

		if ('' === $license_key) {
			$this->abort(401, \__('Clé de licence manquante.', 'g2rd'));
		}

		if (!(new LicenseServer())->is_key_valid_for_download($license_key)) {
			$this->log_download($license_key, $ip, 'denied');
			$this->abort(403, \__('Licence invalide ou expirée.', 'g2rd'));
		}

		$zip_path = $this->resolve_zip();
		if ('' === $zip_path) {
			$this->abort(404, \__('Aucune version disponible au téléchargement pour le moment.', 'g2rd'));
		}

		$this->log_download($license_key, $ip, 'allowed');
		$this->stream_zip($zip_path);
	}

	// ── Mise en cache du ZIP ──────────────────────────────────────────────────

	/**
	 * Met en cache le ZIP de la dernière release (hook g2rd_release_webhook_received).
	 *
	 * @param string $version      Version de la release.
	 * @param string $download_url URL du ZIP de production.
	 * @return void
	 */
	public function cacheRelease( $version, $download_url ): void {
		$download_url = (string) $download_url;
		if ('' === $download_url) {
			return;
		}
		$this->cache_zip_from_url($download_url, (string) $version);
	}

	/**
	 * Télécharge le ZIP depuis une URL et le met en cache de façon atomique.
	 *
	 * @param string $url     URL du ZIP source.
	 * @param string $version Version (pour nommer le fichier).
	 * @return string Chemin local du ZIP caché, ou '' en cas d'échec.
	 */
	private function cache_zip_from_url( string $url, string $version ): string {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		global $wp_filesystem;
		\WP_Filesystem();

		$dir = $this->ensure_private_dir();
		if ('' === $dir || !$wp_filesystem instanceof \WP_Filesystem_Base) {
			return '';
		}

		$tmp = \download_url($url, 60);
		if (\is_wp_error($tmp)) {
			if (\defined('WP_DEBUG') && WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- diagnostic conditionné à WP_DEBUG
				\error_log('G2RD ThemeDownload : échec du téléchargement du ZIP — ' . $tmp->get_error_message());
			}
			return '';
		}

		$safe_version = \preg_replace('/[^0-9A-Za-z._-]/', '', $version);
		$safe_version = ('' !== (string) $safe_version) ? $safe_version : 'latest';
		$dest         = $dir . '/g2rd-theme-' . $safe_version . '.zip';

		// Déplacement atomique (écrase si présent).
		$moved = $wp_filesystem->move($tmp, $dest, true);
		if (!$moved) {
			$wp_filesystem->delete($tmp);
			return '';
		}

		$this->purge_old_zips($dir, \basename($dest));
		\update_option('g2rd_cached_zip_path', $dest, false);
		\update_option('g2rd_cached_zip_version', $safe_version, false);

		return $dest;
	}

	/**
	 * Résout le chemin du ZIP à servir, avec lazy-cache si absent.
	 *
	 * @return string Chemin local, ou '' si indisponible.
	 */
	private function resolve_zip(): string {
		$path = (string) \get_option('g2rd_cached_zip_path', '');
		if ('' !== $path && \file_exists($path)) {
			return $path;
		}

		// Lazy-cache : pas encore de ZIP en cache (ex. après déploiement).
		$url = (string) \get_option('g2rd_latest_download_url', '');
		if ('' !== $url) {
			$cached = $this->cache_zip_from_url($url, (string) \get_option('g2rd_latest_version', ''));
			if ('' !== $cached && \file_exists($cached)) {
				return $cached;
			}
		}

		return '';
	}

	/**
	 * Crée le dossier de cache hors webroot avec garde-fous (.htaccess + index.php).
	 *
	 * @return string Chemin du dossier, ou '' en cas d'échec.
	 */
	private function ensure_private_dir(): string {
		$dir = \WP_CONTENT_DIR . '/' . self::CACHE_SUBDIR;

		if (!\is_dir($dir)) {
			\wp_mkdir_p($dir);
		}
		if (!\is_dir($dir)) {
			return '';
		}

		global $wp_filesystem;
		if (!$wp_filesystem instanceof \WP_Filesystem_Base) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			\WP_Filesystem();
		}

		$htaccess = $dir . '/.htaccess';
		if (!\file_exists($htaccess) && $wp_filesystem instanceof \WP_Filesystem_Base) {
			$wp_filesystem->put_contents(
				$htaccess,
				"Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
			);
		}

		$index = $dir . '/index.php';
		if (!\file_exists($index) && $wp_filesystem instanceof \WP_Filesystem_Base) {
			$wp_filesystem->put_contents($index, "<?php\n// Silence is golden.\n");
		}

		return $dir;
	}

	/**
	 * Supprime les anciens ZIP cachés, conserve uniquement le fichier courant.
	 *
	 * @param string $dir  Dossier de cache.
	 * @param string $keep Nom du fichier à conserver.
	 * @return void
	 */
	private function purge_old_zips( string $dir, string $keep ): void {
		$files = \glob($dir . '/g2rd-theme-*.zip');
		if (!\is_array($files)) {
			return;
		}
		foreach ($files as $file) {
			if (\basename($file) !== $keep) {
				\wp_delete_file($file);
			}
		}
	}

	// ── Stream + journalisation ───────────────────────────────────────────────

	/**
	 * Streame le ZIP au client puis termine la requête.
	 *
	 * @param string $path Chemin du fichier ZIP.
	 * @return void
	 */
	private function stream_zip( string $path ): void {
		if (\function_exists('set_time_limit')) {
			@\set_time_limit(0); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- environnements où set_time_limit est restreint
		}

		\nocache_headers();
		\header('Content-Type: application/zip');
		\header('Content-Disposition: attachment; filename="g2rd-theme.zip"');
		\header('Content-Length: ' . (string) \filesize($path));
		\header('X-Content-Type-Options: nosniff');

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- flux binaire direct vers le client (un readfile, pas de manipulation)
		\readfile($path);
		exit;
	}

	/**
	 * Journalise un téléchargement (clé tronquée à 8 caractères).
	 *
	 * @param string $license_key Clé de licence.
	 * @param string $ip          Adresse IP.
	 * @param string $decision    'allowed' ou 'denied'.
	 * @return void
	 */
	private function log_download( string $license_key, string $ip, string $decision ): void {
		$logs = (array) \get_option('g2rd_download_logs', []);

		$logs[] = [
			'date'       => \current_time('mysql'),
			'ip'         => $ip,
			'user_agent' => \substr(\sanitize_text_field(\wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255),
			'license'    => \substr($license_key, 0, 8),
			'decision'   => $decision,
		];

		if (\count($logs) > self::LOG_MAX_ENTRIES) {
			$logs = \array_slice($logs, -self::LOG_MAX_ENTRIES);
		}

		\update_option('g2rd_download_logs', $logs, false);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Termine la requête avec un code HTTP et un message en clair.
	 *
	 * @param int    $status  Code HTTP.
	 * @param string $message Message.
	 * @return void
	 */
	private function abort( int $status, string $message ): void {
		\status_header($status);
		\nocache_headers();
		\header('Content-Type: text/plain; charset=utf-8');
		echo \esc_html($message);
		exit;
	}

	/**
	 * Rate limit par IP (transient).
	 *
	 * @param string $ip Adresse IP.
	 * @return bool True si la limite est dépassée.
	 */
	private function is_rate_limited( string $ip ): bool {
		$key   = 'g2rd_dl_rl_' . \md5($ip);
		$count = (int) \get_transient($key);

		if ($count >= self::RATE_LIMIT_MAX) {
			return true;
		}

		\set_transient($key, $count + 1, self::RATE_LIMIT_WINDOW);
		return false;
	}

	/**
	 * Retourne l'IP client nettoyée.
	 *
	 * @return string
	 */
	private function client_ip(): string {
		$ip = \sanitize_text_field(\wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
		$ip = \preg_replace('/[^0-9a-fA-F.:]/', '', $ip);
		return ('' !== (string) $ip) ? $ip : 'unknown';
	}

	/**
	 * Indique si FluentCart est actif (donc on est sur g2rd.fr).
	 *
	 * @return bool
	 */
	private function is_fluent_cart_active(): bool {
		return \function_exists('fluentCart') || \class_exists('\FluentCart\App\App');
	}
}
