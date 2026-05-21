/**
 * G2RDAiButton — Bouton de génération IA
 *
 * Gère les états idle / loading / error avec accessibilité RGAA :
 * - aria-busy pendant le chargement
 * - disabled pendant le chargement (évite les doubles soumissions)
 * - aria-label explicite pour les lecteurs d'écran
 * - focus visible conforme WCAG 2.1 AA (géré par le CSS)
 */

import { __ }             from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';

/**
 * @param {Object}   props
 * @param {string}   props.label      Libellé du bouton (visible + aria-label).
 * @param {Function} props.onClick    Callback de déclenchement.
 * @param {boolean}  props.loading    Vrai si une génération est en cours.
 * @param {boolean}  [props.disabled] Désactiver explicitement.
 * @param {string}   [props.variant]  Variante WP (primary|secondary|tertiary|link).
 * @param {string}   [props.className] Classes supplémentaires.
 */
export default function G2RDAiButton( {
	label,
	onClick,
	loading   = false,
	disabled  = false,
	variant   = 'secondary',
	className = '',
} ) {
	const isDisabled = disabled || loading;

	return (
		<Button
			variant={ variant }
			onClick={ onClick }
			disabled={ isDisabled }
			aria-busy={ loading }
			aria-label={ loading
				? /* translators: %s = libellé du bouton */ `${ __( 'Génération en cours', 'g2rd' ) } — ${ label }`
				: label
			}
			className={ `g2rd-ai-button${ loading ? ' g2rd-ai-button--loading' : '' }${ className ? ` ${ className }` : '' }` }
			__next40pxDefaultSize
		>
			{ loading ? (
				<>
					<Spinner aria-hidden="true" />
					<span className="screen-reader-text">
						{ __( 'Génération en cours…', 'g2rd' ) }
					</span>
				</>
			) : label }
		</Button>
	);
}
