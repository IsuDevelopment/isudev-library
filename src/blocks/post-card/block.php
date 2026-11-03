<?php
/**
 * Descriptor for the isudev/post-card block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'post-card',
	'name'       => 'isudev/post-card',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(),
);
