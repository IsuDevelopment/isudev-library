<?php
/**
 * Checks for the shared slider shell (includes/utils/slider.php).
 *
 * The class names, roles and data attribute asserted here are the contract
 * src/utils/slider/index.js and consuming themes rely on; see guides/slider.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/utils/icon.php';
require_once dirname( __DIR__, 2 ) . '/includes/utils/slider.php';

use function IsuDevLibrary\Utils\Slider\join_classes;
use function IsuDevLibrary\Utils\Slider\normalize_options;
use function IsuDevLibrary\Utils\Slider\render;

/*
 * normalize_options(): defaults, clamping, and one motion source at a time.
 */
Checks::is(
	'slider options: defaults',
	normalize_options( array() ),
	array(
		'loop'           => false,
		'align'          => 'start',
		'slidesToScroll' => 1,
		'dragFree'       => false,
		'autoplay'       => false,
		'autoScroll'     => false,
	)
);
Checks::is( 'slider options: unknown align falls back to start', normalize_options( array( 'align' => 'middle' ) )['align'], 'start' );
Checks::is( 'slider options: slidesToScroll accepts auto', normalize_options( array( 'slidesToScroll' => 'auto' ) )['slidesToScroll'], 'auto' );
Checks::is( 'slider options: slidesToScroll is at least 1', normalize_options( array( 'slidesToScroll' => -3 ) )['slidesToScroll'], 1 );
Checks::is( 'slider options: autoplay delay is at least 1000ms', normalize_options( array( 'autoplay' => 10 ) )['autoplay'], 1000 );
Checks::is( 'slider options: autoScroll speed is clamped', normalize_options( array( 'autoScroll' => 99 ) )['autoScroll'], 10.0 );
Checks::is(
	'slider options: autoScroll wins over autoplay',
	normalize_options(
		array(
			'autoplay'   => 5000,
			'autoScroll' => 1,
		)
	)['autoplay'],
	false
);
Checks::is( 'slider options: unknown keys are dropped', array_key_exists( 'speed', normalize_options( array( 'speed' => 3 ) ) ), false );

Checks::is( 'join_classes: drops unsafe tokens and duplicates', join_classes( array( 'a b', 'a', '"x', '' ) ), 'a b' );

/*
 * render(): nothing for no slides; region, slides, controls otherwise.
 */
Checks::is( 'slider render: no slides renders nothing', render( array() ), '' );

$isudev_slider = render(
	array( '<p>One</p>', '<p>Two</p>', '<p>Three</p>' ),
	array(
		'label'           => 'Reviews "quoted"',
		'class'           => 'extra is-mode-x',
		'container_class' => 'list',
		'slide_class'     => 'item',
		'attrs'           => array(
			'data-foo' => 'bar',
			'onclick'  => 'x()',
		),
		'options'         => array(
			'loop'     => true,
			'autoplay' => 4000,
		),
	)
);

Checks::true( 'slider render: root classes', false !== strpos( $isudev_slider, '<div class="isudev-slider extra is-mode-x" role="region" aria-roledescription="carousel"' ) );
Checks::true( 'slider render: label is escaped', false !== strpos( $isudev_slider, 'aria-label="Reviews &quot;quoted&quot;"' ) );
Checks::true( 'slider render: data-* attrs pass', false !== strpos( $isudev_slider, ' data-foo="bar"' ) );
Checks::is( 'slider render: event-handler attrs are dropped', strpos( $isudev_slider, 'onclick' ), false );
Checks::true( 'slider render: focusable viewport', false !== strpos( $isudev_slider, '<div class="isudev-slider__viewport" tabindex="0">' ) );
Checks::true( 'slider render: container classes', false !== strpos( $isudev_slider, '<div class="isudev-slider__container list">' ) );
Checks::is( 'slider render: one slide group per slide', substr_count( $isudev_slider, 'role="group" aria-roledescription="slide"' ), 3 );
Checks::true( 'slider render: slide position label', false !== strpos( $isudev_slider, 'class="isudev-slider__slide item" role="group" aria-roledescription="slide" aria-label="2 of 3"><p>Two</p>' ) );
Checks::true( 'slider render: prev button', false !== strpos( $isudev_slider, 'class="isudev-slider__button isudev-slider__prev" type="button" aria-label="Previous slide"' ) );
Checks::true( 'slider render: next button', false !== strpos( $isudev_slider, 'class="isudev-slider__button isudev-slider__next" type="button" aria-label="Next slide"' ) );
Checks::true( 'slider render: dots carry the label template', false !== strpos( $isudev_slider, '<div class="isudev-slider__dots" data-label="Go to slide %d"></div>' ) );
Checks::true( 'slider render: autoplay renders a pause control', false !== strpos( $isudev_slider, 'isudev-slider__pause" type="button" aria-label="Pause carousel" data-label-pause="Pause carousel" data-label-play="Play carousel"' ) );
Checks::true( 'slider render: buttons carry icons', 1 === preg_match( '/isudev-slider__prev[^>]*><svg[^>]*class="isudev-slider__icon"/', $isudev_slider ) );

preg_match( '/data-isudev-slider="([^"]*)"/', $isudev_slider, $isudev_slider_match );
$isudev_slider_options = json_decode( html_entity_decode( $isudev_slider_match[1] ?? '', ENT_QUOTES ), true );
Checks::is( 'slider render: options JSON round-trips', $isudev_slider_options['loop'] ?? null, true );
Checks::is( 'slider render: options JSON carries autoplay', $isudev_slider_options['autoplay'] ?? null, 4000 );

$isudev_slider_bare = render(
	array( 'A' ),
	array(
		'navigation' => false,
		'pagination' => false,
	)
);
Checks::is( 'slider render: no motion, no pause control', strpos( $isudev_slider_bare, 'isudev-slider__pause' ), false );
Checks::is( 'slider render: no controls container without controls', strpos( $isudev_slider_bare, 'isudev-slider__controls' ), false );
Checks::true( 'slider render: default region label', false !== strpos( $isudev_slider_bare, 'aria-label="Carousel"' ) );
