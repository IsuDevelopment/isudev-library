<?php
/**
 * Checks for the pure parts of IsuDevLibrary\Extensions.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/class-extensions.php';

use IsuDevLibrary\Extensions;

// normalize_descriptor().
Checks::is( 'ext normalize: missing slug rejected', Extensions::normalize_descriptor( array( 'title' => 'X' ) ), null );
Checks::is( 'ext normalize: missing title rejected', Extensions::normalize_descriptor( array( 'slug' => 'x' ) ), null );
Checks::is( 'ext normalize: empty title rejected', Extensions::normalize_descriptor( array( 'slug' => 'x', 'title' => '' ) ), null );

Checks::is(
	'ext normalize: fills every default',
	Extensions::normalize_descriptor( array( 'slug' => 'skip-links', 'title' => 'Skip links' ) ),
	array(
		'slug'        => 'skip-links',
		'title'       => 'Skip links',
		'description' => '',
		'category'    => 'admin',
		'default'     => false,
		'bootstrap'   => array(),
		'boot'        => '',
		'requires'    => null,
	)
);

Checks::is(
	'ext normalize: coerces types and drops non-string bootstrap entries',
	Extensions::normalize_descriptor(
		array(
			'slug'        => 'gf-wrapper',
			'title'       => 'GF wrapper',
			'description' => 'Wraps the form.',
			'category'    => 'plugins',
			'default'     => 1,
			'bootstrap'   => array( 'hooks.php', null, '' ),
			'boot'        => 'Ns\\boot',
			'requires'    => array( 'label' => 'Gravity Forms', 'class' => 'GFForms' ),
		)
	),
	array(
		'slug'        => 'gf-wrapper',
		'title'       => 'GF wrapper',
		'description' => 'Wraps the form.',
		'category'    => 'plugins',
		'default'     => true,
		'bootstrap'   => array( 'hooks.php' ),
		'boot'        => 'Ns\\boot',
		'requires'    => array(
			'label'    => 'Gravity Forms',
			'class'    => 'GFForms',
			'function' => '',
		),
	)
);

// normalize_requirement().
Checks::is( 'ext requirement: non-array is dropped', Extensions::normalize_requirement( 'Gravity Forms' ), null );
Checks::is( 'ext requirement: missing label is dropped', Extensions::normalize_requirement( array( 'class' => 'GFForms' ) ), null );
Checks::is(
	'ext requirement: label alone is kept',
	Extensions::normalize_requirement( array( 'label' => 'Gravity Forms' ) ),
	array(
		'label'    => 'Gravity Forms',
		'class'    => '',
		'function' => '',
	)
);

// requirement_met().
Checks::true( 'ext requirement_met: no requirement', Extensions::requirement_met( null ) );
Checks::true(
	'ext requirement_met: label only is treated as met',
	Extensions::requirement_met( array( 'label' => 'X', 'class' => '', 'function' => '' ) )
);
Checks::true(
	'ext requirement_met: existing class',
	Extensions::requirement_met( array( 'label' => 'X', 'class' => 'stdClass', 'function' => '' ) )
);
Checks::is(
	'ext requirement_met: missing class',
	Extensions::requirement_met( array( 'label' => 'X', 'class' => 'IsuDevNoSuchClass', 'function' => '' ) ),
	false
);
Checks::true(
	'ext requirement_met: existing function',
	Extensions::requirement_met( array( 'label' => 'X', 'class' => '', 'function' => 'strlen' ) )
);
Checks::is(
	'ext requirement_met: missing function',
	Extensions::requirement_met( array( 'label' => 'X', 'class' => '', 'function' => 'isudev_no_such_function' ) ),
	false
);

// resolve_states().
$descriptor = static function ( array $overrides = array() ): array {
	return Extensions::normalize_descriptor(
		array_merge( array( 'slug' => 'ext', 'title' => 'Ext' ), $overrides )
	);
};

Checks::is(
	'ext state: default is off',
	Extensions::resolve_states( array( 'ext' => $descriptor() ), array(), array() ),
	array(
		'ext' => array(
			'enabled' => false,
			'source'  => 'default',
			'locked'  => false,
		),
	)
);

Checks::is(
	'ext state: descriptor default of true is honoured',
	Extensions::resolve_states( array( 'ext' => $descriptor( array( 'default' => true ) ) ), array(), array() ),
	array(
		'ext' => array(
			'enabled' => true,
			'source'  => 'default',
			'locked'  => false,
		),
	)
);

Checks::is(
	'ext state: panel option wins over the default',
	Extensions::resolve_states( array( 'ext' => $descriptor() ), array(), array( 'ext' => true ) ),
	array(
		'ext' => array(
			'enabled' => true,
			'source'  => 'panel',
			'locked'  => false,
		),
	)
);

Checks::is(
	'ext state: isudev.json wins over the panel and locks',
	Extensions::resolve_states(
		array( 'ext' => $descriptor() ),
		array( 'ext' => array( 'enabled' => false ) ),
		array( 'ext' => true )
	),
	array(
		'ext' => array(
			'enabled' => false,
			'source'  => 'code',
			'locked'  => true,
		),
	)
);

Checks::is(
	'ext state: a non-boolean isudev.json value falls through to the panel',
	Extensions::resolve_states(
		array( 'ext' => $descriptor() ),
		array( 'ext' => array( 'enabled' => 'yes' ) ),
		array( 'ext' => true )
	),
	array(
		'ext' => array(
			'enabled' => true,
			'source'  => 'panel',
			'locked'  => false,
		),
	)
);

Checks::is(
	'ext state: an unmet requirement beats every other source',
	Extensions::resolve_states(
		array(
			'ext' => $descriptor(
				array( 'requires' => array( 'label' => 'Gravity Forms', 'class' => 'IsuDevNoSuchClass' ) )
			),
		),
		array( 'ext' => array( 'enabled' => true ) ),
		array( 'ext' => true )
	),
	array(
		'ext' => array(
			'enabled' => false,
			'source'  => 'unavailable',
			'locked'  => true,
		),
	)
);

/*
 * Every shipped descriptor must survive normalization, name a category the
 * panel knows, and point at bootstrap files and a boot callback that really
 * exist. A typo in any of those is silent at runtime — Extensions::load()
 * reports it through _doing_it_wrong(), which nobody reads on a live site.
 */
$isudev_extension_files = \glob( dirname( __DIR__, 2 ) . '/extensions/*/extension.php' );
$isudev_extension_files = \is_array( $isudev_extension_files ) ? $isudev_extension_files : array();

Checks::true( 'ext: at least one extension ships', \count( $isudev_extension_files ) > 0 );

foreach ( $isudev_extension_files as $isudev_extension_file ) {
	$isudev_dir  = \dirname( $isudev_extension_file );
	$isudev_slug = \basename( $isudev_dir );
	$isudev_raw  = require $isudev_extension_file;

	$isudev_descriptor = \is_array( $isudev_raw ) ? Extensions::normalize_descriptor( $isudev_raw ) : null;

	Checks::true( 'ext descriptor valid: ' . $isudev_slug, null !== $isudev_descriptor );

	if ( null === $isudev_descriptor ) {
		continue;
	}

	Checks::is( 'ext slug matches directory: ' . $isudev_slug, $isudev_descriptor['slug'], $isudev_slug );

	Checks::true(
		'ext category is known: ' . $isudev_slug,
		\array_key_exists( $isudev_descriptor['category'], Extensions::categories() )
	);

	Checks::true( 'ext has a README: ' . $isudev_slug, \is_readable( $isudev_dir . '/README.md' ) );

	foreach ( $isudev_descriptor['bootstrap'] as $isudev_bootstrap ) {
		Checks::true(
			'ext bootstrap file exists: ' . $isudev_slug . '/' . $isudev_bootstrap,
			\is_readable( $isudev_dir . '/' . $isudev_bootstrap )
		);

		require_once $isudev_dir . '/' . $isudev_bootstrap;
	}

	Checks::true(
		'ext boot callback is callable once bootstrapped: ' . $isudev_slug,
		'' !== $isudev_descriptor['boot'] && \is_callable( $isudev_descriptor['boot'] )
	);
}
