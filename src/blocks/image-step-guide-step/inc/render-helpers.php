<?php
/**
 * Render helpers for the image step guide step block.
 *
 * Pure functions only — no get_block_config(), no wp_get_attachment_image().
 * render.php gathers that context and passes it in.
 *
 * @package IsuDevLibrary\Blocks\ImageStepGuideStep
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ImageStepGuideStep;

defined( 'ABSPATH' ) || exit;

/**
 * Decide which image source wins, without rendering anything. Pure.
 *
 * @param array $media The step's `media` attribute.
 * @return array{kind:string,id:int,url:string,alt:string}
 */
function pick_image( array $media ): array {
	$id  = isset( $media['id'] ) && \is_numeric( $media['id'] ) ? (int) $media['id'] : 0;
	$url = isset( $media['url'] ) && \is_string( $media['url'] ) ? $media['url'] : '';
	$alt = isset( $media['alt'] ) && \is_string( $media['alt'] ) ? $media['alt'] : '';

	if ( $id > 0 ) {
		return array(
			'kind' => 'attachment',
			'id'   => $id,
			'url'  => '',
			'alt'  => $alt,
		);
	}

	if ( '' !== $url ) {
		return array(
			'kind' => 'url',
			'id'   => 0,
			'url'  => $url,
			'alt'  => $alt,
		);
	}

	return array(
		'kind' => 'none',
		'id'   => 0,
		'url'  => '',
		'alt'  => '',
	);
}

/**
 * Resolve the label prefix from the step's own override and the guide's
 * configured default, without applying the final translated fallback. Pure.
 *
 * @param string $own_prefix        The step's own `labelPrefix` attribute.
 * @param string $configured_prefix `stepLabel.prefix` from isudev.json.
 * @return string The resolved prefix, or '' when neither is set.
 */
function resolve_label_prefix( string $own_prefix, string $configured_prefix ): string {
	if ( '' !== \trim( $own_prefix ) ) {
		return $own_prefix;
	}

	return $configured_prefix;
}

/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Turn a pick_image() descriptor into markup for the step's media area.
 *
 * Reports whether an image was actually drawn, because an attachment id can
 * point at a deleted attachment and yield nothing.
 *
 * @param array $picked Descriptor from pick_image().
 * @return array{html:string,has_image:bool}
 */
function render_image( array $picked ): array {
	$kind = isset( $picked['kind'] ) ? (string) $picked['kind'] : 'none';
	$html = '';

	if ( 'attachment' === $kind ) {
		$html = (string) \wp_get_attachment_image(
			(int) $picked['id'],
			'large',
			false,
			array( 'class' => 'isudev-image-step-guide-step__image-figure' )
		);
	}

	if ( 'url' === $kind ) {
		$html = \sprintf(
			'<img class="isudev-image-step-guide-step__image-figure" src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
			\esc_url( (string) $picked['url'] ),
			\esc_attr( (string) $picked['alt'] )
		);
	}

	if ( '' === $html ) {
		return array(
			'html'      => '<span class="isudev-image-step-guide-step__media-placeholder" aria-hidden="true"></span>',
			'has_image' => false,
		);
	}

	return array(
		'html'      => $html,
		'has_image' => true,
	);
}
