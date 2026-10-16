/**
 * Jeu d'icônes linéaires de la console (SVG inline, 24×24, trait 1.75).
 *
 * Remplace les Dashicons dans la navigation et les lignes de réglages :
 * un trait fin et homogène, à la taille du texte, sans dépendance
 * supplémentaire. Décoratives par défaut (`aria-hidden`) : le libellé
 * adjacent porte le sens.
 */

const PATHS = {
	settings: (
		<>
			<line x1="4" y1="6" x2="20" y2="6" />
			<circle cx="9" cy="6" r="2" fill="currentColor" stroke="none" />
			<line x1="4" y1="12" x2="20" y2="12" />
			<circle cx="15" cy="12" r="2" fill="currentColor" stroke="none" />
			<line x1="4" y1="18" x2="20" y2="18" />
			<circle cx="8" cy="18" r="2" fill="currentColor" stroke="none" />
		</>
	),
	folder: <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />,
	layout: (
		<>
			<rect x="3" y="4" width="18" height="16" rx="2" />
			<line x1="3" y1="10" x2="21" y2="10" />
			<line x1="9" y1="10" x2="9" y2="20" />
		</>
	),
	users: (
		<>
			<circle cx="9" cy="8" r="3.5" />
			<path d="M3 20a6 6 0 0 1 12 0" />
			<path d="M16 4.5a3.5 3.5 0 0 1 0 7" />
			<path d="M17.5 14.5a6 6 0 0 1 3.5 5.5" />
		</>
	),
	clock: (
		<>
			<circle cx="12" cy="12" r="8.5" />
			<path d="M12 7.5v4.5l3 2" />
		</>
	),
	lock: (
		<>
			<rect x="5" y="11" width="14" height="10" rx="2" />
			<path d="M8 11V8a4 4 0 0 1 8 0v3" />
		</>
	),
	key: (
		<>
			<circle cx="8" cy="15" r="4" />
			<path d="M10.8 12.2L20 3" />
			<path d="M16 7l3 3" />
		</>
	),
	sparkles: (
		<>
			<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z" />
			<path d="M19 16l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z" />
		</>
	),
	shield: <path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6z" />,
	list: (
		<>
			<line x1="9" y1="7" x2="20" y2="7" />
			<line x1="9" y1="12" x2="20" y2="12" />
			<line x1="9" y1="17" x2="20" y2="17" />
			<circle cx="5" cy="7" r="1.1" fill="currentColor" stroke="none" />
			<circle cx="5" cy="12" r="1.1" fill="currentColor" stroke="none" />
			<circle cx="5" cy="17" r="1.1" fill="currentColor" stroke="none" />
		</>
	),
	inbox: (
		<>
			<path d="M4 13l2.5-7h11L20 13" />
			<path d="M4 13v5a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5" />
			<path d="M4 13h4l1.5 2.5h5L16 13h4" />
		</>
	),
	plug: (
		<>
			<path d="M9 3v4" />
			<path d="M15 3v4" />
			<path d="M6 7h12v4a6 6 0 0 1-12 0z" />
			<path d="M12 17v4" />
		</>
	),
	home: (
		<>
			<path d="M3 11l9-7 9 7" />
			<path d="M5 10v10h14V10" />
			<path d="M10 20v-6h4v6" />
		</>
	),
	mail: (
		<>
			<rect x="3" y="5" width="18" height="14" rx="2" />
			<path d="M3 7l9 6 9-6" />
		</>
	),
	cart: (
		<>
			<path d="M3 4h2l2.5 11h11L21 7H6.5" />
			<circle cx="9" cy="19" r="1.5" />
			<circle cx="17" cy="19" r="1.5" />
		</>
	),
	minus: <line x1="5" y1="12" x2="19" y2="12" />,
	check: <path d="M5 12.5l4.5 4.5L19 7" />,
	play: <path d="M7 4.5l12 7.5-12 7.5z" />,
	star: <path d="M12 3.5l2.6 5.4 5.9.8-4.3 4.1 1.1 5.9L12 16.9l-5.3 2.8 1.1-5.9-4.3-4.1 5.9-.8z" />,
	layers: (
		<>
			<path d="M12 4l9 5-9 5-9-5z" />
			<path d="M3 14l9 5 9-5" />
		</>
	),
	link: (
		<>
			<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1" />
			<path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1" />
		</>
	),
	accessibility: (
		<>
			<circle cx="12" cy="4.5" r="1.5" fill="currentColor" stroke="none" />
			<path d="M5 9h14" />
			<path d="M12 9v6" />
			<path d="M12 15l-3.5 5.5" />
			<path d="M12 15l3.5 5.5" />
		</>
	),
	arrowUp: (
		<>
			<path d="M12 19V5" />
			<path d="M6 11l6-6 6 6" />
		</>
	),
	moon: <path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z" />,
	film: (
		<>
			<rect x="3" y="5" width="18" height="14" rx="2" />
			<path d="M7 5v14" />
			<path d="M17 5v14" />
			<path d="M3 12h18" />
		</>
	),
	blocks: (
		<>
			<rect x="4" y="4" width="7" height="7" rx="1.5" />
			<rect x="13" y="4" width="7" height="7" rx="1.5" />
			<rect x="4" y="13" width="7" height="7" rx="1.5" />
			<rect x="13" y="13" width="7" height="7" rx="1.5" />
		</>
	),
	briefcase: (
		<>
			<rect x="3" y="7" width="18" height="13" rx="2" />
			<path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
			<path d="M3 12h18" />
		</>
	),
	image: (
		<>
			<rect x="3" y="4" width="18" height="16" rx="2" />
			<circle cx="9" cy="10" r="1.5" />
			<path d="M21 16l-5-5-8 8" />
		</>
	),
	palette: (
		<>
			<path d="M12 3a9 9 0 0 0 0 18c1.5 0 2-1 2-2s-1-1.5-1-2.5 1-1.5 2-1.5h2a4 4 0 0 0 4-4c0-4.5-4-8-9-8z" />
			<circle cx="7.5" cy="11" r="1.1" fill="currentColor" stroke="none" />
			<circle cx="10.5" cy="7" r="1.1" fill="currentColor" stroke="none" />
			<circle cx="15" cy="7" r="1.1" fill="currentColor" stroke="none" />
		</>
	),
	cloud: <path d="M7 18h10a4 4 0 0 0 .5-7.97A6 6 0 0 0 6.2 9.5 4.25 4.25 0 0 0 7 18z" />,
	dot: <circle cx="12" cy="12" r="4" fill="currentColor" stroke="none" />,
};

export function Icon( { name, size = 16, className = '', label } ) {
	const paths = PATHS[ name ] || PATHS.dot;
	return (
		<svg
			className={ `g2rd-icon${ className ? ` ${ className }` : '' }` }
			width={ size }
			height={ size }
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.75"
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden={ label ? undefined : 'true' }
			role={ label ? 'img' : undefined }
			focusable="false"
		>
			{ label && <title>{ label }</title> }
			{ paths }
		</svg>
	);
}

export const ICON_NAMES = Object.keys( PATHS );
