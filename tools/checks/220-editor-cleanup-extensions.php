<?php
/**
 * Checks for the pure parts of the editor clean-up extensions: disable-block-directory,
 * disable-openverse, disable-fit-text, disable-custom-css, remove-core-block-styles.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

$isudev_ext = dirname( __DIR__, 2 ) . '/extensions/';

require_once $isudev_ext . 'disable-block-directory/hooks.php';
require_once $isudev_ext . 'disable-openverse/hooks.php';
require_once $isudev_ext . 'disable-fit-text/hooks.php';
require_once $isudev_ext . 'disable-custom-css/hooks.php';
require_once $isudev_ext . 'remove-core-block-styles/hooks.php';

use IsuDevLibrary\Extensions\DisableBlockDirectory;
use IsuDevLibrary\Extensions\DisableCustomCss;
use IsuDevLibrary\Extensions\DisableFitText;
use IsuDevLibrary\Extensions\DisableOpenverse;
use IsuDevLibrary\Extensions\RemoveCoreBlockStyles;

// disable-block-directory.
Checks::is(
	'block directory: search route removed, others kept',
	DisableBlockDirectory\remove_route(
		array(
			'/wp/v2/block-directory/search' => array(),
			'/wp/v2/plugins'                => array(),
		)
	),
	array( '/wp/v2/plugins' => array() )
);
Checks::is( 'block directory: non-array endpoints', DisableBlockDirectory\remove_route( null ), array() );

// disable-openverse.
Checks::is(
	'openverse: setting forced off',
	DisableOpenverse\filter_settings( array( 'enableOpenverseMediaCategory' => true, 'x' => 1 ) ),
	array( 'enableOpenverseMediaCategory' => false, 'x' => 1 )
);

// disable-fit-text.
Checks::is(
	'fit text: declared support turned off',
	DisableFitText\disable_support( array( 'supports' => array( 'typography' => array( 'fontSize' => true, 'fitText' => true ) ) ) ),
	array( 'supports' => array( 'typography' => array( 'fontSize' => true, 'fitText' => false ) ) )
);
Checks::is(
	'fit text: block without the support untouched',
	DisableFitText\disable_support( array( 'supports' => array( 'typography' => array( 'fontSize' => true ) ) ) ),
	array( 'supports' => array( 'typography' => array( 'fontSize' => true ) ) )
);
Checks::is( 'fit text: no supports untouched', DisableFitText\disable_support( array( 'title' => 'X' ) ), array( 'title' => 'X' ) );
Checks::is(
	'fit text: attribute dropped before render',
	DisableFitText\drop_attribute( array( 'blockName' => 'core/heading', 'attrs' => array( 'fitText' => true, 'level' => 2 ) ) ),
	array( 'blockName' => 'core/heading', 'attrs' => array( 'level' => 2 ) )
);

// disable-custom-css.
Checks::is( 'custom css: edit_css denied', DisableCustomCss\deny_capability( array( 'unfiltered_html' ), 'edit_css' ), array( 'do_not_allow' ) );
Checks::is( 'custom css: other caps untouched', DisableCustomCss\deny_capability( array( 'edit_posts' ), 'edit_post' ), array( 'edit_posts' ) );
Checks::is(
	'custom css: every css key stripped from a styles tree',
	DisableCustomCss\strip_css_keys(
		array(
			'css'      => 'body{}',
			'color'    => array( 'text' => 'red' ),
			'blocks'   => array(
				'core/button' => array(
					'css'        => 'a{}',
					'variations' => array( 'outline' => array( 'css' => 'b{}', 'border' => array( 'width' => '1px' ) ) ),
				),
			),
			'elements' => array( 'link' => array( 'css' => 'c{}' ) ),
		)
	),
	array(
		'color'    => array( 'text' => 'red' ),
		'blocks'   => array(
			'core/button' => array(
				'variations' => array( 'outline' => array( 'border' => array( 'width' => '1px' ) ) ),
			),
		),
		'elements' => array( 'link' => array() ),
	)
);
Checks::is(
	'custom css: block style.css dropped, other style kept',
	DisableCustomCss\drop_block_css( array( 'attrs' => array( 'style' => array( 'css' => 'color:red', 'spacing' => array() ) ) ) ),
	array( 'attrs' => array( 'style' => array( 'spacing' => array() ) ) )
);

// remove-core-block-styles.
Checks::is(
	'core styles keep: invalid entries dropped, duplicates removed',
	RemoveCoreBlockStyles\normalize_keep( array( 'core/button', 'core/image:rounded', 'core/button', 'acme/x', 'core/Table', 5, 'core/table:' ) ),
	array( 'core/button', 'core/image:rounded' )
);
Checks::is( 'core styles keep: non-array', RemoveCoreBlockStyles\normalize_keep( 'core/button' ), array() );

$isudev_styles = array(
	array( 'name' => 'default', 'isDefault' => true ),
	array( 'name' => 'rounded' ),
);
Checks::is( 'core styles: all removed by default', RemoveCoreBlockStyles\filter_styles( 'core/image', $isudev_styles, array() ), array() );
Checks::is( 'core styles: whole block kept', RemoveCoreBlockStyles\filter_styles( 'core/image', $isudev_styles, array( 'core/image' ) ), $isudev_styles );
Checks::is(
	'core styles: one style kept',
	RemoveCoreBlockStyles\filter_styles( 'core/image', $isudev_styles, array( 'core/image:rounded' ) ),
	array( array( 'name' => 'rounded' ) )
);
Checks::is( 'core styles: non-core block untouched', RemoveCoreBlockStyles\filter_styles( 'acme/card', $isudev_styles, array() ), $isudev_styles );
