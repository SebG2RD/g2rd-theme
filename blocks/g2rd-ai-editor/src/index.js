/**
 * G2RD AI Editor — Plugin sidebar Gutenberg
 *
 * Enregistre le panneau "Assistant IA G2RD" dans la barre latérale de l'éditeur.
 * Accessible via l'icône dans la barre d'outils supérieure.
 *
 * Chargé uniquement si g2rdAiConfig.editorEnabled === true.
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar }  from '@wordpress/editor';
import { __ }             from '@wordpress/i18n';

import G2RDAiSidebar from './components/G2RDAiSidebar';
import './style.css';

const config = window.g2rdAiConfig ?? {};

/** Icône SVG du module IA */
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
		<path
			d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<path
			d="M8 12h8M12 8v8"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
		/>
		<circle cx="17" cy="7" r="2" fill="currentColor" />
	</svg>
);

// La sidebar éditoriale n'est enregistrée que si elle est activée.
if ( config.editorEnabled ) {
	registerPlugin( 'g2rd-ai-editor', {
		icon:   AiIcon,
		render: function G2RDAiEditorPlugin() {
			return (
				<PluginSidebar
					name="g2rd-ai-editor"
					title={ __( 'Assistant IA G2RD', 'g2rd' ) }
					icon={ AiIcon }
					className="g2rd-ai-sidebar"
				>
					<G2RDAiSidebar />
				</PluginSidebar>
			);
		},
	} );
}
