import { ToggleControl, SelectControl, Button } from '@wordpress/components';

const { pages, onboardingUrl } = window.G2RDOptionsData || {};

const PAGE_OPTIONS = [
	{ label: '— Sélectionner une page —', value: '0' },
	...( pages || [] ).map( ( { id, title } ) => ( { label: title, value: String( id ) } ) ),
];

const COMING_SOON_DEFAULT = { enabled: false, page_id: 0 };

export function TabMaintenance( { settings, update } ) {
	const comingSoon = settings.comingSoon || COMING_SOON_DEFAULT;

	return (
		<div className="g2rd-tab-content">

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-clock"></span>
					Mode « Bientôt disponible »
				</h2>
				<p className="g2rd-section__desc">
					Redirige les visiteurs non connectés vers une page de maintenance.
					Les administrateurs continuent de voir le site normalement.
				</p>
				<div className="g2rd-card">
					<ToggleControl
						label="Activer le mode bientôt disponible"
						checked={ !! comingSoon.enabled }
						onChange={ ( val ) =>
							update( [ 'comingSoon', 'enabled' ], val )
						}
						__nextHasNoMarginBottom
					/>
					{ comingSoon.enabled && (
						<div className="g2rd-indent">
							<SelectControl
								label="Page de maintenance"
								help="La page affichée aux visiteurs pendant la maintenance."
								value={ String( comingSoon.page_id || 0 ) }
								options={ PAGE_OPTIONS }
								onChange={ ( val ) =>
									update( [ 'comingSoon', 'page_id' ], parseInt( val, 10 ) )
								}
								__nextHasNoMarginBottom
							/>
						</div>
					) }
				</div>
			</section>

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-welcome-learn-more"></span>
					Assistant de démarrage
				</h2>
				<p className="g2rd-section__desc">
					Relancez l'assistant G2RD pour reconfigurer les bases du thème.
				</p>
				<div className="g2rd-card">
					<Button
						variant="secondary"
						href={ onboardingUrl }
						icon="welcome-learn-more"
					>
						Ouvrir l'assistant de démarrage
					</Button>
				</div>
			</section>

		</div>
	);
}
