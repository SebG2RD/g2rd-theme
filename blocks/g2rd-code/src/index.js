/**
 * Point d’entrée du script d’édition : enregistre le bloc côté React.
 * Les métadonnées viennent de block.json (source de vérité WordPress).
 */
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from '../block.json';

registerBlockType(metadata.name, {
	...metadata,
	edit: Edit,
	save: () => null,
});
