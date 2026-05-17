import { registerBlockType }    from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import './style.css';
import Edit from './edit';
import Save from './save';

registerBlockType( 'g2rd/geo-summary', {
	edit: Edit,
	save: Save,
	deprecated: [
		{
			// v1 : badge GEO visible en front (retiré)
			migrate( attributes ) {
				return attributes;
			},
			save( { attributes } ) {
				const { summary, keyPoints, tagline } = attributes;
				const blockProps = useBlockProps.save( { className: 'wp-block-g2rd-geo-summary' } );
				const filledPoints = ( keyPoints ?? [] ).filter( ( p ) => p.trim() );
				return (
					<div { ...blockProps } itemScope itemType="https://schema.org/Article">
						<div className="geo-summary__header" aria-hidden="true">
							<span className="geo-summary__icon">📝</span>
							<span className="geo-summary__tagline">{ tagline }</span>
							<span className="geo-summary__badge">GEO</span>
						</div>
						{ summary && (
							<RichText.Content
								tagName="p"
								className="geo-summary__text"
								value={ summary }
								itemProp="abstract"
							/>
						) }
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
			},
		},
	],
} );
