/**
 * Carte des actions IA par type de bloc et endpoint REST.
 *
 * Hoissé au niveau module (jamais recréé à chaque render).
 */

/** @type {Object.<string, Array<{id: string, label: string, targetAttr: string|null}>>} */
export const BLOCK_ACTIONS = {
	'g2rd/hero': [
		{ id: 'hero-heading',    label: 'Générer un titre H1',        targetAttr: 'heading' },
		{ id: 'hero-subheading', label: 'Générer un sous-titre',       targetAttr: 'subheading' },
		{ id: 'hero-cta',        label: 'Générer 3 CTA',               targetAttr: null }, // JSON array → modal
		{ id: 'hero-rewrite',    label: 'Réécrire le texte',           targetAttr: 'heading' },
		{ id: 'hero-seo-local',  label: 'Optimiser pour le SEO local', targetAttr: 'heading' },
	],
	'g2rd/faq': [
		{ id: 'faq-generate', label: 'Générer une FAQ complète', targetAttr: null }, // JSON → modal
	],
	'g2rd/cta-band': [
		{ id: 'cta-texts', label: 'Générer textes CTA', targetAttr: null }, // JSON → modal
	],
	'g2rd/pricing-table': [
		{ id: 'pricing-benefits', label: 'Générer contenu offres', targetAttr: null },
	],
	'g2rd/testimonial': [
		{ id: 'testimonial', label: 'Améliorer le témoignage', targetAttr: null },
	],
	'g2rd/card': [
		{ id: 'image-alt', label: 'Générer texte alt + légende', targetAttr: null },
	],
};

/**
 * Options de ton disponibles.
 * Hoissé au niveau module — jamais recréé.
 *
 * @type {Array<{label: string, value: string}>}
 */
export const TONE_OPTIONS = [
	{ label: 'Professionnel',  value: 'professionnel' },
	{ label: 'Décontracté',    value: 'decontracte' },
	{ label: 'Technique',      value: 'technique' },
	{ label: 'Humain',         value: 'humain' },
	{ label: 'Commercial',     value: 'commercial' },
];

/**
 * Options de longueur disponibles.
 *
 * @type {Array<{label: string, value: string}>}
 */
export const LENGTH_OPTIONS = [
	{ label: 'Court',  value: 'court' },
	{ label: 'Moyen',  value: 'moyen' },
	{ label: 'Long',   value: 'long' },
];


/**
 * Construit le payload pour un endpoint REST IA.
 *
 * @param {string} endpoint   Route relative (ex. 'block-action').
 * @param {Object} actionData Données spécifiques à l'action.
 * @param {Object} ctx        Contexte partagé (activity, city, tone, language…).
 * @returns {Object}
 */
export function buildPayload( endpoint, actionData, ctx ) {
	const config = window.g2rdAiConfig ?? {};

	return {
		...actionData,
		context: {
			tone:     config.tone     ?? 'professionnel',
			language: config.language ?? 'fr',
			length:   'moyen',
			...ctx,
		},
	};
}
