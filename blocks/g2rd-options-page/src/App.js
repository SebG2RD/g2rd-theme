import { useState } from '@wordpress/element';
import { TabPanel, Notice } from '@wordpress/components';
import { useSettings } from './hooks/useSettings';
import { SaveBar } from './components/SaveBar';
import { TabConfiguration } from './tabs/TabConfiguration';
import { TabContenu } from './tabs/TabContenu';
import { TabEditeur } from './tabs/TabEditeur';
import { TabClients } from './tabs/TabClients';
import { TabMaintenance } from './tabs/TabMaintenance';
import { TabConnexion } from './tabs/TabConnexion';

const { version, licensed } = window.G2RDOptionsData || {};

const TAB_CONFIG = [
	{ name: 'configuration', title: 'Configuration', icon: 'admin-settings' },
	{ name: 'contenu',       title: 'Contenu',        icon: 'category' },
	{ name: 'editeur',       title: 'Éditeur',        icon: 'block-default' },
	{ name: 'clients',       title: 'Clients',        icon: 'admin-users' },
	{ name: 'maintenance',   title: 'Maintenance',    icon: 'clock' },
	{ name: 'connexion',     title: 'Connexion',      icon: 'lock' },
];

const TABS = TAB_CONFIG.map( ( { name, title, icon } ) => ( {
	name,
	title: (
		<span className="g2rd-tab-label">
			<span className={ `dashicons dashicons-${ icon }` }></span>
			{ title }
		</span>
	),
} ) );

export function App() {
	const { settings, update, isDirty, isSaving, save, reset, notice, clearNotice } = useSettings();

	return (
		<div className="g2rd-options-app">
			<div className="g2rd-options-header">
				<div className="g2rd-options-header__left">
					<span className="g2rd-options-header__logo">G2RD</span>
					<div>
						<h1 className="g2rd-options-header__title">Options du thème</h1>
						<span className="g2rd-options-header__version">v{ version }</span>
					</div>
				</div>
				<div className="g2rd-options-header__right">
					{ licensed ? (
						<span className="g2rd-badge g2rd-badge--active">
							<span className="dashicons dashicons-yes-alt"></span>
							Licence active
						</span>
					) : (
						<span className="g2rd-badge g2rd-badge--inactive">
							<span className="dashicons dashicons-warning"></span>
							Sans licence
						</span>
					) }
				</div>
			</div>

			{ notice && (
				<Notice
					status={ notice.type }
					onRemove={ clearNotice }
					className="g2rd-notice"
				>
					{ notice.message }
				</Notice>
			) }

			<TabPanel
				className="g2rd-tab-panel"
				tabs={ TABS }
			>
				{ ( tab ) => {
					const props = { settings, update };
					switch ( tab.name ) {
						case 'configuration': return <TabConfiguration { ...props } />;
						case 'contenu':       return <TabContenu       { ...props } />;
						case 'editeur':       return <TabEditeur       { ...props } />;
						case 'clients':       return <TabClients       { ...props } />;
						case 'maintenance':   return <TabMaintenance   { ...props } />;
						case 'connexion':     return <TabConnexion     { ...props } />;
						default:              return null;
					}
				} }
			</TabPanel>

			<SaveBar
				isDirty={ isDirty }
				isSaving={ isSaving }
				onSave={ save }
				onReset={ reset }
			/>
		</div>
	);
}
