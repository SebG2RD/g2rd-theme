import { registerBlockType } from '@wordpress/blocks';
import './style.css';
import Edit from './edit';

// Bloc dynamique : save retourne null (rendu via render.php)
registerBlockType( 'g2rd/geo-faq', {
	edit: Edit,
	save: () => null,
} );
