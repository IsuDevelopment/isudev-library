/**
 * WordPress dependencies
 */
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';

const ALLOWED_BLOCKS = ['isudev/agenda-accordion-item'];
const TEMPLATE = [['isudev/agenda-accordion-item']];

/**
 * Editor UI for the agenda accordion wrapper.
 *
 * @return {Element} Editor markup.
 */
export default function Edit() {
	const blockProps = useBlockProps({
		className: 'isudev-agenda-accordion',
	});

	const innerBlocksProps = useInnerBlocksProps(blockProps, {
		allowedBlocks: ALLOWED_BLOCKS,
		template: TEMPLATE,
		templateInsertUpdatesSelection: true,
		renderAppender: InnerBlocks.ButtonBlockAppender,
	});

	return <div {...innerBlocksProps} />;
}
