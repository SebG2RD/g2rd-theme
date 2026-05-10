import { useBlockProps } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const {
		blockId,
		images,
		scrollDistance,
		renderMode,
		imageWidth,
		imageHeight,
		minHeight,
		backgroundColor,
		showOverlayText,
		overlayText,
		overlayTextColor,
		overlayTextSize,
		overlayTextWeight,
		overlayPosition,
		overlayFade,
	} = attributes;

	if ( ! images || images.length === 0 ) return null;

	const blockProps = useBlockProps.save( {
		className:              'g2rd-pin-scroll',
		'data-block-id':        blockId,
		'data-images':          JSON.stringify( images.map( ( img ) => ( { url: img.url, alt: img.alt } ) ) ),
		'data-scroll-distance': scrollDistance,
		'data-render-mode':     renderMode,
		'data-image-width':     imageWidth,
		'data-image-height':    imageHeight,
		style: {
			backgroundColor,
			minHeight,
		},
	} );

	return (
		<div { ...blockProps }>
			{ renderMode === 'canvas' ? (
				<canvas
					className="g2rd-pin-scroll__canvas"
					aria-label={ images[ 0 ]?.alt || '' }
					role="img"
				/>
			) : (
				<img
					className="g2rd-pin-scroll__frame"
					src={ images[ 0 ].url }
					alt={ images[ 0 ].alt }
				/>
			) }

			{ showOverlayText && overlayText && (
				<div
					className="g2rd-pin-scroll__overlay"
					data-position={ overlayPosition }
					data-fade={ overlayFade ? '1' : '0' }
					aria-hidden="true"
					style={ {
						color:      overlayTextColor,
						fontSize:   overlayTextSize + 'px',
						fontWeight: overlayTextWeight,
					} }
				>
					<span>{ overlayText }</span>
				</div>
			) }
		</div>
	);
}
