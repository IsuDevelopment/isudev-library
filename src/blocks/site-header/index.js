/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import variations from './variations';
import './style.scss';
import './editor.scss';

registerBlockType(metadata.name, {
	edit: Edit,
	save,
	variations,
});
