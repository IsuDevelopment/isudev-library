/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

/**
 * External dependencies
 */
const fs = require('fs');
const path = require('path');

const adminEntry = path.resolve(__dirname, 'src/admin/index.js');

/**
 * Extension scripts that need a build: `extensions/<slug>/src/<entry>.js`
 * becomes `build/extensions/<slug>/<entry>.js`, enqueued by
 * Extensions::enqueue_build(). Static `editor.js` files next to `hooks.php`
 * (button-color) are not entries and ship as written.
 *
 * @return {Object} Entry name => absolute path.
 */
function extensionEntries() {
	const root = path.resolve(__dirname, 'extensions');
	const entries = {};

	for (const slug of fs.readdirSync(root)) {
		const src = path.join(root, slug, 'src');

		if (!fs.existsSync(src)) {
			continue;
		}

		for (const file of fs.readdirSync(src)) {
			if (/^[a-z-]+\.js$/.test(file)) {
				entries[`extensions/${slug}/${file.slice(0, -3)}`] = path.join(
					src,
					file
				);
			}
		}
	}

	return entries;
}

module.exports = {
	...defaultConfig,
	entry: {
		// Spread the block entry points discovered from src/blocks/**/block.json.
		...(typeof defaultConfig.entry === 'function'
			? defaultConfig.entry()
			: defaultConfig.entry),
		// The admin panel lands in a later task than the first build, so this entry
		// is added only once its source exists. Without the guard, webpack fails
		// the whole build on an unresolved entry and emits nothing at all.
		...(fs.existsSync(adminEntry) ? { 'admin/index': adminEntry } : {}),
		...extensionEntries(),
	},
};
