import { useCallback } from '@wordpress/element';
import { ToggleControl, SelectControl, TextControl, RangeControl } from '@wordpress/components';
import { ColorInput } from '../components/ColorInput';
import { MediaPicker } from '../components/MediaPicker';
import { LoginPreview } from '../components/LoginPreview';

const LAYOUT_OPTIONS = [
	{ label: 'Deux colonnes (formulaire + image)', value: 'two-columns' },
	{ label: 'Une colonne (formulaire centré)',     value: 'one-column' },
];

const BG_TYPE_OPTIONS = [
	{ label: 'Image',        value: 'image' },
	{ label: 'Couleur unie', value: 'color' },
];

export function TabConnexion( { settings, update } ) {
	const s = settings.loginSettings || {};

	const set = useCallback(
		( key, val ) => update( [ 'loginSettings', key ], val ),
		[ update ]
	);

	return (
		<div className="g2rd-tab-content g2rd-tab-connexion">
			<div className="g2rd-login-layout">
				{ /* ── Options ────────────────────────────────────────── */ }
				<div className="g2rd-login-options">

					<div className="g2rd-section">
						<h3 className="g2rd-section__title">Page de connexion</h3>
						<ToggleControl
							label="Activer la page personnalisée"
							help="Si désactivé, WordPress affiche sa page de connexion par défaut."
							checked={ !! s.enabled }
							onChange={ ( v ) => set( 'enabled', v ) }
						/>
					</div>

					{ s.enabled && (
						<>
							<div className="g2rd-section">
								<h3 className="g2rd-section__title">Mise en page</h3>
								<SelectControl
									label="Disposition"
									value={ s.layout || 'two-columns' }
									options={ LAYOUT_OPTIONS }
									onChange={ ( v ) => set( 'layout', v ) }
								/>
								<RangeControl
									label="Rayon des bords (px)"
									value={ s.borderRadius ?? 8 }
									onChange={ ( v ) => set( 'borderRadius', v ) }
									min={ 0 }
									max={ 32 }
								/>
							</div>

							<div className="g2rd-section">
								<h3 className="g2rd-section__title">Logo</h3>
								<MediaPicker
									label="Logo de la page de connexion"
									value={ s.logoUrl || '' }
									onChange={ ( v ) => set( 'logoUrl', v ) }
									placeholder="Laissez vide pour utiliser le logo G2RD par défaut."
								/>
								<TextControl
									label="Lien du logo"
									value={ s.logoLink || '' }
									onChange={ ( v ) => set( 'logoLink', v ) }
									placeholder="https://votre-site.fr"
									type="url"
								/>
							</div>

							<div className="g2rd-section">
								<h3 className="g2rd-section__title">Couleurs</h3>
								<div className="g2rd-color-grid">
									<ColorInput
										label="Panneau (fond)"
										value={ s.panelColor || '#2f425d' }
										onChange={ ( v ) => set( 'panelColor', v ) }
									/>
									<ColorInput
										label="Liens (mot de passe…)"
										value={ s.linksColor || '#cccccc' }
										onChange={ ( v ) => set( 'linksColor', v ) }
									/>
								</div>
								<p className="g2rd-color-group-label">Bouton de connexion</p>
								<div className="g2rd-color-grid">
									<ColorInput
										label="Fond (normal)"
										value={ s.buttonColor || '#d4a373' }
										onChange={ ( v ) => set( 'buttonColor', v ) }
									/>
									<ColorInput
										label="Texte (normal)"
										value={ s.buttonTextColor || '#ffffff' }
										onChange={ ( v ) => set( 'buttonTextColor', v ) }
									/>
									<ColorInput
										label="Fond (hover)"
										value={ s.buttonHoverColor || '#c4935c' }
										onChange={ ( v ) => set( 'buttonHoverColor', v ) }
									/>
									<ColorInput
										label="Texte (hover)"
										value={ s.buttonHoverTextColor || '#ffffff' }
										onChange={ ( v ) => set( 'buttonHoverTextColor', v ) }
									/>
								</div>
							</div>

							<div className="g2rd-section">
								<h3 className="g2rd-section__title">Arrière-plan</h3>
								<SelectControl
									label="Type d'arrière-plan"
									value={ s.bgType || 'image' }
									options={ BG_TYPE_OPTIONS }
									onChange={ ( v ) => set( 'bgType', v ) }
								/>
								{ ( s.bgType || 'image' ) === 'image' ? (
									<MediaPicker
										label="Image d'arrière-plan"
										value={ s.bgImageUrl || '' }
										onChange={ ( v ) => set( 'bgImageUrl', v ) }
										placeholder="Laissez vide pour utiliser l'image par défaut."
									/>
								) : (
									<ColorInput
										label="Couleur de fond"
										value={ s.bgColor || '#1a2a3a' }
										onChange={ ( v ) => set( 'bgColor', v ) }
									/>
								) }
							</div>

							<div className="g2rd-section">
								<h3 className="g2rd-section__title">Message d'accueil</h3>
								<TextControl
									label="Texte affiché au-dessus du formulaire"
									value={ s.welcomeText || '' }
									onChange={ ( v ) => set( 'welcomeText', v ) }
									placeholder="Bienvenue sur l'espace d'administration"
								/>
							</div>

							<div className="g2rd-section">
								<h3 className="g2rd-section__title">Bouton CTA</h3>
								<ToggleControl
									label="Afficher un bouton sous le formulaire"
									checked={ !! s.ctaShow }
									onChange={ ( v ) => set( 'ctaShow', v ) }
								/>
								{ s.ctaShow && (
									<>
										<TextControl
											label="Texte du bouton"
											value={ s.ctaText || '' }
											onChange={ ( v ) => set( 'ctaText', v ) }
										/>
										<TextControl
											label="URL du bouton"
											value={ s.ctaUrl || '' }
											onChange={ ( v ) => set( 'ctaUrl', v ) }
											type="url"
										/>
										<div className="g2rd-color-grid">
											<ColorInput
												label="Fond (normal)"
												value={ s.ctaColor || '#d4a373' }
												onChange={ ( v ) => set( 'ctaColor', v ) }
											/>
											<ColorInput
												label="Texte (normal)"
												value={ s.buttonTextColor || '#ffffff' }
												onChange={ ( v ) => set( 'buttonTextColor', v ) }
											/>
											<ColorInput
												label="Fond (hover)"
												value={ s.ctaHoverColor || '#c4935c' }
												onChange={ ( v ) => set( 'ctaHoverColor', v ) }
											/>
											<ColorInput
												label="Texte (hover)"
												value={ s.ctaHoverTextColor || '#ffffff' }
												onChange={ ( v ) => set( 'ctaHoverTextColor', v ) }
											/>
										</div>
									</>
								) }
							</div>
						</>
					) }
				</div>

				{ /* ── Aperçu ──────────────────────────────────────────── */ }
				<div className="g2rd-login-preview-col">
					<div className="g2rd-section g2rd-section--sticky">
						<h3 className="g2rd-section__title">Aperçu en direct</h3>
						<LoginPreview settings={ s } />
					</div>
				</div>
			</div>
		</div>
	);
}
