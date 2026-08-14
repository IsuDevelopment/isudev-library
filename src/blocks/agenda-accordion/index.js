/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { listView as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.scss';
import './editor.scss';

registerBlockType(metadata.name, {
	edit: Edit,
	save,
	icon,
});
