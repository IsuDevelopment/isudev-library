<?php
/**
 * Checks for the pure helpers of the three core-accordion-* extensions.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

$isudev_extensions_dir = dirname( __DIR__, 2 ) . '/extensions';

require_once $isudev_extensions_dir . '/core-accordion-custom-icons/hooks.php';
require_once $isudev_extensions_dir . '/core-accordion-faq-schema/hooks.php';
require_once $isudev_extensions_dir . '/core-accordion-hash-navigation/hooks.php';

use IsuDevLibrary\Extensions\CoreAccordionCustomIcons as Icons;
use IsuDevLibrary\Extensions\CoreAccordionFaqSchema as Faq;
use IsuDevLibrary\Extensions\CoreAccordionHashNavigation as Hash;

// Custom icons: size clamping and name validation.
Checks::is( 'acc icons: int size kept', Icons\clamp_size( 20, 24 ), 20 );
Checks::is( 'acc icons: numeric string size', Icons\clamp_size( '18', 24 ), 18 );
Checks::is( 'acc icons: float size rounded', Icons\clamp_size( 17.6, 24 ), 18 );
Checks::is( 'acc icons: size clamped up', Icons\clamp_size( 2, 24 ), 8 );
Checks::is( 'acc icons: size clamped down', Icons\clamp_size( 9000, 24 ), 128 );
Checks::is( 'acc icons: zero size falls back', Icons\clamp_size( 0, 24 ), 24 );
Checks::is( 'acc icons: garbage size falls back', Icons\clamp_size( '1e', 24 ), 24 );
Checks::is( 'acc icons: array size falls back', Icons\clamp_size( array( 20 ), 24 ), 24 );
Checks::is( 'acc icons: valid name', Icons\icon_name( 'chevronDown' ), 'chevronDown' );
Checks::is( 'acc icons: markup is not a name', Icons\icon_name( '<svg>' ), '' );
Checks::is( 'acc icons: non-string name', Icons\icon_name( 5 ), '' );

$isudev_icon_defaults = array(
	'closed' => 'chevronDown',
	'open'   => 'chevronUp',
	'size'   => 24,
);

Checks::is( 'acc icons: no context uses defaults', Icons\resolve( array(), $isudev_icon_defaults ), $isudev_icon_defaults );
Checks::is(
	'acc icons: context wins, bad values fall back',
	Icons\resolve(
		array(
			'isudev/accordion-closed-icon' => 'plus',
			'isudev/accordion-open-icon'   => 'bad name',
			'isudev/accordion-icon-size'   => 300,
		),
		$isudev_icon_defaults
	),
	array(
		'closed' => 'plus',
		'open'   => 'chevronUp',
		'size'   => 128,
	)
);

// FAQ schema: question building and validation.
Checks::is( 'acc faq: empty question skipped', Faq\build_question( '  ', '<p>A</p>' ), null );
Checks::is( 'acc faq: tag-only answer skipped', Faq\build_question( 'Q?', '<p> </p><br>' ), null );
Checks::is(
	'acc faq: question node',
	Faq\build_question( ' Q? ', '<p>A</p>' ),
	array(
		'@type'          => 'Question',
		'name'           => 'Q?',
		'acceptedAnswer' => array(
			'@type' => 'Answer',
			'text'  => '<p>A</p>',
		),
	)
);
Checks::is( 'acc faq: whitespace collapsed', Faq\collapse_whitespace( " a \n\t b " ), 'a b' );

$isudev_question = Faq\build_question( 'Q?', '<p>A</p>' );

Checks::is( 'acc faq: non-array questions', Faq\valid_questions( 'x' ), array() );
Checks::is(
	'acc faq: malformed questions dropped',
	Faq\valid_questions( array( $isudev_question, array( '@type' => 'Question' ), 'x', array( 'name' => 'n' ) ) ),
	array( $isudev_question )
);

Checks::is(
	'acc faq: FAQPage added to a string @type',
	Faq\add_faq( array( '@type' => 'WebPage' ), array( $isudev_question ) ),
	array(
		'@type'      => array( 'WebPage', 'FAQPage' ),
		'mainEntity' => array( $isudev_question ),
	)
);
Checks::is(
	'acc faq: existing single mainEntity kept as a list',
	Faq\add_faq(
		array(
			'@type'      => array( 'WebPage', 'FAQPage' ),
			'mainEntity' => array( '@id' => '#q1' ),
		),
		array( $isudev_question )
	),
	array(
		'@type'      => array( 'WebPage', 'FAQPage' ),
		'mainEntity' => array( array( '@id' => '#q1' ), $isudev_question ),
	)
);

$isudev_graph = array(
	array(
		'@type' => 'Organization',
		'@id'   => '#org',
	),
	array(
		'@type' => 'WebPage',
		'@id'   => '#other',
	),
	array(
		'@type' => 'CollectionPage',
		'@id'   => '#main',
	),
);

$isudev_merged = Faq\merge_yoast_graph( $isudev_graph, array( $isudev_question ), '#main' );
Checks::is( 'acc faq yoast: main id wins', $isudev_merged[2]['@type'] ?? null, array( 'CollectionPage', 'FAQPage' ) );
Checks::is( 'acc faq yoast: other WebPage untouched', $isudev_merged[1], $isudev_graph[1] );

$isudev_merged = Faq\merge_yoast_graph( $isudev_graph, array( $isudev_question ), '' );
Checks::is( 'acc faq yoast: first WebPage without main id', $isudev_merged[1]['@type'] ?? null, array( 'WebPage', 'FAQPage' ) );
Checks::is( 'acc faq yoast: no WebPage node', Faq\merge_yoast_graph( array( $isudev_graph[0] ), array( $isudev_question ), '' ), null );

Checks::is(
	'acc faq rank math: joins its FAQ block entity',
	Faq\merge_rank_math(
		array(
			'faqs' => array(
				'@type'      => 'FAQPage',
				'mainEntity' => array( array( 'name' => 'theirs' ) ),
			),
		),
		array( $isudev_question )
	),
	array(
		'faqs' => array(
			'@type'      => 'FAQPage',
			'mainEntity' => array( array( 'name' => 'theirs' ), $isudev_question ),
		),
	)
);
Checks::is(
	'acc faq rank math: adds its own entity',
	Faq\merge_rank_math( array( 'WebPage' => array( '@type' => 'WebPage' ) ), array( $isudev_question ) ),
	array(
		'WebPage'       => array( '@type' => 'WebPage' ),
		'isudevFaqPage' => array(
			'@type'      => 'FAQPage',
			'mainEntity' => array( $isudev_question ),
		),
	)
);

$isudev_on  = array(
	'blockName'   => 'core/accordion',
	'attrs'       => array( 'isudevFaqSchema' => true ),
	'innerBlocks' => array(),
);
$isudev_off = array(
	'blockName'   => 'core/accordion',
	'attrs'       => array(),
	'innerBlocks' => array( $isudev_on ),
);

Checks::is(
	'acc faq: flagged accordions found at any depth, unflagged skipped',
	Faq\flagged_accordions(
		array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array( $isudev_off ),
			),
		)
	),
	array( $isudev_on )
);

// Hash navigation: ids and the registry.
Checks::is( 'acc hash: id trimmed', Hash\valid_id( ' faq ' ), 'faq' );
Checks::is( 'acc hash: whitespace refused', Hash\valid_id( 'a b' ), '' );
Checks::is( 'acc hash: hash sign refused', Hash\valid_id( 'a#b' ), '' );
Checks::is( 'acc hash: non-string refused', Hash\valid_id( null ), '' );
Checks::is( 'acc hash: unicode kept', Hash\valid_id( 'zażółć' ), 'zażółć' );
Checks::is( 'acc hash: length capped', strlen( Hash\valid_id( str_repeat( 'a', 200 ) ) ), 80 );

$isudev_registry = array();

Checks::is( 'acc hash: first claim', Hash\claim( $isudev_registry, 'faq', 'fp1' ), 'faq' );
Checks::is( 'acc hash: duplicate gets -2', Hash\claim( $isudev_registry, 'faq', 'fp2' ), 'faq-2' );
Checks::is( 'acc hash: and -3', Hash\claim( $isudev_registry, 'faq', 'fp3' ), 'faq-3' );
Checks::is( 'acc hash: same item rendered again keeps its id', Hash\claim( $isudev_registry, 'faq', 'fp2' ), 'faq-2' );
Checks::is( 'acc hash: suffix collision skipped', Hash\claim( $isudev_registry, 'faq-2', 'fp4' ), 'faq-2-2' );
