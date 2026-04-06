/**
 * G2RD — Visibilité par appareil
 *
 * Ajoute un panneau « Visibilité par appareil » dans l'Inspector de TOUS les blocs.
 * Les classes CSS g2rd-hide-desktop / g2rd-hide-tablet / g2rd-hide-mobile
 * sont injectées côté PHP via render_block (functions.php).
 *
 * UX inspirée du plugin Astra : 3 boutons toggle (Desktop / Tablette / Mobile).
 */
( function () {
	'use strict';

	var addFilter              = wp.hooks.addFilter;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var createElement          = wp.element.createElement;
	var Fragment               = wp.element.Fragment;
	var InspectorControls      = wp.blockEditor.InspectorControls;
	var PanelBody              = wp.components.PanelBody;
	var __                     = wp.i18n.__;

	// Blocs exclus (meta-blocs sans contenu frontal)
	var EXCLUDED = [
		'core/nextpage', 'core/more', 'core/freeform',
		'core/block', 'core/template-part', 'core/pattern',
		'core/post-template', 'core/query-no-results',
	];

	// ────────────────────────────────────────────
	// 1. Ajout des attributs à tous les blocs
	// ────────────────────────────────────────────
	addFilter(
		'blocks.registerBlockType',
		'g2rd/responsive-attributes',
		function ( settings, name ) {
			if ( EXCLUDED.indexOf( name ) !== -1 ) return settings;
			return Object.assign( {}, settings, {
				attributes: Object.assign( {}, settings.attributes || {}, {
					g2rdHideDesktop: { type: 'boolean', default: false },
					g2rdHideTablet:  { type: 'boolean', default: false },
					g2rdHideMobile:  { type: 'boolean', default: false },
				} ),
			} );
		}
	);

	// ────────────────────────────────────────────
	// 2. Classes CSS sur l'enveloppe dans l'éditeur
	//    (rendu visuel — ne masque pas réellement)
	// ────────────────────────────────────────────
	addFilter(
		'editor.BlockListBlock',
		'g2rd/responsive-editor-classes',
		createHigherOrderComponent( function ( BlockListBlock ) {
			return function ( props ) {
				var attrs = props.attributes || {};
				var extra = [
					attrs.g2rdHideDesktop ? 'g2rd-hide-desktop' : '',
					attrs.g2rdHideTablet  ? 'g2rd-hide-tablet'  : '',
					attrs.g2rdHideMobile  ? 'g2rd-hide-mobile'  : '',
				].filter( Boolean ).join( ' ' );

				return createElement( BlockListBlock, Object.assign( {}, props, {
					className: [ props.className, extra ].filter( Boolean ).join( ' ' ),
				} ) );
			};
		}, 'withG2rdResponsiveEditorClasses' )
	);

	// ────────────────────────────────────────────
	// 3. Panneau Inspector avec les 3 boutons
	// ────────────────────────────────────────────

	/**
	 * Rendu d'un bouton de visibilité appareil.
	 *
	 * @param {object} opts
	 * @param {string}   opts.attrKey   – clé d'attribut (g2rdHideDesktop …)
	 * @param {string}   opts.icon      – slug dashicon (desktop, tablet, smartphone)
	 * @param {string}   opts.label     – libellé affiché
	 * @param {boolean}  opts.isHidden  – état actuel
	 * @param {Function} opts.onChange  – callback
	 */
	function DeviceBtn( opts ) {
		return createElement(
			'button',
			{
				type:      'button',
				className: 'g2rd-vis-btn' + ( opts.isHidden ? ' g2rd-vis-btn--hidden' : ' g2rd-vis-btn--visible' ),
				onClick:   opts.onChange,
				title:     opts.isHidden
					? __( 'Cliquer pour afficher sur ', 'g2rd' ) + opts.label
					: __( 'Cliquer pour masquer sur ', 'g2rd' ) + opts.label,
			},
			// Icône appareil
			createElement( 'span', {
				className: 'g2rd-vis-btn__device dashicons dashicons-' + opts.icon,
			} ),
			// Libellé
			createElement( 'span', { className: 'g2rd-vis-btn__label' }, opts.label ),
			// Indicateur œil
			createElement( 'span', {
				className: 'g2rd-vis-btn__eye dashicons dashicons-'
					+ ( opts.isHidden ? 'hidden' : 'visibility' ),
			} )
		);
	}

	addFilter(
		'editor.BlockEdit',
		'g2rd/responsive-controls',
		createHigherOrderComponent( function ( BlockEdit ) {
			return function ( props ) {
				if ( EXCLUDED.indexOf( props.name ) !== -1 ) {
					return createElement( BlockEdit, props );
				}

				var attrs          = props.attributes || {};
				var setAttributes  = props.setAttributes;
				var hideDesktop    = !! attrs.g2rdHideDesktop;
				var hideTablet     = !! attrs.g2rdHideTablet;
				var hideMobile     = !! attrs.g2rdHideMobile;

				var hasAnyHidden = hideDesktop || hideTablet || hideMobile;

				// Badge de synthèse en haut du panneau
				var badgeLabels = [
					hideDesktop ? __( 'Desktop', 'g2rd' )  : '',
					hideTablet  ? __( 'Tablette', 'g2rd' ) : '',
					hideMobile  ? __( 'Mobile', 'g2rd' )   : '',
				].filter( Boolean ).join( ', ' );

				return createElement( Fragment, null,
					createElement( BlockEdit, props ),
					createElement( InspectorControls, null,
						createElement( PanelBody,
							{
								title:       __( 'Visibilité par appareil', 'g2rd' ),
								initialOpen: false,
								className:   'g2rd-responsive-panel',
							},

							// Description
							createElement( 'p', { className: 'g2rd-vis-description' },
								__( 'Cochez les appareils sur lesquels ce bloc doit être masqué.', 'g2rd' )
							),

							// 3 boutons
							createElement( 'div', { className: 'g2rd-vis-devices' },
								DeviceBtn( {
									attrKey:  'g2rdHideDesktop',
									icon:     'desktop',
									label:    __( 'Desktop', 'g2rd' ),
									isHidden: hideDesktop,
									onChange: function () { setAttributes( { g2rdHideDesktop: ! hideDesktop } ); },
								} ),
								DeviceBtn( {
									attrKey:  'g2rdHideTablet',
									icon:     'tablet',
									label:    __( 'Tablette', 'g2rd' ),
									isHidden: hideTablet,
									onChange: function () { setAttributes( { g2rdHideTablet: ! hideTablet } ); },
								} ),
								DeviceBtn( {
									attrKey:  'g2rdHideMobile',
									icon:     'smartphone',
									label:    __( 'Mobile', 'g2rd' ),
									isHidden: hideMobile,
									onChange: function () { setAttributes( { g2rdHideMobile: ! hideMobile } ); },
								} )
							),

							// Résumé quand des appareils sont masqués
							hasAnyHidden && createElement( 'p', { className: 'g2rd-vis-summary' },
								createElement( 'span', { className: 'dashicons dashicons-hidden' } ),
								' ' + __( 'Masqué sur : ', 'g2rd' ) + badgeLabels
							)
						)
					)
				);
			};
		}, 'withG2rdResponsiveControls' )
	);

} )();
