/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * The card renders in PHP. Only a custom layout's inner blocks are saved.
 *
 * @return {Element} Inner blocks placeholder.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
