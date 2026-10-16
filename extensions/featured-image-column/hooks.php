<?php
/**
 * Featured image column: a thumbnail at the start of post and user list screens.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\FeaturedImageColumn;

use IsuDevLibrary\Extensions;

defined( 'ABSPATH' ) || exit;

const SLUG   = 'featured-image-column';
const COLUMN = 'isudev_featured_image';

/**
 * Display box used when neither isudev.json nor a filter says otherwise.
 *
 * @return array{aspectRatio:string,maxHeight:int,width:int}
 */
function defaults(): array {
	return array(
		'aspectRatio' => '4/3',
		'maxHeight'   => 48,
		'width'       => 64,
	);
}

/**
 * Normalize a display box from config, falling back key by key. Pure.
 *
 * `aspectRatio` is `auto` or `W/H` with positive numbers; `maxHeight` and
 * `width` are pixels clamped to 16–400. Anything else keeps the fallback, so a
 * typo in isudev.json degrades to the default instead of breaking the screen.
 *
 * @param mixed $value    Candidate box, usually from isudev.json.
 * @param array $fallback Normalized box to fall back on.
 * @return array{aspectRatio:string,maxHeight:int,width:int}
 */
function normalize_box( $value, array $fallback ): array {
	$box = $fallback;

	if ( ! \is_array( $value ) ) {
		return $box;
	}

	$ratio = $value['aspectRatio'] ?? null;
	if ( \is_string( $ratio ) ) {
		$ratio = \str_replace( ' ', '', $ratio );
		if ( 'auto' === $ratio || 1 === \preg_match( '#^(?=[^/]*[1-9])\d+(\.\d+)?/(?=[^/]*[1-9])\d+(\.\d+)?$#', $ratio ) ) {
			$box['aspectRatio'] = $ratio;
		}
	}

	foreach ( array( 'maxHeight', 'width' ) as $key ) {
		if ( isset( $value[ $key ] ) && \is_numeric( $value[ $key ] ) ) {
			$box[ $key ] = \max( 16, \min( 400, (int) $value[ $key ] ) );
		}
	}

	return $box;
}

/**
 * Inline style for the image inside a display box. Pure.
 *
 * @param array $box Normalized box.
 * @return string
 */
function image_style( array $box ): string {
	return \sprintf(
		'width:%1$dpx;max-width:100%%;height:auto;max-height:%2$dpx;aspect-ratio:%3$s;object-fit:cover;',
		$box['width'],
		$box['maxHeight'],
		$box['aspectRatio']
	);
}

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'admin_init', __NAMESPACE__ . '\\register_post_columns' );
	\add_filter( 'manage_users_columns', __NAMESPACE__ . '\\add_user_column' );
	\add_filter( 'manage_users_custom_column', __NAMESPACE__ . '\\render_user_column', 10, 3 );
	\add_action( 'admin_head-edit.php', __NAMESPACE__ . '\\print_styles' );
	\add_action( 'admin_head-users.php', __NAMESPACE__ . '\\print_styles' );
}

/**
 * Post types that get the column: those with a UI that support featured images.
 *
 * Setting `postTypes.<type>` to false in isudev.json, or ticking the type in
 * the panel, drops one.
 *
 * @return array List of post type slugs.
 */
function post_types(): array {
	$config     = Extensions::setting( SLUG, 'postTypes', array() );
	$config     = \is_array( $config ) ? $config : array();
	$hidden     = Extensions::setting( SLUG, 'hiddenPostTypes', array() );
	$hidden     = \is_array( $hidden ) ? $hidden : array();
	$post_types = array();

	foreach ( \get_post_types( array( 'show_ui' => true ) ) as $post_type ) {
		if ( \post_type_supports( $post_type, 'thumbnail' ) && false !== ( $config[ $post_type ] ?? null ) && ! \in_array( $post_type, $hidden, true ) ) {
			$post_types[] = $post_type;
		}
	}

	/**
	 * Filters the post types that get a featured image column.
	 *
	 * @param array $post_types List of post type slugs. Defaults to every post type
	 *                          with a UI that supports featured images.
	 */
	$post_types = \apply_filters( 'isudev_library/extensions/featured_image_column/post_types', $post_types );

	return \is_array( $post_types ) ? \array_values( \array_filter( $post_types, 'is_string' ) ) : array();
}

/**
 * Display box for a list screen: `default`, then `postTypes.<type>` (or `users`) over it.
 *
 * @param string $screen Post type slug, or `users`.
 * @return array{aspectRatio:string,maxHeight:int,width:int}
 */
function box( string $screen ): array {
	$box = normalize_box( Extensions::setting( SLUG, 'default', array() ), defaults() );

	if ( 'users' === $screen ) {
		$box = normalize_box( Extensions::setting( SLUG, 'users', array() ), $box );
	} else {
		$post_types = Extensions::setting( SLUG, 'postTypes', array() );
		$box        = normalize_box( \is_array( $post_types ) ? ( $post_types[ $screen ] ?? array() ) : array(), $box );
	}

	/**
	 * Filters the display box of the image on one list screen.
	 *
	 * @param array  $box    aspectRatio (`auto` or `W/H`), maxHeight and width in px.
	 * @param string $screen Post type slug, or `users`.
	 */
	return normalize_box( \apply_filters( 'isudev_library/extensions/featured_image_column/box', $box, $screen ), $box );
}

/**
 * Hook the column into every post type it applies to.
 *
 * @return void
 */
function register_post_columns(): void {
	foreach ( post_types() as $post_type ) {
		\add_filter( 'manage_' . $post_type . '_posts_columns', __NAMESPACE__ . '\\add_column' );
		\add_action( 'manage_' . $post_type . '_posts_custom_column', __NAMESPACE__ . '\\render_column', 10, 2 );
	}
}

/**
 * Insert a column right after the checkbox, before the title.
 *
 * @param array  $columns Existing columns, as id => label.
 * @param string $label   Screen-reader label of the column.
 * @return array
 */
function insert_column( array $columns, string $label ): array {
	$header = \sprintf(
		'<span class="dashicons dashicons-format-image" aria-hidden="true"></span><span class="screen-reader-text">%s</span>',
		\esc_html( $label )
	);

	$out = array();
	foreach ( $columns as $id => $name ) {
		if ( 'cb' !== $id && ! isset( $out[ COLUMN ] ) ) {
			$out[ COLUMN ] = $header;
		}
		$out[ $id ] = $name;
	}

	if ( ! isset( $out[ COLUMN ] ) ) {
		$out[ COLUMN ] = $header;
	}

	return $out;
}

/**
 * Add the column header on a post list screen.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function add_column( $columns ): array {
	return insert_column( \is_array( $columns ) ? $columns : array(), \__( 'Featured image', 'isudev-library' ) );
}

/**
 * Image size requested for the thumbnail.
 *
 * @param string $screen Post type slug, or `users`.
 * @return string|array
 */
function image_size( string $screen ) {
	/**
	 * Filters the registered image size used in the column.
	 *
	 * @param string|array $size   Default `thumbnail`. The browser picks from srcset.
	 * @param string       $screen Post type slug, or `users`.
	 */
	return \apply_filters( 'isudev_library/extensions/featured_image_column/image_size', 'thumbnail', $screen );
}

/**
 * Render one post's cell.
 *
 * @param string $column  Column id being rendered.
 * @param int    $post_id Post being listed.
 * @return void
 */
function render_column( $column, $post_id ): void {
	if ( COLUMN !== $column ) {
		return;
	}

	$post_id      = (int) $post_id;
	$thumbnail_id = (int) \get_post_thumbnail_id( $post_id );

	if ( $thumbnail_id <= 0 ) {
		echo '<span aria-hidden="true">&mdash;</span>';
		return;
	}

	$screen = (string) \get_post_type( $post_id );
	$image  = \wp_get_attachment_image(
		$thumbnail_id,
		image_size( $screen ),
		false,
		array(
			'alt'     => '',
			'loading' => 'lazy',
			'class'   => 'isudev-featured-image-column__image',
			'style'   => image_style( box( $screen ) ),
		)
	);

	if ( '' === $image ) {
		echo '<span aria-hidden="true">&mdash;</span>';
		return;
	}

	$link = \current_user_can( 'edit_post', $post_id ) ? \get_edit_post_link( $post_id ) : '';

	// The title next to it carries the name; the image is decorative (empty alt) and out of the tab order.
	$cell = $link
		? '<a href="' . \esc_url( $link ) . '" tabindex="-1" aria-hidden="true">' . $image . '</a>'
		: $image;

	echo $cell; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output; the link is escaped.
}

/**
 * User meta key that holds a user's image: an attachment ID or an image URL.
 *
 * Set with isudev.json `users.metaKey`, or in the panel. Empty means no user column.
 *
 * @return string
 */
function user_meta_key(): string {
	$users = Extensions::setting( SLUG, 'users', array() );
	$key   = \is_array( $users ) && \is_string( $users['metaKey'] ?? null ) ? $users['metaKey'] : '';

	if ( '' === $key ) {
		$panel = Extensions::setting( SLUG, 'userMetaKey', '' );
		$key   = \is_string( $panel ) ? $panel : '';
	}

	/**
	 * Filters the user meta key holding the user's image (attachment ID or URL).
	 *
	 * @param string $key Default from isudev.json `users.metaKey`, else '' (no user column).
	 */
	$key = \apply_filters( 'isudev_library/extensions/featured_image_column/user_meta_key', $key );

	return \is_string( $key ) ? \sanitize_key( $key ) : '';
}

/**
 * Add the column on the Users screen, when a meta key is configured.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function add_user_column( $columns ): array {
	$columns = \is_array( $columns ) ? $columns : array();

	if ( '' === user_meta_key() ) {
		return $columns;
	}

	return insert_column( $columns, \__( 'Profile image', 'isudev-library' ) );
}

/**
 * Render one user's cell.
 *
 * @param string $output  Cell markup so far.
 * @param string $column  Column id being rendered.
 * @param int    $user_id User being listed.
 * @return string
 */
function render_user_column( $output, $column, $user_id ): string {
	if ( COLUMN !== $column ) {
		return (string) $output;
	}

	$key   = user_meta_key();
	$value = '' === $key ? '' : \get_user_meta( (int) $user_id, $key, true );
	$attrs = array(
		'alt'     => '',
		'loading' => 'lazy',
		'class'   => 'isudev-featured-image-column__image',
		'style'   => image_style( box( 'users' ) ),
	);

	/**
	 * Filters the image of one user: an attachment ID, an image URL, or '' for none.
	 *
	 * @param mixed $value   Meta value.
	 * @param int   $user_id User ID.
	 */
	$value = \apply_filters( 'isudev_library/extensions/featured_image_column/user_image', $value, (int) $user_id );

	if ( \is_numeric( $value ) && (int) $value > 0 ) {
		$image = \wp_get_attachment_image( (int) $value, image_size( 'users' ), false, $attrs );
	} elseif ( \is_string( $value ) && '' !== $value && \wp_http_validate_url( $value ) ) {
		$image = \sprintf(
			'<img src="%1$s" alt="" loading="lazy" class="%2$s" style="%3$s">',
			\esc_url( $value ),
			\esc_attr( $attrs['class'] ),
			\esc_attr( $attrs['style'] )
		);
	} else {
		$image = '';
	}

	return '' === $image ? '<span aria-hidden="true">&mdash;</span>' : $image;
}

/**
 * Column width and image corners on the list screens.
 *
 * @return void
 */
function print_styles(): void {
	global $typenow, $pagenow;

	$screen = 'users.php' === $pagenow ? 'users' : ( \is_string( $typenow ) && '' !== $typenow ? $typenow : 'post' );
	$width  = box( $screen )['width'];

	\printf(
		'<style id="isudev-featured-image-column">.column-%1$s{width:%2$dpx}.column-%1$s .dashicons{vertical-align:middle}.isudev-featured-image-column__image{display:block;border-radius:2px;background:#f0f0f1}</style>' . "\n",
		\esc_attr( COLUMN ),
		(int) $width + 8
	);
}
