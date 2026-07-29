/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

/**
 * External dependencies
 */
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		// Spread the block entry points discovered from src/blocks/**/block.json.
		...(typeof defaultConfig.entry === 'function'
			? defaultConfig.entry()
			: defaultConfig.entry),
		admin: path.resolve(__dirname, 'src/admin/index.js'),
	},
};
