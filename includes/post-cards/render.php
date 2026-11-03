<?php
/**
 * Rendering one card: which post, which options, which template.
 *
 * Shared by `isudev/post-card`'s render.php and the editor preview endpoint,
 * so the editor shows exactly what the front end renders.
 *
 * @package IsuDevLibrary\PostCards
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\PostCards;

defined( 'ABSPATH' ) || exit;

const CONTEXT_OPTIONS = 'isudev/cardOptions';
const CONTEXT_IN_LOOP = 'isudev/inQueryLoop';

/**
 * Option definitions after `isudev_library/post_card/register_options`.
 *
 * @return array key => normalised definition.
 */
function option_definitions(): array {
	static $defs = null;

	if ( null === $defs ) {
		$registry = new Option_Registry();

		foreach ( builtin_options() as $key => $def ) {
			$registry->add_option( $key, $def );
		}

		/**
		 * Register card options. See guides/post-cards.md → "Card options".
		 *
		 * @param Option_Registry $registry Call `add_option( $key, $def )` / `remove_option( $key )`.
		 */
		\do_action( 'isudev_library/post_card/register_options', $registry );

		$defs = $registry->all();
	}

	return $defs;
}

/**
 * A post a card may show to anyone.
 *
 * @param int $post_id Post ID.
 * @return \WP_Post|null
 */
function card_post( int $post_id ): ?\WP_Post {
	$post = $post_id > 0 ? \get_post( $post_id ) : null;

	return $post instanceof \WP_Post && \is_post_publicly_viewable( $post ) ? $post : null;
}

/**
 * Work out what a card shows.
 *
 * Post: a query slot takes `isudev/slotPostId` from its wrapper; otherwise the
 * card's own `postId`, then — inside a Query Loop only — the `postId` context.
 *
 * @param array $attributes `isudev/post-card` attributes.
 * @param array $context    Block context.
 * @return array{post:\WP_Post,variant:string,options:array}|null
 */
function resolve_card( array $attributes, array $context ): ?array {
	if ( 'query' === ( $attributes['source'] ?? '' ) ) {
		$post_id = (int) ( $context['isudev/slotPostId'] ?? 0 );
	} else {
		$post_id = (int) ( $attributes['postId'] ?? 0 );
		// Only a Query Loop hands its post down; elsewhere `postId` is the page being viewed.
		// The editor preview sends `queryId`; on the front end core/post-template drops it,
		// so boot.php flags cards rendered inside one.
		if ( $post_id < 1 && ( isset( $context['queryId'] ) || ! empty( $context[ CONTEXT_IN_LOOP ] ) ) ) {
			$post_id = (int) ( $context['postId'] ?? 0 );
		}
	}

	$post = card_post( $post_id );

	if ( null === $post ) {
		return null;
	}

	$variant = isset( $attributes['variant'] ) && \is_string( $attributes['variant'] ) ? $attributes['variant'] : '';
	$variant = '' !== $variant ? $variant : (string) ( $context['isudev/cardsVariant'] ?? '' );

	$wrapper = isset( $context['isudev/cardsOptions'] ) && \is_array( $context['isudev/cardsOptions'] ) ? $context['isudev/cardsOptions'] : array();
	$own     = isset( $attributes['cardOptions'] ) && \is_array( $attributes['cardOptions'] ) ? $attributes['cardOptions'] : array();

	return array(
		'post'    => $post,
		'variant' => is_valid_slug( $variant ) ? $variant : '',
		'options' => resolve_options( option_definitions(), $wrapper, $own, $post->post_type ),
	);
}

/**
 * Card wrapper classes.
 *
 * @param \WP_Post $post    Card post.
 * @param string   $variant Variant name.
 * @param array    $options Resolved options.
 * @return string[]
 */
function card_classes( \WP_Post $post, string $variant, array $options ): array {
	$classes = array( 'isudev-post-card', 'is-post-type-' . \sanitize_html_class( $post->post_type ) );

	if ( '' !== $variant ) {
		$classes[] = 'is-variant-' . \sanitize_html_class( $variant );
	}

	foreach ( \get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy ) {
		if ( ! $taxonomy->public ) {
			continue;
		}

		$terms = \get_the_terms( $post, $taxonomy->name );

		foreach ( \is_array( $terms ) ? $terms : array() as $term ) {
			$classes[] = \sanitize_html_class( $taxonomy->name . '-' . $term->slug, $taxonomy->name . '-' . $term->term_id );
		}
	}

	$classes = \array_merge( $classes, option_classes( option_definitions(), $options ) );

	/**
	 * Filter a card's wrapper classes.
	 *
	 * @param string[] $classes Class names.
	 * @param \WP_Post $post    Card post.
	 * @param array    $options Resolved card options.
	 * @param string   $variant Variant name, '' for none.
	 */
	$classes = (array) \apply_filters( 'isudev_library/post_card/classes', $classes, $post, $options, $variant );

	return \array_values( \array_unique( \array_filter( \array_map( 'sanitize_html_class', $classes ) ) ) );
}

/**
 * The template a card uses.
 *
 * @param \WP_Post $post       Card post.
 * @param string   $variant    Variant name.
 * @param array    $options    Resolved options.
 * @param array    $attributes Card attributes.
 * @return array See resolve_template().
 */
function card_template( \WP_Post $post, string $variant, array $options, array $attributes ): array {
	/**
	 * Choose the template slug for a card. Runs on the front end and in the
	 * editor preview alike. Return a slug such as `person-simple`; if no
	 * template has that slug, the post type's and then the default apply.
	 *
	 * @param string   $slug       Preferred slug: `{post_type}-{variant}` or `{post_type}`.
	 * @param \WP_Post $post       Card post.
	 * @param array    $options    Resolved card options.
	 * @param array    $attributes Card block attributes.
	 */
	$preferred = (string) \apply_filters(
		'isudev_library/post_card/template',
		'' !== $variant ? $post->post_type . '-' . $variant : $post->post_type,
		$post,
		$options,
		$attributes
	);

	return resolve_template( candidate_slugs( $post->post_type, $variant, $preferred ) );
}

/**
 * Render a card's inner markup.
 *
 * @param \WP_Post $post         Card post.
 * @param string   $variant      Variant name.
 * @param array    $options      Resolved options.
 * @param array    $attributes   Card attributes, passed to hooks and PHP templates.
 * @param array    $inner_blocks Parsed inner blocks of a card with its own layout; empty for a template card.
 * @return string
 */
function render_card_inner( \WP_Post $post, string $variant, array $options, array $attributes, array $inner_blocks = array() ): string {
	$template = array() !== $inner_blocks
		? array(
			'type' => 'custom',
			'slug' => '',
		)
		: card_template( $post, $variant, $options, $attributes );

	$args = array(
		'post'       => $post,
		'options'    => $options,
		'variant'    => $variant,
		'attributes' => $attributes,
		'template'   => $template['slug'],
	);

	return with_post(
		$post,
		static function () use ( $template, $args, $inner_blocks ): string {
			\ob_start();

			/**
			 * Fires inside a card, before its template. Echo to output.
			 *
			 * @param \WP_Post $post Card post.
			 * @param array    $args `post`, `options`, `variant`, `attributes`, `template`.
			 */
			\do_action( 'isudev_library/post_card/before', $args['post'], $args );

			if ( 'php' === $template['type'] ) {
				\load_template( $template['path'], false, $args );
			} else {
				$blocks = 'custom' === $template['type'] ? $inner_blocks : \parse_blocks( $template['content'] );
				echo render_blocks_with_card( $blocks, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered blocks.
			}

			/**
			 * Fires inside a card, after its template. Echo to output.
			 *
			 * @param \WP_Post $post Card post.
			 * @param array    $args `post`, `options`, `variant`, `attributes`, `template`.
			 */
			\do_action( 'isudev_library/post_card/after', $args['post'], $args );

			$html = (string) \ob_get_clean();

			// The card is the size container; the inner element is what a container query can lay out.
			return '' === \trim( $html ) ? '' : '<div class="isudev-post-card__inner">' . $html . '</div>';
		}
	);
}

/**
 * Render parsed blocks with the card's post in context, as `core/post-template` does.
 *
 * @param array[] $blocks Parsed blocks.
 * @param array   $args   Card args.
 * @return string
 */
function render_blocks_with_card( array $blocks, array $args ): string {
	$post    = $args['post'];
	$context = array(
		'postId'        => $post->ID,
		'postType'      => $post->post_type,
		CONTEXT_OPTIONS => $args['options'],
	);

	$filter = null;

	if ( \has_filter( 'isudev_library/post_card/render_block' ) ) {
		$filter = static function ( $html, $block ) use ( $args ) {
			/**
			 * Filter the output of any block inside a card.
			 *
			 * @param string   $html    Rendered block.
			 * @param array    $block   Parsed block.
			 * @param array    $options Resolved card options.
			 * @param \WP_Post $post    Card post.
			 */
			return \apply_filters( 'isudev_library/post_card/render_block', $html, $block, $args['options'], $args['post'] );
		};
		\add_filter( 'render_block', $filter, 10, 2 );
	}

	$html = '';

	foreach ( $blocks as $parsed ) {
		if ( empty( $parsed['blockName'] ) ) {
			$html .= (string) ( $parsed['innerHTML'] ?? '' );
			continue;
		}

		$html .= ( new \WP_Block( $parsed, $context ) )->render();
	}

	if ( null !== $filter ) {
		\remove_filter( 'render_block', $filter, 10 );
	}

	return $html;
}

/**
 * Run a callback with a post as the global post, then restore the previous one.
 *
 * @param \WP_Post $card_post Post.
 * @param callable $callback  Returns a string.
 * @return string
 */
function with_post( \WP_Post $card_post, callable $callback ): string {
	$previous = $GLOBALS['post'] ?? null;

	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restored below, as the loop in core/post-template does.
	$GLOBALS['post'] = $card_post;
	\setup_postdata( $card_post );

	try {
		return (string) $callback();
	} finally {
		$GLOBALS['post'] = $previous; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( $previous instanceof \WP_Post ) {
			\setup_postdata( $previous );
		}
	}
}

/**
 * Column spans per breakpoint, cascading desktop → tablet → mobile and clamped
 * to the wrapper's columns. Pure.
 *
 * @param array $attributes Card attributes (`span`, `spanTablet`, `spanMobile`).
 * @param array $context    Wrapper context (`isudev/cardsColumns`, `…Tablet`, `…Mobile`).
 * @return array{desktop:int,tablet:int,mobile:int}
 */
function card_spans( array $attributes, array $context ): array {
	$keys   = array(
		'desktop' => '',
		'tablet'  => 'Tablet',
		'mobile'  => 'Mobile',
	);
	$spans  = array();
	$span   = 1;
	$column = 0;

	foreach ( $keys as $device => $suffix ) {
		$own_span   = (int) ( $attributes[ 'span' . $suffix ] ?? 0 );
		$own_column = (int) ( $context[ 'isudev/cardsColumns' . $suffix ] ?? 0 );
		$span       = $own_span > 0 ? $own_span : $span;
		$column     = $own_column > 0 ? $own_column : $column;

		$spans[ $device ] = $column > 0 ? \min( $span, $column ) : $span;
	}

	return $spans;
}

/**
 * Inline style for a card's spans; '' when the card spans one column everywhere. Pure.
 *
 * @param array $spans Output of card_spans().
 * @return string
 */
function span_style( array $spans ): string {
	if ( \max( $spans ) <= 1 ) {
		return '';
	}

	return \sprintf(
		'--isudev-card-span:%d;--isudev-card-span-tablet:%d;--isudev-card-span-mobile:%d;',
		$spans['desktop'],
		$spans['tablet'],
		$spans['mobile']
	);
}
