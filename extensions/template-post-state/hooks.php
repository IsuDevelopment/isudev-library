<?php
/**
 * Template name badge in post list screens.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\TemplatePostState;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Priority 100: after core's own states, so the template reads last.
	\add_filter( 'display_post_states', __NAMESPACE__ . '\\add_template_state', 100, 2 );
	\add_action( 'admin_print_styles-edit.php', __NAMESPACE__ . '\\print_styles' );
}

/**
 * Append the assigned template to a post's states.
 *
 * @param array    $post_states Existing post states.
 * @param \WP_Post $post        Post being listed.
 * @return array
 */
function add_template_state( $post_states, $post ): array {
	$post_states = \is_array( $post_states ) ? $post_states : array();

	if ( ! $post instanceof \WP_Post ) {
		return $post_states;
	}

	$assigned = (string) \get_post_meta( $post->ID, '_wp_page_template', true );

	// `default` and an empty value both mean "no template chosen", which is the
	// common case and not worth a badge.
	if ( '' === $assigned || 'default' === $assigned ) {
		return $post_states;
	}

	$templates = \get_page_templates( $post );
	$name      = \array_search( $assigned, $templates, true );

	// A template file that is no longer in the theme has no name to show. Fall
	// back to the file so a stale assignment is visible rather than silent.
	$label = false === $name ? $assigned : (string) $name;

	$post_states[] = \sprintf(
		'<span class="isudev-template-state">%s</span>',
		\esc_html( $label )
	);

	return $post_states;
}

/**
 * Style the badge.
 *
 * A handful of declarations on one admin screen — an inline style keeps this
 * extension to two files and costs no extra request.
 *
 * @return void
 */
function print_styles(): void {
	echo '<style>
		.isudev-template-state {
			display: inline-block;
			padding: 2px 6px;
			border: 1px solid rgb(0 124 186 / 30%);
			border-radius: 4px;
			background: rgb(0 124 186 / 10%);
			color: #007cba;
			font-size: 10px;
			font-weight: 500;
			line-height: 1.2;
			text-transform: uppercase;
			vertical-align: middle;
		}
	</style>';
}
