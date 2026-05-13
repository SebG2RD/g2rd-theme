import { useState } from '@wordpress/element';
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

const { version, licensed, licenseServerMode } = window.G2RDOptionsData || {};

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
		label: 'MCP',
		items: [
			{ name: 'mcp-tokens', title: 'Tokens',  icon: 'shield' },
			{ name: 'mcp-audit',  title: 'Audit',   icon: 'list-view' },
			{ name: 'mcp-queue',  title: 'File',    icon: 'clock' },
		],
	},
];

const ALL_ITEMS = NAV_GROUPS.flatMap( ( g ) => g.items );

function renderTab( name, settings, update ) {
	const props = { settings, update };
	switch ( name ) {
		case 'configuration': return <TabConfiguration { ...props } />;
		case 'contenu':       return <TabContenu       { ...props } />;
		case 'editeur':       return <TabEditeur       { ...props } />;
		case 'clients':       return <TabClients       { ...props } />;
		case 'maintenance':   return <TabMaintenance   { ...props } />;
		case 'connexion':     return <TabConnexion     { ...props } />;
		case 'licence':       return <TabLicence />;
		case 'licence-admin': return <TabLicenceAdmin />;
		case 'mcp-tokens':    return <TabMcpTokens />;
		case 'mcp-audit':     return <TabMcpAudit />;
		case 'mcp-queue':     return <TabMcpQueue />;
		default:              return null;
	}
}

export function App() {
	const { settings, update, isDirty, isSaving, save, reset, notice, clearNotice } = useSettings();
	const [ activeTab, setActiveTab ] = useState( ALL_ITEMS[ 0 ].name );

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
					{ NAV_GROUPS.map( ( group ) => (
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
					<div className="g2rd-main__content">
						{ renderTab( activeTab, settings, update ) }
					</div>
				</main>

			</div>
		</div>
	);
}
