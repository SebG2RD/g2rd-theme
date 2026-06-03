import { useState, useEffect, useRef } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';
import { useSettings } from './hooks/useSettings';
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

const NAV_GROUPS = [
	{
		label: 'Thème',
		items: [
			{ name: 'configuration', title: 'Configuration', icon: 'admin-settings' },
			{ name: 'contenu',       title: 'Contenu',       icon: 'category' },
			{ name: 'editeur',       title: 'Éditeur',       icon: 'block-default' },
			{ name: 'clients',       title: 'Clients',       icon: 'admin-users' },
		],
	},
	{
		label: 'Site',
		items: [
			{ name: 'maintenance', title: 'Maintenance', icon: 'clock' },
			{ name: 'connexion',   title: 'Connexion',   icon: 'lock' },
		],
	},
	{
		label: 'Système',
		items: [
			{ name: 'licence', title: 'Licence', icon: 'admin-network' },
			...( licenseServerMode ? [ { name: 'licence-admin', title: 'Gestion licences', icon: 'admin-network' } ] : [] ),
		],
	},
	{
		label: 'IA',
		items: [
			{ name: 'ia', title: 'Module IA', icon: 'superhero-alt' },
		],
	},
	{
		label: 'MCP',
		items: [
			{ name: 'mcp-tokens', title: 'Tokens',  icon: 'shield' },
			{ name: 'mcp-audit',  title: 'Audit',   icon: 'list-view' },
			{ name: 'mcp-queue',  title: 'File',    icon: 'clock' },
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
		icon: tab.icon || 'admin-plugins',
		_external: tab,
	} ) ),
} : null;

const FULL_NAV_GROUPS = EXTERNAL_GROUP ? [ ...NAV_GROUPS, EXTERNAL_GROUP ] : NAV_GROUPS;
const ALL_ITEMS = FULL_NAV_GROUPS.flatMap( ( g ) => g.items );

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

			{ /* ── Header ── */ }
			<header className="g2rd-header">
				<div className="g2rd-header__brand">
					<span className="g2rd-header__logo">G2RD</span>
					<div className="g2rd-header__meta">
						<span className="g2rd-header__title">Options du thème</span>
						<span className="g2rd-header__version">v{ version }</span>
					</div>
				</div>

				<div className="g2rd-header__actions">
					{ licensed ? (
						<span className="g2rd-pill g2rd-pill--ok">
							<span className="dashicons dashicons-yes-alt" aria-hidden="true"></span>
							Licence active
						</span>
					) : (
						<span className="g2rd-pill g2rd-pill--warn">
							<span className="dashicons dashicons-warning" aria-hidden="true"></span>
							Sans licence
						</span>
					) }

					{ ( isDirty || isSaving ) && (
						<div className="g2rd-header__save">
							{ ! isSaving && (
								<Button variant="tertiary" onClick={ reset } disabled={ isSaving }>
									Annuler
								</Button>
							) }
							<Button variant="primary" onClick={ save } disabled={ isSaving } isBusy={ isSaving }>
								{ isSaving ? 'Enregistrement…' : 'Enregistrer' }
							</Button>
						</div>
					) }
				</div>
			</header>

			{ notice && (
				<Notice status={ notice.type } onRemove={ clearNotice } className="g2rd-notice">
					{ notice.message }
				</Notice>
			) }

			{ /* ── Body ── */ }
			<div className="g2rd-body">

				{ /* ── Sidebar nav ── */ }
				<nav className="g2rd-nav" aria-label="Navigation options thème">
					{ FULL_NAV_GROUPS.map( ( group ) => (
						<div key={ group.label } className="g2rd-nav__group">
							<span className="g2rd-nav__group-label">{ group.label }</span>
							{ group.items.map( ( item ) => (
								<button
									key={ item.name }
									className={ `g2rd-nav__item${ activeTab === item.name ? ' g2rd-nav__item--active' : '' }` }
									onClick={ () => setActiveTab( item.name ) }
									aria-current={ activeTab === item.name ? 'page' : undefined }
								>
									<span className={ `dashicons dashicons-${ item.icon }` } aria-hidden="true"></span>
									{ item.title }
								</button>
							) ) }
						</div>
					) ) }
				</nav>

				{ /* ── Main content ── */ }
				<main className="g2rd-main">
					<div className="g2rd-main__heading">
						<span className={ `dashicons dashicons-${ activeItem?.icon }` } aria-hidden="true"></span>
						<h2>{ activeItem?.title }</h2>
					</div>
					{ /* key force le remount et déclenche l'animation CSS */ }
					<div className="g2rd-main__content" key={ activeTab }>
						{ renderTab( activeTab, settings, update ) }
					</div>
				</main>

			</div>
		</div>
	);
}
