<?php
/**
 * Checks for the pure parts of the image step guide blocks.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/src/blocks/image-step-guide/inc/render-helpers.php';
require_once dirname( __DIR__, 2 ) . '/src/blocks/image-step-guide-step/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\ImageStepGuide\sanitize_display_style;
use function IsuDevLibrary\Blocks\ImageStepGuide\wrapper_classes;
use function IsuDevLibrary\Blocks\ImageStepGuideStep\pick_image;
use function IsuDevLibrary\Blocks\ImageStepGuideStep\resolve_label_prefix;

/*
 * sanitize_display_style(): only 'grid' is recognized as grid; everything
 * else, including a mistyped or missing value, falls back to 'list'.
 */
Checks::is( 'sanitize_display_style: grid stays grid', sanitize_display_style( 'grid' ), 'grid' );
Checks::is( 'sanitize_display_style: list stays list', sanitize_display_style( 'list' ), 'list' );
Checks::is( 'sanitize_display_style: an unknown value falls back to list', sanitize_display_style( 'columns' ), 'list' );
Checks::is( 'sanitize_display_style: empty string falls back to list', sanitize_display_style( '' ), 'list' );

/*
 * wrapper_classes(): the root and display-style classes are always present;
 * has-lightbox-images appears only when the lightbox is on.
 */
Checks::is(
	'wrapper_classes: list, no lightbox',
	wrapper_classes( 'list', false ),
	array( 'isudev-image-step-guide', 'is-display-list' )
);
Checks::is(
	'wrapper_classes: grid, with lightbox',
	wrapper_classes( 'grid', true ),
	array( 'isudev-image-step-guide', 'is-display-grid', 'has-lightbox-images' )
);

/*
 * pick_image(): an id wins over a url, a url wins over nothing, and neither
 * present resolves to 'none'.
 */
Checks::is(
	'pick_image: an id resolves to an attachment, dropping any url',
	pick_image(
		array(
			'id'  => 42,
			'url' => 'https://example.com/ignored.jpg',
			'alt' => 'Alt text',
		)
	),
	array(
		'kind' => 'attachment',
		'id'   => 42,
		'url'  => '',
		'alt'  => 'Alt text',
	)
);
Checks::is(
	'pick_image: a url with no id resolves to url',
	pick_image( array( 'url' => 'https://example.com/step.jpg' ) ),
	array(
		'kind' => 'url',
		'id'   => 0,
		'url'  => 'https://example.com/step.jpg',
		'alt'  => '',
	)
);
Checks::is(
	'pick_image: neither id nor url resolves to none',
	pick_image( array() ),
	array(
		'kind' => 'none',
		'id'   => 0,
		'url'  => '',
		'alt'  => '',
	)
);
Checks::is(
	'pick_image: a non-numeric id is ignored',
	pick_image( array( 'id' => 'not-a-number' ) ),
	array(
		'kind' => 'none',
		'id'   => 0,
		'url'  => '',
		'alt'  => '',
	)
);

/*
 * resolve_label_prefix(): the step's own prefix wins when not blank; the
 * configured prefix is the fallback; neither set resolves to '', leaving the
 * translated "Step" fallback to the caller.
 */
Checks::is( 'resolve_label_prefix: own prefix wins', resolve_label_prefix( 'Phase', 'Step' ), 'Phase' );
Checks::is( 'resolve_label_prefix: falls back to the configured prefix', resolve_label_prefix( '', 'Step' ), 'Step' );
Checks::is( 'resolve_label_prefix: a whitespace-only own prefix falls back', resolve_label_prefix( '   ', 'Step' ), 'Step' );
Checks::is( 'resolve_label_prefix: neither set resolves to empty', resolve_label_prefix( '', '' ), '' );
