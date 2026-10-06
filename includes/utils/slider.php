<?php
/**
 * Slider shell markup shared by every carousel-style block.
 *
 * Renders the markup `src/utils/slider/` (the Embla wrapper) initializes.
 * Class contract, options and a11y behaviour: guides/slider.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Utils\Slider;

use function IsuDevLibrary\Utils\get_icon;

defined( 'ABSPATH' ) || exit;

const ALIGNS = array( 'start', 'center', 'end' );

/**
 * Normalize slider options to the shape the view script reads. Pure.
 *
 * @param array $options {
 *     Optional. Unknown keys are dropped.
 *
 *     @type bool       $loop           Wrap around at the ends. Default false.
 *     @type string     $align          'start', 'center' or 'end'. Default 'start'.
 *     @type int|string $slidesToScroll Slides per step, or 'auto' (one viewport). Default 1.
 *     @type bool       $dragFree       Momentum scrolling without snapping. Default false.
 *     @type int|false  $autoplay       Delay in ms between steps, or false. Default false.
 *     @type float|false $autoScroll    Continuous speed in px per frame, or false. Default false.
 * }
 * @return array{loop:bool,align:string,slidesToScroll:int|string,dragFree:bool,autoplay:int|false,autoScroll:float|false}
 */
function normalize_options( array $options ): array {
	$align = isset( $options['align'] ) && \in_array( $options['align'], ALIGNS, true ) ? $options['align'] : 'start';

	$slides_to_scroll = $options['slidesToScroll'] ?? 1;
	$slides_to_scroll = 'auto' === $slides_to_scroll ? 'auto' : \max( 1, (int) $slides_to_scroll );

	$autoplay = $options['autoplay'] ?? false;
	$autoplay = false === $autoplay || null === $autoplay ? false : \max( 1000, (int) $autoplay );

	$auto_scroll = $options['autoScroll'] ?? false;
	$auto_scroll = false === $auto_scroll || null === $auto_scroll ? false : \max( 0.1, \min( 10.0, (float) $auto_scroll ) );

	// One motion source at a time; continuous scrolling wins.
	if ( false !== $auto_scroll ) {
		$autoplay = false;
	}

	return array(
		'loop'           => ! empty( $options['loop'] ),
		'align'          => $align,
		'slidesToScroll' => $slides_to_scroll,
		'dragFree'       => ! empty( $options['dragFree'] ),
		'autoplay'       => $autoplay,
		'autoScroll'     => $auto_scroll,
	);
}

/**
 * Default, translated control labels.
 *
 * @return array{label:string,prev:string,next:string,goto:string,slide:string,pause:string,play:string}
 */
function default_labels(): array {
	return array(
		'label' => __( 'Carousel', 'isudev-library' ),
		'prev'  => __( 'Previous slide', 'isudev-library' ),
		'next'  => __( 'Next slide', 'isudev-library' ),
		/* translators: %d: slide number. */
		'goto'  => __( 'Go to slide %d', 'isudev-library' ),
		/* translators: 1: slide number, 2: number of slides. */
		'slide' => __( '%1$d of %2$d', 'isudev-library' ),
		'pause' => __( 'Pause carousel', 'isudev-library' ),
		'play'  => __( 'Play carousel', 'isudev-library' ),
	);
}

/**
 * Join class names, dropping anything that is not a safe class token. Pure.
 *
 * @param array $classes Class names; strings with spaces are split.
 * @return string
 */
function join_classes( array $classes ): string {
	$tokens = array();

	foreach ( $classes as $class_name ) {
		foreach ( \preg_split( '/\s+/', \trim( (string) $class_name ) ) as $token ) {
			if ( '' !== $token && 1 === \preg_match( '/^[A-Za-z_][A-Za-z0-9_-]*$/', $token ) ) {
				$tokens[ $token ] = true;
			}
		}
	}

	return \implode( ' ', \array_keys( $tokens ) );
}

/**
 * Render the slider shell around pre-rendered slides.
 *
 * Slides are trusted HTML the caller already escaped. Everything this
 * function adds is escaped here.
 *
 * @param string[] $slides Slide inner HTML, one entry per slide.
 * @param array    $args {
 *     Optional.
 *
 *     @type string $label           Accessible name of the carousel region.
 *     @type string $class           Extra root classes.
 *     @type array  $attrs           Extra root attributes (data-*, id). Values are escaped.
 *     @type string $container_class Extra classes on `.isudev-slider__container`.
 *     @type string $slide_class     Extra classes on every `.isudev-slider__slide`.
 *     @type array  $options         See normalize_options().
 *     @type bool   $navigation      Render prev/next buttons. Default true.
 *     @type bool   $pagination      Render the dots container. Default true.
 *     @type array  $labels          Overrides for default_labels() keys.
 *     @type array  $icons           Icon names for 'prev', 'next', 'pause', 'play'.
 * }
 * @return string Empty when there are no slides.
 */
function render( array $slides, array $args = array() ): string {
	$slides = \array_values( \array_filter( $slides, 'is_string' ) );
	$total  = \count( $slides );

	if ( 0 === $total ) {
		return '';
	}

	$labels     = \array_merge( default_labels(), isset( $args['labels'] ) && \is_array( $args['labels'] ) ? $args['labels'] : array() );
	$label      = isset( $args['label'] ) && \is_string( $args['label'] ) && '' !== $args['label'] ? $args['label'] : $labels['label'];
	$icons      = \array_merge(
		array(
			'prev'  => 'chevronLeft',
			'next'  => 'chevronRight',
			'pause' => 'pause',
			'play'  => 'play',
		),
		isset( $args['icons'] ) && \is_array( $args['icons'] ) ? $args['icons'] : array()
	);
	$options    = normalize_options( isset( $args['options'] ) && \is_array( $args['options'] ) ? $args['options'] : array() );
	$navigation = ! isset( $args['navigation'] ) || (bool) $args['navigation'];
	$pagination = ! isset( $args['pagination'] ) || (bool) $args['pagination'];
	$has_motion = false !== $options['autoplay'] || false !== $options['autoScroll'];

	$slide_class = join_classes( array( 'isudev-slider__slide', $args['slide_class'] ?? '' ) );
	$items       = '';

	foreach ( $slides as $index => $slide ) {
		$items .= \sprintf(
			'<div class="%1$s" role="group" aria-roledescription="%2$s" aria-label="%3$s">%4$s</div>',
			\esc_attr( $slide_class ),
			\esc_attr__( 'slide', 'isudev-library' ),
			\esc_attr( \sprintf( $labels['slide'], $index + 1, $total ) ),
			$slide
		);
	}

	$controls = '';

	if ( $has_motion ) {
		$controls .= \sprintf(
			'<button class="isudev-slider__button isudev-slider__pause" type="button" aria-label="%1$s" data-label-pause="%1$s" data-label-play="%2$s">%3$s%4$s</button>',
			\esc_attr( $labels['pause'] ),
			\esc_attr( $labels['play'] ),
			get_icon( (string) $icons['pause'], array( 'class' => 'isudev-slider__icon-pause' ) ),
			get_icon( (string) $icons['play'], array( 'class' => 'isudev-slider__icon-play' ) )
		);
	}

	if ( $navigation ) {
		$controls .= \sprintf(
			'<button class="isudev-slider__button isudev-slider__prev" type="button" aria-label="%1$s">%2$s</button>',
			\esc_attr( $labels['prev'] ),
			get_icon( (string) $icons['prev'], array( 'class' => 'isudev-slider__icon' ) )
		);
	}

	if ( $pagination ) {
		$controls .= \sprintf( '<div class="isudev-slider__dots" data-label="%s"></div>', \esc_attr( $labels['goto'] ) );
	}

	if ( $navigation ) {
		$controls .= \sprintf(
			'<button class="isudev-slider__button isudev-slider__next" type="button" aria-label="%1$s">%2$s</button>',
			\esc_attr( $labels['next'] ),
			get_icon( (string) $icons['next'], array( 'class' => 'isudev-slider__icon' ) )
		);
	}

	$root_attrs = isset( $args['attrs'] ) && \is_array( $args['attrs'] ) ? $args['attrs'] : array();
	$extra      = '';

	foreach ( $root_attrs as $name => $value ) {
		if ( ! \is_string( $name ) || 1 !== \preg_match( '/^(?:id|data-[a-z0-9_-]+)$/', $name ) || ! \is_scalar( $value ) ) {
			continue;
		}

		$extra .= \sprintf( ' %s="%s"', $name, \esc_attr( (string) $value ) );
	}

	$json = \function_exists( 'wp_json_encode' ) ? \wp_json_encode( $options ) : \json_encode( $options ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fallback for tools/check.php, which runs without WordPress.

	return \sprintf(
		'<div class="%1$s" role="region" aria-roledescription="%2$s" aria-label="%3$s" data-isudev-slider="%4$s"%5$s><div class="isudev-slider__viewport" tabindex="0"><div class="%6$s">%7$s</div></div>%8$s</div>',
		\esc_attr( join_classes( array( 'isudev-slider', $args['class'] ?? '' ) ) ),
		\esc_attr__( 'carousel', 'isudev-library' ),
		\esc_attr( $label ),
		\esc_attr( (string) $json ),
		$extra,
		\esc_attr( join_classes( array( 'isudev-slider__container', $args['container_class'] ?? '' ) ) ),
		$items,
		'' === $controls ? '' : '<div class="isudev-slider__controls">' . $controls . '</div>'
	);
}
