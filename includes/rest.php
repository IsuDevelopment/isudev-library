<?php
/**
 * REST controller for block toggles and diagnostics.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\REST;

use IsuDevLibrary\Admin;
use IsuDevLibrary\Config;
use IsuDevLibrary\Extensions;
use IsuDevLibrary\Registry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

use const IsuDevLibrary\PATH;
use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const NAMESPACE_V1 = 'isudev-library/v1';

/**
 * Hook registration.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );
}

/**
 * Whether the current user may read or write the library configuration.
 *
 * Mirrors the admin panel gate: hiding the panel also closes the endpoints, so
 * `show_admin => false` is not merely cosmetic.
 *
 * @return bool
 */
function permission_check(): bool {
	return Admin\show_admin();
}

/**
 * Register the routes.
 *
 * @return void
 */
function register_routes(): void {
	\register_rest_route(
		NAMESPACE_V1,
		'/blocks',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\\get_blocks',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
		)
	);

	\register_rest_route(
		NAMESPACE_V1,
		'/blocks/(?P<slug>[a-z0-9-]+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\update_block',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
			'args'                => array(
				'enabled' => array(
					'type'     => 'boolean',
					'required' => true,
				),
			),
		)
	);

	\register_rest_route(
		NAMESPACE_V1,
		'/extensions',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\\get_extensions',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
		)
	);

	\register_rest_route(
		NAMESPACE_V1,
		'/extensions/(?P<slug>[a-z0-9-]+)/settings',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\update_extension_settings',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
			'args'                => array(
				'values' => array(
					'type'     => 'object',
					'required' => true,
				),
			),
		)
	);

	\register_rest_route(
		NAMESPACE_V1,
		'/extensions/(?P<slug>[a-z0-9-]+)',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\update_extension',
			'permission_callback' => __NAMESPACE__ . '\\permission_check',
			'args'                => array(
				'enabled' => array(
					'type'     => 'boolean',
					'required' => true,
				),
			),
		)
	);
}

/**
 * Write one slug => bool toggle into a panel option, safe against concurrent requests.
 *
 * Every toggle shares one option array, so two overlapping requests that each
 * read, change one key and write the whole array back lose one of the changes —
 * a quick second click in the panel silently undid the first. A MySQL named
 * lock serialises the read-modify-write, and the option is re-read from the
 * database inside the lock: the copy WordPress loaded at bootstrap (alloptions)
 * predates whatever the request holding the lock before us just wrote.
 *
 * Also stores an extension's panel settings array under its slug.
 *
 * @param string $option  Option name.
 * @param string $slug    Key to set.
 * @param mixed  $enabled New value: a bool for a toggle, an array of settings.
 * @return true|WP_Error
 */
function save_toggle( string $option, string $slug, $enabled ) {
	global $wpdb;

	$lock = $wpdb->prefix . $option;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery -- A named lock, not data; there is nothing to cache.
	if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, 10 )', $lock ) ) ) {
		return new WP_Error(
			'isudev_library_busy',
			\__( 'Another change is being saved. Try again.', 'isudev-library' ),
			array( 'status' => 409 )
		);
	}

	\wp_cache_delete( 'alloptions', 'options' );
	\wp_cache_delete( 'notoptions', 'options' );
	\wp_cache_delete( $option, 'options' );

	$value = \get_option( $option, array() );
	$value = \is_array( $value ) ? $value : array();

	$value[ $slug ] = $enabled;

	\update_option( $option, $value );

	$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $lock ) );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery

	return true;
}

/**
 * Metadata from the compiled manifest, keyed by block directory name.
 *
 * The block type registry only holds blocks that were registered, and a disabled
 * block is never registered, so this is the only place its real title, icon and
 * description can come from.
 *
 * @return array slug => decoded block.json contents.
 */
function manifest_metadata(): array {
	static $manifest = null;

	if ( null === $manifest ) {
		$file     = PATH . 'build/blocks-manifest.php';
		$manifest = \is_readable( $file ) ? (array) require $file : array();
	}

	return $manifest;
}

/**
 * Shape one block for the REST response.
 *
 * @param array $block Decorated descriptor from Registry::blocks().
 * @return array
 */
function prepare_block( array $block ): array {
	$type = \WP_Block_Type_Registry::get_instance()->get_registered( $block['name'] );
	$meta = manifest_metadata()[ $block['slug'] ] ?? array();

	/*
	 * Prefer the registered type, which reflects anything a filter changed at
	 * registration time. Fall back to the manifest, because a disabled block is
	 * never registered and would otherwise report its slug as its title — the
	 * panel would lose the name of every block the user just switched off.
	 */
	$title       = $type && $type->title ? $type->title : ( $meta['title'] ?? $block['slug'] );
	$description = $type && $type->description ? $type->description : ( $meta['description'] ?? '' );
	$icon        = $type && \is_string( $type->icon ) ? $type->icon : $meta['icon'] ?? 'block-default';

	return array(
		'slug'        => $block['slug'],
		'name'        => $block['name'],
		'title'       => (string) $title,
		'description' => (string) $description,
		'icon'        => \is_string( $icon ) ? $icon : 'block-default',
		'enabled'     => (bool) $block['enabled'],
		'source'      => (string) $block['source'],
		'locked'      => (bool) $block['locked'],
		'requires'    => \array_values( $block['requires'] ),
		'dependents'  => \array_values( $block['dependents'] ),
	);
}

/**
 * Diagnostics payload for the Settings tab.
 *
 * @return array
 */
function diagnostics(): array {
	$blocks  = Registry::blocks();
	$sources = Config\config_sources();

	/*
	 * Ask the block registry rather than re-deriving this from `enabled`. The
	 * Loader skips an enabled block whose build metadata is unreadable, and a
	 * missing `npm run build` is exactly what this number exists to surface.
	 */
	$block_types = \WP_Block_Type_Registry::get_instance();
	$registered  = 0;
	foreach ( $blocks as $block ) {
		if ( $block_types->get_registered( $block['name'] ) instanceof \WP_Block_Type ) {
			++$registered;
		}
	}

	return array(
		'version'      => VERSION,
		'discovered'   => \count( $blocks ),
		'registered'   => $registered,
		'configParent' => $sources['parent'],
		'configChild'  => $sources['child'],
	);
}

/**
 * GET /blocks
 *
 * @return WP_REST_Response
 */
function get_blocks(): WP_REST_Response {
	$blocks = array();

	foreach ( Registry::blocks() as $block ) {
		$blocks[] = prepare_block( $block );
	}

	return new WP_REST_Response(
		array(
			'blocks'      => $blocks,
			'diagnostics' => diagnostics(),
		),
		200
	);
}

/**
 * POST /blocks/<slug>
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function update_block( WP_REST_Request $request ) {
	$slug   = (string) $request->get_param( 'slug' );
	$blocks = Registry::blocks();

	if ( ! isset( $blocks[ $slug ] ) ) {
		return new WP_Error(
			'isudev_library_unknown_block',
			\__( 'Unknown block.', 'isudev-library' ),
			array( 'status' => 404 )
		);
	}

	if ( $blocks[ $slug ]['locked'] ) {
		return new WP_Error(
			'isudev_library_block_locked',
			\__( 'This block is managed in code and cannot be toggled here.', 'isudev-library' ),
			array( 'status' => 403 )
		);
	}

	$saved = save_toggle( Registry::OPTION, $slug, (bool) $request->get_param( 'enabled' ) );
	if ( \is_wp_error( $saved ) ) {
		return $saved;
	}
	Registry::flush();

	$refreshed = Registry::blocks();

	return new WP_REST_Response( prepare_block( $refreshed[ $slug ] ), 200 );
}

/**
 * Shape one extension for the REST response.
 *
 * @param array $extension Decorated descriptor from Extensions::all().
 * @return array
 */
function prepare_extension( array $extension ): array {
	return array(
		'slug'          => $extension['slug'],
		'title'         => (string) $extension['title'],
		'description'   => (string) $extension['description'],
		'category'      => (string) $extension['category'],
		'enabled'       => (bool) $extension['enabled'],
		'source'        => (string) $extension['source'],
		'locked'        => (bool) $extension['locked'],
		'requiresLabel' => null === $extension['requires'] ? '' : (string) $extension['requires']['label'],
		'settings'      => prepare_extension_settings( $extension ),
	);
}

/**
 * An extension's settings fields with their current values for the panel.
 *
 * A key the theme sets in isudev.json is reported as `locked` with that value:
 * the panel shows it but cannot change it, the same rule as `enabled`.
 *
 * @param array $extension Decorated descriptor.
 * @return array List of fields.
 */
function prepare_extension_settings( array $extension ): array {
	$config = Extensions::config()[ $extension['slug'] ] ?? array();
	$config = \is_array( $config ) ? $config : array();
	$panel  = Extensions::panel_settings( $extension['slug'] );
	$fields = array();

	foreach ( $extension['settings'] as $field ) {
		$locked = \array_key_exists( $field['key'], $config );
		$value  = $locked ? $config[ $field['key'] ] : ( $panel[ $field['key'] ] ?? $field['default'] );

		$item = array(
			'key'    => $field['key'],
			'type'   => $field['type'],
			'label'  => $field['label'],
			'help'   => $field['help'],
			'value'  => Extensions::sanitize_setting_value( $field, $value ),
			'locked' => $locked,
		);

		if ( 'post_types' === $field['type'] ) {
			$item['choices'] = array();

			foreach ( \get_post_types( array( 'show_ui' => true ), 'objects' ) as $slug => $object ) {
				if ( '' === $field['supports'] || \post_type_supports( $slug, $field['supports'] ) ) {
					$item['choices'][] = array(
						'value' => $slug,
						'label' => (string) $object->labels->name,
					);
				}
			}
		}

		$fields[] = $item;
	}

	return $fields;
}

/**
 * POST /extensions/<slug>/settings
 *
 * Stores only the declared, unlocked fields, each coerced to its type.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function update_extension_settings( WP_REST_Request $request ) {
	$slug       = (string) $request->get_param( 'slug' );
	$extensions = Extensions::all();

	if ( ! isset( $extensions[ $slug ] ) || array() === $extensions[ $slug ]['settings'] ) {
		return new WP_Error(
			'isudev_library_unknown_extension',
			\__( 'Unknown extension.', 'isudev-library' ),
			array( 'status' => 404 )
		);
	}

	$incoming = $request->get_param( 'values' );
	$incoming = \is_array( $incoming ) ? $incoming : array();
	$config   = Extensions::config()[ $slug ] ?? array();
	$config   = \is_array( $config ) ? $config : array();
	$values   = Extensions::panel_settings( $slug );

	foreach ( $extensions[ $slug ]['settings'] as $field ) {
		if ( \array_key_exists( $field['key'], $config ) || ! \array_key_exists( $field['key'], $incoming ) ) {
			continue;
		}

		$value = Extensions::sanitize_setting_value( $field, $incoming[ $field['key'] ] );

		if ( 'email' === $field['type'] && '' !== $value && ! \is_email( $value ) ) {
			return new WP_Error(
				'isudev_library_invalid_setting',
				/* translators: %s: setting label. */
				\sprintf( \__( '%s is not a valid email address.', 'isudev-library' ), $field['label'] ),
				array( 'status' => 400 )
			);
		}

		$values[ $field['key'] ] = $value;
	}

	$saved = save_toggle( Extensions::SETTINGS_OPTION, $slug, $values );
	if ( \is_wp_error( $saved ) ) {
		return $saved;
	}
	Extensions::flush();

	return new WP_REST_Response( prepare_extension( Extensions::all()[ $slug ] ), 200 );
}

/**
 * GET /extensions
 *
 * @return WP_REST_Response
 */
function get_extensions(): WP_REST_Response {
	$extensions = array();

	foreach ( Extensions::all() as $extension ) {
		$extensions[] = prepare_extension( $extension );
	}

	$categories = array();

	foreach ( Extensions::categories() as $slug => $label ) {
		$categories[] = array(
			'slug'  => $slug,
			'label' => $label,
		);
	}

	return new WP_REST_Response(
		array(
			'extensions' => $extensions,
			'categories' => $categories,
		),
		200
	);
}

/**
 * POST /extensions/<slug>
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error
 */
function update_extension( WP_REST_Request $request ) {
	$slug       = (string) $request->get_param( 'slug' );
	$extensions = Extensions::all();

	if ( ! isset( $extensions[ $slug ] ) ) {
		return new WP_Error(
			'isudev_library_unknown_extension',
			\__( 'Unknown extension.', 'isudev-library' ),
			array( 'status' => 404 )
		);
	}

	if ( 'unavailable' === $extensions[ $slug ]['source'] ) {
		return new WP_Error(
			'isudev_library_extension_unavailable',
			\sprintf(
				/* translators: %s: name of the required plugin. */
				\__( 'This extension needs %s to be active.', 'isudev-library' ),
				(string) ( $extensions[ $slug ]['requires']['label'] ?? '' )
			),
			array( 'status' => 409 )
		);
	}

	if ( $extensions[ $slug ]['locked'] ) {
		return new WP_Error(
			'isudev_library_extension_locked',
			\__( 'This extension is managed in code and cannot be toggled here.', 'isudev-library' ),
			array( 'status' => 403 )
		);
	}

	$saved = save_toggle( Extensions::OPTION, $slug, (bool) $request->get_param( 'enabled' ) );
	if ( \is_wp_error( $saved ) ) {
		return $saved;
	}
	Extensions::flush();

	/*
	 * The new state takes effect on the next request, not this one: an
	 * extension's hooks are attached on init, long before this handler runs, and
	 * an extension just switched off has already registered everything it does.
	 */
	$refreshed = Extensions::all();

	return new WP_REST_Response( prepare_extension( $refreshed[ $slug ] ), 200 );
}
