<?php
/**
 * Client dashboard: the Content overview widget.
 *
 * One row per post type with its own menu icon, the total as a badge, a status
 * breakdown and View all / Add new. Media closes the list.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Dashboard\ContentOverview;

use IsuDevLibrary\Dashboard;

defined( 'ABSPATH' ) || exit;

/**
 * Statuses shown in a row, in display order, with the class that colours them.
 *
 * @return array status => modifier class ('' for muted text).
 */
function statuses(): array {
	return array(
		'publish' => '',
		'draft'   => '',
		'future'  => 'is-scheduled',
		'pending' => 'is-pending',
		'private' => '',
	);
}

/**
 * Order post types: Posts, Pages, then the rest by menu position, Media last. Pure.
 *
 * @param array $rows Rows keyed by post type slug, each with an int `position`.
 * @return array Same rows, ordered.
 */
function order_rows( array $rows ): array {
	$fixed = array(
		'post'       => -2,
		'page'       => -1,
		'attachment' => PHP_INT_MAX,
	);

	\uksort(
		$rows,
		static function ( $a, $b ) use ( $rows, $fixed ): int {
			$pa = $fixed[ $a ] ?? (int) $rows[ $a ]['position'];
			$pb = $fixed[ $b ] ?? (int) $rows[ $b ]['position'];

			$order = $pa <=> $pb;

			return 0 !== $order ? $order : \strcmp( (string) $a, (string) $b );
		}
	);

	return $rows;
}

/**
 * How a post type's menu icon should be drawn. Pure.
 *
 * @param mixed  $menu_icon The post type's `menu_icon`.
 * @param string $post_type Post type slug, for the built-in fallbacks.
 * @return array{type:string,value:string} `dashicon` (class) or `image` (URL / data URI).
 */
function icon( $menu_icon, string $post_type ): array {
	$builtin = array(
		'post'       => 'dashicons-admin-post',
		'page'       => 'dashicons-admin-page',
		'attachment' => 'dashicons-admin-media',
	);

	if ( \is_string( $menu_icon ) ) {
		$menu_icon = \trim( $menu_icon );

		if ( 1 === \preg_match( '/^dashicons-[a-z0-9-]+$/', $menu_icon ) ) {
			return array(
				'type'  => 'dashicon',
				'value' => $menu_icon,
			);
		}

		if ( \str_starts_with( $menu_icon, 'data:image/svg+xml;base64,' ) || 1 === \preg_match( '#^https?://#', $menu_icon ) ) {
			return array(
				'type'  => 'image',
				'value' => $menu_icon,
			);
		}
	}

	return array(
		'type'  => 'dashicon',
		'value' => $builtin[ $post_type ] ?? 'dashicons-admin-post',
	);
}

/**
 * Post types the overview can show: the ones with an admin menu entry.
 *
 * Block-editor internals (patterns, navigation, templates, fonts, global
 * styles) are left out even when a plugin exposes them.
 *
 * @return \WP_Post_Type[] Keyed by slug.
 */
function candidate_post_types(): array {
	$internal = array( 'wp_block', 'wp_navigation', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_font_family', 'wp_font_face' );
	$types    = array();

	foreach ( \get_post_types( array( 'show_ui' => true ), 'objects' ) as $slug => $object ) {
		if ( \in_array( $slug, $internal, true ) || false === $object->show_in_menu ) {
			continue;
		}
		$types[ $slug ] = $object;
	}

	$attachment = \get_post_type_object( 'attachment' );
	if ( $attachment instanceof \WP_Post_Type ) {
		$types['attachment'] = $attachment;
	}

	/**
	 * Filters the post types the Content overview can show (before the panel's
	 * hidden list is applied).
	 *
	 * @param \WP_Post_Type[] $types Keyed by slug.
	 */
	$types = \apply_filters( 'isudev_library/dashboard/content_overview/post_types', $types );

	return \is_array( $types ) ? \array_filter( $types, static fn( $type ): bool => $type instanceof \WP_Post_Type ) : array();
}

/**
 * Rows for the current user.
 *
 * @return array slug => row data.
 */
function rows(): array {
	$hidden = Dashboard\get()['contentOverview']['hiddenPostTypes'];
	$rows   = array();

	foreach ( candidate_post_types() as $slug => $object ) {
		if ( \in_array( $slug, $hidden, true ) || ! \current_user_can( $object->cap->edit_posts ) ) {
			continue;
		}

		if ( 'attachment' === $slug ) {
			$counts = (array) \wp_count_attachments();
			unset( $counts['trash'] );
			$total = (int) \array_sum( \array_map( 'intval', $counts ) );

			$rows[ $slug ] = array(
				'label'    => (string) $object->labels->name,
				'position' => PHP_INT_MAX,
				'icon'     => icon( null, 'attachment' ),
				'total'    => $total,
				'statuses' => array(),
				'list'     => \admin_url( 'upload.php' ),
				'add'      => \current_user_can( 'upload_files' ) ? \admin_url( 'media-new.php' ) : '',
				'addLabel' => \__( 'Upload', 'isudev-library' ),
				'pending'  => 0,
			);
			continue;
		}

		$counts   = (array) \wp_count_posts( $slug, 'readable' );
		$statuses = array();
		$total    = 0;

		foreach ( \array_keys( statuses() ) as $status ) {
			$count  = (int) ( $counts[ $status ] ?? 0 );
			$total += $count;
			if ( $count > 0 ) {
				$statuses[ $status ] = $count;
			}
		}

		$list = 'post' === $slug ? \admin_url( 'edit.php' ) : \admin_url( 'edit.php?post_type=' . $slug );

		$rows[ $slug ] = array(
			'label'    => (string) $object->labels->name,
			'position' => (int) ( $object->menu_position ?? 100 ),
			'icon'     => icon( $object->menu_icon, $slug ),
			'total'    => $total,
			'statuses' => $statuses,
			'list'     => $list,
			'add'      => \current_user_can( $object->cap->create_posts ) ? \admin_url( 'post' === $slug ? 'post-new.php' : 'post-new.php?post_type=' . $slug ) : '',
			'addLabel' => \__( 'Add new', 'isudev-library' ),
			'pending'  => (int) ( $statuses['pending'] ?? 0 ),
		);
	}

	return order_rows( $rows );
}

/**
 * Label for one status count.
 *
 * @param string $status Post status.
 * @param int    $count  Count.
 * @return string
 */
function status_label( string $status, int $count ): string {
	$number = \number_format_i18n( $count );

	switch ( $status ) {
		case 'publish':
			/* translators: %s: number of posts. */
			return \sprintf( \_n( '%s published', '%s published', $count, 'isudev-library' ), $number );
		case 'draft':
			/* translators: %s: number of posts. */
			return \sprintf( \_n( '%s draft', '%s drafts', $count, 'isudev-library' ), $number );
		case 'future':
			/* translators: %s: number of posts. */
			return \sprintf( \_n( '%s scheduled', '%s scheduled', $count, 'isudev-library' ), $number );
		case 'pending':
			/* translators: %s: number of posts. */
			return \sprintf( \_n( '%s pending review', '%s pending review', $count, 'isudev-library' ), $number );
		default:
			/* translators: %s: number of posts. */
			return \sprintf( \_n( '%s private', '%s private', $count, 'isudev-library' ), $number );
	}
}

/**
 * Widget body.
 *
 * @return void
 */
function render(): void {
	$rows = rows();

	if ( array() === $rows ) {
		echo '<p class="isudev-dash__empty">' . \esc_html__( 'No content types to show.', 'isudev-library' ) . '</p>';
		return;
	}

	$classes = statuses();
	$total   = 0;
	$pending = 0;

	echo '<ul class="isudev-dash__rows">';

	foreach ( $rows as $row ) {
		$total   += $row['total'];
		$pending += $row['pending'];

		echo '<li class="isudev-dash__row">';

		echo '<span class="isudev-dash__tile" aria-hidden="true">';
		if ( 'image' === $row['icon']['type'] ) {
			echo '<img src="' . \esc_attr( $row['icon']['value'] ) . '" alt="" width="20" height="20">';
		} else {
			echo '<span class="dashicons ' . \esc_attr( $row['icon']['value'] ) . '"></span>';
		}
		echo '</span>';

		echo '<span class="isudev-dash__main">';
		echo '<span class="isudev-dash__name"><a href="' . \esc_url( $row['list'] ) . '">' . \esc_html( $row['label'] ) . '</a>';
		echo ' <span class="isudev-dash__badge">' . \esc_html( \number_format_i18n( $row['total'] ) ) . '</span></span>';

		if ( array() !== $row['statuses'] ) {
			echo '<span class="isudev-dash__statuses">';
			foreach ( $row['statuses'] as $status => $count ) {
				$url = \add_query_arg( 'post_status', $status, $row['list'] );
				\printf(
					'<a class="isudev-dash__status %1$s" href="%2$s">%3$s</a>',
					\esc_attr( $classes[ $status ] ?? '' ),
					\esc_url( $url ),
					\esc_html( status_label( (string) $status, (int) $count ) )
				);
			}
			echo '</span>';
		}
		echo '</span>';

		echo '<span class="isudev-dash__actions">';
		\printf(
			'<a class="button" href="%1$s">%2$s<span class="screen-reader-text"> %3$s</span></a>',
			\esc_url( $row['list'] ),
			\esc_html__( 'View all', 'isudev-library' ),
			\esc_html( $row['label'] )
		);
		if ( '' !== $row['add'] ) {
			\printf(
				'<a class="button button-primary" href="%1$s">%2$s<span class="screen-reader-text"> %3$s</span></a>',
				\esc_url( $row['add'] ),
				\esc_html( $row['addLabel'] ),
				\esc_html( $row['label'] )
			);
		}
		echo '</span>';

		echo '</li>';
	}

	echo '</ul>';

	echo '<p class="isudev-dash__footer">';
	echo '<span>' . \esc_html(
		$pending > 0
			/* translators: %s: number of items. */
			? \sprintf( \_n( '%s item awaiting review', '%s items awaiting review', $pending, 'isudev-library' ), \number_format_i18n( $pending ) )
			: \__( 'Nothing awaiting review', 'isudev-library' )
	) . '</span>';
	/* translators: %s: number of items. */
	echo '<span>' . \esc_html( \sprintf( \_n( '%s item total', '%s items total', $total, 'isudev-library' ), \number_format_i18n( $total ) ) ) . '</span>';
	echo '</p>';
}
