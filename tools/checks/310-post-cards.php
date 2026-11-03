<?php
/**
 * Checks for the pure parts of post cards: options, template slugs, query slots, spans.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/includes/post-cards/options.php';
require_once dirname( __DIR__, 2 ) . '/includes/post-cards/templates.php';
require_once dirname( __DIR__, 2 ) . '/includes/post-cards/query.php';
require_once dirname( __DIR__, 2 ) . '/includes/post-cards/render.php';

use function IsuDevLibrary\PostCards\assign_slots;
use function IsuDevLibrary\PostCards\candidate_slugs;
use function IsuDevLibrary\PostCards\card_spans;
use function IsuDevLibrary\PostCards\kebab;
use function IsuDevLibrary\PostCards\normalize_option;
use function IsuDevLibrary\PostCards\normalize_query;
use function IsuDevLibrary\PostCards\option_classes;
use function IsuDevLibrary\PostCards\parse_card_label;
use function IsuDevLibrary\PostCards\picked_ids;
use function IsuDevLibrary\PostCards\query_args;
use function IsuDevLibrary\PostCards\resolve_options;
use function IsuDevLibrary\PostCards\sanitize_option_value;
use function IsuDevLibrary\PostCards\span_style;
use function IsuDevLibrary\PostCards\split_slug;

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Shim: strip tags.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function wp_strip_all_tags( $text ) {
		return trim( strip_tags( (string) $text ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	}
}

/*
 * normalize_option(): invalid type or key is rejected; defaults are filled.
 */
Checks::is( 'normalize_option: unknown type is rejected', normalize_option( 'dark', array( 'type' => 'colour' ) ), null );
Checks::is( 'normalize_option: a key with a dash is rejected', normalize_option( 'is-dark', array( 'type' => 'boolean' ) ), null );
Checks::is( 'normalize_option: select without choices is rejected', normalize_option( 'size', array( 'type' => 'select' ) ), null );

$dark = normalize_option( 'dark', array( 'type' => 'boolean' ) );
Checks::is( 'normalize_option: boolean defaults to false', $dark['default'], false );
Checks::is( 'normalize_option: scope defaults to both', $dark['scope'], 'both' );
Checks::is( 'normalize_option: control defaults by type', $dark['control'], 'toggle' );

$size = normalize_option(
	'size',
	array(
		'type'    => 'select',
		'choices' => array(
			'small' => 'Small',
			'large' => 'Large',
		),
		'default' => 'huge',
	)
);
Checks::is( 'normalize_option: keyed choices become value/label pairs', $size['choices'][1], array( 'value' => 'large', 'label' => 'Large' ) );
Checks::is( 'normalize_option: an invalid default is dropped', $size['default'], null );

/*
 * sanitize_option_value(): coercion per type.
 */
$text = normalize_option( 'customExcerpt', array( 'type' => 'text' ) );
Checks::is( 'sanitize: text strips tags and trims', sanitize_option_value( $text, ' <b>Hi</b> ' ), 'Hi' );
Checks::is( 'sanitize: empty text is unset', sanitize_option_value( $text, '   ' ), null );
Checks::is( 'sanitize: select outside choices is unset', sanitize_option_value( $size, 'huge' ), null );
Checks::is( 'sanitize: boolean from 1', sanitize_option_value( $dark, 1 ), true );

$media = normalize_option( 'customImage', array( 'type' => 'media' ) );
Checks::is( 'sanitize: media without id or url is unset', sanitize_option_value( $media, array( 'alt' => 'x' ) ), null );
Checks::is(
	'sanitize: media focal point is clamped',
	sanitize_option_value(
		$media,
		array(
			'id'         => 5,
			'focalPoint' => array(
				'x' => 2,
				'y' => -1,
			),
		)
	)['focalPoint'],
	array(
		'x' => 1.0,
		'y' => 0.0,
	)
);

/*
 * resolve_options(): card → wrapper → default; post type and scope limits.
 */
$defs = array(
	'dark'          => $dark,
	'size'          => $size,
	'customExcerpt' => normalize_option(
		'customExcerpt',
		array(
			'type'  => 'text',
			'scope' => 'card',
		)
	),
	'onlyPeople'    => normalize_option(
		'onlyPeople',
		array(
			'type'      => 'boolean',
			'postTypes' => array( 'person' ),
		)
	),
	'wrapperOnly'   => normalize_option(
		'wrapperOnly',
		array(
			'type'  => 'boolean',
			'scope' => 'cards',
		)
	),
);

$resolved = resolve_options(
	$defs,
	array(
		'dark'          => true,
		'size'          => 'small',
		'customExcerpt' => 'from wrapper',
		'wrapperOnly'   => true,
	),
	array(
		'size'        => 'large',
		'wrapperOnly' => false,
	),
	'post'
);
Checks::is( 'resolve_options: wrapper value applies when the card has none', $resolved['dark'], true );
Checks::is( 'resolve_options: card value wins', $resolved['size'], 'large' );
Checks::is( 'resolve_options: a card-scope option ignores the wrapper', $resolved['customExcerpt'], null );
Checks::is( 'resolve_options: a cards-scope option ignores the card', $resolved['wrapperOnly'], true );
Checks::true( 'resolve_options: an option for other post types is left out', ! array_key_exists( 'onlyPeople', $resolved ) );
Checks::is( 'resolve_options: falls back to the default', resolve_options( $defs, array(), array(), 'post' )['dark'], false );

/*
 * option_classes() and kebab().
 */
Checks::is( 'kebab: camelCase to kebab-case', kebab( 'hideExcerpt' ), 'hide-excerpt' );
Checks::is(
	'option_classes: true booleans and selects',
	option_classes( $defs, $resolved ),
	array( 'has-card-dark', 'has-card-size-large', 'has-card-wrapper-only' )
);

/*
 * Template slugs.
 */
Checks::is(
	'candidate_slugs: variant first, then type, then default',
	candidate_slugs( 'person', 'simple', 'person-custom' ),
	array( 'person-custom', 'person-simple', 'default-simple', 'person', 'default' )
);
Checks::is( 'candidate_slugs: an invalid preferred slug is skipped', candidate_slugs( 'post', '', '../x' ), array( 'post', 'default' ) );
Checks::is(
	'split_slug: longest post type prefix wins',
	split_slug( 'customer-case-wide', array( 'customer', 'customer-case' ) ),
	array(
		'postType' => 'customer-case',
		'variant'  => 'wide',
	)
);
Checks::is(
	'split_slug: default variant',
	split_slug( 'default-tall', array( 'post' ) ),
	array(
		'postType' => 'default',
		'variant'  => 'tall',
	)
);
Checks::is( 'split_slug: unknown prefix', split_slug( 'product-wide', array( 'post' ) ), null );
Checks::is( 'parse_card_label: HTML comment', parse_card_label( "<!-- Card: Wide image -->\n<!-- wp:group -->" ), 'Wide image' );
Checks::is( 'parse_card_label: PHP docblock', parse_card_label( "<?php\n/**\n * Card: Simplified\n */" ), 'Simplified' );
Checks::is( 'parse_card_label: absent', parse_card_label( '<!-- wp:group /-->' ), '' );

/*
 * Query slots.
 */
$query = normalize_query(
	array(
		'postType' => 'post',
		'order'    => 'asc',
		'orderBy'  => 'nonsense',
		'terms'    => array( '3', 0, 'x' ),
	)
);
Checks::is( 'normalize_query: unknown orderBy falls back to date', $query['orderBy'], 'date' );
Checks::is( 'normalize_query: order is upper-cased', $query['order'], 'ASC' );
Checks::is( 'normalize_query: terms become positive ints', $query['terms'], array( 3 ) );

$args = query_args( $query, array( 4, 4, 9 ), 3 );
Checks::is( 'query_args: one request for every slot', $args['posts_per_page'], 3 );
Checks::is( 'query_args: excludes are unique', $args['post__not_in'], array( 4, 9 ) );
Checks::true( 'query_args: no tax_query without a taxonomy', ! isset( $args['tax_query'] ) );

$cards = array(
	array(
		'source' => 'post',
		'postId' => 7,
	),
	array( 'source' => 'query' ),
	array( 'source' => 'query' ),
	array( 'source' => 'query' ),
);
Checks::is( 'picked_ids: hand-picked only', picked_ids( $cards ), array( 7 ) );
Checks::is(
	'assign_slots: in order, missing posts become 0',
	assign_slots( $cards, array( 11, 12 ) ),
	array(
		1 => 11,
		2 => 12,
		3 => 0,
	)
);

/*
 * Spans.
 */
$spans = card_spans(
	array( 'span' => 4 ),
	array(
		'isudev/cardsColumns'       => 3,
		'isudev/cardsColumnsTablet' => 2,
		'isudev/cardsColumnsMobile' => 1,
	)
);
Checks::is(
	'card_spans: cascades and clamps to the columns',
	$spans,
	array(
		'desktop' => 3,
		'tablet'  => 2,
		'mobile'  => 1,
	)
);
Checks::is( 'span_style: all three when any is wider than one', span_style( $spans ), '--isudev-card-span:3;--isudev-card-span-tablet:2;--isudev-card-span-mobile:1;' );
Checks::is(
	'span_style: empty for single-column cards',
	span_style(
		array(
			'desktop' => 1,
			'tablet'  => 1,
			'mobile'  => 1,
		)
	),
	''
);

/*
 * Query Loop flag: core/post-template drops `queryId`, so cards inside one are
 * flagged while it renders.
 */
require_once dirname( __DIR__, 2 ) . '/includes/post-cards/boot.php';

$card_block = array( 'blockName' => 'isudev/post-card' );
Checks::is( 'flag_query_loop: outside a loop nothing is added', \IsuDevLibrary\PostCards\flag_query_loop( array(), $card_block ), array() );

\IsuDevLibrary\PostCards\enter_query_loop( array( 'blockName' => 'core/post-template' ) );
Checks::is( 'flag_query_loop: a card inside a loop is flagged', \IsuDevLibrary\PostCards\flag_query_loop( array(), $card_block ), array( 'isudev/inQueryLoop' => true ) );
Checks::is( 'flag_query_loop: other blocks are left alone', \IsuDevLibrary\PostCards\flag_query_loop( array(), array( 'blockName' => 'core/paragraph' ) ), array() );

\IsuDevLibrary\PostCards\leave_query_loop( '' );
Checks::is( 'leave_query_loop: depth back to zero', \IsuDevLibrary\PostCards\query_loop_depth(), 0 );
\IsuDevLibrary\PostCards\leave_query_loop( '' );
Checks::is( 'leave_query_loop: never below zero', \IsuDevLibrary\PostCards\query_loop_depth(), 0 );
