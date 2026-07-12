import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import Edit from './edit';
import Save from './save';
import metadata from '../block.json';

/**
 * Déprécation v1 — ancien save() qui enveloppait les InnerBlocks dans un
 * <div> supplémentaire. Ce double wrapper cassait les layouts grille/flex.
 * Conservée pour que les blocs déjà enregistrés restent valides et soient
 * migrés vers le nouveau save() (InnerBlocks.Content nu) sans erreur.
 */
const v1 = {
	attributes: metadata.attributes,
	supports: metadata.supports,
	save() {
		return (
			<div { ...useBlockProps.save() }>
				<InnerBlocks.Content />
			</div>
		);
	},
};

registerBlockType( metadata.name, {
	edit: Edit,
	save: Save,
	deprecated: [ v1 ],
} );
