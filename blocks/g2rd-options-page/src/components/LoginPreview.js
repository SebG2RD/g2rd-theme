import { useMemo } from '@wordpress/element';
import { LOGIN_DEFAULTS } from './login-defaults';

const { themeUri = '' } = window.G2RDOptionsData || {};

const DEFAULT_LOGO = themeUri
	? `${ themeUri }/assets/img/Nouveau-logo-G2RD-Agence-Web-blanc-Horizontale@3x.png`
	: '';

export function LoginPreview( { settings } ) {
	const {
		layout          = LOGIN_DEFAULTS.layout,
		panelColor      = LOGIN_DEFAULTS.panelColor,
		buttonColor     = LOGIN_DEFAULTS.buttonColor,
		buttonTextColor = LOGIN_DEFAULTS.buttonTextColor,
		linksColor      = LOGIN_DEFAULTS.linksColor,
		bgType          = LOGIN_DEFAULTS.bgType,
		bgColor         = LOGIN_DEFAULTS.bgColor,
		bgImageUrl      = LOGIN_DEFAULTS.bgImageUrl,
		logoUrl         = LOGIN_DEFAULTS.logoUrl,
		welcomeText     = LOGIN_DEFAULTS.welcomeText,
		borderRadius    = LOGIN_DEFAULTS.borderRadius,
		ctaShow         = LOGIN_DEFAULTS.ctaShow,
		ctaText         = LOGIN_DEFAULTS.ctaText,
		ctaColor        = LOGIN_DEFAULTS.ctaColor,
	} = settings || {};

	const isOneCol = layout === 'one-column';

	const bgStyle = useMemo( () => {
		if ( isOneCol ) return { background: bgColor };
		if ( bgType === 'image' ) {
			const url = bgImageUrl || ( themeUri ? `${ themeUri }/assets/img/g2rd_image_admin.jpg` : '' );
			return url
				? { backgroundImage: `url(${ url })`, backgroundSize: 'cover', backgroundPosition: 'center' }
				: { background: bgColor };
		}
		return { background: bgColor };
	}, [ isOneCol, bgType, bgImageUrl, bgColor ] );

	const logo = logoUrl || DEFAULT_LOGO;

	/*
	 * Les réglages de l'utilisateur passent par des variables CSS plutôt que
	 * par un attribut style sur chaque élément : le rayon était recopié cinq
	 * fois et la couleur de texte deux, si bien qu'un oubli désynchronisait
	 * l'aperçu du rendu réel. Ces valeurs sont les seules encore en ligne,
	 * puisqu'elles changent à chaque frappe.
	 */
	const previewVars = {
		'--g2rd-lp-radius':  `${ borderRadius }px`,
		'--g2rd-lp-button':  buttonColor,
		'--g2rd-lp-on-button': buttonTextColor,
		'--g2rd-lp-links':   linksColor,
		'--g2rd-lp-cta':     ctaColor,
	};

	return (
		<div className="g2rd-login-preview">
			<div className="g2rd-lp-wrap" style={ previewVars }>
				{ ! isOneCol && (
					<div className="g2rd-lp-image" style={ bgStyle } />
				) }

				<div
					className={ `g2rd-lp-panel${ isOneCol ? ' is-one-col' : '' }` }
					style={ isOneCol ? bgStyle : { background: panelColor } }
				>
					{ logo && (
						<img src={ logo } alt="Logo" className="g2rd-lp-logo" />
					) }
					{ welcomeText && (
						<p className="g2rd-lp-welcome">
							{ welcomeText }
						</p>
					) }

					<div className="g2rd-lp-form">
						<div className="g2rd-lp-field" />
						<div className="g2rd-lp-field" />
						<div className="g2rd-lp-submit">
							Se connecter
						</div>
					</div>

					<span className="g2rd-lp-link">Mot de passe oublié ?</span>

					{ ctaShow && ctaText && (
						<div className="g2rd-lp-cta">
							{ ctaText }
						</div>
					) }
				</div>
			</div>
			<p className="g2rd-login-preview__hint">Aperçu approximatif — le rendu final peut légèrement différer.</p>
		</div>
	);
}
