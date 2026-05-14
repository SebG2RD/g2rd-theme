/**
 * Save — Bloc Résumé GEO
 *
 * Génère le HTML sémantique avec microdata Article pour les moteurs IA.
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { summary, keyPoints, tagline, summaryFontSize, keyPointFontSize } = attributes;
	const blockProps                      = useBlockProps.save( {
		className: 'wp-block-g2rd-geo-summary',
	} );

	const filledPoints = ( keyPoints ?? [] ).filter( ( p ) => p.trim() );

	return (
		<div { ...blockProps } itemScope itemType="https://schema.org/Article">
			{/* En-tête */}
			<div className="geo-summary__header" aria-hidden="true">
				<span className="geo-summary__icon">📝</span>
				<span className="geo-summary__tagline" dangerouslySetInnerHTML={ { __html: tagline } } />
			</div>

			{/* Texte de résumé — itemprop abstract pour schema.org */}
			{ summary && (
				<RichText.Content
					tagName="p"
					className="geo-summary__text"
					value={ summary }
					itemProp="abstract"
					style={ summaryFontSize ? { fontSize: summaryFontSize } : undefined }
				/>
			) }

			{/* Points clés */}
			{ filledPoints.length > 0 && (
				<ul className="geo-summary__points">
					{ filledPoints.map( ( point, i ) => (
						<li key={ i } className="geo-summary__point">
							<span className="geo-summary__bullet" aria-hidden="true">✦</span>
							<RichText.Content
								tagName="span"
								className="geo-summary__point-text"
								value={ point }
								style={ keyPointFontSize ? { fontSize: keyPointFontSize } : undefined }
							/>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}
