/**
 * Post picker strings in this plugin's text domain: the package's own use the
 * default domain, which ships no Polish for most of them.
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * @param {boolean} single One post rather than a list.
 * @return {Object} Labels for `@isudev/gutenberg` post picker controls.
 */
export default function pickerLabels(single = false) {
	return {
		modalTitle: single
			? __('Select a post', 'isudev-library')
			: __('Select posts', 'isudev-library'),
		search: __('Search posts', 'isudev-library'),
		postType: __('Post type', 'isudev-library'),
		allTypes: __('All types', 'isudev-library'),
		/* translators: %s: taxonomy name. */
		allTerms: __('All %s', 'isudev-library'),
		order: __('Order by', 'isudev-library'),
		newest: __('Newest', 'isudev-library'),
		oldest: __('Oldest', 'isudev-library'),
		titleAsc: __('Title A → Z', 'isudev-library'),
		titleDesc: __('Title Z → A', 'isudev-library'),
		/* translators: %d: number of posts. */
		tabAll: __('All (%d)', 'isudev-library'),
		/* translators: %d: number of selected posts. */
		tabSelected: __('Selected (%d)', 'isudev-library'),
		/* translators: 1: number of selected posts, 2: maximum. */
		selectedOfMax: __('Selected %1$d of %2$d', 'isudev-library'),
		/* translators: %d: number of selected posts. */
		selectedCount: __('Selected %d', 'isudev-library'),
		cancel: __('Cancel', 'isudev-library'),
		save: __('Save selection', 'isudev-library'),
		loadMore: __('Load more', 'isudev-library'),
		noResults: __('No posts found.', 'isudev-library'),
		noSelection: __('Nothing selected yet.', 'isudev-library'),
		choose: __('Choose posts', 'isudev-library'),
		change: __('Change selection', 'isudev-library'),
		/* translators: %s: post title. */
		remove: __('Remove %s', 'isudev-library'),
		/* translators: %s: post title. */
		reorder: __('Reorder %s', 'isudev-library'),
		reorderHelp: __('Drag to reorder.', 'isudev-library'),
		/* translators: 1: new position, 2: number of items. */
		moved: __('Moved to position %1$d of %2$d.', 'isudev-library'),
		/* translators: %d: post ID. */
		unavailable: __('Unavailable post (#%d)', 'isudev-library'),
		error: __('The posts could not be loaded.', 'isudev-library'),
	};
}
