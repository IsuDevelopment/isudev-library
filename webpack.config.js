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
	},
};
