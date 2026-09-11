<?php
/**
 * Last edited column: who last changed a post, and when.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\LastEditedColumn;

defined( 'ABSPATH' ) || exit;

const COLUMN = 'isudev_last_edited';

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_filter( 'manage_posts_columns', __NAMESPACE__ . '\\add_column' );
	\add_filter( 'manage_pages_columns', __NAMESPACE__ . '\\add_column' );
	\add_action( 'manage_posts_custom_column', __NAMESPACE__ . '\\render_column', 10, 2 );
	\add_action( 'manage_pages_custom_column', __NAMESPACE__ . '\\render_column', 10, 2 );
	\add_action( 'admin_init', __NAMESPACE__ . '\\register_sortable_columns' );
	\add_action( 'pre_get_posts', __NAMESPACE__ . '\\apply_orderby' );
}

/**
 * Post types the column is added to.
 *
 * @return array List of post type slugs.
 */
function post_types(): array {
	$post_types = \get_post_types( array( 'show_ui' => true ) );

	/**
	 * Filters the post types that get a "Last edited" column.
	 *
	 * @param array $post_types List of post type slugs. Defaults to every post type with a UI.
	 */
	$post_types = \apply_filters( 'isudev_library/extensions/last_edited_column/post_types', \array_values( $post_types ) );

	return \is_array( $post_types ) ? $post_types : array();
}

/**
 * Add the column header.
 *
 * @param array $columns Existing columns, as id => label.
 * @return array
 */
function add_column( $columns ): array {
	$columns = \is_array( $columns ) ? $columns : array();

	$screen = \function_exists( 'get_current_screen' ) ? \get_current_screen() : null;

	if ( $screen instanceof \WP_Screen && ! \in_array( $screen->post_type, post_types(), true ) ) {
		return $columns;
	}

	$columns[ COLUMN ] = \__( 'Last edited', 'isudev-library' );

	return $columns;
}

/**
 * Render one cell.
 *
 * @param string $column  Column id being rendered.
 * @param int    $post_id Post being listed.
 * @return void
 */
function render_column( $column, $post_id ): void {
	if ( COLUMN !== $column ) {
		return;
	}

	echo \wp_kses( cell( (int) $post_id ), array( 'br' => array() ) );
}

/**
 * The cell markup for one post.
 *
 * Split out from render_column() so the branch that matters — never edited
 * versus edited — is readable without the escaping noise around it.
 *
 * @param int $post_id Post being listed.
 * @return string Escaped markup; `<br>` is the only tag.
 */
function cell( int $post_id ): string {
	$modified = (string) \get_post_field( 'post_modified', $post_id, 'raw' );
	$created  = (string) \get_post_field( 'post_date', $post_id, 'raw' );

	// WordPress seeds post_modified from post_date, so an untouched post reports
	// an edit that never happened. An em dash is the honest answer there.
	if ( '' === $modified || $modified === $created ) {
		return '&mdash;';
	}

	$format    = \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' );
	$timestamp = \strtotime( $modified );
	$date      = false === $timestamp ? '' : \date_i18n( (string) $format, $timestamp );

	$editor_id = (int) \get_post_meta( $post_id, '_edit_last', true );
	$editor    = $editor_id > 0 ? \get_userdata( $editor_id ) : false;

	if ( ! $editor ) {
		return \esc_html( $date );
	}

	return \sprintf(
		'%1$s<br>%2$s',
		\esc_html( $editor->display_name ),
		\esc_html( $date )
	);
}

/**
 * Make the column sortable on every post type it appears on.
 *
 * @return void
 */
function register_sortable_columns(): void {
	foreach ( post_types() as $post_type ) {
		\add_filter( 'manage_edit-' . $post_type . '_sortable_columns', __NAMESPACE__ . '\\add_sortable_column' );
	}
}

/**
 * Declare the column sortable.
 *
 * @param array $columns Sortable columns, as column id => orderby value.
 * @return array
 */
function add_sortable_column( $columns ): array {
	$columns           = \is_array( $columns ) ? $columns : array();
	$columns[ COLUMN ] = COLUMN;

	return $columns;
}

/**
 * Translate the column's orderby value into a real one.
 *
 * @param \WP_Query $query Query about to run.
 * @return void
 */
function apply_orderby( $query ): void {
	if ( ! \is_admin() || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
		return;
	}

	if ( COLUMN === $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'modified' );
	}
}
