<?php
/**
 * Checks for the pure parts of IsuDevLibrary\Registry.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/class-registry.php';

use IsuDevLibrary\Registry;

// normalize_descriptor().
Checks::is( 'normalize: missing slug rejected', Registry::normalize_descriptor( array( 'name' => 'isudev/x' ) ), null );
Checks::is( 'normalize: missing name rejected', Registry::normalize_descriptor( array( 'slug' => 'x' ) ), null );
Checks::is( 'normalize: empty slug rejected', Registry::normalize_descriptor( array( 'slug' => '', 'name' => 'isudev/x' ) ), null );
Checks::is( 'normalize: non-string name rejected', Registry::normalize_descriptor( array( 'slug' => 'x', 'name' => 42 ) ), null );

Checks::is(
	'normalize: fills every default',
	Registry::normalize_descriptor( array( 'slug' => 'site-header', 'name' => 'isudev/site-header' ) ),
	array(
		'slug'       => 'site-header',
		'name'       => 'isudev/site-header',
		'requires'   => array(),
		'always_on'  => false,
		'variations' => false,
		'bootstrap'  => array(),
	)
);

Checks::is(
	'normalize: coerces types and drops non-string entries',
	Registry::normalize_descriptor(
		array(
			'slug'       => 'bento-card',
			'name'       => 'isudev/bento-card',
			'requires'   => array( 'bento-grid', 7, '' ),
			'always_on'  => 1,
			'variations' => 'yes',
			'bootstrap'  => array( 'inc/a.php', null ),
		)
	),
	array(
		'slug'       => 'bento-card',
		'name'       => 'isudev/bento-card',
		'requires'   => array( 'bento-grid' ),
		'always_on'  => true,
		'variations' => true,
		'bootstrap'  => array( 'inc/a.php' ),
	)
);

// build_dependents().
$descriptors = array(
	'bento-grid'  => Registry::normalize_descriptor( array( 'slug' => 'bento-grid', 'name' => 'isudev/bento-grid' ) ),
	'bento-card'  => Registry::normalize_descriptor( array( 'slug' => 'bento-card', 'name' => 'isudev/bento-card', 'requires' => array( 'bento-grid' ) ) ),
	'site-header' => Registry::normalize_descriptor( array( 'slug' => 'site-header', 'name' => 'isudev/site-header' ) ),
);

Checks::is(
	'dependents: parent lists its child, every slug has a key',
	Registry::build_dependents( $descriptors ),
	array(
		'bento-grid'  => array( 'bento-card' ),
		'bento-card'  => array(),
		'site-header' => array(),
	)
);

// The map's keys must be exactly the known slugs. Inventing a key for an unknown
// requires target would put a block that does not exist into the map, and
// anything later iterating those keys would read a phantom entry.
Checks::is(
	'dependents: a requires entry naming an unknown slug creates no phantom key',
	Registry::build_dependents(
		array(
			'orphan' => Registry::normalize_descriptor( array( 'slug' => 'orphan', 'name' => 'isudev/orphan', 'requires' => array( 'ghost' ) ) ),
		)
	),
	array( 'orphan' => array() )
);

// resolve_states() — spec §7 precedence table.
$simple = array(
	'site-header' => Registry::normalize_descriptor( array( 'slug' => 'site-header', 'name' => 'isudev/site-header' ) ),
);

Checks::is(
	'resolve: no config, no option -> enabled by default',
	Registry::resolve_states( $simple, array(), array() ),
	array( 'site-header' => array( 'enabled' => true, 'source' => 'default', 'locked' => false ) )
);

Checks::is(
	'resolve: option disables, source is panel',
	Registry::resolve_states( $simple, array(), array( 'site-header' => false ) ),
	array( 'site-header' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ) )
);

Checks::is(
	'resolve: isudev.json beats the option and locks',
	Registry::resolve_states(
		$simple,
		array( 'isudev/site-header' => array( 'enabled' => true ) ),
		array( 'site-header' => false )
	),
	array( 'site-header' => array( 'enabled' => true, 'source' => 'code', 'locked' => true ) )
);

Checks::is(
	'resolve: isudev.json can disable and lock',
	Registry::resolve_states(
		$simple,
		array( 'isudev/site-header' => array( 'enabled' => false ) ),
		array( 'site-header' => true )
	),
	array( 'site-header' => array( 'enabled' => false, 'source' => 'code', 'locked' => true ) )
);

Checks::is(
	'resolve: isudev.json without an enabled key does not lock',
	Registry::resolve_states(
		$simple,
		array( 'isudev/site-header' => array( 'sticky' => false ) ),
		array( 'site-header' => false )
	),
	array( 'site-header' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ) )
);

$always = array(
	'site-header' => Registry::normalize_descriptor( array( 'slug' => 'site-header', 'name' => 'isudev/site-header', 'always_on' => true ) ),
);

Checks::is(
	'resolve: always_on beats both config and option',
	Registry::resolve_states(
		$always,
		array( 'isudev/site-header' => array( 'enabled' => false ) ),
		array( 'site-header' => false )
	),
	array( 'site-header' => array( 'enabled' => true, 'source' => 'always_on', 'locked' => true ) )
);

// Dependencies win over everything.
Checks::is(
	'resolve: child disabled when parent is off, source is dependency',
	Registry::resolve_states( $descriptors, array(), array( 'bento-grid' => false ) ),
	array(
		'bento-grid'  => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
		'bento-card'  => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
		'site-header' => array( 'enabled' => true, 'source' => 'default', 'locked' => false ),
	)
);

Checks::is(
	'resolve: child enabled when parent is on',
	Registry::resolve_states( $descriptors, array(), array() ),
	array(
		'bento-grid'  => array( 'enabled' => true, 'source' => 'default', 'locked' => false ),
		'bento-card'  => array( 'enabled' => true, 'source' => 'default', 'locked' => false ),
		'site-header' => array( 'enabled' => true, 'source' => 'default', 'locked' => false ),
	)
);

Checks::is(
	'resolve: dependency beats always_on on the child',
	Registry::resolve_states(
		array(
			'bento-grid' => Registry::normalize_descriptor( array( 'slug' => 'bento-grid', 'name' => 'isudev/bento-grid' ) ),
			'bento-card' => Registry::normalize_descriptor( array( 'slug' => 'bento-card', 'name' => 'isudev/bento-card', 'requires' => array( 'bento-grid' ), 'always_on' => true ) ),
		),
		array(),
		array( 'bento-grid' => false )
	),
	array(
		'bento-grid' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
		'bento-card' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
	)
);

// Row 1 also beats row 3. The cascade's guard skips only slugs already resolved
// to `dependency`, so a `code`-locked state must still be overwritten. Special
// casing `code` in that guard would ship silently without this check.
Checks::is(
	'resolve: dependency beats an isudev.json-forced enabled child',
	Registry::resolve_states(
		array(
			'bento-grid' => Registry::normalize_descriptor( array( 'slug' => 'bento-grid', 'name' => 'isudev/bento-grid' ) ),
			'bento-card' => Registry::normalize_descriptor( array( 'slug' => 'bento-card', 'name' => 'isudev/bento-card', 'requires' => array( 'bento-grid' ) ) ),
		),
		array( 'isudev/bento-card' => array( 'enabled' => true ) ),
		array( 'bento-grid' => false )
	),
	array(
		'bento-grid' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
		'bento-card' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
	)
);

Checks::is(
	'resolve: requires pointing at an unknown slug disables the block',
	Registry::resolve_states(
		array( 'orphan' => Registry::normalize_descriptor( array( 'slug' => 'orphan', 'name' => 'isudev/orphan', 'requires' => array( 'ghost' ) ) ) ),
		array(),
		array()
	),
	array( 'orphan' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ) )
);

// Transitive: c requires b, b requires a, a off -> both off.
Checks::is(
	'resolve: dependency cascade is transitive',
	Registry::resolve_states(
		array(
			'a' => Registry::normalize_descriptor( array( 'slug' => 'a', 'name' => 'isudev/a' ) ),
			'b' => Registry::normalize_descriptor( array( 'slug' => 'b', 'name' => 'isudev/b', 'requires' => array( 'a' ) ) ),
			'c' => Registry::normalize_descriptor( array( 'slug' => 'c', 'name' => 'isudev/c', 'requires' => array( 'b' ) ) ),
		),
		array(),
		array( 'a' => false )
	),
	array(
		'a' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
		'b' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
		'c' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
	)
);

// Same chain, declared in REVERSE dependency order. This is the check that
// actually pins the stabilizing while loop: with descriptors ordered c, b, a a
// single ordered pass sees c before b is demoted, so c stays enabled and the
// cascade silently stops one level short. The check above passes even without
// the loop, because a, b, c happen to be in dependency order already.
Checks::is(
	'resolve: transitive cascade holds when descriptors are declared in reverse order',
	Registry::resolve_states(
		array(
			'c' => Registry::normalize_descriptor( array( 'slug' => 'c', 'name' => 'isudev/c', 'requires' => array( 'b' ) ) ),
			'b' => Registry::normalize_descriptor( array( 'slug' => 'b', 'name' => 'isudev/b', 'requires' => array( 'a' ) ) ),
			'a' => Registry::normalize_descriptor( array( 'slug' => 'a', 'name' => 'isudev/a' ) ),
		),
		array(),
		array( 'a' => false )
	),
	array(
		'c' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
		'b' => array( 'enabled' => false, 'source' => 'dependency', 'locked' => true ),
		'a' => array( 'enabled' => false, 'source' => 'panel', 'locked' => false ),
	)
);
