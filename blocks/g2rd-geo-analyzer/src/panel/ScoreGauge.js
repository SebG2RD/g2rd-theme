/**
 * Composant ScoreGauge
 *
 * Jauge circulaire SVG affichant le score GEO sur 100.
 * Technique : stroke-dasharray / stroke-dashoffset sur un cercle SVG.
 */

import { getGlobalColor } from '../utils/analyzer';

const RADIUS        = 44;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS; // ≈ 276.5

/**
 * @param {{ score: number }} props
 */
export default function ScoreGauge( { score } ) {
	const color  = getGlobalColor( score );
	const offset = CIRCUMFERENCE * ( 1 - score / 100 );

	const label =
		score >= 75 ? 'Excellent' :
		score >= 50 ? 'Correct'   :
		score >= 25 ? 'Faible'    : 'Insuffisant';

	return (
		<div className="g2rd-geo__gauge">
			<svg
				width="110"
				height="110"
				viewBox="0 0 110 110"
				aria-label={ `Score GEO : ${ score } sur 100` }
				role="img"
			>
				{/* Piste de fond */}
				<circle
					cx="55"
					cy="55"
					r={ RADIUS }
					fill="none"
					stroke="#e5e7eb"
					strokeWidth="9"
				/>
				{/* Arc de progression */}
				<circle
					cx="55"
					cy="55"
					r={ RADIUS }
					fill="none"
					stroke={ color }
					strokeWidth="9"
					strokeLinecap="round"
					strokeDasharray={ CIRCUMFERENCE }
					strokeDashoffset={ offset }
					transform="rotate(-90 55 55)"
					style={ { transition: 'stroke-dashoffset 0.7s ease, stroke 0.4s ease' } }
				/>
				{/* Score numérique */}
				<text
					x="55"
					y="50"
					textAnchor="middle"
					dominantBaseline="middle"
					fontSize="22"
					fontWeight="700"
					fill="#1e293b"
				>
					{ score }
				</text>
				{/* Label /100 */}
				<text
					x="55"
					y="68"
					textAnchor="middle"
					fontSize="10"
					fill="#64748b"
				>
					/ 100
				</text>
			</svg>

			{/* Label qualitatif */}
			<span
				className="g2rd-geo__gauge-label"
				style={ { color } }
			>
				{ label }
			</span>
		</div>
	);
}
