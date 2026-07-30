/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import App from './app';
import './style.scss';

const mount = document.getElementById('isudev-library-admin');

if (mount) {
	createRoot(mount).render(<App />);
}
