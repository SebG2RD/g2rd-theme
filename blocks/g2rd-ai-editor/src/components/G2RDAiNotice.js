/**
 * G2RDAiNotice — Notice d'erreur ou de succès IA
 *
 * RGAA : role="alert" pour les erreurs (assertive), role="status" pour
 * les succès (polite). Annonce dynamiquement aux lecteurs d'écran.
 */

import { Notice } from '@wordpress/components';

/**
 * @param {Object}          props
 * @param {string}          props.message  Texte à afficher.
 * @param {'error'|'success'|'info'|'warning'} props.type Type de notice.
 * @param {Function|null}   [props.onDismiss] Callback de fermeture.
 */
export default function G2RDAiNotice( {
	message,
	type      = 'error',
	onDismiss = null,
} ) {
	if ( ! message ) {
		return null;
	}

	// RGAA : erreurs = assertive, succès/info = polite.
	const ariaRole = 'error' === type ? 'alert' : 'status';
	const ariaLive = 'error' === type ? 'assertive' : 'polite';

	return (
		<div
			role={ ariaRole }
			aria-live={ ariaLive }
			aria-atomic="true"
			className="g2rd-ai-notice"
		>
			<Notice
				status={ type }
				isDismissible={ !! onDismiss }
				onDismiss={ onDismiss ?? undefined }
			>
				{ message }
			</Notice>
		</div>
	);
}
