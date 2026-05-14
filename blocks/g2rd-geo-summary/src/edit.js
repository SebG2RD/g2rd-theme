/**
 * Edit — Bloc Résumé GEO
 *
 * Affiche dans l'éditeur un widget de saisie du résumé et des points clés.
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';
import { Button }                  from '@wordpress/components';
import { __ }                      from '@wordpress/i18n';
import { TypographySizePanel }     from '../../shared/TypographySizePanel';

export default function Edit( { attributes, setAttributes } ) {
	const { summary, keyPoints, tagline, summaryFontSize, keyPointFontSize } = attributes;

	const blockProps = useBlockProps( { className: 'wp-block-g2rd-geo-summary' } );

	/** Mise à jour d'un point clé par index */
	function updatePoint( index, value ) {
		const updated = [ ...keyPoints ];
		updated[ index ] = value;
		setAttributes( { keyPoints: updated } );
	}

	/** Ajout d'un point clé */
	function addPoint() {
		setAttributes( { keyPoints: [ ...keyPoints, '' ] } );
	}

	/** Suppression d'un point clé */
	function removePoint( index ) {
		setAttributes( { keyPoints: keyPoints.filter( ( _, i ) => i !== index ) } );
	}

	return (
		<>
		<TypographySizePanel
			elements={ [
				{
					label:    __( 'Résumé', 'g2rd' ),
					value:    summaryFontSize,
					onChange: ( v ) => setAttributes( { summaryFontSize: v ?? '' } ),
				},
				{
					label:    __( 'Points clés', 'g2rd' ),
					value:    keyPointFontSize,
					onChange: ( v ) => setAttributes( { keyPointFontSize: v ?? '' } ),
				},
			] }
		/>

		<div { ...blockProps }>
			{/* Bandeau titre */}
			<div className="geo-summary__header">
				<span className="geo-summary__icon" aria-hidden="true">📝</span>
				<RichText
					tagName="span"
					className="geo-summary__tagline-input"
					value={ tagline }
					onChange={ ( val ) => setAttributes( { tagline: val } ) }
					placeholder={ __( 'En résumé', 'g2rd' ) }
					allowedFormats={ [] }
				/>
				<span className="geo-summary__badge">GEO</span>
			</div>

			{/* Résumé principal */}
			<RichText
				tagName="p"
				className="geo-summary__text"
				value={ summary }
				onChange={ ( val ) => setAttributes( { summary: val } ) }
				placeholder={ __( 'Rédigez ici un résumé concis de cette page (2–4 phrases). Les IA utilisent ce passage en priorité pour répondre aux questions des utilisateurs.', 'g2rd' ) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
				style={ summaryFontSize ? { fontSize: summaryFontSize } : undefined }
			/>

			{/* Points clés */}
			{ keyPoints.length > 0 && (
				<div className="geo-summary__points-editor">
					<p className="geo-summary__points-label">
						{ __( 'Points clés :', 'g2rd' ) }
					</p>
					{ keyPoints.map( ( point, i ) => (
						<div key={ i } className="geo-summary__point-row">
							<span className="geo-summary__bullet" aria-hidden="true">✦</span>
							<RichText
								tagName="span"
								className="geo-summary__point-text"
								value={ point }
								onChange={ ( val ) => updatePoint( i, val ) }
								placeholder={ __( `Point clé ${ i + 1 }…`, 'g2rd' ) }
								allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
								style={ keyPointFontSize ? { fontSize: keyPointFontSize } : undefined }
							/>
							<Button
								className="geo-summary__point-remove"
								icon="no-alt"
								label={ __( 'Supprimer ce point', 'g2rd' ) }
								size="small"
								onClick={ () => removePoint( i ) }
							/>
						</div>
					) ) }
					<Button
						variant="tertiary"
						icon="plus"
						onClick={ addPoint }
						size="small"
					>
						{ __( 'Ajouter un point', 'g2rd' ) }
					</Button>
				</div>
			) }

			{ keyPoints.length === 0 && (
				<Button
					variant="tertiary"
					icon="plus"
					onClick={ addPoint }
					size="small"
					className="geo-summary__add-points"
				>
					{ __( 'Ajouter des points clés', 'g2rd' ) }
				</Button>
			) }
		</div>
		</>
	);
}
