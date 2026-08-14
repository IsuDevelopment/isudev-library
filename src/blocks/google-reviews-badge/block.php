<?php
/**
 * Descriptor for the isudev/google-reviews-badge block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'google-reviews-badge',
	'name'       => 'isudev/google-reviews-badge',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => false,
	// No block-own bootstrap: this block reads only the shared
	// IsuDevLibrary\GoogleReviews helpers, which the main plugin file already
	// loads unconditionally — see includes/google-reviews/repository.php.
	'bootstrap'  => array(),
);
