import { useMemo } from '@wordpress/element';

const { themeUri = '' } = window.G2RDOptionsData || {};

const DEFAULT_LOGO = themeUri
	? `${ themeUri }/assets/img/Nouveau-logo-G2RD-Agence-Web-blanc-Horizontale@3x.png`
	: '';

export function LoginPreview( { settings } ) {
	const {
		layout        = 'two-columns',
		panelColor    = '#2f425d',
		buttonColor   = '#d4a373',
		buttonTextColor = '#ffffff',
		linksColor    = '#cccccc',
		bgType        = 'image',
		bgColor       = '#1a2a3a',
		bgImageUrl    = '',
		logoUrl       = '',
		welcomeText   = '',
		borderRadius  = 8,
		ctaShow       = true,
		ctaText       = 'Visiter notre site',
		ctaColor      = '#d4a373',
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
	const r    = `${ borderRadius }px`;

	return (
		<div className="g2rd-login-preview">
			<div
				className="g2rd-lp-wrap"
				style={ { display: 'flex', height: '380px', borderRadius: '8px', overflow: 'hidden', boxShadow: '0 8px 32px rgba(0,0,0,.25)' } }
			>
				{ ! isOneCol && (
					<div className="g2rd-lp-image" style={ { flex: 1, ...bgStyle } } />
				) }

				<div
					className="g2rd-lp-panel"
					style={ {
						width: isOneCol ? '100%' : '45%',
						background: isOneCol ? undefined : panelColor,
						display: 'flex',
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'center',
						padding: '28px',
						gap: '12px',
						...( isOneCol ? { ...bgStyle } : {} ),
					} }
				>
					{ logo && (
						<img src={ logo } alt="Logo" style={ { maxWidth: '160px', marginBottom: '8px' } } />
					) }
					{ welcomeText && (
						<p style={ { color: '#fff', textAlign: 'center', fontSize: '13px', margin: 0 } }>
							{ welcomeText }
						</p>
					) }

					<div style={ { width: '100%', background: 'rgba(255,255,255,.08)', borderRadius: r, padding: '16px', display: 'flex', flexDirection: 'column', gap: '10px' } }>
						<div style={ { background: 'rgba(255,255,255,.15)', borderRadius: r, height: '34px' } } />
						<div style={ { background: 'rgba(255,255,255,.15)', borderRadius: r, height: '34px' } } />
						<div style={ { background: buttonColor, color: buttonTextColor, borderRadius: r, height: '36px', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 600, fontSize: '13px' } }>
							Se connecter
						</div>
					</div>

					<span style={ { fontSize: '11px', color: linksColor } }>Mot de passe oublié ?</span>

					{ ctaShow && ctaText && (
						<div style={ { background: ctaColor, color: buttonTextColor, borderRadius: r, padding: '7px 18px', fontSize: '12px', fontWeight: 600 } }>
							{ ctaText }
						</div>
					) }
				</div>
			</div>
			<p className="g2rd-login-preview__hint">Aperçu approximatif — le rendu final peut légèrement différer.</p>
		</div>
	);
}
