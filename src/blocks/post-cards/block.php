<?php
/**
 * Descriptor for the isudev/post-cards block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'post-cards',
	'name'       => 'isudev/post-cards',
	'requires'   => array( 'post-card' ),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(),
);
