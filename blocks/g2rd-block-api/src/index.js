import { registerBlockType } from '@wordpress/blocks';
import './editor.css';
import './style.css';
import Edit from './edit';
import Save from './save';

registerBlockType( 'g2rd/block-api', {
	edit: Edit,
	save: Save,
} );
