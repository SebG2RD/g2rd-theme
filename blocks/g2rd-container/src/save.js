import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * save() — uniquement pour sérialiser les InnerBlocks.
 * Le wrapper HTML est généré côté serveur par render.php.
 */
export default function Save() {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
}
