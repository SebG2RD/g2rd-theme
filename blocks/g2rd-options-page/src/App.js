import { useState, useEffect, useRef } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';
import { useSettings } from './hooks/useSettings';
import { Icon } from './components/Icon';
import { TabConfiguration } from './tabs/TabConfiguration';
import { TabContenu }       from './tabs/TabContenu';
import { TabEditeur }       from './tabs/TabEditeur';
import { TabClients }       from './tabs/TabClients';
import { TabMaintenance }   from './tabs/TabMaintenance';
import { TabConnexion }     from './tabs/TabConnexion';
import { TabLicence }       from './tabs/TabLicence';
import { TabLicenceAdmin }  from './tabs/TabLicenceAdmin';
import { TabMcpTokens }     from './tabs/TabMcpTokens';
import { TabMcpAudit }      from './tabs/TabMcpAudit';
import { TabMcpQueue }      from './tabs/TabMcpQueue';
import { TabIA }            from './tabs/TabIA';

const { version, licensed, licenseServerMode, externalTabs = [] } = window.G2RDOptionsData || {};

/**
 * Point d'extension v1.19+ — `window.G2RDOptions.registerTab({ key, mount })`
 * permet à un plugin tiers de fournir un renderer DOM pour son onglet.
 * `mount(container, data)` est appelé une fois lorsque l'onglet devient actif.
 * Cf docs/plugin-wordpress.md.
 */
if ( typeof window.G2RDOptions === 'undefined' ) {
	const renderers = {};
	window.G2RDOptions = {
		registerTab( tab ) {
			if ( ! tab || typeof tab.key !== 'string' ) return;
			renderers[ tab.key ] = tab;
			window.dispatchEvent( new CustomEvent( 'g2rd:options:tab-registered', { detail: tab } ) );
		},
		_renderers: renderers,
	};
}

/**
 * Wrapper qui monte le DOM custom d'un plugin externe. Le plugin appelle
 * `window.G2RDOptions.registerTab({ key, mount })` au DOMContentLoaded ;
 * ce composant attache puis nettoie via la fonction `unmount` éventuellement
 * retournée par `mount`.
 */
function ExternalTabHost({ tab }) {
	const ref = useRef( null );

	useEffect( () => {
		if ( ! ref.current ) return undefined;
		const renderer = window.G2RDOptions?._renderers?.[ tab.key ];
		if ( renderer?.mount ) {
			const cleanup = renderer.mount( ref.current, tab.data || {} );
			return typeof cleanup === 'function' ? cleanup : undefined;
		}
		return undefined;
	}, [ tab.key ] );

	return (
		<div className="g2rd-external-tab">
			{ tab.description && (
				<p className="g2rd-external-tab__description">{ tab.description }</p>
			) }
			<div ref={ ref } id={ tab.mount_id || `g2rd-external-tab-${ tab.key }` } />
		</div>
	);
}

/*
 * `icon` : nom d'une icône du jeu interne (components/Icon.js).
 * `description` : affichée sous le titre de page, elle dit ce que l'onglet
 * pilote avant même d'en lire les sections.
 */
const NAV_GROUPS = [
	{
		label: 'Thème',
		items: [
			{ name: 'configuration', title: 'Configuration', icon: 'settings', description: 'Type de site, couleurs de l\'administration et intégrations tierces.' },
			{ name: 'contenu',       title: 'Contenu',       icon: 'folder',   description: 'Types de contenus personnalisés inclus dans le thème.' },
			{ name: 'editeur',       title: 'Éditeur',       icon: 'layout',   description: 'Modules optionnels, boutons flottants et blocs Gutenberg disponibles.' },
			{ name: 'clients',       title: 'Clients',       icon: 'users',    description: 'Interface simplifiée et aides SEO / GEO pour vos clients.' },
		],
	},
	{
		label: 'Site',
		items: [
			{ name: 'maintenance', title: 'Maintenance', icon: 'clock', description: 'Mode « bientôt disponible » et assistant de démarrage.' },
			{ name: 'connexion',   title: 'Connexion',   icon: 'lock',  description: 'Apparence de la page de connexion WordPress, avec aperçu en direct.' },
		],
	},
	{
		label: 'Système',
		items: [
			{ name: 'licence', title: 'Licence', icon: 'key', description: 'Activation de la licence G2RD sur ce domaine.' },
			...( licenseServerMode ? [ { name: 'licence-admin', title: 'Gestion licences', icon: 'shield', description: 'Création et suivi des clés de licence de vos clients.' } ] : [] ),
		],
	},
	{
		label: 'IA',
		items: [
			{ name: 'ia', title: 'Module IA', icon: 'sparkles', description: 'Profil du site, clé API, quotas et consignes des générations IA.' },
		],
	},
	{
		label: 'MCP',
		items: [
			{ name: 'mcp-tokens', title: 'Tokens',  icon: 'key',   description: 'Tokens d\'accès des agents MCP et snippets d\'intégration.' },
			{ name: 'mcp-audit',  title: 'Audit',   icon: 'list',  description: 'Journal immuable de toutes les requêtes MCP reçues.' },
			{ name: 'mcp-queue',  title: 'File',    icon: 'inbox', description: 'Opérations d\'écriture en attente de votre approbation.' },
		],
	},
];

// Tabs externes (plugins tiers via filtre PHP `g2rd_options_external_tabs`).
// Préfixe `ext:` sur le name pour éviter les collisions avec les tabs internes.
const EXTERNAL_GROUP = externalTabs.length > 0 ? {
	label: 'Extensions',
	items: externalTabs.map( ( tab ) => ( {
		name: `ext:${ tab.key }`,
		title: tab.label || tab.key,
		icon: 'plug',
		description: tab.description || '',
		_external: tab,
	} ) ),
} : null;

const FULL_NAV_GROUPS = EXTERNAL_GROUP ? [ ...NAV_GROUPS, EXTERNAL_GROUP ] : NAV_GROUPS;
const ALL_ITEMS = FULL_NAV_GROUPS.flatMap( ( g ) => g.items.map( ( item ) => ( { ...item, group: g.label } ) ) );

function renderTab( name, settings, update ) {
	const props = { settings, update };
	if ( name.startsWith( 'ext:' ) ) {
		const item = ALL_ITEMS.find( ( i ) => i.name === name );
		return item?._external ? <ExternalTabHost tab={ item._external } /> : null;
	}
	switch ( name ) {
		case 'configuration': return <TabConfiguration { ...props } />;
		case 'contenu':       return <TabContenu       { ...props } />;
		case 'editeur':       return <TabEditeur       { ...props } />;
		case 'clients':       return <TabClients       { ...props } />;
		case 'maintenance':   return <TabMaintenance   { ...props } />;
		case 'connexion':     return <TabConnexion     { ...props } />;
		case 'licence':       return <TabLicence />;
		case 'licence-admin': return <TabLicenceAdmin />;
		case 'ia':            return <TabIA />;
		case 'mcp-tokens':    return <TabMcpTokens />;
		case 'mcp-audit':     return <TabMcpAudit />;
		case 'mcp-queue':     return <TabMcpQueue />;
		default:              return null;
	}
}

function getInitialTab() {
	const hash = window.location.hash.replace( '#', '' );
	return ALL_ITEMS.find( ( i ) => i.name === hash ) ? hash : ALL_ITEMS[ 0 ].name;
}

export function App() {
	const { settings, update, isDirty, isSaving, save, reset, notice, clearNotice } = useSettings();
	const [ activeTab, setActiveTab ] = useState( getInitialTab );

	useEffect( () => {
		window.location.hash = activeTab;
	}, [ activeTab ] );

	const activeItem = ALL_ITEMS.find( ( i ) => i.name === activeTab );

	return (
		<div className="g2rd-app">

			<a className="g2rd-skip" href="#g2rd-main">Aller au contenu</a>

			{ /* ── Barre supérieure ── */ }
			<header className="g2rd-topbar">
				<div className="g2rd-topbar__brand">
					<span className="g2rd-topbar__mark" aria-hidden="true">G</span>
					<h1 className="g2rd-topbar__title">Options du thème</h1>
					<span className="g2rd-topbar__version">v{ version }</span>
				</div>

				<div className="g2rd-topbar__actions">
					<span className={ `g2rd-status${ licensed ? ' g2rd-status--ok' : ' g2rd-status--warn' }` }>
						<span className="g2rd-status__dot" aria-hidden="true"></span>
						{ licensed ? 'Licence active' : 'Sans licence' }
					</span>

					{ /* aria-live : l'apparition des boutons est annoncée aux lecteurs d'écran */ }
					<div className="g2rd-topbar__save" aria-live="polite">
						{ ( isDirty || isSaving ) && (
							<>
								{ ! isSaving && (
									<span className="g2rd-topbar__dirty">
										<span className="g2rd-topbar__dirty-dot" aria-hidden="true"></span>
										Non enregistré
									</span>
								) }
								{ ! isSaving && (
									<Button variant="tertiary" onClick={ reset } disabled={ isSaving }>
										Annuler
									</Button>
								) }
								<Button variant="primary" onClick={ save } disabled={ isSaving } isBusy={ isSaving }>
									{ isSaving ? 'Enregistrement…' : 'Enregistrer' }
								</Button>
							</>
						) }
					</div>
				</div>
			</header>

			{ /* ── Corps ── */ }
			<div className="g2rd-body">

				{ /* ── Navigation ── */ }
				<nav className="g2rd-nav" aria-label="Navigation options thème">
					{ FULL_NAV_GROUPS.map( ( group ) => (
						<div key={ group.label } className="g2rd-nav__group">
							<span className="g2rd-nav__group-label">{ group.label }</span>
							{ group.items.map( ( item ) => (
								<button
									key={ item.name }
									type="button"
									className={ `g2rd-nav__item${ activeTab === item.name ? ' is-active' : '' }` }
									onClick={ () => setActiveTab( item.name ) }
									aria-current={ activeTab === item.name ? 'page' : undefined }
								>
									<Icon name={ item.icon } size={ 16 } />
									<span>{ item.title }</span>
								</button>
							) ) }
						</div>
					) ) }
				</nav>

				{ /* ── Contenu ── */ }
				<main className="g2rd-main" id="g2rd-main" tabIndex={ -1 }>
					<div className="g2rd-main__inner">
						<header className="g2rd-page-head">
							<p className="g2rd-page-head__eyebrow">{ activeItem?.group }</p>
							<h2 className="g2rd-page-head__title">{ activeItem?.title }</h2>
							{ activeItem?.description && (
								<p className="g2rd-page-head__desc">{ activeItem.description }</p>
							) }
						</header>
						{ /* key force le remount et déclenche l'animation CSS */ }
						<div className="g2rd-main__content" key={ activeTab }>
							{ renderTab( activeTab, settings, update ) }
						</div>
					</div>
				</main>

			</div>

			{ /* ── Toast ── */ }
			{ notice && (
				<div className="g2rd-toast" role="status">
					<Notice status={ notice.type } onRemove={ clearNotice } className="g2rd-notice">
						{ notice.message }
					</Notice>
				</div>
			) }
		</div>
	);
}
