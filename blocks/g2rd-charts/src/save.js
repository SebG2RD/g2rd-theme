/**
 * G2RD Graphiques — fonction de sauvegarde
 * Sérialise la configuration Chart.js en data-attribute JSON.
 * Le canvas est initialisé par view.js côté frontend.
 */
import { useBlockProps } from "@wordpress/block-editor";

export default function save( { attributes } ) {
	const { chartHeight, backgroundColor, labelFontSize } = attributes;

	// La totalité de la config est passée au frontend en JSON
	const config = JSON.stringify( attributes );

	const blockProps = useBlockProps.save( {
		className:              "g2rd-charts-wrap",
		style:                  { backgroundColor: backgroundColor || undefined },
		"data-g2rd-chart":      config,
		"data-label-font-size": labelFontSize || undefined,
	} );

	return (
		<div { ...blockProps }>
			<div
				className="g2rd-charts__canvas-wrap"
				style={ { position: "relative", height: chartHeight + "px" } }
			>
				<canvas className="g2rd-charts__canvas" aria-label={ attributes.chartTitle || "Graphique" } role="img" />
			</div>
		</div>
	);
}
