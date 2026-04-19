/**
 * GEO Analyzer — Plugin sidebar Gutenberg
 *
 * Enregistre le panneau GEO Analyzer dans la barre latérale de l'éditeur.
 * Accessible via l'icône dans la barre d'outils supérieure ou le menu "Plus d'options".
 */

import { registerPlugin }             from '@wordpress/plugins';
import { PluginSidebar }              from '@wordpress/editor';
import { __ }                         from '@wordpress/i18n';
import GeoPanel                       from './panel/GeoPanel';

// Import des styles du panneau
import './style.css';

/** Icône SVG custom du plugin */
const GeoIcon = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="20"
		height="20"
		fill="none"
		aria-hidden="true"
	>
		<circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" />
		<path
			d="M12 6v6l4 2"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
		<path
			d="M6 18l2-2M18 6l-2 2"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
		/>
	</svg>
);

registerPlugin( 'g2rd-geo-analyzer', {
	icon:   GeoIcon,
	render: function G2RDGeoAnalyzerPlugin() {
		return (
			<PluginSidebar
				name="g2rd-geo-analyzer"
				title={ __( 'GEO Analyzer', 'g2rd' ) }
				icon={ GeoIcon }
				className="g2rd-geo-sidebar"
			>
				<GeoPanel />
			</PluginSidebar>
		);
	},
} );
