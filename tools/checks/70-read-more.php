<?php
/**
 * Checks for the pure parts of the read-more card block.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/src/blocks/read-more/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\ReadMore\card_classes;
use function IsuDevLibrary\Blocks\ReadMore\heading_tag;
use function IsuDevLibrary\Blocks\ReadMore\pick_image;
use function IsuDevLibrary\Blocks\ReadMore\pick_title;

/*
 * pick_title: four-step precedence.
 */
Checks::is(
	'pick_title: a custom title wins over everything',
	pick_title( true, 'Custom', 'Post', 'Link', 'https://example.com/' ),
	'Custom'
);
Checks::is(
	'pick_title: the linked post title wins over the link label',
	pick_title( false, 'Custom', 'Post', 'Link', 'https://example.com/' ),
	'Post'
);
Checks::is(
	'pick_title: the link label is used when no post resolved',
	pick_title( false, 'Custom', '', 'Link', 'https://example.com/' ),
	'Link'
);
Checks::is(
	'pick_title: the URL is the last resort so a card is never blank',
	pick_title( false, 'Custom', '', '', 'https://example.com/' ),
	'https://example.com/'
);

/*
 * The dangerous edge: hasCustomTitle true but the string is empty. Falling
 * through matters — otherwise switching the toggle on blanks the card until
 * something is typed.
 */
Checks::is(
	'pick_title: an empty custom title falls through instead of blanking the card',
	pick_title( true, '', 'Post', 'Link', 'https://example.com/' ),
	'Post'
);
Checks::is(
	'pick_title: a whitespace-only custom title also falls through',
	pick_title( true, '   ', 'Post', 'Link', 'https://example.com/' ),
	'Post'
);
Checks::is(
	'pick_title: everything empty yields an empty string, not a notice',
	pick_title( false, '', '', '', '' ),
	''
);

/*
 * pick_image: four-step precedence.
 */
Checks::is(
	'pick_image: an explicit attachment wins',
	pick_image( array( 'id' => 7, 'url' => 'https://cdn/x.jpg', 'alt' => 'A' ), 42 ),
	array( 'kind' => 'attachment', 'id' => 7, 'url' => 'https://cdn/x.jpg', 'alt' => 'A' )
);
Checks::is(
	'pick_image: a URL-only selection has no attachment id',
	pick_image( array( 'url' => 'https://cdn/x.jpg', 'source' => 'url' ), 42 ),
	array( 'kind' => 'url', 'id' => 0, 'url' => 'https://cdn/x.jpg', 'alt' => '' )
);
Checks::is(
	'pick_image: falls back to the linked post thumbnail',
	pick_image( array(), 42 ),
	array( 'kind' => 'attachment', 'id' => 42, 'url' => '', 'alt' => '' )
);
Checks::is(
	'pick_image: nothing at all is reported as none',
	pick_image( array(), 0 ),
	array( 'kind' => 'none', 'id' => 0, 'url' => '', 'alt' => '' )
);
Checks::is(
	'pick_image: a zero id is not a selection',
	pick_image( array( 'id' => 0 ), 42 ),
	array( 'kind' => 'attachment', 'id' => 42, 'url' => '', 'alt' => '' )
);
Checks::is(
	'pick_image: an empty url string is not a selection',
	pick_image( array( 'url' => '' ), 0 ),
	array( 'kind' => 'none', 'id' => 0, 'url' => '', 'alt' => '' )
);
/*
 * `true` is the value that actually distinguishes the is_numeric() guard:
 * (int) true is 1, so without the guard a boolean id would resolve to
 * attachment #1 — a real image belonging to someone else. A string like
 * 'seven' casts to 0 either way and would NOT pin the guard.
 */
Checks::is(
	'pick_image: a boolean id is not an attachment id',
	pick_image( array( 'id' => true ), 0 ),
	array( 'kind' => 'none', 'id' => 0, 'url' => '', 'alt' => '' )
);

/*
 * card_classes.
 */
Checks::is(
	'card_classes: all three flags present',
	card_classes( true, true, 'page' ),
	array( 'has-image', 'has-read-more-badge', 'is-link-type-page' )
);
Checks::is(
	'card_classes: nothing set yields an empty list, not a list of empties',
	card_classes( false, false, '' ),
	array()
);
Checks::is(
	'card_classes: the link type alone',
	card_classes( false, false, 'post' ),
	array( 'is-link-type-post' )
);

/*
 * heading_tag.
 */
Checks::is( 'heading_tag: level 2', heading_tag( true, 2 ), 'h2' );
Checks::is( 'heading_tag: level 6', heading_tag( true, 6 ), 'h6' );
Checks::is( 'heading_tag: h1 is out of range and clamps to h3', heading_tag( true, 1 ), 'h3' );
Checks::is( 'heading_tag: level 7 is out of range and clamps to h3', heading_tag( true, 7 ), 'h3' );
Checks::is( 'heading_tag: zero clamps to h3', heading_tag( true, 0 ), 'h3' );
Checks::is( 'heading_tag: not a heading yields a div', heading_tag( false, 2 ), 'div' );
