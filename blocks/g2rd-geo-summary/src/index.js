import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';

registerBlockType( 'g2rd/geo-summary', {
	edit: Edit,
	save: Save,
} );
