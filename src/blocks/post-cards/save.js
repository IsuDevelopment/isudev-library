/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * The block renders in PHP. Only the cards are saved.
 *
 * @return {Element} Inner blocks placeholder.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
