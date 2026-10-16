<?php
/**
 * Checks for the pure parts of featured-image-column.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/extensions/featured-image-column/hooks.php';

use IsuDevLibrary\Extensions\FeaturedImageColumn;

$isudev_fic_default = FeaturedImageColumn\defaults();

Checks::is( 'fic box: non-array keeps fallback', FeaturedImageColumn\normalize_box( 'x', $isudev_fic_default ), $isudev_fic_default );
Checks::is(
	'fic box: valid values override',
	FeaturedImageColumn\normalize_box( array( 'aspectRatio' => '1 / 1', 'maxHeight' => '60', 'width' => 80 ), $isudev_fic_default ),
	array( 'aspectRatio' => '1/1', 'maxHeight' => 60, 'width' => 80 )
);
Checks::is( 'fic box: auto ratio accepted', FeaturedImageColumn\normalize_box( array( 'aspectRatio' => 'auto' ), $isudev_fic_default )['aspectRatio'], 'auto' );
Checks::is( 'fic box: decimal ratio accepted', FeaturedImageColumn\normalize_box( array( 'aspectRatio' => '1.5/1' ), $isudev_fic_default )['aspectRatio'], '1.5/1' );
Checks::is( 'fic box: zero ratio rejected', FeaturedImageColumn\normalize_box( array( 'aspectRatio' => '0/1' ), $isudev_fic_default )['aspectRatio'], '4/3' );
Checks::is( 'fic box: css injection rejected', FeaturedImageColumn\normalize_box( array( 'aspectRatio' => '1/1;color:red' ), $isudev_fic_default )['aspectRatio'], '4/3' );
Checks::is( 'fic box: sizes clamped', FeaturedImageColumn\normalize_box( array( 'maxHeight' => 2, 'width' => 9000 ), $isudev_fic_default ), array( 'aspectRatio' => '4/3', 'maxHeight' => 16, 'width' => 400 ) );
Checks::is( 'fic box: non-numeric size ignored', FeaturedImageColumn\normalize_box( array( 'width' => 'wide' ), $isudev_fic_default )['width'], 64 );
Checks::is(
	'fic style: built from box',
	FeaturedImageColumn\image_style( $isudev_fic_default ),
	'width:64px;max-width:100%;height:auto;max-height:48px;aspect-ratio:4/3;object-fit:cover;'
);
