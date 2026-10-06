/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

/**
 * Editor UI for the sidebar of isudev/content-sidebar.
 *
 * @return {Element} Editor markup.
 */
export default function Edit() {
	// templateLock false: the parent's 'all' lock would otherwise be inherited.
	const innerBlocksProps = useInnerBlocksProps(
		useBlockProps({ className: 'isudev-content-sidebar__aside' }),
		{ templateLock: false, template: [['core/paragraph']] }
	);

	return <aside {...innerBlocksProps} />;
}
