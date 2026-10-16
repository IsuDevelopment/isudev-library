<?php
/**
 * Checks for the pure attribute logic of the button-icon extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/extensions/button-icon/inc/attributes.php';

use function IsuDevLibrary\Extensions\ButtonIcon\inserts;
use function IsuDevLibrary\Extensions\ButtonIcon\normalize_settings;
use function IsuDevLibrary\Extensions\ButtonIcon\resolve;
use function IsuDevLibrary\Extensions\ButtonIcon\sanitize_bool;
use function IsuDevLibrary\Extensions\ButtonIcon\sanitize_icon_name;
use function IsuDevLibrary\Extensions\ButtonIcon\sanitize_position;
use function IsuDevLibrary\Extensions\ButtonIcon\sanitize_size;
use function IsuDevLibrary\Extensions\ButtonIcon\size_style;
use function IsuDevLibrary\Extensions\ButtonIcon\wrapper_classes;

// sanitize_icon_name(): registry names, T2 collection names; nothing that could break out of markup.
Checks::is( 'button-icon name: camelCase kept', sanitize_icon_name( 'arrowForward' ), 'arrowForward' );
Checks::is( 'button-icon name: collection name kept', sanitize_icon_name( 'core/arrow-right' ), 'core/arrow-right' );
Checks::is( 'button-icon name: trimmed', sanitize_icon_name( ' play ' ), 'play' );
Checks::is( 'button-icon name: quote rejected', sanitize_icon_name( 'a"onload' ), '' );
Checks::is( 'button-icon name: regex metachar rejected', sanitize_icon_name( 'a$1' ), '' );
Checks::is( 'button-icon name: array rejected', sanitize_icon_name( array( 'play' ) ), '' );
Checks::is( 'button-icon name: int rejected', sanitize_icon_name( 5 ), '' );
Checks::is( 'button-icon name: too long rejected', sanitize_icon_name( str_repeat( 'a', 65 ) ), '' );

// sanitize_position().
Checks::is( 'button-icon position: before', sanitize_position( 'before' ), 'before' );
Checks::is( 'button-icon position: T2 left', sanitize_position( 'left' ), 'before' );
Checks::is( 'button-icon position: T2 right', sanitize_position( 'right' ), 'after' );
Checks::is( 'button-icon position: unknown is null', sanitize_position( 'top' ), null );
Checks::is( 'button-icon position: non-string is null', sanitize_position( 1 ), null );

// sanitize_size(): clamped, numeric strings accepted, wrong types null (no TypeError).
Checks::is( 'button-icon size: int kept', sanitize_size( 24 ), 24 );
Checks::is( 'button-icon size: clamped low', sanitize_size( 1 ), 8 );
Checks::is( 'button-icon size: clamped high', sanitize_size( 9999 ), 128 );
Checks::is( 'button-icon size: negative clamped', sanitize_size( -5 ), 8 );
Checks::is( 'button-icon size: float rounded', sanitize_size( 23.6 ), 24 );
Checks::is( 'button-icon size: numeric string', sanitize_size( ' 32 ' ), 32 );
Checks::is( 'button-icon size: word string null', sanitize_size( 'big' ), null );
Checks::is( 'button-icon size: bool null', sanitize_size( true ), null );
Checks::is( 'button-icon size: array null', sanitize_size( array( 24 ) ), null );
Checks::is( 'button-icon size: NAN null', sanitize_size( NAN ), null );

// sanitize_bool().
Checks::true( 'button-icon bool: true', sanitize_bool( true ) );
Checks::true( 'button-icon bool: "true"', sanitize_bool( 'true' ) );
Checks::is( 'button-icon bool: "false" is false', sanitize_bool( 'false' ), false );
Checks::is( 'button-icon bool: array is false', sanitize_bool( array( true ) ), false );

// normalize_settings().
Checks::is(
	'button-icon settings: defaults',
	normalize_settings( null, null, null, true ),
	array(
		'icons'           => null,
		'defaultPosition' => 'after',
		'defaultSize'     => null,
		'readT2'          => true,
	)
);
Checks::is(
	'button-icon settings: icons filtered, position and size sanitised',
	normalize_settings( array( 'play', 'play', 'bad"', 7, 'close' ), 'left', '300', false ),
	array(
		'icons'           => array( 'play', 'close' ),
		'defaultPosition' => 'before',
		'defaultSize'     => 128,
		'readT2'          => false,
	)
);
Checks::is( 'button-icon settings: empty icon list means all', normalize_settings( array(), 'x', 'x', true )['icons'], null );

$settings = normalize_settings( null, 'after', null, true );

// resolve().
Checks::is(
	'button-icon resolve: nothing set',
	resolve( array(), $settings ),
	array(
		'icon'     => '',
		'position' => 'after',
		'size'     => null,
		'iconOnly' => false,
	)
);
Checks::is(
	'button-icon resolve: own attributes',
	resolve(
		array(
			'isudevIcon'         => 'play',
			'isudevIconPosition' => 'before',
			'isudevIconSize'     => 20,
			'isudevIconOnly'     => true,
		),
		$settings
	),
	array(
		'icon'     => 'play',
		'position' => 'before',
		'size'     => 20,
		'iconOnly' => true,
	)
);
Checks::is(
	'button-icon resolve: T2 fallback',
	resolve(
		array(
			'icon'         => 'play',
			'iconPosition' => 'left',
			'iconSize'     => 300,
			'hideText'     => true,
		),
		$settings
	),
	array(
		'icon'     => 'play',
		'position' => 'before',
		'size'     => 128,
		'iconOnly' => true,
	)
);
Checks::is(
	'button-icon resolve: own wins over T2',
	resolve(
		array(
			'isudevIcon' => 'close',
			'icon'       => 'play',
		),
		$settings
	)['icon'],
	'close'
);
Checks::is(
	'button-icon resolve: cleared own icon is not overridden by T2',
	resolve(
		array(
			'isudevIcon' => '',
			'icon'       => 'play',
		),
		$settings
	)['icon'],
	''
);
Checks::is(
	'button-icon resolve: T2 ignored when disabled',
	resolve( array( 'icon' => 'play' ), normalize_settings( null, null, null, false ) )['icon'],
	''
);
Checks::is(
	'button-icon resolve: settings defaults fill unset values',
	resolve( array( 'isudevIcon' => 'play' ), normalize_settings( null, 'before', 16, true ) ),
	array(
		'icon'     => 'play',
		'position' => 'before',
		'size'     => 16,
		'iconOnly' => false,
	)
);
Checks::is(
	'button-icon resolve: wrong types do not throw',
	resolve(
		array(
			'isudevIcon'         => array( 'x' ),
			'isudevIconPosition' => 3,
			'isudevIconSize'     => 'huge',
			'isudevIconOnly'     => array(),
		),
		$settings
	),
	array(
		'icon'     => '',
		'position' => 'after',
		'size'     => null,
		'iconOnly' => false,
	)
);

// wrapper_classes() / inserts() / size_style().
$after = resolve( array( 'isudevIcon' => 'play' ), $settings );
$only  = resolve(
	array(
		'isudevIcon'     => 'play',
		'isudevIconOnly' => true,
	),
	$settings
);

Checks::is( 'button-icon classes: after', wrapper_classes( $after ), array( 'has-isudev-icon', 'has-isudev-icon-after' ) );
Checks::is( 'button-icon classes: icon only', wrapper_classes( $only ), array( 'has-isudev-icon', 'has-isudev-icon-after', 'is-isudev-icon-only' ) );
Checks::is( 'button-icon inserts: after', inserts( '<svg/>', $after ), array( '', '<svg/>' ) );
Checks::is( 'button-icon inserts: before', inserts( '<svg/>', array_merge( $after, array( 'position' => 'before' ) ) ), array( '<svg/>', '' ) );
Checks::is( 'button-icon inserts: icon only wraps text', inserts( '<svg/>', $only ), array( '<svg/><span class="isudev-visually-hidden">', '</span>' ) );
Checks::is( 'button-icon inserts: $1 in markup stays literal', inserts( '<svg data-x="$1\\1"/>', $after )[1], '<svg data-x="$1\\1"/>' );
Checks::is( 'button-icon style: no size, unchanged', size_style( 'width: 50%', null ), '' );
Checks::is( 'button-icon style: merged', size_style( 'width: 50%', 20 ), 'width: 50%;--isudev-button-icon-size:20px;' );
Checks::is( 'button-icon style: empty', size_style( '', 20 ), '--isudev-button-icon-size:20px;' );
