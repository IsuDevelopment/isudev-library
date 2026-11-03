/**
 * What a card inside `isudev/post-cards` needs from its wrapper for the
 * preview: the wrapper's query and its siblings, so a query slot resolves the
 * same post the front end gives it.
 *
 * Carried as editor block context (`isudev/cardsWrapper`, in the card's
 * `usesContext`), not React context: the two blocks are separate bundles, and
 * a `createContext()` in each would never match. The server never provides it.
 */

/**
 * WordPress dependencies
 */
import { BlockContextProvider } from '@wordpress/block-editor';
import { useMemo } from '@wordpress/element';

export const WRAPPER_CONTEXT = 'isudev/cardsWrapper';

/**
 * @param {Object}  props
 * @param {Object}  props.value    `{ attributes, cards, order, currentPostId, also }`.
 * @param {Element} props.children Inner blocks.
 * @return {Element} Provider.
 */
export function WrapperProvider({ value, children }) {
	const context = useMemo(() => ({ [WRAPPER_CONTEXT]: value }), [value]);

	return (
		<BlockContextProvider value={context}>{children}</BlockContextProvider>
	);
}

/**
 * @param {string} clientId Card client ID.
 * @param {Object} context  Card block context.
 * @return {Object|null} `{ attributes, cards, index, currentPostId, also }`, or null outside a wrapper.
 */
export function useWrapper(clientId, context) {
	const wrapper = context?.[WRAPPER_CONTEXT];

	if (!wrapper) {
		return null;
	}

	return {
		attributes: wrapper.attributes,
		cards: wrapper.cards,
		currentPostId: wrapper.currentPostId,
		also: wrapper.also,
		index: wrapper.order.indexOf(clientId),
	};
}
