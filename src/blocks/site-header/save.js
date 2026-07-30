/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Dynamic block: server-rendered. Save only the inner blocks (the "end" region).
 *
 * @return {Element} Inner blocks content.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
