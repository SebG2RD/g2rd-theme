/**
 * Edit — Bloc FAQ GEO
 *
 * Interface d'édition des questions/réponses optimisées GEO.
 */

import { useBlockProps } from '@wordpress/block-editor';
import { TextControl, TextareaControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const { items } = attributes;

	const blockProps = useBlockProps( { className: 'wp-block-g2rd-geo-faq' } );

	function updateItem( index, field, value ) {
		const updated = items.map( ( item, i ) =>
			i === index ? { ...item, [ field ]: value } : item
		);
		setAttributes( { items: updated } );
	}

	function addItem() {
		setAttributes( {
			items: [
				...items,
				{ question: __( 'Nouvelle question ?', 'g2rd' ), answer: '' },
			],
		} );
	}

	function removeItem( index ) {
		setAttributes( { items: items.filter( ( _, i ) => i !== index ) } );
	}

	function moveItem( index, direction ) {
		const updated = [ ...items ];
		const target  = index + direction;
		if ( target < 0 || target >= updated.length ) return;
		[ updated[ index ], updated[ target ] ] = [ updated[ target ], updated[ index ] ];
		setAttributes( { items: updated } );
	}

	return (
		<div { ...blockProps }>
			{/* En-tête */}
			<div className="geo-faq__header">
				<span className="geo-faq__header-icon" aria-hidden="true">❓</span>
				<span className="geo-faq__header-title">
					{ __( 'FAQ GEO', 'g2rd' ) }
				</span>
				<span className="geo-faq__badge">schema.org</span>
			</div>

			{/* Items FAQ */}
			<div className="geo-faq__items-editor">
				{ items.map( ( item, i ) => (
					<div key={ i } className="geo-faq__item-editor">
						<div className="geo-faq__item-controls">
							<span className="geo-faq__item-num">Q{ i + 1 }</span>
							<div className="geo-faq__item-actions">
								<Button
									icon="arrow-up-alt2"
									label={ __( 'Monter', 'g2rd' ) }
									size="small"
									disabled={ i === 0 }
									onClick={ () => moveItem( i, -1 ) }
								/>
								<Button
									icon="arrow-down-alt2"
									label={ __( 'Descendre', 'g2rd' ) }
									size="small"
									disabled={ i === items.length - 1 }
									onClick={ () => moveItem( i, 1 ) }
								/>
								<Button
									icon="no-alt"
									label={ __( 'Supprimer', 'g2rd' ) }
									size="small"
									onClick={ () => removeItem( i ) }
								/>
							</div>
						</div>

						<TextControl
							label={ __( 'Question', 'g2rd' ) }
							value={ item.question }
							onChange={ ( val ) => updateItem( i, 'question', val ) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							placeholder={ __( 'Posez une question claire…', 'g2rd' ) }
							className="geo-faq__question-input"
						/>

						<TextareaControl
							label={ __( 'Réponse', 'g2rd' ) }
							value={ item.answer }
							onChange={ ( val ) => updateItem( i, 'answer', val ) }
							__nextHasNoMarginBottom
							placeholder={ __( 'Répondez en 1 à 3 phrases courtes, directement actionnables…', 'g2rd' ) }
							rows={ 3 }
							className="geo-faq__answer-input"
						/>
					</div>
				) ) }
			</div>

			{/* Ajouter une question */}
			<Button
				variant="secondary"
				icon="plus"
				onClick={ addItem }
				className="geo-faq__add-btn"
			>
				{ __( 'Ajouter une question', 'g2rd' ) }
			</Button>

			<p className="geo-faq__hint">
				{ __( '💡 Ce bloc génère automatiquement le schema.org FAQPage et améliore votre score GEO.', 'g2rd' ) }
			</p>
		</div>
	);
}
