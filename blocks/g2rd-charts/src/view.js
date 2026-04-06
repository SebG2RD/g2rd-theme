/**
 * G2RD Graphiques — script frontend
 * Lit la configuration sérialisée et instancie Chart.js.
 */
import { Chart, registerables } from "chart.js";

Chart.register( ...registerables );

const PALETTE = [
	"#2F425D", "#4A90D9", "#E74C3C", "#2ECC71",
	"#F39C12", "#9B59B6", "#1ABC9C", "#E67E22",
];

function buildChartConfig( attrs ) {
	const {
		chartType, chartTitle, labels, datasets,
		showLegend, showGrid, stacked, borderRadius,
		tension, specMaxValue, gridColor, labelColor,
	} = attrs;

	const tension01 = tension / 100;

	const typeMap = {
		"bar":                 "bar",
		"bar-horizontal":      "bar",
		"bar-horizontal-spec": "bar",
		"circle":              "doughnut",
		"line":                "line",
		"area":                "line",
		"stacked":             "bar",
	};
	const type         = typeMap[ chartType ] || "bar";
	const isHorizontal = chartType === "bar-horizontal" || chartType === "bar-horizontal-spec";
	const isCircle     = chartType === "circle";
	const isStacked    = chartType === "stacked" || stacked;
	const isSpec       = chartType === "bar-horizontal-spec";

	const chartDatasets = datasets.map( ( ds ) => {
		const base = {
			label:        ds.label,
			data:         ds.data,
			borderColor:  ds.color,
			borderWidth:  2,
		};

		if ( type === "bar" ) {
			base.backgroundColor = ds.color + "CC";
			base.borderRadius    = parseInt( borderRadius, 10 ) || 0;
		} else if ( type === "line" ) {
			base.backgroundColor  = chartType === "area" ? ds.color + "33" : "transparent";
			base.fill             = chartType === "area";
			base.tension          = tension01;
			base.pointRadius      = 4;
			base.pointHoverRadius = 6;
		} else if ( type === "doughnut" ) {
			base.backgroundColor = datasets[ 0 ].data.map(
				( _, i ) => ( ds.colors && ds.colors[ i ] ) || PALETTE[ i % PALETTE.length ]
			);
			base.borderColor     = "#ffffff";
			base.borderWidth     = 2;
		}

		return base;
	} );

	return {
		type,
		data: { labels, datasets: chartDatasets },
		options: {
			indexAxis:           isHorizontal ? "y" : "x",
			responsive:          true,
			maintainAspectRatio: false,
			animation:           { duration: 800, easing: "easeOutQuart" },
			plugins: {
				legend: {
					display:  isCircle ? true : showLegend,
					position: isCircle ? "right" : "bottom",
					labels:   { color: labelColor, font: { size: 13 }, padding: 16 },
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
					stacked:    isStacked,
					grid:       { display: showGrid, color: gridColor },
					ticks:      {
						color:    labelColor,
						callback: isSpec ? ( v ) => v + "%" : undefined,
					},
					...(
						isSpec ? { min: 0, max: parseInt( specMaxValue, 10 ) || 100 } : {}
					),
				},
				y: {
					stacked:     isStacked,
					grid:        { display: showGrid, color: gridColor },
					ticks:       { color: labelColor },
					beginAtZero: true,
				},
			},
		},
	};
}

// ── Initialisation de chaque bloc au chargement
document.addEventListener( "DOMContentLoaded", () => {
	document.querySelectorAll( ".g2rd-charts-wrap[data-g2rd-chart]" ).forEach( ( wrap ) => {
		let attrs;
		try {
			attrs = JSON.parse( wrap.dataset.g2rdChart );
		} catch ( e ) {
			return;
		}

		const canvas = wrap.querySelector( ".g2rd-charts__canvas" );
		if ( ! canvas ) return;

		const config = buildChartConfig( attrs );
		new Chart( canvas, config );
	} );
} );
