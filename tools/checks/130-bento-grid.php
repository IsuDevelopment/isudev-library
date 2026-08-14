<?php
/**
 * Checks for the pure parts of the bento grid blocks.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/src/blocks/bento-grid/inc/render-helpers.php';
require_once dirname( __DIR__, 2 ) . '/src/blocks/bento-card/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\BentoCard\card_style;
use function IsuDevLibrary\Blocks\BentoGrid\device_value;
use function IsuDevLibrary\Blocks\BentoGrid\grid_style;

/*
 * device_value(): a numeric value at the given key wins; a missing or
 * non-numeric one falls back.
 */
Checks::is( 'device_value: reads the device key', device_value( array( 'desktop' => 6 ), 'desktop', 4 ), 6 );
Checks::is( 'device_value: falls back when the key is missing', device_value( array(), 'desktop', 4 ), 4 );
Checks::is( 'device_value: falls back when the value is not numeric', device_value( array( 'desktop' => 'six' ), 'desktop', 4 ), 4 );

/*
 * grid_style(): every declaration is present, with each device's own value
 * substituted in and non-numeric input falling back to the plugin defaults.
 */
$style = grid_style(
	array(
		'desktop' => 6,
		'tablet'  => 4,
		'mobile'  => 2,
	),
	array(
		'desktop' => '16',
		'tablet'  => '12',
		'mobile'  => '8',
	),
	150
);
Checks::true( 'grid_style: contains the desktop column count', false !== strpos( $style, '--bento-cols-desktop: 6;' ) );
Checks::true( 'grid_style: contains the tablet gap', false !== strpos( $style, '--bento-gap-tablet: 12px;' ) );
Checks::true( 'grid_style: contains the min card height', false !== strpos( $style, '--bento-min-card-height: 150px;' ) );

$style_defaults = grid_style( array(), array(), 150 );
Checks::true( 'grid_style: an empty columns array falls back to the default desktop count', false !== strpos( $style_defaults, '--bento-cols-desktop: 6;' ) );

/*
 * card_style(): a declaration appears only for a device with a numeric span;
 * a missing device is silently skipped rather than emitting `span` with no
 * number.
 */
$card_style_full = card_style(
	array(
		'desktop' => array( 'span' => 4 ),
		'mobile'  => array( 'span' => 1 ),
	),
	array(
		'desktop' => array( 'span' => 2 ),
	)
);
Checks::true( 'card_style: contains the desktop column span', false !== strpos( $card_style_full, '--grid-col-desktop: span 4' ) );
Checks::true( 'card_style: contains the mobile column span', false !== strpos( $card_style_full, '--grid-col-mobile: span 1' ) );
Checks::true( 'card_style: contains the desktop row span', false !== strpos( $card_style_full, '--grid-row-desktop: span 2' ) );
Checks::is( 'card_style: omits a device with no row span', false !== strpos( $card_style_full, '--grid-row-mobile' ), false );

Checks::is( 'card_style: no spans at all yields an empty string', card_style( array(), array() ), '' );
