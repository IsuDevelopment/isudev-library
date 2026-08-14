<?php
/**
 * Checks for the pure parts of the selling points blocks.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/src/blocks/selling-points/inc/render-helpers.php';
require_once dirname( __DIR__, 2 ) . '/src/blocks/selling-point/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\SellingPoint\item_classes;
use function IsuDevLibrary\Blocks\SellingPoint\link_rel;
use function IsuDevLibrary\Blocks\SellingPoint\sanitize_item_width;
use function IsuDevLibrary\Blocks\SellingPoint\title_tag;
use function IsuDevLibrary\Blocks\SellingPoints\sanitize_default_span;
use function IsuDevLibrary\Blocks\SellingPoints\wrapper_classes;

/*
 * sanitize_default_span(): a numeric value wins; anything else falls back.
 */
Checks::is( 'sanitize_default_span: a numeric value wins', sanitize_default_span( 20, 12 ), 20 );
Checks::is( 'sanitize_default_span: a numeric string wins', sanitize_default_span( '20', 12 ), 20 );
Checks::is( 'sanitize_default_span: a non-numeric value falls back', sanitize_default_span( 'wide', 12 ), 12 );
Checks::is( 'sanitize_default_span: null falls back', sanitize_default_span( null, 12 ), 12 );

/*
 * wrapper_classes(): the base class is always present; has-animate-in
 * appears only when requested.
 */
Checks::is( 'wrapper_classes: no animation', wrapper_classes( false ), array( 'isudev-selling-points' ) );
Checks::is( 'wrapper_classes: with animation', wrapper_classes( true ), array( 'isudev-selling-points', 'has-animate-in' ) );

/*
 * sanitize_item_width(): only a recognized size wins; anything else,
 * including empty, resolves to '' (the grid's own default span applies).
 */
Checks::is( 'sanitize_item_width: a recognized size wins', sanitize_item_width( 'size-33' ), 'size-33' );
Checks::is( 'sanitize_item_width: an unrecognized value resolves to empty', sanitize_item_width( 'size-40' ), '' );
Checks::is( 'sanitize_item_width: empty stays empty', sanitize_item_width( '' ), '' );

/*
 * title_tag(): a non-heading title is always a div, regardless of level;
 * a heading title resolves to h2..h6, falling back to h2 outside that range.
 */
Checks::is( 'title_tag: not a heading is a div even with a valid level', title_tag( false, 3 ), 'div' );
Checks::is( 'title_tag: a heading at level 4', title_tag( true, 4 ), 'h4' );
Checks::is( 'title_tag: a heading below the valid range falls back to h2', title_tag( true, 1 ), 'h2' );
Checks::is( 'title_tag: a heading above the valid range falls back to h2', title_tag( true, 7 ), 'h2' );

/*
 * item_classes(): the base class is always present; every modifier appears
 * only when its own flag is set.
 */
Checks::is(
	'item_classes: nothing extra',
	item_classes( '', false, false, false ),
	array( 'isudev-selling-point' )
);
Checks::is(
	'item_classes: every modifier',
	item_classes( 'size-50', true, true, true ),
	array( 'isudev-selling-point', 'is-size-50', 'has-icon', 'has-image', 'has-link' )
);

/*
 * link_rel(): each flag contributes its own token; neither set yields an
 * empty string, not a stray space.
 */
Checks::is( 'link_rel: new tab only', link_rel( true, false ), 'noopener noreferrer' );
Checks::is( 'link_rel: nofollow only', link_rel( false, true ), 'nofollow' );
Checks::is( 'link_rel: both', link_rel( true, true ), 'noopener noreferrer nofollow' );
Checks::is( 'link_rel: neither', link_rel( false, false ), '' );
