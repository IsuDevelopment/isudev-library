<?php
/**
 * Rename posts: relabel the built-in `post` type without registering a new one.
 *
 * Labels only. Nothing here changes visibility, permalinks or capabilities —
 * pair it with the Disable posts extension if the post type should also be
 * hidden from the front end.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\PostRename;

defined( 'ABSPATH' ) || exit;

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	// Priority 1: labels must be in place before anything on init reads them.
	\add_action( 'init', __NAMESPACE__ . '\\rename_post_type', 1 );
	\add_action( 'admin_menu', __NAMESPACE__ . '\\rename_admin_menu' );
}

/**
 * The singular and plural name the post type is relabelled to.
 *
 * @return array{singular:string,plural:string}
 */
function names(): array {
	$names = array(
		'singular' => \__( 'Article', 'isudev-library' ),
		'plural'   => \__( 'Articles', 'isudev-library' ),
	);

	/**
	 * Filters the words posts are relabelled with.
	 *
	 * Both keys are required; a return value missing either falls back to the
	 * default for that key. Use this to rename posts to anything else — News,
	 * Stories, Aktualności — without writing a second extension.
	 *
	 * @param array $names array{singular:string,plural:string}.
	 */
	$filtered = \apply_filters( 'isudev_library/extensions/post_rename/names', $names );

	if ( ! \is_array( $filtered ) ) {
		return $names;
	}

	return array(
		'singular' => \is_string( $filtered['singular'] ?? null ) && '' !== $filtered['singular'] ? $filtered['singular'] : $names['singular'],
		'plural'   => \is_string( $filtered['plural'] ?? null ) && '' !== $filtered['plural'] ? $filtered['plural'] : $names['plural'],
	);
}

/**
 * Every label of the post type, built from the two names.
 *
 * Returned as an array rather than applied in place so the whole set is
 * filterable in one go, and so the mapping is readable at a glance.
 *
 * @param string $singular Singular name.
 * @param string $plural   Plural name.
 * @return array Label name => value.
 */
function labels( string $singular, string $plural ): array {
	$labels = array(
		'name'                   => $plural,
		'singular_name'          => $singular,
		/* translators: %s: singular post type name, e.g. Article. */
		'add_new_item'           => \sprintf( \__( 'Add new %s', 'isudev-library' ), $singular ),
		/* translators: %s: singular post type name, e.g. Article. */
		'edit_item'              => \sprintf( \__( 'Edit %s', 'isudev-library' ), $singular ),
		/* translators: %s: singular post type name, e.g. Article. */
		'new_item'               => \sprintf( \__( 'New %s', 'isudev-library' ), $singular ),
		/* translators: %s: singular post type name, e.g. Article. */
		'view_item'              => \sprintf( \__( 'View %s', 'isudev-library' ), $singular ),
		/* translators: %s: plural post type name, e.g. Articles. */
		'view_items'             => \sprintf( \__( 'View %s', 'isudev-library' ), $plural ),
		/* translators: %s: plural post type name, e.g. Articles. */
		'search_items'           => \sprintf( \__( 'Search %s', 'isudev-library' ), $plural ),
		/* translators: %s: lowercase plural post type name, e.g. articles. */
		'not_found'              => \sprintf( \__( 'No %s found.', 'isudev-library' ), \strtolower( $plural ) ),
		/* translators: %s: lowercase plural post type name, e.g. articles. */
		'not_found_in_trash'     => \sprintf( \__( 'No %s found in Trash.', 'isudev-library' ), \strtolower( $plural ) ),
		/* translators: %s: plural post type name, e.g. Articles. */
		'all_items'              => \sprintf( \__( 'All %s', 'isudev-library' ), $plural ),
		/* translators: %s: plural post type name, e.g. Articles. */
		'archives'               => \sprintf( \__( '%s archives', 'isudev-library' ), $plural ),
		/* translators: %s: singular post type name, e.g. Article. */
		'attributes'             => \sprintf( \__( '%s attributes', 'isudev-library' ), $singular ),
		/* translators: %s: singular post type name, e.g. Article. */
		'insert_into_item'       => \sprintf( \__( 'Insert into %s', 'isudev-library' ), \strtolower( $singular ) ),
		/* translators: %s: singular post type name, e.g. Article. */
		'item_published'         => \sprintf( \__( '%s published.', 'isudev-library' ), $singular ),
		/* translators: %s: singular post type name, e.g. Article. */
		'item_updated'           => \sprintf( \__( '%s updated.', 'isudev-library' ), $singular ),
		/* translators: %s: singular post type name, e.g. Article. */
		'item_scheduled'         => \sprintf( \__( '%s scheduled.', 'isudev-library' ), $singular ),
		/* translators: %s: singular post type name, e.g. Article. */
		'item_reverted_to_draft' => \sprintf( \__( '%s reverted to draft.', 'isudev-library' ), $singular ),
		'menu_name'              => $plural,
		'name_admin_bar'         => $singular,
	);

	/**
	 * Filters the full label set applied to the post type.
	 *
	 * @param array  $labels   Label name => value.
	 * @param string $singular Singular name.
	 * @param string $plural   Plural name.
	 */
	$filtered = \apply_filters( 'isudev_library/extensions/post_rename/labels', $labels, $singular, $plural );

	return \is_array( $filtered ) ? $filtered : $labels;
}

/**
 * Apply the labels to the registered post type.
 *
 * @return void
 */
function rename_post_type(): void {
	global $wp_post_types;

	if ( ! isset( $wp_post_types['post'] ) ) {
		return;
	}

	$names = names();

	$wp_post_types['post']->label = $names['plural'];

	foreach ( labels( $names['singular'], $names['plural'] ) as $key => $value ) {
		$wp_post_types['post']->labels->{$key} = $value;
	}
}

/**
 * Relabel the admin menu entries, which core built before init from the old labels.
 *
 * @return void
 */
function rename_admin_menu(): void {
	global $menu, $submenu;

	if ( ! \current_user_can( 'edit_posts' ) ) {
		return;
	}

	$names  = names();
	$labels = labels( $names['singular'], $names['plural'] );

	// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Relabelling core's own menu entries is the whole point of this extension.
	if ( isset( $menu[5][0] ) ) {
		$menu[5][0] = $names['plural'];
	}

	if ( isset( $submenu['edit.php'][5][0] ) ) {
		$submenu['edit.php'][5][0] = $labels['all_items'];
	}

	if ( isset( $submenu['edit.php'][10][0] ) ) {
		$submenu['edit.php'][10][0] = $labels['add_new_item'];
	}
	// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited
}
