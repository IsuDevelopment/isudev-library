<?php
/**
 * Checks for the pure parts of the content-with-sidebar blocks.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/src/blocks/content-sidebar/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\ContentSidebar\effective_value;
use function IsuDevLibrary\Blocks\ContentSidebar\sanitize_mobile_position;
use function IsuDevLibrary\Blocks\ContentSidebar\sanitize_sidebar_position;
use function IsuDevLibrary\Blocks\ContentSidebar\wrapper_classes;

Checks::is( 'sanitize_sidebar_position: left is kept', sanitize_sidebar_position( 'left' ), 'left' );
Checks::is( 'sanitize_sidebar_position: anything else is right', sanitize_sidebar_position( 'middle' ), 'right' );
Checks::is( 'sanitize_sidebar_position: a non-string is right', sanitize_sidebar_position( array() ), 'right' );
Checks::is( 'sanitize_mobile_position: top is kept', sanitize_mobile_position( 'top' ), 'top' );
Checks::is( 'sanitize_mobile_position: anything else is bottom', sanitize_mobile_position( null ), 'bottom' );

Checks::is( 'effective_value: the attribute wins while the control is enabled', effective_value( false, 'left', 'right' ), 'right' );
Checks::is( 'effective_value: the configured value wins once the control is disabled', effective_value( true, 'left', 'right' ), 'left' );

Checks::is(
	'wrapper_classes: defaults',
	wrapper_classes( 'right', 'bottom', true ),
	array( 'isudev-content-sidebar', 'is-sidebar-right', 'is-mobile-sidebar-bottom', 'has-sticky-sidebar' )
);
Checks::is(
	'wrapper_classes: left, top, not sticky',
	wrapper_classes( 'left', 'top', false ),
	array( 'isudev-content-sidebar', 'is-sidebar-left', 'is-mobile-sidebar-top' )
);
Checks::is(
	'wrapper_classes: unknown positions are normalised',
	wrapper_classes( 'x', 'y', false ),
	array( 'isudev-content-sidebar', 'is-sidebar-right', 'is-mobile-sidebar-bottom' )
);
