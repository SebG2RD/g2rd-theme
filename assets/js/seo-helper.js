/**
 * G2RD SEO Helper — panneau Gutenberg sidebar
 *
 * Affiche un score SEO et une checklist dans la sidebar de l'éditeur.
 */
( function () {
	const { registerPlugin }     = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { createElement: el, useState, useEffect, Fragment } = wp.element;
	const { Button, Spinner, Icon }  = wp.components;
	const { useSelect }              = wp.data;
	const { __ }                     = wp.i18n;

	const STATUS_ICONS = {
		ok:      '✓',
		warning: '⚠',
		error:   '✕',
		info:    '·',
	};

	function ScoreGauge( { score } ) {
		const color =
			score >= 75 ? '#4caf50' :
			score >= 50 ? '#ff9800' : '#f44336';

		return el(
			'div',
			{ className: 'g2rd-seo-score' },
			el( 'div', { className: 'g2rd-seo-score__ring' },
				el( 'svg', { viewBox: '0 0 36 36', className: 'g2rd-seo-score__svg' },
					el( 'path', {
						d: 'M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831',
						fill: 'none',
						stroke: '#e0e0e0',
						strokeWidth: '3',
					} ),
					el( 'path', {
						d: 'M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831',
						fill: 'none',
						stroke: color,
						strokeWidth: '3',
						strokeDasharray: `${ score }, 100`,
						strokeLinecap: 'round',
					} ),
					el( 'text', { x: '18', y: '20.35', className: 'g2rd-seo-score__text' }, score )
				)
			),
			el( 'p', { className: 'g2rd-seo-score__label', style: { color } },
				score >= 75 ? __( 'Bon', 'g2rd' ) :
				score >= 50 ? __( 'À améliorer', 'g2rd' ) :
				__( 'Insuffisant', 'g2rd' )
			)
		);
	}

	function CheckItem( { check } ) {
		return el(
			'li',
			{ className: `g2rd-seo-check g2rd-seo-check--${ check.status }` },
			el( 'span', { className: 'g2rd-seo-check__icon', 'aria-hidden': 'true' }, STATUS_ICONS[ check.status ] || '·' ),
			el( 'span', { className: 'g2rd-seo-check__body' },
				el( 'strong', {}, check.label ),
				el( 'span', {}, ' — ' + check.message )
			)
		);
	}

	function SEOSidebarPanel() {
		const [ data, setData ]       = useState( null );
		const [ loading, setLoading ] = useState( false );
		const [ error, setError ]     = useState( null );

		const postId = useSelect( ( select ) =>
			select( 'core/editor' ).getCurrentPostId()
		);

		function analyze() {
			if ( ! postId ) return;
			setLoading( true );
			setError( null );

			window.fetch( g2rdSEO.restUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce':   g2rdSEO.nonce,
				},
				body: JSON.stringify( { post_id: postId } ),
			} )
				.then( ( res ) => res.json() )
				.then( ( json ) => {
					if ( json.error ) throw new Error( json.error );
					setData( json );
				} )
				.catch( ( err ) => setError( err.message ) )
				.finally( () => setLoading( false ) );
		}

		// Lancer l'analyse automatiquement à l'ouverture
		useEffect( () => {
			analyze();
		}, [ postId ] ); // eslint-disable-line react-hooks/exhaustive-deps

		return el(
			'div',
			{ className: 'g2rd-seo-panel' },

			loading && el( 'div', { className: 'g2rd-seo-panel__loading' }, el( Spinner ) ),

			error && el( 'div', { className: 'g2rd-seo-panel__error' }, __( 'Erreur : ', 'g2rd' ) + error ),

			data && el( Fragment, {},
				el( ScoreGauge, { score: data.score } ),
				el( 'ul', { className: 'g2rd-seo-checklist' },
					Object.values( data.checks ).map( ( check, i ) =>
						el( CheckItem, { key: i, check } )
					)
				)
			),

			el( Button, {
				variant:   'secondary',
				className: 'g2rd-seo-panel__refresh',
				onClick:   analyze,
				disabled:  loading,
			}, __( 'Actualiser l\'analyse', 'g2rd' ) )
		);
	}

	registerPlugin( 'g2rd-seo-helper', {
		render() {
			return el( Fragment, {},
				el( PluginSidebarMoreMenuItem, { target: 'g2rd-seo-sidebar' },
					__( 'SEO G2RD', 'g2rd' )
				),
				el( PluginSidebar, {
					name:  'g2rd-seo-sidebar',
					title: __( 'SEO G2RD', 'g2rd' ),
					icon:  'chart-line',
				},
					el( SEOSidebarPanel )
				)
			);
		},
	} );
} )();
