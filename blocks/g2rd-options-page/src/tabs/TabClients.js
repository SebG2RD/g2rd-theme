import { ToggleControl, TextareaControl } from '@wordpress/components';

export function TabClients( { settings, update } ) {
	const clientMode    = !! settings.clientMode;
	const clientMessage = settings.clientMessage || '';
	const seoHelper     = settings.seoHelper !== false;

	return (
		<div className="g2rd-tab-content">

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-admin-users"></span>
					Mode client
				</h2>
				<p className="g2rd-section__desc">
					Simplifie l'interface d'administration WordPress pour vos clients non techniques.
					Masque les menus avancés et affiche un message d'accueil personnalisé.
				</p>
				<div className="g2rd-card">
					<ToggleControl
						label="Activer le mode client"
						help="Les utilisateurs sans rôle Administrateur verront une interface simplifiée."
						checked={ clientMode }
						onChange={ ( val ) => update( [ 'clientMode' ], val ) }
						__nextHasNoMarginBottom
					/>
					{ clientMode && (
						<div className="g2rd-indent">
							<TextareaControl
								label="Message d'accueil"
								help="Affiché sur le tableau de bord. HTML basique autorisé."
								value={ clientMessage }
								onChange={ ( val ) => update( [ 'clientMessage' ], val ) }
								rows={ 4 }
								__nextHasNoMarginBottom
							/>
						</div>
					) }
				</div>
			</section>

			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-search"></span>
					Aide SEO
				</h2>
				<p className="g2rd-section__desc">
					Affiche des indicateurs SEO légers directement dans la barre latérale de l'éditeur Gutenberg.
				</p>
				<div className="g2rd-card">
					<ToggleControl
						label="Activer l'aide SEO dans l'éditeur"
						help="Analyse le titre, la méta-description et la structure des titres en temps réel."
						checked={ seoHelper }
						onChange={ ( val ) => update( [ 'seoHelper' ], val ) }
						__nextHasNoMarginBottom
					/>
				</div>
			</section>

		</div>
	);
}
