/**
 * Button colour property, editor side (no build step; WordPress globals). Mirrors hooks.php:
 * the picked colour on the canvas wrapper as a custom property, and core's Outline style removed.
 *
 * @param {Object} wp     WordPress globals (`window.wp`).
 * @param {Object} config `window.isudevButtonColor` from hooks.php: property, removeCoreOutline.
 */
(function (wp, config) {
	if (!wp || !config) {
		return;
	}

	// Mirrors color_value() in hooks.php.
	const colorValue = (attributes = {}) => {
		const slug = `${attributes.backgroundColor || ''}`
			.toLowerCase()
			.replace(/[^a-z0-9-]/g, '');

		if (slug) {
			return `var(--wp--preset--color--${slug})`;
		}

		return attributes.style?.color?.background || '';
	};

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'isudev-library/button-color/property',
		(settings, name) => {
			if ('core/button' !== name) {
				return settings;
			}

			const inherited = settings.getEditWrapperProps;

			return {
				...settings,
				getEditWrapperProps(attributes) {
					const props = inherited ? inherited(attributes) : {};
					const color = colorValue(attributes);

					return color
						? {
								...props,
								style: {
									...(props.style || {}),
									[config.property]: color,
								},
							}
						: props;
				},
			};
		}
	);

	// Core declares Outline in block.json, so PHP's unregister_block_style() cannot reach it.
	if (config.removeCoreOutline) {
		wp.domReady(() =>
			wp.blocks.unregisterBlockStyle('core/button', 'outline')
		);
	}
})(window.wp, window.isudevButtonColor);
