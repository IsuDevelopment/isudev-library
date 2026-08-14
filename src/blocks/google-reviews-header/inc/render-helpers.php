<?php
/**
 * Render helpers for the Google Reviews header block.
 *
 * @package IsuDevLibrary\Blocks\GoogleReviewsHeader
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\GoogleReviewsHeader;

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the title element name, defaulting to a non-heading div. Pure.
 *
 * @param bool $render_as_heading Whether the title should be a heading at all.
 * @param int  $level             Requested heading level.
 * @return string
 */
function title_tag( bool $render_as_heading, int $level ): string {
	if ( ! $render_as_heading ) {
		return 'div';
	}

	return $level >= 2 && $level <= 6 ? 'h' . $level : 'h2';
}

/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Allow safe inline formatting without links.
 *
 * @param string $content RichText content.
 * @return string
 */
function sanitize_inline_content( string $content ): string {
	return \wp_kses(
		$content,
		array(
			'b'      => array(),
			'code'   => array(),
			'del'    => array(),
			'em'     => array(),
			'i'      => array(),
			'kbd'    => array(),
			'mark'   => array(
				'class' => true,
				'style' => true,
			),
			's'      => array(),
			'span'   => array(
				'class' => true,
				'style' => true,
			),
			'strong' => array(),
			'sub'    => array(),
			'sup'    => array(),
		)
	);
}
