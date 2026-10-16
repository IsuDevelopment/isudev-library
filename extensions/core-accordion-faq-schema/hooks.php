<?php
/**
 * Accordion FAQ structured data: one FAQPage per page from every flagged core/accordion.
 *
 * Questions and answers are captured while the blocks render (render_block_* filters,
 * so an answer is the panel's full rendered HTML, nested blocks included), then handed
 * to Yoast SEO, Rank Math, or printed as standalone JSON-LD in the footer. See README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\CoreAccordionFaqSchema;

use IsuDevLibrary\Extensions;

defined( 'ABSPATH' ) || exit;

const SLUG      = 'core-accordion-faq-schema';
const ATTRIBUTE = 'isudevFaqSchema';

// Google's FAQ answer allowlist (developers.google.com/search/docs/appearance/structured-data/faqpage).
const ANSWER_TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br', 'ol', 'ul', 'li', 'a', 'p', 'div', 'b', 'strong', 'i', 'em' );

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'register_block_type_args', __NAMESPACE__ . '\\register_args', 10, 2 );

	// Last, so a short-circuiting pre_render_block (block skipped) never pushes a frame.
	\add_filter( 'pre_render_block', __NAMESPACE__ . '\\open_frame', PHP_INT_MAX, 2 );
	\add_filter( 'render_block_core/accordion-heading', __NAMESPACE__ . '\\capture_question', 10, 1 );
	\add_filter( 'render_block_core/accordion-panel', __NAMESPACE__ . '\\capture_answer', 10, 1 );
	\add_filter( 'render_block_core/accordion-item', __NAMESPACE__ . '\\close_item', 10, 1 );
	\add_filter( 'render_block_core/accordion', __NAMESPACE__ . '\\close_accordion', 10, 1 );

	// Rank Math links its own entities at 99; join after that.
	\add_filter( 'wpseo_schema_graph', __NAMESPACE__ . '\\inject_yoast', 99, 2 );
	\add_filter( 'rank_math/json_ld', __NAMESPACE__ . '\\inject_rank_math', 100, 1 );
	\add_action( 'wp_footer', __NAMESPACE__ . '\\print_standalone', 20 );

	\add_action(
		'enqueue_block_editor_assets',
		static function (): void {
			Extensions::enqueue_build( SLUG, 'editor' );
		}
	);
}

/**
 * Request-wide state: render frames, collected questions, whether an SEO plugin took them.
 *
 * @return array
 */
function &state(): array {
	static $state = array(
		'accordions'   => array(),
		'items'        => array(),
		'questions'    => array(),
		'injected'     => false,
		'precollected' => false,
	);

	return $state;
}

/**
 * The per-accordion switch, registered server-side so it validates like a core attribute.
 *
 * @param array  $args       Block type args.
 * @param string $block_type Block name.
 * @return array
 */
function register_args( $args, $block_type ): array {
	$args = \is_array( $args ) ? $args : array();

	if ( 'core/accordion' === $block_type ) {
		$args['attributes'] = \array_merge(
			(array) ( $args['attributes'] ?? array() ),
			array(
				ATTRIBUTE => array(
					'type'    => 'boolean',
					'default' => false,
				),
			)
		);
	}

	return $args;
}

/**
 * Push a frame before an accordion or an item renders; its inner blocks fill it.
 *
 * @param string|null $pre_render Short-circuit value; passed through.
 * @param array       $block      Parsed block.
 * @return string|null
 */
function open_frame( $pre_render, $block ) {
	if ( null !== $pre_render || ! \is_array( $block ) ) {
		return $pre_render;
	}

	$name  = $block['blockName'] ?? '';
	$state = &state();

	if ( 'core/accordion' === $name ) {
		$state['accordions'][] = array(
			'enabled'   => true === ( $block['attrs'][ ATTRIBUTE ] ?? false ),
			'questions' => array(),
		);
	} elseif ( 'core/accordion-item' === $name ) {
		$accordion        = \end( $state['accordions'] );
		$state['items'][] = array(
			'collect'  => \is_array( $accordion ) && $accordion['enabled'],
			'question' => null,
			'answer'   => null,
		);
	}

	return $pre_render;
}

/**
 * Index of the innermost open item frame that is collecting, or null.
 *
 * @return int|null
 */
function collecting_item(): ?int {
	$state = state();

	if ( array() === $state['items'] ) {
		return null;
	}

	$index = \array_key_last( $state['items'] );

	return $state['items'][ $index ]['collect'] ? $index : null;
}

/**
 * Record the heading's title text on the open item.
 *
 * @param string $content Rendered heading.
 * @return string Unchanged.
 */
function capture_question( $content ) {
	$index = collecting_item();
	$state = &state();

	if ( null !== $index && null === $state['items'][ $index ]['question'] ) {
		$state['items'][ $index ]['question'] = question_text( (string) $content );
	}

	return $content;
}

/**
 * Record the panel's reduced HTML on the open item.
 *
 * @param string $content Rendered panel, nested blocks included.
 * @return string Unchanged.
 */
function capture_answer( $content ) {
	$index = collecting_item();
	$state = &state();

	if ( null !== $index && null === $state['items'][ $index ]['answer'] ) {
		$state['items'][ $index ]['answer'] = answer_html( (string) $content );
	}

	return $content;
}

/**
 * Pop the item frame and hand a complete question to its accordion.
 *
 * @param string $content Rendered item.
 * @return string Unchanged.
 */
function close_item( $content ) {
	$state = &state();
	$item  = \array_pop( $state['items'] );

	if ( ! \is_array( $item ) || ! $item['collect'] || array() === $state['accordions'] ) {
		return $content;
	}

	$question = build_question( (string) $item['question'], (string) $item['answer'] );

	if ( null !== $question ) {
		$state['accordions'][ \array_key_last( $state['accordions'] ) ]['questions'][] = $question;
	}

	return $content;
}

/**
 * Pop the accordion frame; a flagged accordion's questions join the page's list.
 *
 * @param string $content Rendered accordion.
 * @return string Unchanged.
 */
function close_accordion( $content ) {
	$state     = &state();
	$accordion = \array_pop( $state['accordions'] );

	if ( \is_array( $accordion ) && $accordion['enabled'] ) {
		foreach ( $accordion['questions'] as $question ) {
			// Keyed, so an accordion rendered twice (pre-collection, then the page) counts once.
			$key = \md5( $question['name'] . "\0" . $question['acceptedAnswer']['text'] );

			$state['questions'][ $key ] = $question;
		}
	}

	return $content;
}

/**
 * A Question node, or null when either side is empty.
 *
 * @param string $question Plain-text question.
 * @param string $answer   Reduced answer HTML.
 * @return array|null
 */
function build_question( string $question, string $answer ): ?array {
	$question = \trim( $question );

	if ( '' === $question || '' === \trim( \html_entity_decode( \strip_tags( $answer ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Emptiness test only; the answer is already reduced.
		return null;
	}

	return array(
		'@type'          => 'Question',
		'name'           => $question,
		'acceptedAnswer' => array(
			'@type' => 'Answer',
			'text'  => $answer,
		),
	);
}

/**
 * Plain text of the heading's title span (core: `.wp-block-accordion-heading__toggle-title`).
 *
 * Text nodes come back decoded from the HTML API, so the result needs no entity decoding.
 *
 * @param string $html Rendered heading.
 * @return string
 */
function question_text( string $html ): string {
	$processor = new \WP_HTML_Tag_Processor( $html );

	if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-accordion-heading__toggle-title' ) ) ) {
		return '';
	}

	$text  = '';
	$depth = 0;

	while ( $processor->next_token() ) {
		$type = $processor->get_token_type();

		if ( '#text' === $type ) {
			$text .= $processor->get_modifiable_text();
			continue;
		}

		if ( '#tag' !== $type || 'SPAN' !== $processor->get_tag() ) {
			continue;
		}

		if ( ! $processor->is_tag_closer() ) {
			++$depth;
		} elseif ( 0 === $depth-- ) {
			break;
		}
	}

	return collapse_whitespace( $text );
}

/**
 * Collapse runs of whitespace to one space and trim.
 *
 * @param string $text Text or reduced HTML.
 * @return string
 */
function collapse_whitespace( string $text ): string {
	return \trim( (string) \preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * Reduce a rendered panel to the tags Google allows in an FAQ answer.
 *
 * Rebuilt token by token: allowed tags are written fresh with no attributes but
 * `href`, text is re-escaped, and anything else (including script and style bodies,
 * which the HTML API never yields as text) is dropped. The panel's own wrapper goes.
 *
 * @param string $html Rendered panel.
 * @return string
 */
function answer_html( string $html ): string {
	$processor = new \WP_HTML_Tag_Processor( $html );
	$allowed   = \array_flip( ANSWER_TAGS );
	$output    = '';
	$first     = true;
	$wrapper   = false;
	$div_depth = 0;

	while ( $processor->next_token() ) {
		$type = $processor->get_token_type();

		if ( '#text' === $type ) {
			$output .= \htmlspecialchars( $processor->get_modifiable_text(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
			continue;
		}

		if ( '#tag' !== $type ) {
			continue;
		}

		$tag    = \strtolower( (string) $processor->get_tag() );
		$closer = $processor->is_tag_closer();

		if ( 'div' === $tag ) {
			if ( $first && ! $closer && true === $processor->has_class( 'wp-block-accordion-panel' ) ) {
				$first     = false;
				$wrapper   = true;
				$div_depth = 1;
				continue;
			}

			$div_depth += $closer ? -1 : 1;

			if ( $wrapper && $closer && 0 === $div_depth ) {
				continue;
			}
		}

		$first = false;

		if ( ! isset( $allowed[ $tag ] ) ) {
			// Keeps words in neighbouring blocks apart once their wrappers are gone.
			$output .= ' ';
			continue;
		}

		if ( 'br' === $tag ) {
			$output .= $closer ? '' : '<br>';
			continue;
		}

		if ( $closer ) {
			$output .= '</' . $tag . '>';
			continue;
		}

		if ( 'a' === $tag ) {
			$href    = $processor->get_attribute( 'href' );
			$href    = \is_string( $href ) ? \esc_url( $href ) : '';
			$output .= '' !== $href ? '<a href="' . $href . '">' : '<a>';
			continue;
		}

		$output .= '<' . $tag . '>';
	}

	$kses = array_fill_keys( ANSWER_TAGS, array() );

	$kses['a'] = array( 'href' => true );

	return collapse_whitespace( \force_balance_tags( \wp_kses( $output, $kses ) ) );
}

/**
 * Whether this request publishes FAQ data at all.
 *
 * @return bool
 */
function enabled(): bool {
	/**
	 * Filters whether FAQ structured data is output on this request.
	 *
	 * @param bool $enabled Default: singular views and the front page.
	 */
	return (bool) \apply_filters( 'isudev_library/extensions/core_accordion_faq_schema/enabled', \is_singular() || \is_front_page() );
}

/**
 * Questions for this page, filtered and validated.
 *
 * @return array List of Question nodes.
 */
function page_questions(): array {
	$state = state();

	/**
	 * Filters the Question nodes published for the page.
	 *
	 * @param array $questions List of `Question` arrays (`name`, `acceptedAnswer.text`). Return
	 *                         an empty array to publish nothing.
	 */
	$questions = \apply_filters( 'isudev_library/extensions/core_accordion_faq_schema/questions', \array_values( $state['questions'] ) );

	return valid_questions( $questions );
}

/**
 * Keep only well-formed Question nodes.
 *
 * @param mixed $questions Candidate list.
 * @return array
 */
function valid_questions( $questions ): array {
	if ( ! \is_array( $questions ) ) {
		return array();
	}

	return \array_values(
		\array_filter(
			$questions,
			static function ( $question ): bool {
				return \is_array( $question )
					&& 'Question' === ( $question['@type'] ?? '' )
					&& \is_string( $question['name'] ?? null ) && '' !== $question['name']
					&& \is_string( $question['acceptedAnswer']['text'] ?? null ) && '' !== $question['acceptedAnswer']['text'];
			}
		)
	);
}

/**
 * Classic themes print wp_head before the content renders: render the queried post's
 * flagged accordions once so the SEO plugins' head output can include them.
 *
 * @return void
 */
function precollect(): void {
	$state = &state();

	if ( $state['precollected'] || \wp_is_block_theme() || \did_filter( 'the_content' ) > 0 ) {
		return;
	}

	$state['precollected'] = true;
	$post                  = \get_queried_object();

	if ( ! $post instanceof \WP_Post || ! \has_block( 'core/accordion', $post ) ) {
		return;
	}

	foreach ( flagged_accordions( \parse_blocks( $post->post_content ) ) as $block ) {
		\render_block( $block );
	}
}

/**
 * Flagged core/accordion blocks in a parsed tree, outermost only.
 *
 * @param array $blocks Parsed blocks.
 * @return array
 */
function flagged_accordions( array $blocks ): array {
	$found = array();

	foreach ( $blocks as $block ) {
		if ( ! \is_array( $block ) ) {
			continue;
		}

		if ( 'core/accordion' === ( $block['blockName'] ?? '' ) && true === ( $block['attrs'][ ATTRIBUTE ] ?? false ) ) {
			$found[] = $block;
			continue;
		}

		if ( ! empty( $block['innerBlocks'] ) && \is_array( $block['innerBlocks'] ) ) {
			$found = \array_merge( $found, flagged_accordions( $block['innerBlocks'] ) );
		}
	}

	return $found;
}

/**
 * Whether a schema node's @type includes a type.
 *
 * @param array  $node Schema node.
 * @param string $type Type name.
 * @return bool
 */
function has_type( array $node, string $type ): bool {
	return \in_array( $type, (array) ( $node['@type'] ?? array() ), true );
}

/**
 * Add FAQPage to a node's @type and append the questions to its mainEntity.
 *
 * @param array $node      Schema node.
 * @param array $questions Question nodes.
 * @return array
 */
function add_faq( array $node, array $questions ): array {
	$types = (array) ( $node['@type'] ?? array() );

	if ( ! \in_array( 'FAQPage', $types, true ) ) {
		$types[] = 'FAQPage';
	}

	$entities = $node['mainEntity'] ?? array();

	// A single entity (an associative array) becomes a list of one.
	if ( ! \is_array( $entities ) || ( array() !== $entities && ! \array_is_list( $entities ) ) ) {
		$entities = array( $entities );
	}

	$node['@type']      = 1 === \count( $types ) ? $types[0] : \array_values( $types );
	$node['mainEntity'] = \array_merge( $entities, $questions );

	return $node;
}

/**
 * Merge questions into a Yoast graph: the main WebPage node gains FAQPage.
 *
 * @param array  $graph     Yoast graph pieces.
 * @param array  $questions Question nodes.
 * @param string $main_id   `@id` of the main WebPage node, '' when unknown.
 * @return array|null Updated graph, or null when it has no WebPage node.
 */
function merge_yoast_graph( array $graph, array $questions, string $main_id ): ?array {
	$target = null;

	foreach ( $graph as $index => $piece ) {
		if ( ! \is_array( $piece ) ) {
			continue;
		}

		if ( '' !== $main_id && isset( $piece['@id'] ) && $piece['@id'] === $main_id ) {
			$target = $index;
			break;
		}

		if ( null === $target && ( has_type( $piece, 'WebPage' ) || has_type( $piece, 'FAQPage' ) ) ) {
			$target = $index;
		}
	}

	if ( null === $target ) {
		return null;
	}

	$graph[ $target ] = add_faq( $graph[ $target ], $questions );

	return $graph;
}

/**
 * Merge questions into Rank Math's entities: an existing FAQPage (its FAQ block) or a new one.
 *
 * @param array $data      Rank Math entities keyed by name.
 * @param array $questions Question nodes.
 * @return array
 */
function merge_rank_math( array $data, array $questions ): array {
	foreach ( $data as $key => $entity ) {
		if ( \is_array( $entity ) && has_type( $entity, 'FAQPage' ) ) {
			$data[ $key ] = add_faq( $entity, $questions );

			return $data;
		}
	}

	$data['isudevFaqPage'] = array(
		'@type'      => 'FAQPage',
		'mainEntity' => $questions,
	);

	return $data;
}

/**
 * Filter callback for Yoast SEO's `wpseo_schema_graph`.
 *
 * @param array       $graph   Graph pieces.
 * @param object|null $context Yoast Meta_Tags_Context.
 * @return array
 */
function inject_yoast( $graph, $context = null ) {
	if ( ! \is_array( $graph ) || ! enabled() ) {
		return $graph;
	}

	precollect();
	$questions = page_questions();

	if ( array() === $questions ) {
		return $graph;
	}

	$main_id = \is_object( $context ) && isset( $context->main_schema_id ) && \is_string( $context->main_schema_id ) ? $context->main_schema_id : '';
	$merged  = merge_yoast_graph( $graph, $questions, $main_id );

	if ( null === $merged ) {
		return $graph;
	}

	$state             = &state();
	$state['injected'] = true;

	return $merged;
}

/**
 * Filter callback for Rank Math's `rank_math/json_ld`.
 *
 * @param array $data Entities keyed by name.
 * @return array
 */
function inject_rank_math( $data ) {
	if ( ! \is_array( $data ) || ! enabled() ) {
		return $data;
	}

	precollect();
	$questions = page_questions();

	if ( array() === $questions ) {
		return $data;
	}

	$state             = &state();
	$state['injected'] = true;

	return merge_rank_math( $data, $questions );
}

/**
 * Standalone FAQPage JSON-LD for when no SEO plugin took the questions.
 *
 * @return void
 */
function print_standalone(): void {
	if ( state()['injected'] || ! enabled() ) {
		return;
	}

	$questions = page_questions();

	if ( array() === $questions ) {
		return;
	}

	$json = \wp_json_encode(
		array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $questions,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
	);

	if ( \is_string( $json ) ) {
		\wp_print_inline_script_tag(
			$json,
			array(
				'type' => 'application/ld+json',
				'id'   => 'isudev-faq-schema',
			)
		);
	}
}
