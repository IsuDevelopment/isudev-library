/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { termDescription as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import save from './save';

registerBlockType(metadata.name, {
	edit: Edit,
	save,
	icon,
});
