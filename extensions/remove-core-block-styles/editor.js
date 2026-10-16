/**
 * Remove core block styles, editor side (no build step; WordPress globals). Clears the `styles`
 * a core block declares in its block.json at registration; styles a theme or plugin registers
 * with registerBlockStyle() / register_block_style() are separate and stay. Mirrors
 * filter_styles() in hooks.php.
 *
 * @param {Object} wp     WordPress globals (`window.wp`).
 * @param {Object} config `window.isudevRemoveCoreBlockStyles` from hooks.php: keep.
 */
(function (wp, config) {
	if (!wp || !wp.hooks || !config) {
		return;
	}

	const keep = Array.isArray(config.keep) ? config.keep : [];

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'isudev-library/remove-core-block-styles',
		(settings, name) => {
			if (
				!name.startsWith('core/') ||
				keep.includes(name) ||
				!Array.isArray(settings.styles) ||
				!settings.styles.length
			) {
				return settings;
			}

			return {
				...settings,
				styles: settings.styles.filter((style) =>
					keep.includes(`${name}:${style?.name}`)
				),
			};
		}
	);
})(window.wp, window.isudevRemoveCoreBlockStyles);
