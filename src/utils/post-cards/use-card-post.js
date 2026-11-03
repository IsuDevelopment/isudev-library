/**
 * The post a card building block shows in the editor, from block context.
 */

/**
 * WordPress dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

/**
 * @param {Object} context Block context.
 * @return {{post: Object|null, options: Object, hasPost: boolean}} Post record and card options.
 */
export default function useCardPost(context) {
	const { postId, postType } = context;
	const post = useSelect(
		(select) =>
			postId && postType
				? select(coreStore).getEntityRecord(
						'postType',
						postType,
						postId
					)
				: null,
		[postId, postType]
	);

	return {
		post: post ?? null,
		options: context['isudev/cardOptions'] ?? {},
		hasPost: Boolean(postId && postType),
	};
}
