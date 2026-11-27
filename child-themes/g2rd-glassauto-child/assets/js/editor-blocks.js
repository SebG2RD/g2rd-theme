/**
 * GlassAuto Vernon — déclaration des blocs dynamiques dans l'éditeur.
 *
 * Script sans build : il utilise les globales wp.* fournies par WordPress.
 * Chaque bloc est rendu par PHP (inc/blocks.php) ; l'éditeur affiche
 * l'aperçu serveur.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;
	var __ = wp.i18n.__;

	function placeholder( label ) {
		return function () {
			return el( 'p', { className: 'ga-editor-placeholder' }, label );
		};
	}

	function preview( name, props, label ) {
		return el( ServerSideRender, {
			block: name,
			attributes: props.attributes,
			EmptyResponsePlaceholder: placeholder( label ),
			ErrorResponsePlaceholder: placeholder( label ),
		} );
	}

	function register( name, settings ) {
		if ( wp.blocks.getBlockType( name ) ) {
			return;
		}
		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			title: settings.title,
			description: settings.description,
			category: 'glassauto',
			icon: { src: settings.icon || 'location', foreground: '#FFFFFF', background: '#009CB6' },
			attributes: settings.attributes || {},
			supports: { html: false, align: [ 'wide', 'full' ], spacing: { margin: true } },
			edit: function ( props ) {
				var blockProps = useBlockProps();
				return el(
					Fragment,
					null,
					settings.inspector ? el( InspectorControls, null, settings.inspector( props ) ) : null,
					el( 'div', blockProps, preview( name, props, settings.empty || settings.title ) )
				);
			},
			save: function () {
				return null;
			},
		} );
	}

	register( 'glassauto/horaires', {
		title: __( 'Horaires du centre', 'g2rd-glassauto-child' ),
		description: __( 'Les horaires saisis dans Réglages › Général › GlassAuto Vernon.', 'g2rd-glassauto-child' ),
		icon: 'clock',
		attributes: { resume: { type: 'boolean', default: false } },
		inspector: function ( props ) {
			return el( PanelBody, { title: __( 'Affichage', 'g2rd-glassauto-child' ) }, el( ToggleControl, {
				label: __( 'Résumé (lundi – vendredi sur une ligne)', 'g2rd-glassauto-child' ),
				checked: !! props.attributes.resume,
				onChange: function ( v ) { props.setAttributes( { resume: !! v } ); },
			} ) );
		},
	} );

	register( 'glassauto/coordonnees', {
		title: __( 'Coordonnées du centre', 'g2rd-glassauto-child' ),
		description: __( 'Adresse, téléphone, e-mail, horaires et itinéraire, depuis les réglages.', 'g2rd-glassauto-child' ),
		icon: 'phone',
		attributes: {
			elements: { type: 'string', default: 'adresse,telephone,email' },
			style: { type: 'string', default: 'liste' },
			niveauTitre: { type: 'string', default: 'h3' },
		},
		inspector: function ( props ) {
			return el( PanelBody, { title: __( 'Affichage', 'g2rd-glassauto-child' ) },
				el( TextControl, {
					label: __( 'Éléments (séparés par des virgules)', 'g2rd-glassauto-child' ),
					help: __( 'adresse, telephone, email, horaires, itineraire', 'g2rd-glassauto-child' ),
					value: props.attributes.elements,
					onChange: function ( v ) { props.setAttributes( { elements: v } ); },
				} ),
				el( SelectControl, {
					label: __( 'Style', 'g2rd-glassauto-child' ),
					value: props.attributes.style,
					options: [ { label: __( 'Liste', 'g2rd-glassauto-child' ), value: 'liste' }, { label: __( 'Cartes', 'g2rd-glassauto-child' ), value: 'cartes' } ],
					onChange: function ( v ) { props.setAttributes( { style: v } ); },
				} ),
				el( SelectControl, {
					label: __( 'Niveau des intitulés', 'g2rd-glassauto-child' ),
					value: props.attributes.niveauTitre,
					options: [ { label: 'H2', value: 'h2' }, { label: 'H3', value: 'h3' }, { label: 'H4', value: 'h4' }, { label: __( 'Paragraphe', 'g2rd-glassauto-child' ), value: 'p' } ],
					onChange: function ( v ) { props.setAttributes( { niveauTitre: v } ); },
				} )
			);
		},
	} );

	register( 'glassauto/diagnostic', {
		title: __( 'Diagnostic « Réparer ou remplacer ? »', 'g2rd-glassauto-child' ),
		description: __( 'Schéma interactif du pare-brise : le visiteur situe le dommage et lit l’indication.', 'g2rd-glassauto-child' ),
		icon: 'search',
	} );

	register( 'glassauto/communes', {
		title: __( 'Communes de la zone', 'g2rd-glassauto-child' ),
		description: __( 'Communes desservies avec temps de trajet, en bandes, en secteurs ou en tableau.', 'g2rd-glassauto-child' ),
		icon: 'location-alt',
		attributes: { affichage: { type: 'string', default: 'bandes' }, avecKm: { type: 'boolean', default: false } },
		inspector: function ( props ) {
			return el( PanelBody, { title: __( 'Affichage', 'g2rd-glassauto-child' ) },
				el( SelectControl, {
					label: __( 'Présentation', 'g2rd-glassauto-child' ),
					value: props.attributes.affichage,
					options: [
						{ label: __( 'Bandes de temps de trajet', 'g2rd-glassauto-child' ), value: 'bandes' },
						{ label: __( 'Secteurs géographiques', 'g2rd-glassauto-child' ), value: 'secteurs' },
						{ label: __( 'Tableau des distances', 'g2rd-glassauto-child' ), value: 'tableau' },
					],
					onChange: function ( v ) { props.setAttributes( { affichage: v } ); },
				} ),
				el( ToggleControl, {
					label: __( 'Afficher la distance en km', 'g2rd-glassauto-child' ),
					checked: !! props.attributes.avecKm,
					onChange: function ( v ) { props.setAttributes( { avecKm: !! v } ); },
				} )
			);
		},
	} );

	register( 'glassauto/carte-zone', {
		title: __( 'Carte de la zone', 'g2rd-glassauto-child' ),
		description: __( 'Carte statique des communes autour du centre, cercles de 10, 20 et 30 km.', 'g2rd-glassauto-child' ),
		icon: 'admin-site-alt3',
		attributes: { legende: { type: 'boolean', default: true } },
		inspector: function ( props ) {
			return el( PanelBody, { title: __( 'Affichage', 'g2rd-glassauto-child' ) }, el( ToggleControl, {
				label: __( 'Légende', 'g2rd-glassauto-child' ),
				checked: !! props.attributes.legende,
				onChange: function ( v ) { props.setAttributes( { legende: !! v } ); },
			} ) );
		},
	} );

	register( 'glassauto/plan-acces', {
		title: __( "Plan d'accès", 'g2rd-glassauto-child' ),
		description: __( 'Plan du quartier avec le centre ; le plan interactif ne se charge qu’après un clic (RGPD).', 'g2rd-glassauto-child' ),
		icon: 'location',
	} );
	register( 'glassauto/avis-google', {
		title: __( 'Avis Google du centre', 'g2rd-glassauto-child' ),
		description: __( 'Avis Google authentiques (Place ID des réglages, clé API dans G2RD › Thème). Sans eux, des emplacements vides.', 'g2rd-glassauto-child' ),
		icon: 'star-filled',
		attributes: { max: { type: 'integer', default: 3 }, noteMin: { type: 'integer', default: 4 } },
		inspector: function ( props ) {
			return el( PanelBody, { title: __( 'Affichage', 'g2rd-glassauto-child' ) },
				el( wp.components.RangeControl, { label: __( 'Nombre d’avis', 'g2rd-glassauto-child' ), min: 1, max: 5, value: props.attributes.max, onChange: function ( v ) { props.setAttributes( { max: v } ); } } ),
				el( wp.components.RangeControl, { label: __( 'Note minimale', 'g2rd-glassauto-child' ), min: 1, max: 5, value: props.attributes.noteMin, onChange: function ( v ) { props.setAttributes( { noteMin: v } ); } } )
			);
		},
	} );
} )( window.wp );
