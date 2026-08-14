/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * The block renders in PHP. Only the inner blocks (the step's own content —
 * heading, paragraph, etc.) are saved to post_content; render.php rebuilds
 * the media and label markup around them.
 *
 * @return {Element} Inner blocks placeholder.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
