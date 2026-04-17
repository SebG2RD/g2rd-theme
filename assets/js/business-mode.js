/**
 * G2RD Business Mode — panneau de conseils Gutenberg
 *
 * Affiche dans la sidebar de l'éditeur les recommandations
 * adaptées au type de site configuré dans les options G2RD.
 */
( function () {
	const { registerPlugin }     = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { createElement: el, Fragment } = wp.element;
	const { __ }                  = wp.i18n;
	const data = window.g2rdBusiness;

	if ( ! data ) return;

	registerPlugin( 'g2rd-business-mode', {
		render() {
			return el(
				PluginDocumentSettingPanel,
				{
					name:   'g2rd-business-panel',
					title:  `🎯 ${ __( 'Conseils G2RD', 'g2rd' ) } — ${ data.typeLabel }`,
					icon:   'chart-line',
					className: 'g2rd-business-panel',
				},
				el( 'p', { style: { fontSize: '12px', background: '#FFF8F0', padding: '8px 10px', borderLeft: '3px solid #D4A373', borderRadius: '0 4px 4px 0', lineHeight: '1.5' } },
					'💡 ' + data.tip
				),
				el( 'p', { style: { fontSize: '12px', marginTop: '10px' } },
					el( 'strong', {}, __( 'CTA recommandé :', 'g2rd' ) ),
					' ',
					el( 'code', { style: { background: '#f0f4ff', padding: '2px 6px', borderRadius: '3px' } }, data.cta )
				),
				el( 'p', { style: { fontSize: '12px', marginTop: '8px' } },
					el( 'strong', {}, __( 'Patterns adaptés :', 'g2rd' ) )
				),
				el( 'ul', { style: { fontSize: '11px', margin: '4px 0 0 0', padding: '0 0 0 12px' } },
					data.recommendedPatterns.map( ( p, i ) =>
						el( 'li', { key: i, style: { marginBottom: '2px' } },
							el( 'code', {}, p )
						)
					)
				)
			);
		},
	} );
} )();
