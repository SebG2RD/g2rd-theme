import { useState, useEffect, useRef } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';
import { useSettings } from './hooks/useSettings';
import { Icon, ICON_NAMES } from './components/Icon';
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

const DATA = window.G2RDOptionsData || {};
const { version, licensed, licenseServerMode, externalTabs = [] } = DATA;

/**
 * Version du contrat d'interface exposé aux extensions (classes stables de la
 * console). Doit rester égale à AdminConsole::CONTRACT_VERSION côté PHP.
 */
const CONSOLE_CONTRACT_VERSION = 2;

/**
 * Point d'extension v1.19+ — `window.G2RDOptions.registerTab({ key, mount })`
 * permet à un plugin tiers de fournir un renderer DOM pour son onglet.
 * `mount(container, data)` est appelé une fois lorsque l'onglet devient actif.
 *
 * L'objet peut déjà exister (snippet PHP dans <head>, ou extension chargée
 * avant le bundle) : on complète sans jamais l'écraser, et on garantit
 * `console.version` au plus tard ici, donc avant tout appel à `mount`.
 * Cf docs/plugin-wordpress.md.
 */
window.G2RDOptions = window.G2RDOptions || {};
if ( typeof window.G2RDOptions.registerTab !== 'function' ) {
	const renderers = window.G2RDOptions._renderers || {};
	window.G2RDOptions._renderers = renderers;
	window.G2RDOptions.registerTab = ( tab ) => {
		if ( ! tab || typeof tab.key !== 'string' ) return;
		renderers[ tab.key ] = tab;
		window.dispatchEvent( new CustomEvent( 'g2rd:options:tab-registered', { detail: tab } ) );
	};
}
window.G2RDOptions.console = { ...( window.G2RDOptions.console || {} ), version: CONSOLE_CONTRACT_VERSION };

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
		let cleanup = null;

		const mountIfReady = () => {
			const renderer = window.G2RDOptions?._renderers?.[ tab.key ];
			if ( ! renderer?.mount || cleanup ) return;
			const result = renderer.mount( ref.current, tab.data || {} );
			cleanup = typeof result === 'function' ? result : () => {};
		};

		// L'extension peut s'enregistrer après le premier rendu (DOMContentLoaded,
		// script chargé plus tard) : on monte dès que sa clé est annoncée.
		const onRegistered = ( event ) => {
			if ( event.detail?.key === tab.key ) mountIfReady();
		};

		mountIfReady();
		window.addEventListener( 'g2rd:options:tab-registered', onRegistered );

		return () => {
			window.removeEventListener( 'g2rd:options:tab-registered', onRegistered );
			if ( cleanup ) cleanup();
		};
	}, [ tab.key ] );

	// La description est déjà affichée dans l'en-tête de page (bandeau).
	return (
		<div className="g2rd-external-tab">
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
		label: 'IA · MCP',
		items: [
			{ name: 'ia',         title: 'Module IA', icon: 'sparkles', description: 'Profil du site, clé API, quotas et consignes des générations IA.' },
			{ name: 'mcp-tokens', title: 'Tokens',    icon: 'key',      description: 'Tokens d\'accès des agents MCP et snippets d\'intégration.' },
			{ name: 'mcp-audit',  title: 'Audit',     icon: 'list',     description: 'Journal immuable de toutes les requêtes MCP reçues.' },
			{ name: 'mcp-queue',  title: 'File',      icon: 'inbox',    description: 'Opérations d\'écriture en attente de votre approbation.' },
		],
	},
];

// Tabs externes (plugins tiers via filtre PHP `g2rd_options_external_tabs`).
// Préfixe `ext:` sur le name pour éviter les collisions avec les tabs internes.
const EXTERNAL_GROUP = externalTabs.length > 0 ? {
	label: 'Extensions G2RD',
	items: externalTabs.map( ( tab ) => ( {
		name: `ext:${ tab.key }`,
		title: tab.label || tab.key,
		// Icône fournie par l'extension si elle existe dans le jeu interne, sinon `plug`.
		icon: ICON_NAMES.includes( tab.icon ) ? tab.icon : 'plug',
		description: tab.description || '',
		_external: tab,
	} ) ),
} : null;

const FULL_NAV_GROUPS = EXTERNAL_GROUP ? [ ...NAV_GROUPS, EXTERNAL_GROUP ] : NAV_GROUPS;
const ALL_ITEMS = FULL_NAV_GROUPS.flatMap( ( g ) => g.items.map( ( item ) => ( { ...item, group: g.label } ) ) );

const BUSINESS_LABELS = { vitrine: 'Vitrine', leads: 'Leads', ecommerce: 'E-commerce', '': 'Non défini' };
const LICENSE_LABELS  = { active: 'Active', expired: 'Expirée', invalid: 'Invalide', inactive: 'Inactive' };

const count = ( obj, pred ) => Object.values( obj || {} ).filter( pred ).length;

/**
 * Tuiles du bandeau : trois chiffres ou états utiles, dérivés des données déjà
 * chargées (aucune requête). Les onglets qui chargent leurs données eux-mêmes
 * (MCP, gestion des licences, IA) n'en affichent pas.
 */
function computeTiles( name, settings ) {
	switch ( name ) {
		case 'configuration':
			return [
				{ label: 'Type de site', value: BUSINESS_LABELS[ settings.businessType || '' ] || 'Non défini', hint: 'recommandations GEO adaptées' },
				{ label: 'Palette active', value: ( DATA.palette || [] ).length, hint: 'couleurs du thème', mono: true },
				{ label: 'Google Places', value: settings.googleMapsApiKeySet ? 'Clé configurée' : 'Non configurée', hint: 'cache des avis 12 h' },
			];
		case 'contenu': {
			const cpts  = settings.cpts || {};
			const total = Object.keys( DATA.cptDefaults || cpts ).length || 3;
			return [
				{ label: 'Types de contenus', value: count( cpts, ( c ) => c?.enabled !== false ), hint: `actifs sur ${ total }`, mono: true },
				{ label: 'Archives', value: count( cpts, ( c ) => !! c?.has_archive ), hint: 'avec page d\'archive', mono: true },
				{ label: 'Taxonomies', value: count( cpts, ( c ) => !! c?.tax_enabled ), hint: 'activées', mono: true },
			];
		}
		case 'editeur': {
			const defs   = DATA.features || {};
			const blocks = Object.keys( DATA.blocks || {} );
			const off    = ( settings.disabledBlocks || [] ).length;
			return [
				{ label: 'Modules optionnels', value: count( settings.features, Boolean ), hint: `actifs sur ${ Object.keys( defs ).length }`, mono: true },
				{ label: 'Blocs Gutenberg', value: Math.max( 0, blocks.length - off ), hint: `visibles sur ${ blocks.length }`, mono: true },
				{ label: 'Licence', value: licensed ? 'Active' : 'Absente', hint: licensed ? 'blocs et compositions débloqués' : 'Pin Scroll et blocs verrouillés' },
			];
		}
		case 'clients':
			return [
				{ label: 'Mode client', value: settings.clientMode ? 'Activé' : 'Désactivé', hint: 'interface simplifiée' },
				{ label: 'Aide SEO', value: settings.seoHelper !== false ? 'Activée' : 'Désactivée', hint: 'barre latérale de l\'éditeur' },
				{ label: 'Aide GEO', value: settings.geoHelper !== false ? 'Activée' : 'Désactivée', hint: 'score /100 dans l\'éditeur' },
			];
		case 'maintenance': {
			const cs   = settings.comingSoon || {};
			const page = ( DATA.pages || [] ).find( ( p ) => String( p.id ) === String( cs.page_id ) );
			return [
				{ label: 'Bientôt disponible', value: cs.enabled ? 'Activé' : 'Désactivé', hint: cs.enabled ? 'visiteurs redirigés' : 'site visible par tous' },
				{ label: 'Page de maintenance', value: page ? page.title : 'Aucune', hint: 'affichée aux visiteurs' },
				{ label: 'Assistant', value: 'Disponible', hint: 'reconfiguration en 4 étapes' },
			];
		}
		case 'connexion': {
			const s = settings.loginSettings || {};
			return [
				{ label: 'Page personnalisée', value: s.enabled ? 'Activée' : 'Désactivée', hint: s.enabled ? 'remplace wp-login.php' : 'page WordPress par défaut' },
				{ label: 'Disposition', value: ( s.layout || 'two-columns' ) === 'one-column' ? 'Une colonne' : 'Deux colonnes', hint: s.bgType === 'color' ? 'fond uni' : 'fond image' },
				{ label: 'Bouton CTA', value: s.ctaShow ? 'Affiché' : 'Masqué', hint: s.ctaText || 'sous le formulaire' },
			];
		}
		case 'licence': {
			const l = DATA.licenseData || {};
			return [
				{ label: 'Statut', value: LICENSE_LABELS[ l.status ] || 'Inactive', hint: 'vérifiée toutes les 24 h' },
				{ label: 'Domaine', value: l.domain || '—', hint: 'activation en cours' },
				{ label: 'Expiration', value: l.data?.expires_at ? new Date( l.data.expires_at ).toLocaleDateString( 'fr-FR' ) : '—', hint: 'renouvellement FluentCart', mono: !! l.data?.expires_at },
			];
		}
		default:
			return [];
	}
}

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
	const tiles      = computeTiles( activeTab, settings );

	// Les onglets d'extension ne lisent pas ces réglages : la barre y masquerait le
	// bas de leur panneau. Une modification du thème en attente reste signalée par
	// la puce « Non enregistré » du bandeau, et la barre revient sur les onglets du thème.
	const showSavebar = ( isDirty || isSaving ) && ! activeItem?._external;

	return (
		<div className="g2rd-app">

			<a className="g2rd-skip" href="#g2rd-main">Aller au contenu</a>

			{ /* Bandeau sombre : décor flouté, barre, titre et tuiles */ }
			<div className="g2rd-band" aria-hidden="true"></div>

			<div className="g2rd-inner">

				<header className="g2rd-topbar">
					<div className="g2rd-topbar__brand">
						<span className="g2rd-topbar__mark" aria-hidden="true">G</span>
						<h1 className="g2rd-topbar__title">Console G2RD</h1>
						<span className="g2rd-chip g2rd-chip--mono">v{ version }</span>
					</div>

					<div className="g2rd-topbar__status" aria-live="polite">
						<span className="g2rd-chip">
							<span className={ `g2rd-dot ${ licensed ? 'is-ok' : 'is-warn' }` } aria-hidden="true"></span>
							{ licensed ? 'Licence active' : 'Sans licence' }
						</span>
						<span className="g2rd-chip">
							<span className={ `g2rd-dot ${ isDirty || isSaving ? 'is-warn' : 'is-ok' }` } aria-hidden="true"></span>
							{ isSaving ? 'Enregistrement…' : ( isDirty ? 'Non enregistré' : 'À jour' ) }
						</span>
					</div>
				</header>

				<div className="g2rd-page-head">
					<p className="g2rd-page-head__eyebrow">{ activeItem?.group }</p>
					<h2 className="g2rd-page-head__title">{ activeItem?.title }</h2>
					{ activeItem?.description && (
						<p className="g2rd-page-head__desc">{ activeItem.description }</p>
					) }
				</div>

				{ tiles.length > 0 && (
					<div className="g2rd-tiles" key={ activeTab }>
						{ tiles.map( ( t ) => (
							<div key={ t.label } className="g2rd-tile">
								<small className="g2rd-tile__label">{ t.label }</small>
								<div className="g2rd-tile__row">
									<b className={ `g2rd-tile__value${ t.mono ? ' is-mono' : '' }` }>{ t.value }</b>
									<span className="g2rd-tile__hint">{ t.hint }</span>
								</div>
							</div>
						) ) }
					</div>
				) }

				<div className="g2rd-body">

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

					<main className="g2rd-main" id="g2rd-main" tabIndex={ -1 }>
						{ /* key force le remount et déclenche l'animation CSS */ }
						<div className="g2rd-main__content" key={ activeTab }>
							{ renderTab( activeTab, settings, update ) }
						</div>
					</main>

				</div>
			</div>

			{ /* Barre d'enregistrement flottante : visible dès qu'un réglage change, sur les onglets du thème */ }
			{ showSavebar && (
				<div className="g2rd-savebar" role="region" aria-label="Modifications en attente">
					<span className="g2rd-savebar__text">
						<span className="g2rd-dot is-warn" aria-hidden="true"></span>
						{ isSaving ? 'Enregistrement…' : 'Modifications non enregistrées' }
					</span>
					{ ! isSaving && (
						<Button variant="tertiary" className="g2rd-savebar__cancel" onClick={ reset }>
							Annuler
						</Button>
					) }
					<Button variant="primary" className="g2rd-savebar__save" onClick={ save } disabled={ isSaving } isBusy={ isSaving }>
						{ isSaving ? 'Enregistrement…' : 'Enregistrer' }
					</Button>
				</div>
			) }

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
