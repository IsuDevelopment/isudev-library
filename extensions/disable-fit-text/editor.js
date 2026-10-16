/**
 * Disable fit text, editor side (no build step; WordPress globals). Core blocks register their
 * supports from client-side block.json, so the server-side filter in hooks.php does not reach
 * the control. Priority 5: before core's `core/fit-text/addAttribute` (10), so the `fitText`
 * attribute is never added either. Saved blocks are not touched (no setAttributes: that would
 * mark every old post as changed).
 *
 * @param {Object} wp WordPress globals (`window.wp`).
 */
(function (wp) {
	if (!wp || !wp.hooks) {
		return;
	}

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'isudev-library/disable-fit-text',
		(settings) => {
			const typography = settings?.supports?.typography;

			if (
				!typography ||
				typeof typography !== 'object' ||
				!('fitText' in typography)
			) {
				return settings;
			}

			return {
				...settings,
				supports: {
					...settings.supports,
					typography: { ...typography, fitText: false },
				},
			};
		},
		5
	);
})(window.wp);
