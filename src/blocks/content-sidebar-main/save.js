/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * The block renders in PHP. Only the main column's own blocks are saved to
 * post_content; render.php rebuilds the column around them.
 *
 * @return {Element} Inner blocks placeholder.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
