/**
 * Editor data published by `IsuDevLibrary\PostCards\enqueue_editor_data()`
 * as `window.isudevPostCards`, plus JS mirrors of the PHP option cascade
 * (includes/post-cards/options.php). The mirrors must change together with
 * the PHP; there is no JS test runner to catch drift.
 */

/* global globalThis */

/**
 * @return {{options: Object[], templates: Object[]}} Published data.
 */
export function getCardsData() {
	const data = globalThis.isudevPostCards ?? {};

	return {
		options: Array.isArray(data.options) ? data.options : [],
		templates: Array.isArray(data.templates) ? data.templates : [],
	};
}

/**
 * Option definitions editable at one level.
 *
 * @param {'card'|'cards'} level    Card or wrapper.
 * @param {string|null}    postType Card post type; null when unknown (query slot).
 * @return {Object[]} Definitions.
 */
export function optionsFor(level, postType) {
	return getCardsData().options.filter((def) => {
		if (level === 'card' && def.scope === 'cards') {
			return false;
		}
		if (level === 'cards' && def.scope === 'card') {
			return false;
		}
		return (
			!postType ||
			!def.postTypes?.length ||
			def.postTypes.includes(postType)
		);
	});
}

/**
 * Variants the theme has for a post type (or for any post type when null).
 *
 * @param {string|null} postType Post type.
 * @return {{value: string, label: string}[]} Unique variants.
 */
export function variantsFor(postType) {
	const seen = new Map();

	getCardsData().templates.forEach((template) => {
		if (!template.variant) {
			return;
		}
		if (
			postType &&
			template.postType !== postType &&
			template.postType !== 'default'
		) {
			return;
		}
		if (!seen.has(template.variant)) {
			seen.set(template.variant, template.label || template.variant);
		}
	});

	return [...seen].map(([value, label]) => ({ value, label }));
}

const isSet = (value) =>
	value !== undefined &&
	value !== null &&
	value !== '' &&
	!(Array.isArray(value) && !value.length);

/**
 * Mirror of `resolve_options()`: card value → wrapper value → default.
 *
 * @param {Object} wrapper  Wrapper `cardOptions`.
 * @param {Object} card     Card `cardOptions`.
 * @param {string} postType Card post type.
 * @return {Object} Resolved options.
 */
export function resolveOptions(wrapper = {}, card = {}, postType = '') {
	const resolved = {};

	getCardsData().options.forEach((def) => {
		if (def.postTypes?.length && !def.postTypes.includes(postType)) {
			return;
		}

		let value = null;

		if (def.scope !== 'cards' && isSet(card[def.key])) {
			value = card[def.key];
		}
		if (value === null && def.scope !== 'card' && isSet(wrapper[def.key])) {
			value = wrapper[def.key];
		}

		resolved[def.key] = value === null ? def.default : value;
	});

	return resolved;
}

/**
 * Mirror of `card_spans()`.
 *
 * @param {Object} attributes Card attributes.
 * @param {Object} context    Block context.
 * @return {{desktop: number, tablet: number, mobile: number}} Spans.
 */
export function cardSpans(attributes, context) {
	const spans = {};
	let span = 1;
	let column = 0;

	[
		['desktop', ''],
		['tablet', 'Tablet'],
		['mobile', 'Mobile'],
	].forEach(([device, suffix]) => {
		const ownSpan = Number(attributes[`span${suffix}`]) || 0;
		const ownColumn = Number(context[`isudev/cardsColumns${suffix}`]) || 0;
		span = ownSpan > 0 ? ownSpan : span;
		column = ownColumn > 0 ? ownColumn : column;
		spans[device] = column > 0 ? Math.min(span, column) : span;
	});

	return spans;
}

/**
 * Mirror of `span_style()`, as a React style object.
 *
 * @param {Object} spans Output of cardSpans().
 * @return {Object|undefined} Style.
 */
export function spanStyle(spans) {
	if (Math.max(spans.desktop, spans.tablet, spans.mobile) <= 1) {
		return undefined;
	}

	return {
		'--isudev-card-span': spans.desktop,
		'--isudev-card-span-tablet': spans.tablet,
		'--isudev-card-span-mobile': spans.mobile,
	};
}
