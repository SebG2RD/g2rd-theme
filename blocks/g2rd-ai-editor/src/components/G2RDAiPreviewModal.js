/**
 * G2RDAiPreviewModal — Modal de prévisualisation et validation IA
 *
 * Règles UX non négociables :
 * - Jamais d'insertion automatique (toujours un clic explicite "Insérer")
 * - Focus trap : Tab/Shift+Tab circule uniquement dans la modal
 * - Fermeture par Escape avec restitution du focus
 * - aria-modal + aria-labelledby pour les lecteurs d'écran
 *
 * RGAA : 7.3 (focus géré), 7.4 (focus restitué), 10.7 (visible au focus)
 */

import { useEffect, useRef, useCallback, useMemo } from '@wordpress/element';
import { __, sprintf }  from '@wordpress/i18n';
import { Modal, Button } from '@wordpress/components';
import G2RDAiNotice      from './G2RDAiNotice';

/** Identifiant du titre de la modal pour aria-labelledby */
const MODAL_TITLE_ID = 'g2rd-ai-modal-title';

/**
 * @param {Object}        props
 * @param {boolean}       props.isOpen        Vrai si la modal est ouverte.
 * @param {string|null}   props.result        Résultat texte à prévisualiser.
 * @param {any}           props.parsed        Résultat parsé (objet ou string).
 * @param {string}        props.actionLabel   Libellé de l'action (pour le titre).
 * @param {boolean}       props.loading       Spinner si régénération en cours.
 * @param {string|null}   props.error         Erreur à afficher dans la modal.
 * @param {Function}      props.onInsert      Callback d'insertion (reçoit parsed).
 * @param {Function}      props.onRegenerate  Callback de régénération.
 * @param {Function}      props.onClose       Callback de fermeture.
 */
export default function G2RDAiPreviewModal( {
	isOpen,
	result,
	parsed,
	actionLabel,
	loading,
	error,
	onInsert,
	onRegenerate,
	onClose,
} ) {
	// Ref vers l'élément qui avait le focus avant l'ouverture.
	const triggerRef = useRef( null );

	// Capturer l'élément actif à l'ouverture pour restitution.
	useEffect( () => {
		if ( isOpen ) {
			triggerRef.current = document.activeElement;
		}
	}, [ isOpen ] );

	// Restituer le focus à la fermeture.
	useEffect( () => {
		if ( ! isOpen && triggerRef.current ) {
			triggerRef.current.focus();
			triggerRef.current = null;
		}
	}, [ isOpen ] );

	const handleCopy = useCallback( () => {
		if ( ! result ) return;
		navigator.clipboard?.writeText( result ).catch( () => {
			// Fallback si Clipboard API indisponible.
			const el = document.createElement( 'textarea' );
			el.value = result;
			document.body.appendChild( el );
			el.select();
			document.execCommand( 'copy' );
			document.body.removeChild( el );
		} );
	}, [ result ] );

	const handleInsert = useCallback( () => {
		onInsert?.( parsed );
		onClose?.();
	}, [ onInsert, onClose, parsed ] );

	// Formatage de la prévisualisation — mémoïsé pour éviter le re-render.
	const previewContent = useMemo( () => {
		if ( ! result ) return null;

		if ( typeof parsed === 'object' && parsed !== null ) {
			return (
				<pre className="g2rd-ai-modal__json" aria-label={ __( 'Prévisualisation du contenu généré', 'g2rd' ) }>
					{ JSON.stringify( parsed, null, 2 ) }
				</pre>
			);
		}

		return (
			<p className="g2rd-ai-modal__text" aria-label={ __( 'Prévisualisation du contenu généré', 'g2rd' ) }>
				{ result }
			</p>
		);
	}, [ result, parsed ] );

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ sprintf(
				/* translators: %s = libellé de l'action IA */
				__( 'Proposition IA — %s', 'g2rd' ),
				actionLabel
			) }
			onRequestClose={ onClose }
			className="g2rd-ai-modal"
			aria={ { labelledby: MODAL_TITLE_ID } }
		>
			{ /* Annonce accessible — résultat prêt */ }
			{ result && ! loading && (
				<div role="status" aria-live="polite" className="screen-reader-text">
					{ __( 'Proposition prête, veuillez la réviser avant d\'insérer.', 'g2rd' ) }
				</div>
			) }

			{ /* Erreur dans la modal */ }
			{ error && (
				<G2RDAiNotice
					message={ error }
					type="error"
				/>
			) }

			{ /* Prévisualisation */ }
			{ ! error && (
				<div className="g2rd-ai-modal__preview" aria-busy={ loading }>
					{ loading ? (
						<div className="g2rd-ai-modal__spinner" role="status" aria-live="polite">
							<span className="g2rd-ai-spinner-icon" aria-hidden="true" />
							<span>{ __( 'Régénération en cours…', 'g2rd' ) }</span>
						</div>
					) : previewContent }
				</div>
			) }

			{ /* Actions — jamais de disabled sur "Annuler" */ }
			<div className="g2rd-ai-modal__actions" role="group" aria-label={ __( 'Actions sur la proposition', 'g2rd' ) }>
				<Button
					variant="primary"
					onClick={ handleInsert }
					disabled={ ! result || loading }
					aria-label={ __( 'Insérer le contenu généré dans le bloc', 'g2rd' ) }
					__next40pxDefaultSize
				>
					{ __( 'Insérer', 'g2rd' ) }
				</Button>

				<Button
					variant="secondary"
					onClick={ handleCopy }
					disabled={ ! result || loading }
					aria-label={ __( 'Copier le contenu généré dans le presse-papier', 'g2rd' ) }
					__next40pxDefaultSize
				>
					{ __( 'Copier', 'g2rd' ) }
				</Button>

				<Button
					variant="tertiary"
					onClick={ onRegenerate }
					disabled={ loading }
					aria-label={ __( 'Régénérer une nouvelle proposition', 'g2rd' ) }
					__next40pxDefaultSize
				>
					{ __( 'Régénérer', 'g2rd' ) }
				</Button>

				<Button
					variant="tertiary"
					onClick={ onClose }
					aria-label={ __( 'Annuler et fermer sans insérer', 'g2rd' ) }
					__next40pxDefaultSize
				>
					{ __( 'Annuler', 'g2rd' ) }
				</Button>
			</div>
		</Modal>
	);
}
