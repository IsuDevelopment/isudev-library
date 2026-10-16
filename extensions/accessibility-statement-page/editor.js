/**
 * Accessibility statement binding source, editor side (no build step; WordPress globals).
 * Values come from hooks.php; the server source does the real work on render.
 *
 * @param {Object} wp     WordPress globals (`window.wp`).
 * @param {Object} config `window.isudevAccessibilityStatement`: source, values, fields.
 */
(function (wp, config) {
	if (!wp?.blocks?.registerBlockBindingsSource || !config?.source) {
		return;
	}

	const fields = Array.isArray(config.fields) ? config.fields : [];
	const values = config.values || {};

	wp.blocks.registerBlockBindingsSource({
		name: config.source,
		getValues({ bindings }) {
			const result = {};

			for (const [attribute, binding] of Object.entries(bindings || {})) {
				const key = binding?.args?.key || 'url';
				const field = fields.find((item) => item.args?.key === key);

				// Unpublished statement: show the field name instead of an empty block.
				result[attribute] =
					values[key] || ('url' === key ? '' : field?.label || key);
			}

			return result;
		},
		canUserEditValue: () => false,
		getFieldsList: () => fields,
	});
})(window.wp, window.isudevAccessibilityStatement);
