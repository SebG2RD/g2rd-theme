import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

registerBlockType( 'g2rd/pricing-table', {
	edit: Edit,
	save: () => null, // bloc dynamique : rendu côté PHP
} );
