/**
 * Gestion du mode sombre (Dark Mode)
 *
 * Ce script gère :
 * - La détection de la préférence système (prefers-color-scheme)
 * - Le toggle manuel via un bouton flottant
 * - La sauvegarde de la préférence dans localStorage et via AJAX (utilisateurs connectés)
 * - L'application des classes CSS pour le dark mode
 *
 * @package G2RD
 * @since 1.0.0
 */

(function () {
	'use strict';

	// Configuration (synchronisée avec la classe PHP DarkMode)
	const STORAGE_KEY   = (typeof g2rdDarkMode !== 'undefined') ? g2rdDarkMode.preferenceKey : 'g2rd_dark_mode';
	const BODY_CLASS    = 'dark-mode-active';
	const DATA_ATTR     = 'data-theme';

	/**
	 * Gestionnaire du dark mode
	 */
	class DarkModeManager {
		constructor() {
			this.isDarkMode = false;
			this.init();
		}

		/**
		 * Initialise l'état du dark mode
		 * Priorité : localStorage → cookie PHP → préférence système
		 */
		init() {
			const saved = localStorage.getItem(STORAGE_KEY);

			if (saved === 'enabled') {
				this.enableDarkMode(false);
			} else if (saved === 'disabled') {
				this.disableDarkMode(false);
			} else {
				// Aucune préférence sauvegardée → suivre le système
				this.followSystemPreference();
			}

			this.createToggleButton();

			// Écouter les changements de préférence système
			if (window.matchMedia) {
				window.matchMedia('(prefers-color-scheme: dark)')
					.addEventListener('change', () => {
						// Respecter uniquement si aucun choix manuel
						if (!localStorage.getItem(STORAGE_KEY)) {
							this.followSystemPreference();
						}
					});
			}
		}

		/**
		 * Synchronise le cookie PHP avec l'état JS.
		 * Nécessaire car PHP lit le cookie côté serveur pour ajouter la classe body.
		 *
		 * @param {string} state - 'enabled' ou 'disabled'
		 */
		setCookie(state) {
			try {
				const maxAge = 365 * 24 * 60 * 60;
				document.cookie = STORAGE_KEY + '=' + state + '; path=/; max-age=' + maxAge + '; SameSite=Strict';
			} catch(e) {}
		}

		/**
		 * Active le mode sombre
		 *
		 * @param {boolean} save - Sauvegarder la préférence (false lors de l'init)
		 */
		enableDarkMode(save = true) {
			this.isDarkMode = true;
			document.body.classList.add(BODY_CLASS);
			document.documentElement.setAttribute(DATA_ATTR, 'dark');
			this.setCookie('enabled');

			if (save) {
				localStorage.setItem(STORAGE_KEY, 'enabled');
				this.persistViaAjax('enabled');
			}

			this.updateToggleButton();
		}

		/**
		 * Désactive le mode sombre
		 *
		 * @param {boolean} save - Sauvegarder la préférence (false lors de l'init)
		 */
		disableDarkMode(save = true) {
			this.isDarkMode = false;
			document.body.classList.remove(BODY_CLASS);
			document.documentElement.removeAttribute(DATA_ATTR);
			this.setCookie('disabled');

			if (save) {
				localStorage.setItem(STORAGE_KEY, 'disabled');
				this.persistViaAjax('disabled');
			}

			this.updateToggleButton();
		}

		/**
		 * Suit la préférence système
		 */
		followSystemPreference() {
			const prefersDark = window.matchMedia &&
				window.matchMedia('(prefers-color-scheme: dark)').matches;

			if (prefersDark) {
				this.enableDarkMode(false);
			} else {
				this.disableDarkMode(false);
			}
		}

		/**
		 * Bascule entre dark et light
		 */
		toggle() {
			if (this.isDarkMode) {
				this.disableDarkMode();
			} else {
				this.enableDarkMode();
			}
		}

		/**
		 * Persiste la préférence via AJAX (utilisateurs connectés)
		 *
		 * @param {string} state - 'enabled' ou 'disabled'
		 */
		persistViaAjax(state) {
			if (typeof g2rdDarkMode === 'undefined' || !g2rdDarkMode.isUserLogged) {
				return;
			}

			const data = new FormData();
			data.append('action',  'g2rd_toggle_dark_mode');
			data.append('nonce',   g2rdDarkMode.nonce);
			data.append('enabled', state);

			fetch(g2rdDarkMode.ajaxUrl, { method: 'POST', body: data })
				.catch(() => {
					// Silencieux : la préférence est déjà en localStorage
				});
		}

		/**
		 * Crée le bouton toggle flottant
		 */
		createToggleButton() {
			if (document.getElementById('g2rd-dark-mode-toggle')) {
				return;
			}

			const btn = document.createElement('button');
			btn.id        = 'g2rd-dark-mode-toggle';
			btn.className = 'g2rd-dark-mode-toggle';
			btn.setAttribute('aria-label', 'Basculer le mode sombre');
			btn.innerHTML = this.getToggleIcon();

			btn.addEventListener('click', () => this.toggle());

			// Insérer après le bouton d'accessibilité s'il existe, sinon en début de body
			const accessBtn = document.querySelector('.accessibility-floating-btn');
			if (accessBtn && accessBtn.parentNode) {
				accessBtn.parentNode.insertBefore(btn, accessBtn.nextSibling);
			} else {
				document.body.insertBefore(btn, document.body.firstChild);
			}
		}

		/**
		 * Met à jour l'icône du bouton
		 */
		updateToggleButton() {
			const btn = document.getElementById('g2rd-dark-mode-toggle');
			if (btn) {
				btn.innerHTML = this.getToggleIcon();
				btn.setAttribute('aria-label',
					this.isDarkMode ? 'Désactiver le mode sombre' : 'Activer le mode sombre'
				);
				btn.setAttribute('aria-pressed', String(this.isDarkMode));
			}
		}

		/**
		 * Retourne l'icône SVG selon l'état courant
		 */
		getToggleIcon() {
			if (this.isDarkMode) {
				// Soleil → repasser en mode clair
				return '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/><line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/></svg>';
			}
			// Lune → activer le dark mode
			return '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
		}
	}

	// Initialiser quand le DOM est prêt
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => new DarkModeManager());
	} else {
		new DarkModeManager();
	}
})();
