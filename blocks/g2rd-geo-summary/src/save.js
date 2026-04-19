/**
 * Save — Bloc Résumé GEO
 *
 * Génère le HTML sémantique avec microdata Article pour les moteurs IA.
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { summary, keyPoints, tagline } = attributes;
	const blockProps                      = useBlockProps.save( {
		className: 'wp-block-g2rd-geo-summary',
	} );

	const filledPoints = keyPoints.filter( ( p ) => p.trim() );

	return (
		<div { ...blockProps } itemScope itemType="https://schema.org/Article">
			{/* En-tête */}
			<div className="geo-summary__header" aria-hidden="true">
				<span className="geo-summary__icon">📝</span>
				<span className="geo-summary__tagline">{ tagline }</span>
				<span className="geo-summary__badge">GEO</span>
			</div>

			{/* Texte de résumé — itemprop abstract pour schema.org */}
			{ summary && (
				<RichText.Content
					tagName="p"
					className="geo-summary__text"
					value={ summary }
					itemProp="abstract"
				/>
			) }

			{/* Points clés */}
			{ filledPoints.length > 0 && (
				<ul className="geo-summary__points">
					{ filledPoints.map( ( point, i ) => (
						<li key={ i } className="geo-summary__point">
							<span className="geo-summary__bullet" aria-hidden="true">✦</span>
							{ point }
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}
