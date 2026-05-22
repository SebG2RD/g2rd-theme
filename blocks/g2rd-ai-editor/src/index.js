/**
 * G2RD AI Editor — Entrée plugin Gutenberg
 *
 * Enregistre une petite sidebar "launcher" dont le seul rôle
 * est d'ouvrir la modal principale G2RDAiModal.
 * L'icône IA reste visible dans la toolbar de l'éditeur.
 */

import { useState, useCallback } from '@wordpress/element';
import { registerPlugin }        from '@wordpress/plugins';
import { PluginSidebar }         from '@wordpress/editor';
import { useDispatch }           from '@wordpress/data';
import { __ }                    from '@wordpress/i18n';

import G2RDAiModal from './components/G2RDAiModal';
import './style.css';

const config = window.g2rdAiConfig ?? {};

const AiIcon = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="20"
		height="20"
		fill="none"
		aria-hidden="true"
		focusable="false"
	>
		<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"
			stroke="currentColor" strokeWidth="1.5" />
		<path d="M8 12h8M12 8v8" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
		<circle cx="17" cy="7" r="2" fill="currentColor" />
	</svg>
);

if ( config.editorEnabled ) {
	registerPlugin( 'g2rd-ai-editor', {
		icon:   AiIcon,
		render: function G2RDAiEditorPlugin() {
			const [ modalOpen, setModalOpen ] = useState( false );
			const { closeGeneralSidebar }     = useDispatch( 'core/edit-post' );

			const openModal = useCallback( () => {
				closeGeneralSidebar?.();
				setModalOpen( true );
			}, [ closeGeneralSidebar ] );

			return (
				<>
					<PluginSidebar
						name="g2rd-ai-editor"
						title={ __( 'Assistant IA G2RD', 'g2rd' ) }
						icon={ AiIcon }
						className="g2rd-ai-launcher-sidebar"
					>
						<div className="g2rd-ail">
							<div className="g2rd-ail__star" aria-hidden="true">✦</div>
							<p className="g2rd-ail__title">
								{ __( 'Assistant IA G2RD', 'g2rd' ) }
							</p>
							<p className="g2rd-ail__desc">
								{ __( 'Génère du contenu optimisé pour vos pages, articles et blocs Gutenberg.', 'g2rd' ) }
							</p>
							<button
								className="g2rd-ail__btn"
								onClick={ openModal }
								type="button"
							>
								{ __( "Ouvrir l'assistant", 'g2rd' ) }
							</button>
							{ ! config.connectorReady && (
								<p className="g2rd-ail__warn">
									<a href={ config.settingsUrl } target="_blank" rel="noreferrer">
										{ __( '⚠ Clé API Anthropic manquante', 'g2rd' ) }
									</a>
								</p>
							) }
						</div>
					</PluginSidebar>

					{ modalOpen && (
						<G2RDAiModal onClose={ () => setModalOpen( false ) } />
					) }
				</>
			);
		},
	} );
}
