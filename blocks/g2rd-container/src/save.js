import { InnerBlocks } from '@wordpress/block-editor';

/**
 * save() — sérialise UNIQUEMENT les InnerBlocks, sans wrapper.
 *
 * Le wrapper HTML (balise, id, classes, styles, layout) est généré côté serveur
 * par render.php. Ajouter un wrapper ici créerait un double conteneur : la
 * grille/flex serait posée sur le conteneur externe (#block_id) tandis que les
 * blocs enfants resteraient enfermés dans le wrapper interne — ils ne seraient
 * donc plus des items directs de la grille/flex et s'empileraient.
 */
export default function Save() {
	return <InnerBlocks.Content />;
}
