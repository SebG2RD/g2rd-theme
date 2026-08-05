/**
 * Valeurs par défaut de la page de connexion personnalisée.
 *
 * Elles étaient recopiées dans TabConnexion.js et dans LoginPreview.js : un
 * défaut modifié d'un seul côté faisait mentir l'aperçu sur ce que verrait
 * réellement l'utilisateur en se connectant.
 *
 * L'autorité reste PHP — `LoginCustomizer::DEFAULTS` s'applique à
 * l'enregistrement. Cette table doit lui rester identique ; elle sert
 * uniquement à afficher la bonne valeur avant tout enregistrement.
 *
 * @see classes/class-login-customizer.php
 */
export const LOGIN_DEFAULTS = {
	enabled:              true,
	layout:               'two-columns',
	logoUrl:              '',
	logoLink:             '',
	panelColor:           '#2f425d',
	buttonColor:          '#d4a373',
	buttonTextColor:      '#ffffff',
	buttonHoverColor:     '#c4935c',
	buttonHoverTextColor: '#ffffff',
	linksColor:           '#cccccc',
	bgType:               'image',
	bgColor:              '#1a2a3a',
	bgImageUrl:           '',
	ctaShow:              true,
	ctaText:              'Visiter notre site',
	ctaUrl:               'https://g2rd.fr',
	ctaColor:             '#d4a373',
	ctaHoverColor:        '#c4935c',
	ctaHoverTextColor:    '#ffffff',
	welcomeText:          '',
	borderRadius:         8,
};
