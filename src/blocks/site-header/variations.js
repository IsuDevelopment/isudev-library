/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const variations = [
	{
		name: 'minimal',
		title: __('Minimal header (no actions)', 'isudev-library'),
		description: __('Logo and navigation only.', 'isudev-library'),
		attributes: { logoSource: 'site' },
		scope: ['inserter'],
	},
];

export default variations;
