/**
 * G2RD Graphiques — composant éditeur
 * Utilise Chart.js pour le rendu live dans le canvas Gutenberg.
 */
import { __ } from "@wordpress/i18n";
import {
	useBlockProps,
	InspectorControls,
	PanelColorSettings,
	store as blockEditorStore,
} from "@wordpress/block-editor";
import { TypographySizePanel } from "../../shared/TypographySizePanel";
import {
	PanelBody,
	SelectControl,
	RangeControl,
	TextControl,
	ToggleControl,
	Button,
	TextareaControl,
	ColorPalette,
} from "@wordpress/components";
import { useEffect, useRef, useState } from "@wordpress/element";
import { useSelect } from "@wordpress/data";
import { Chart, registerables } from "chart.js";

Chart.register( ...registerables );

// ─────────────────────────────────────────────
// Couleurs par défaut pour nouveaux datasets
// ─────────────────────────────────────────────
const PALETTE = [
	"#2F425D", "#4A90D9", "#E74C3C", "#2ECC71",
	"#F39C12", "#9B59B6", "#1ABC9C", "#E67E22",
];

// ─────────────────────────────────────────────
// Construit la config Chart.js depuis les attrs
// ─────────────────────────────────────────────
function buildChartConfig( attrs ) {
	const {
		chartType, labels, datasets, showLegend, showGrid,
		showValues, stacked, borderRadius, tension,
		specMaxValue, gridColor, labelColor, chartTitle,
	} = attrs;

	const tension01 = tension / 100;

	// Type Chart.js natif
	const typeMap = {
		"bar":                "bar",
		"bar-horizontal":     "bar",
		"bar-horizontal-spec":"bar",
		"circle":             "doughnut",
		"line":               "line",
		"area":               "line",
		"stacked":            "bar",
	};
	const type = typeMap[ chartType ] || "bar";

	// Datasets Chart.js
	const chartDatasets = datasets.map( ( ds ) => {
		const base = {
			label:        ds.label,
			data:         ds.data,
			borderColor:  ds.color,
			borderWidth:  2,
		};

		if ( type === "bar" ) {
			base.backgroundColor = ds.color + "CC"; // 80% opacité
			base.borderRadius    = borderRadius;
		} else if ( type === "line" ) {
			base.backgroundColor  = chartType === "area" ? ds.color + "33" : "transparent";
			base.fill             = chartType === "area";
			base.tension          = tension01;
			base.pointRadius      = 4;
			base.pointHoverRadius = 6;
		} else if ( type === "doughnut" ) {
			// Couleurs multiples par segment
			base.backgroundColor = datasets[ 0 ].data.map(
				( _, i ) => ( ds.colors && ds.colors[ i ] ) || PALETTE[ i % PALETTE.length ]
			);
			base.borderColor     = "#ffffff";
			base.borderWidth     = 2;
		}

		return base;
	} );

	// Options Chart.js
	const isHorizontal   = chartType === "bar-horizontal" || chartType === "bar-horizontal-spec";
	const isCircle       = chartType === "circle";
	const isStacked      = chartType === "stacked" || stacked;
	const isSpec         = chartType === "bar-horizontal-spec";

	const options = {
		indexAxis:   isHorizontal ? "y" : "x",
		responsive:  true,
		maintainAspectRatio: false,
		animation:   { duration: 600 },
		plugins: {
			legend: {
				display:  ! isCircle ? showLegend : true,
				position: isCircle ? "right" : "bottom",
				labels:   { color: labelColor, font: { size: 13 } },
			},
			title: {
				display: !! chartTitle,
				text:    chartTitle,
				color:   labelColor,
				font:    { size: 16, weight: "bold" },
				padding: { bottom: 12 },
			},
			tooltip: { enabled: true },
		},
		scales: isCircle ? {} : {
			x: {
				stacked: isStacked,
				grid:    { display: showGrid, color: gridColor },
				ticks:   { color: labelColor },
				...(
					isSpec ? {
						min: 0, max: specMaxValue,
						ticks: { callback: ( v ) => v + "%" },
					} : {}
				),
			},
			y: {
				stacked: isStacked,
				grid:    { display: showGrid, color: gridColor },
				ticks:   { color: labelColor },
				beginAtZero: true,
			},
		},
	};

	return { type, data: { labels, datasets: chartDatasets }, options };
}

// ─────────────────────────────────────────────
// Composant principal Edit
// ─────────────────────────────────────────────
export default function Edit( { attributes, setAttributes } ) {
	const {
		chartType, chartTitle, labels, datasets,
		chartHeight, showLegend, showGrid, showValues,
		stacked, borderRadius, tension, specMaxValue,
		backgroundColor, gridColor, labelColor, labelFontSize,
	} = attributes;

	// ── Couleurs du thème via block-editor settings
	const themeColors = useSelect( ( select ) => {
		return select( blockEditorStore ).getSettings()?.colors ?? [];
	}, [] );

	// Palette de secours si le thème n'en définit pas
	const activePalette = themeColors.length > 0
		? themeColors.map( ( c ) => c.color )
		: PALETTE;

	const canvasRef  = useRef( null );
	const chartRef   = useRef( null );

	// ── Rendu Chart.js dans le canvas éditeur
	useEffect( () => {
		if ( ! canvasRef.current ) return;

		if ( chartRef.current ) {
			chartRef.current.destroy();
		}

		const config = buildChartConfig( attributes );
		chartRef.current = new Chart( canvasRef.current, config );

		return () => {
			if ( chartRef.current ) {
				chartRef.current.destroy();
				chartRef.current = null;
			}
		};
	}, [ chartType, labels, datasets, showLegend, showGrid, stacked, borderRadius, tension, specMaxValue, gridColor, labelColor, chartTitle ] );

	// ── Helpers datasets
	const updateDataset = ( index, key, value ) => {
		const updated = datasets.map( ( ds, i ) => i === index ? { ...ds, [ key ]: value } : ds );
		setAttributes( { datasets: updated } );
	};

	const updateDatasetData = ( dsIndex, labelIndex, value ) => {
		const num     = parseFloat( value ) || 0;
		const updated = datasets.map( ( ds, i ) => {
			if ( i !== dsIndex ) return ds;
			const newData = [ ...ds.data ];
			newData[ labelIndex ] = num;
			return { ...ds, data: newData };
		} );
		setAttributes( { datasets: updated } );
	};

	const addDataset = () => {
		const color   = activePalette[ datasets.length % activePalette.length ];
		const newData = labels.map( () => Math.round( Math.random() * 80 + 10 ) );
		setAttributes( {
			datasets: [ ...datasets, { label: `Série ${ datasets.length + 1 }`, data: newData, color } ],
		} );
	};

	const removeDataset = ( i ) => {
		if ( datasets.length <= 1 ) return;
		setAttributes( { datasets: datasets.filter( ( _, j ) => j !== i ) } );
	};

	// ── Helpers labels
	// labelsRaw conserve le texte brut du textarea (y compris les lignes vides en cours de frappe)
	// afin que la touche Entrée fonctionne nativement.
	const [ labelsRaw, setLabelsRaw ] = useState( () => labels.join( "\n" ) );

	const onLabelsChange = ( text ) => {
		setLabelsRaw( text ); // mise à jour instantanée de l'affichage
		const newLabels = text.split( "\n" ).map( ( l ) => l.trim() ).filter( Boolean );
		if ( newLabels.length === 0 ) return; // ne pas vider les étiquettes en cours de frappe
		// Synchronise les longueurs des données
		const updated = datasets.map( ( ds ) => {
			const newData = newLabels.map( ( _, i ) => ds.data[ i ] ?? 0 );
			return { ...ds, data: newData };
		} );
		setAttributes( { labels: newLabels, datasets: updated } );
	};

	const blockProps = useBlockProps( {
		className:               "g2rd-charts-wrap",
		style:                   { backgroundColor: backgroundColor || undefined },
		"data-label-font-size":  labelFontSize || undefined,
	} );

	return (
		<>
			<TypographySizePanel
				elements={ [
					{
						label:    __( "Libellés", "g2rd" ),
						value:    labelFontSize,
						onChange: ( v ) => setAttributes( { labelFontSize: v ?? "" } ),
					},
				] }
			/>

			<InspectorControls>

				{ /* Type de graphique */ }
				<PanelBody title={ __( "Type de graphique", "g2rd" ) } initialOpen={ true }>
					<SelectControl __next40pxDefaultSize __nextHasNoMarginBottom
						label={ __( "Type", "g2rd" ) }
						value={ chartType }
						options={ [
							{ label: __( "Barres verticales",              "g2rd" ), value: "bar"                  },
							{ label: __( "Barres horizontales",            "g2rd" ), value: "bar-horizontal"        },
							{ label: __( "Barres horizontales (specs)",    "g2rd" ), value: "bar-horizontal-spec"   },
							{ label: __( "Barres empilées",                "g2rd" ), value: "stacked"               },
							{ label: __( "Graphique circulaire (cercle)",  "g2rd" ), value: "circle"                },
							{ label: __( "Courbe (line)",                  "g2rd" ), value: "line"                  },
							{ label: __( "Aire (area)",                    "g2rd" ), value: "area"                  },
						] }
						onChange={ ( v ) => setAttributes( { chartType: v } ) }
					/>
					<TextControl
						label={ __( "Titre du graphique", "g2rd" ) }
						value={ chartTitle }
						onChange={ ( v ) => setAttributes( { chartTitle: v } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom
						label={ __( "Hauteur (px)", "g2rd" ) }
						value={ chartHeight }
						onChange={ ( v ) => setAttributes( { chartHeight: v } ) }
						min={ 150 } max={ 700 }
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				{ /* Données */ }
				<PanelBody title={ __( "Étiquettes (axe X / catégories)", "g2rd" ) } initialOpen={ true }>
					<TextareaControl
						label={ __( "Une étiquette par ligne", "g2rd" ) }
						value={ labelsRaw }
						onChange={ onLabelsChange }
						rows={ 5 }
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				{ /* Datasets */ }
				<PanelBody title={ __( "Séries de données", "g2rd" ) } initialOpen={ true }>
					{ datasets.map( ( ds, dsIdx ) => (
						<div key={ dsIdx } style={ { border: "1px solid #e0e0e0", borderRadius: "4px", padding: "10px", marginBottom: "10px" } }>
							<div style={ { display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "8px" } }>
								<strong style={ { fontSize: "12px" } }>{ ds.label || `Série ${ dsIdx + 1 }` }</strong>
								{ datasets.length > 1 && (
									<Button isDestructive size="small" onClick={ () => removeDataset( dsIdx ) } icon="trash" label={ __( "Supprimer", "g2rd" ) } />
								) }
							</div>
							<TextControl
								label={ __( "Nom de la série", "g2rd" ) }
								value={ ds.label }
								onChange={ ( v ) => updateDataset( dsIdx, "label", v ) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<div style={ { marginTop: "8px", marginBottom: "4px" } }>
								<p style={ { fontSize: "11px", margin: "0 0 6px" } }>{ __( "Couleur de la série", "g2rd" ) }</p>
								<ColorPalette
									colors={ themeColors }
									value={ ds.color }
									onChange={ ( color ) => updateDataset( dsIdx, "color", color || activePalette[ dsIdx % activePalette.length ] ) }
									clearable={ false }
									__experimentalIsRenderedInSidebar
								/>
							</div>
							<p style={ { fontSize: "11px", fontWeight: 600, margin: "8px 0 4px", textTransform: "uppercase" } }>
								{ __( "Valeurs", "g2rd" ) }
							</p>
							{ labels.map( ( label, lIdx ) => (
								<div key={ lIdx } style={ { display: "flex", alignItems: "center", gap: "6px", marginBottom: "4px" } }>
									<span style={ { fontSize: "12px", flex: 1, color: "#555" } }>{ label }</span>
									<input
										type="number"
										value={ ds.data[ lIdx ] ?? 0 }
										onChange={ ( e ) => updateDatasetData( dsIdx, lIdx, e.target.value ) }
										style={ { width: "70px", padding: "2px 4px", border: "1px solid #ddd", borderRadius: "4px", fontSize: "12px", textAlign: "right" } }
									/>
								</div>
							) ) }
						</div>
					) ) }
					{ chartType !== "circle" && (
						<Button variant="secondary" size="small" onClick={ addDataset } icon="plus">
							{ __( "Ajouter une série", "g2rd" ) }
						</Button>
					) }
				</PanelBody>

				{ /* Options d'affichage */ }
				<PanelBody title={ __( "Options d'affichage", "g2rd" ) } initialOpen={ false }>
					<ToggleControl label={ __( "Afficher la légende", "g2rd" ) }  checked={ showLegend }  onChange={ ( v ) => setAttributes( { showLegend: v } ) }  __nextHasNoMarginBottom />
					<ToggleControl label={ __( "Afficher la grille", "g2rd" ) }   checked={ showGrid }    onChange={ ( v ) => setAttributes( { showGrid: v } ) }    __nextHasNoMarginBottom />
					{ ( chartType === "bar" || chartType === "stacked" ) && (
						<>
							<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( "Arrondi des barres (px)", "g2rd" ) } value={ borderRadius } onChange={ ( v ) => setAttributes( { borderRadius: v } ) } min={ 0 } max={ 20 } __nextHasNoMarginBottom />
						</>
					) }
					{ ( chartType === "line" || chartType === "area" ) && (
						<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( "Courbure de la ligne (%)", "g2rd" ) } value={ tension } onChange={ ( v ) => setAttributes( { tension: v } ) } min={ 0 } max={ 100 } __nextHasNoMarginBottom />
					) }
					{ chartType === "bar-horizontal-spec" && (
						<RangeControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( "Valeur maximale (specs)", "g2rd" ) } value={ specMaxValue } onChange={ ( v ) => setAttributes( { specMaxValue: v } ) } min={ 10 } max={ 1000 } __nextHasNoMarginBottom />
					) }
				</PanelBody>

				{ /* Couleurs */ }
				<PanelColorSettings
					title={ __( "Couleurs", "g2rd" ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: labelColor,
							onChange: ( v ) => setAttributes( { labelColor: v } ),
							label: __( "Étiquettes", "g2rd" ),
						},
						{
							value: gridColor,
							onChange: ( v ) => setAttributes( { gridColor: v } ),
							label: __( "Grille", "g2rd" ),
						},
						{
							value: backgroundColor,
							onChange: ( v ) => setAttributes( { backgroundColor: v } ),
							label: __( "Fond du bloc", "g2rd" ),
						},
					] }
				/>

			</InspectorControls>

			{ /* ──────────── Rendu éditeur ──────────── */ }
			<div { ...blockProps }>
				<div className="g2rd-charts__canvas-wrap" style={ { height: chartHeight + "px", position: "relative" } }>
					<canvas ref={ canvasRef } />
				</div>
			</div>
		</>
	);
}
