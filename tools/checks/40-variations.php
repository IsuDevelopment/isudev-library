<?php
/**
 * Checks for IsuDevLibrary\Variations\build_variations().
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/variations.php';

use function IsuDevLibrary\Variations\build_variations;

$existing = array(
	array(
		'name'  => 'minimal',
		'title' => 'Minimal header',
	),
);

Checks::is( 'variations: no config leaves existing untouched', build_variations( $existing, array() ), $existing );
Checks::is( 'variations: non-array variations key is ignored', build_variations( $existing, array( 'variations' => 'nope' ) ), $existing );

Checks::is(
	'variations: injects _namespace and default isActive',
	build_variations(
		array(),
		array(
			'variations' => array(
				'compact' => array(
					'name'       => 'compact',
					'title'      => 'Compact header',
					'icon'       => 'menu',
					'attributes' => array( 'sticky' => false ),
				),
			),
		)
	),
	array(
		array(
			'name'       => 'compact',
			'title'      => 'Compact header',
			'icon'       => 'menu',
			'attributes' => array(
				'sticky'     => false,
				'_namespace' => 'compact',
			),
			'isActive'   => array( '_namespace' ),
		),
	)
);

Checks::is(
	'variations: creates attributes when absent',
	build_variations(
		array(),
		array(
			'variations' => array(
				'bare' => array(
					'name'  => 'bare',
					'title' => 'Bare',
				),
			),
		)
	),
	array(
		array(
			'name'       => 'bare',
			'title'      => 'Bare',
			'attributes' => array( '_namespace' => 'bare' ),
			'isActive'   => array( '_namespace' ),
		),
	)
);

Checks::is(
	'variations: respects an explicit isActive',
	build_variations(
		array(),
		array(
			'variations' => array(
				'custom' => array(
					'name'     => 'custom',
					'title'    => 'Custom',
					'isActive' => array( 'logoSource' ),
				),
			),
		)
	),
	array(
		array(
			'name'       => 'custom',
			'title'      => 'Custom',
			'isActive'   => array( 'logoSource' ),
			'attributes' => array( '_namespace' => 'custom' ),
		),
	)
);

Checks::is(
	'variations: strips the internal settings key',
	build_variations(
		array(),
		array(
			'variations' => array(
				'x' => array(
					'name'     => 'x',
					'title'    => 'X',
					'settings' => array( 'internal' => true ),
				),
			),
		)
	),
	array(
		array(
			'name'       => 'x',
			'title'      => 'X',
			'attributes' => array( '_namespace' => 'x' ),
			'isActive'   => array( '_namespace' ),
		),
	)
);

Checks::is(
	'variations: skips entries missing name or title',
	build_variations(
		array(),
		array(
			'variations' => array(
				'no-title' => array( 'name' => 'no-title' ),
				'no-name'  => array( 'title' => 'No name' ),
			),
		)
	),
	array()
);

Checks::is(
	'variations: appends to existing variations',
	\count(
		build_variations(
			$existing,
			array(
				'variations' => array(
					'compact' => array(
						'name'  => 'compact',
						'title' => 'Compact',
					),
				),
			)
		)
	),
	2
);
