/**
 * View script for the Post Cards carousel layout: the shared slider wrapper
 * (src/utils/slider), bundled here. Markup comes from
 * includes/utils/slider.php; see guides/slider.md.
 */

/**
 * Internal dependencies
 */
import { initSliders } from '../../utils/slider';

const init = () =>
	initSliders(document, {
		selector: '.isudev-post-cards__slider.isudev-slider',
	});

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', init);
} else {
	init();
}
