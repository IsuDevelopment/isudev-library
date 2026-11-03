<?php
/**
 * Descriptor for the isudev/card-slot block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'card-slot',
	'name'       => 'isudev/card-slot',
	// Always on while isudev/post-card is: card templates depend on it.
	'requires'   => array( 'post-card' ),
	'always_on'  => true,
	'variations' => false,
	'bootstrap'  => array(),
);
