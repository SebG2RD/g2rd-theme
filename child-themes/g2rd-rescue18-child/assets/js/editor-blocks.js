/**
 * Rescue 18 · déclaration des blocs dynamiques dans l'éditeur, sans build.
 *
 * Les définitions (titre, icône, attributs) viennent de PHP
 * (window.rescue18Blocks, voir inc/blocks.php). Chaque bloc s'affiche via
 * l'aperçu serveur ; les réglages sont générés selon le type d'attribut.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! window.rescue18Blocks ) {
		return;
	}
	const { registerBlockType, getBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, __experimentalNumberControl: NumberControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;

	Object.keys( window.rescue18Blocks ).forEach( ( slug ) => {
		const def = window.rescue18Blocks[ slug ];
		const name = 'rescue18/' + slug;
		if ( getBlockType( name ) ) {
			return;
		}
		const attributes = {};
		Object.keys( def.attrs || {} ).forEach( ( k ) => {
			attributes[ k ] = { type: def.attrs[ k ].type, default: def.attrs[ k ].default };
		} );

		registerBlockType( name, {
			apiVersion: 3,
			title: def.title,
			icon: def.icon,
			category: 'rescue18',
			attributes,
			supports: { html: false, align: [ 'wide', 'full' ] },
			edit( props ) {
				const blockProps = useBlockProps();
				const controls = Object.keys( def.attrs || {} ).map( ( k ) => {
					const a = def.attrs[ k ];
					const value = props.attributes[ k ];
					const onChange = ( v ) => props.setAttributes( { [ k ]: a.type === 'number' ? parseInt( v || 0, 10 ) : v } );
					if ( a.options ) {
						return el( SelectControl, {
							key: k,
							label: a.label,
							value,
							options: Object.keys( a.options ).map( ( o ) => ( { value: o, label: a.options[ o ] } ) ),
							onChange,
						} );
					}
					if ( a.type === 'number' && NumberControl ) {
						return el( NumberControl, { key: k, label: a.label, value, min: 0, onChange } );
					}
					return el( TextControl, { key: k, label: a.label, value: value === undefined ? '' : String( value ), onChange } );
				} );
				return el(
					Fragment,
					null,
					controls.length ? el( InspectorControls, null, el( PanelBody, { title: def.title }, controls ) ) : null,
					el( 'div', blockProps, el( ServerSideRender, {
						block: name,
						attributes: props.attributes,
						urlQueryArgs: { post_id: wp.data && wp.data.select( 'core/editor' ) ? wp.data.select( 'core/editor' ).getCurrentPostId() : 0 },
					} ) )
				);
			},
			save: () => null,
		} );
	} );
} )( window.wp );
