/**
 * Edit — Bloc Résumé GEO
 *
 * Affiche dans l'éditeur un widget de saisie du résumé et des points clés.
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';
import { TextControl, Button }     from '@wordpress/components';
import { __ }                      from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const { summary, keyPoints, tagline } = attributes;

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
		<div { ...blockProps }>
			{/* Bandeau titre */}
			<div className="geo-summary__header">
				<span className="geo-summary__icon" aria-hidden="true">📝</span>
				<TextControl
					className="geo-summary__tagline-input"
					value={ tagline }
					onChange={ ( val ) =
					__next40pxDefaultSize
					__nextHasNoMarginBottom
onChange={ ( val ) => setAttributes( { tagline: val } ) }
					placeholder={ __( 'En résumé', 'g2rd' ) }
					hideLabelFromVision
					label={ __( 'Titre du résumé', 'g2rd' ) }
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
							<TextControl
								value={ point }
								onChange={ ( val ) =
								__next40pxDefaultSize
								__nextHasNoMarginBottom
onChange={ ( val ) => updatePoint( i, val ) }
								placeholder={ __( `Point clé ${ i + 1 }…`, 'g2rd' ) }
								hideLabelFromVision
								label={ __( `Point clé ${ i + 1 }`, 'g2rd' ) }
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
	);
}
