/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * The block renders in PHP. Only the inner blocks (the selling points) are
 * saved to post_content; render.php rebuilds the wrapper markup around them.
 *
 * @return {Element} Inner blocks placeholder.
 */
export default function save() {
	const blockProps = useBlockProps.save();

	return (
		<div {...blockProps}>
			<InnerBlocks.Content />
		</div>
	);
}
