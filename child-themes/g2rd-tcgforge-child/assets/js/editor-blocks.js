/**
 * TCGForge — déclaration des blocs dynamiques dans l'éditeur.
 *
 * Script sans build : il utilise les globales wp.* fournies par WordPress.
 * Chaque bloc est rendu par PHP (inc/blocks.php). L'éditeur affiche l'aperçu
 * serveur, ou un libellé quand le produit n'a pas encore la donnée.
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
	var RangeControl = wp.components.RangeControl;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;

	function placeholder( label ) {
		return function () {
			return el( 'p', { className: 'tcgf-editor-placeholder' }, label );
		};
	}

	function preview( name, props, label ) {
		var args = {};
		if ( props.context && props.context.postId ) {
			args.post_id = props.context.postId;
		}
		return el( ServerSideRender, {
			block: name,
			attributes: props.attributes,
			urlQueryArgs: args,
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
			category: 'woocommerce',
			icon: settings.icon || 'tag',
			attributes: settings.attributes || {},
			usesContext: settings.usesContext || [ 'postId', 'postType' ],
			supports: { html: false, align: [ 'wide', 'full' ] },
			edit: function ( props ) {
				return el(
					Fragment,
					null,
					settings.controls ? el( InspectorControls, null, el( PanelBody, { title: 'Réglages' }, settings.controls( props ) ) ) : null,
					el( 'div', useBlockProps(), preview( name, props, settings.empty ) )
				);
			},
			save: function () {
				return null;
			},
		} );
	}

	register( 'tcgforge/product-badges', {
		title: 'TCGForge — Badges produit',
		description: 'Nouveau, Précommande, Promotion, Stock faible, Rupture. Tirés des données WooCommerce.',
		empty: 'Badges produit (aucun statut à afficher pour ce produit)',
		attributes: { max: { type: 'number', default: 2 } },
		controls: function ( props ) {
			return el( RangeControl, {
				label: 'Nombre maximum de badges',
				min: 1,
				max: 4,
				value: props.attributes.max,
				onChange: function ( value ) {
					props.setAttributes( { max: value } );
				},
			} );
		},
	} );

	register( 'tcgforge/product-facts', {
		title: 'TCGForge — Jeu, extension, langue',
		description: 'Lit les attributs WooCommerce jeu, extension, langue et type de produit.',
		icon: 'index-card',
		empty: 'Jeu, extension, langue (attributs non renseignés sur ce produit)',
		attributes: {
			display: { type: 'string', default: 'chips' },
			only: { type: 'array', default: [] },
		},
		controls: function ( props ) {
			return el( SelectControl, {
				label: 'Présentation',
				value: props.attributes.display,
				options: [
					{ label: 'Étiquettes (cartes produit)', value: 'chips' },
					{ label: 'Liste détaillée (fiche produit)', value: 'liste' },
				],
				onChange: function ( value ) {
					props.setAttributes( { display: value } );
				},
			} );
		},
	} );

	register( 'tcgforge/product-release', {
		title: 'TCGForge — Précommande et date de sortie',
		description: 'S\'affiche seulement si le produit porte un statut de précommande, une date de sortie ou une mention d\'expédition.',
		icon: 'calendar-alt',
		empty: 'Précommande et date de sortie (aucune donnée sur ce produit)',
		attributes: { compact: { type: 'boolean', default: false } },
		controls: function ( props ) {
			return el( ToggleControl, {
				label: 'Version courte (cartes produit)',
				checked: !! props.attributes.compact,
				onChange: function ( value ) {
					props.setAttributes( { compact: value } );
				},
			} );
		},
	} );

	register( 'tcgforge/product-related', {
		title: 'TCGForge — Même licence ou accessoires',
		description: 'Produits de la même licence, ou accessoires complémentaires (ventes croisées du produit).',
		icon: 'grid-view',
		empty: 'Produits liés (aucun produit correspondant pour le moment)',
		attributes: {
			mode: { type: 'string', default: 'licence' },
			title: { type: 'string', default: '' },
			limit: { type: 'number', default: 4 },
			columns: { type: 'number', default: 4 },
		},
		controls: function ( props ) {
			return el(
				Fragment,
				null,
				el( SelectControl, {
					label: 'Sélection',
					value: props.attributes.mode,
					options: [
						{ label: 'Même licence', value: 'licence' },
						{ label: 'Accessoires complémentaires', value: 'accessoires' },
					],
					onChange: function ( value ) {
						props.setAttributes( { mode: value } );
					},
				} ),
				el( TextControl, {
					label: 'Titre (vide = titre par défaut)',
					value: props.attributes.title,
					onChange: function ( value ) {
						props.setAttributes( { title: value } );
					},
				} ),
				el( RangeControl, {
					label: 'Nombre de produits',
					min: 2,
					max: 12,
					value: props.attributes.limit,
					onChange: function ( value ) {
						props.setAttributes( { limit: value } );
					},
				} )
			);
		},
	} );

	register( 'tcgforge/products', {
		title: 'TCGForge — Rayon de produits',
		description: 'Nouveautés, mis en avant, précommandes, promotions, meilleures ventes ou catégorie. Lit le catalogue WooCommerce. Sans message d\'état vide, le rayon disparaît tant qu\'il n\'a aucun produit.',
		icon: 'screenoptions',
		usesContext: [],
		empty: 'Rayon de produits (aucun produit pour le moment : masqué sur le site)',
		attributes: {
			type: { type: 'string', default: 'nouveautes' },
			title: { type: 'string', default: '' },
			link: { type: 'string', default: '' },
			limit: { type: 'number', default: 8 },
			columns: { type: 'number', default: 4 },
			category: { type: 'string', default: '' },
			attribute: { type: 'string', default: '' },
			terms: { type: 'string', default: '' },
			tag: { type: 'string', default: '' },
			paginate: { type: 'boolean', default: false },
			empty: { type: 'string', default: '' },
		},
		controls: function ( props ) {
			var a = props.attributes;
			function text( label, key, help ) {
				return el( TextControl, {
					label: label,
					help: help,
					value: a[ key ],
					onChange: function ( value ) {
						var next = {};
						next[ key ] = value;
						props.setAttributes( next );
					},
				} );
			}
			return el(
				Fragment,
				null,
				el( SelectControl, {
					label: 'Sélection',
					value: a.type,
					options: [
						{ label: 'Nouveautés (derniers ajouts)', value: 'nouveautes' },
						{ label: 'Mis en avant (étoile WooCommerce)', value: 'mis-en-avant' },
						{ label: 'Précommandes', value: 'precommandes' },
						{ label: 'Promotions', value: 'promotions' },
						{ label: 'Meilleures ventes', value: 'meilleures-ventes' },
						{ label: 'Catégorie', value: 'categorie' },
						{ label: 'Attribut', value: 'attribut' },
					],
					onChange: function ( value ) {
						props.setAttributes( { type: value } );
					},
				} ),
				text( 'Titre du rayon', 'title' ),
				text( 'Lien « Tout voir »', 'link' ),
				text( 'Catégories produit (slugs, séparés par des virgules)', 'category' ),
				text( 'Attribut (slug, ex. jeu)', 'attribute' ),
				text( 'Termes de l\'attribut (slugs)', 'terms' ),
				el( RangeControl, {
					label: 'Nombre de produits',
					min: 2,
					max: 48,
					value: a.limit,
					onChange: function ( value ) {
						props.setAttributes( { limit: value } );
					},
				} ),
				el( ToggleControl, {
					label: 'Pagination',
					checked: !! a.paginate,
					onChange: function ( value ) {
						props.setAttributes( { paginate: value } );
					},
				} ),
				text( 'Message si aucun produit', 'empty', 'Vide : le rayon est masqué tant qu\'il n\'a aucun produit.' )
			);
		},
	} );

	register( 'tcgforge/licence-products', {
		title: 'TCGForge — Catalogue de la licence',
		description: 'Affiche le catalogue de la catégorie produit qui porte le même slug que la page.',
		icon: 'store',
		empty: 'Catalogue de la licence (catégorie produit introuvable ou vide)',
		attributes: {
			limit: { type: 'number', default: 8 },
			columns: { type: 'number', default: 4 },
		},
		controls: function ( props ) {
			return el( RangeControl, {
				label: 'Produits dans « Dernières arrivées »',
				min: 4,
				max: 24,
				value: props.attributes.limit,
				onChange: function ( value ) {
					props.setAttributes( { limit: value } );
				},
			} );
		},
	} );

	register( 'tcgforge/payment-methods', {
		title: 'TCGForge — Moyens de paiement activés',
		description: 'Liste les moyens de paiement activés dans WooCommerce. Rien d\'autre.',
		icon: 'money-alt',
		usesContext: [],
		empty: 'Moyens de paiement (aucun moyen activé dans WooCommerce)',
	} );

	register( 'tcgforge/newsletter', {
		title: 'TCGForge — Newsletter',
		description: 'Affiche le formulaire configuré dans Réglages › Général › TCGForge. Masqué tant qu\'aucun formulaire n\'est configuré.',
		icon: 'email',
		usesContext: [],
		empty: 'Newsletter (aucun formulaire configuré, section masquée sur le site)',
		attributes: {
			title: { type: 'string', default: '' },
			text: { type: 'string', default: '' },
		},
		controls: function ( props ) {
			return el(
				Fragment,
				null,
				el( TextControl, {
					label: 'Titre (vide = titre par défaut)',
					value: props.attributes.title,
					onChange: function ( value ) {
						props.setAttributes( { title: value } );
					},
				} ),
				el( TextControl, {
					label: 'Texte (vide = texte par défaut)',
					value: props.attributes.text,
					onChange: function ( value ) {
						props.setAttributes( { text: value } );
					},
				} )
			);
		},
	} );

	register( 'tcgforge/wishlist-link', {
		title: 'TCGForge — Lien liste de souhaits',
		description: 'Visible seulement si une extension de liste de souhaits est active.',
		icon: 'heart',
		usesContext: [],
		empty: 'Liste de souhaits (aucune extension active, lien masqué)',
	} );

	register( 'tcgforge/withdrawal', {
		title: 'TCGForge — Rétractation en ligne',
		description: 'Formulaire de rétractation en deux étapes, avec accusé de réception par e-mail.',
		icon: 'undo',
		usesContext: [],
		empty: 'Rétractation en ligne : le formulaire s\'affiche sur le site.',
	} );
} )( window.wp );
