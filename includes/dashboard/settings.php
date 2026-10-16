<?php
/**
 * Client dashboard: settings and boot.
 *
 * The feature replaces nothing on its own: it adds two widgets to the admin
 * Dashboard — Content overview and Support — each switchable from the panel's
 * Dashboard tab. The pure functions above the "WordPress adapters" marker are
 * exercised by tools/check.php.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Dashboard;

defined( 'ABSPATH' ) || exit;

const OPTION        = 'isudev_library_dashboard';
const SUPPORT_EMAIL = 'support@isudev.com';
const SUPPORT_URL   = 'https://isudev.com';

/**
 * Default settings. Pure.
 *
 * The feature is off until someone turns it on, like an extension: it changes
 * the client's dashboard the moment it loads. Once on, both widgets and the
 * form are on, and every post type shows.
 *
 * @return array
 */
function defaults(): array {
	return array(
		'enabled'         => false,
		'contentOverview' => array(
			'enabled'         => true,
			'hiddenPostTypes' => array(),
		),
		'support'         => array(
			'enabled'     => true,
			'form'        => true,
			'clientEmail' => '',
		),
	);
}

/**
 * Sanitize a settings value, filling every key from the defaults. Pure.
 *
 * `clientEmail` is validated by the caller-supplied callback so this stays
 * free of WordPress; the adapter passes is_email().
 *
 * @param mixed    $value       Incoming value.
 * @param callable $valid_email Returns true for an acceptable address.
 * @return array
 */
function normalize( $value, callable $valid_email ): array {
	$value    = \is_array( $value ) ? $value : array();
	$defaults = defaults();

	$overview = \is_array( $value['contentOverview'] ?? null ) ? $value['contentOverview'] : array();
	$support  = \is_array( $value['support'] ?? null ) ? $value['support'] : array();

	$hidden = array();
	foreach ( (array) ( $overview['hiddenPostTypes'] ?? array() ) as $post_type ) {
		if ( \is_string( $post_type ) && 1 === \preg_match( '/^[a-z0-9_-]{1,20}$/', $post_type ) ) {
			$hidden[] = $post_type;
		}
	}

	$email = \is_string( $support['clientEmail'] ?? null ) ? \trim( $support['clientEmail'] ) : '';

	return array(
		'enabled'         => (bool) ( $value['enabled'] ?? $defaults['enabled'] ),
		'contentOverview' => array(
			'enabled'         => (bool) ( $overview['enabled'] ?? $defaults['contentOverview']['enabled'] ),
			'hiddenPostTypes' => \array_values( \array_unique( $hidden ) ),
		),
		'support'         => array(
			'enabled'     => (bool) ( $support['enabled'] ?? $defaults['support']['enabled'] ),
			'form'        => (bool) ( $support['form'] ?? $defaults['support']['form'] ),
			'clientEmail' => '' !== $email && $valid_email( $email ) ? $email : '',
		),
	);
}

/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Hook registration.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\register_widgets' );
	\add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
	\add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );
}

/**
 * Sanitize for storage.
 *
 * @param mixed $value Incoming value.
 * @return array
 */
function sanitize( $value ): array {
	return normalize( $value, static fn( string $email ): bool => false !== \is_email( $email ) );
}

/**
 * Current settings.
 *
 * @return array
 */
function get(): array {
	return sanitize( \get_option( OPTION, defaults() ) );
}

/**
 * Save settings.
 *
 * @param mixed $value Incoming value.
 * @return array The stored settings.
 */
function save( $value ): array {
	$settings = sanitize( $value );
	\update_option( OPTION, $settings, false );

	return $settings;
}

/**
 * Whether one widget is active: the feature and the widget both switched on.
 *
 * @param string $widget `contentOverview` or `support`.
 * @return bool
 */
function widget_enabled( string $widget ): bool {
	$settings = get();

	return $settings['enabled'] && ! empty( $settings[ $widget ]['enabled'] );
}

/**
 * Capability needed to see a widget.
 *
 * @param string $widget `contentOverview` or `support`.
 * @return string
 */
function widget_capability( string $widget ): string {
	/**
	 * Filters the capability needed to see a dashboard widget (and, for
	 * `support`, to send the form).
	 *
	 * @param string $capability Default 'edit_posts'.
	 * @param string $widget     `contentOverview` or `support`.
	 */
	$capability = \apply_filters( 'isudev_library/dashboard/capability', 'edit_posts', $widget );

	return \is_string( $capability ) && '' !== $capability ? $capability : 'edit_posts';
}

/**
 * Support contact shown in the widget and used as the form recipient.
 *
 * @return array{email:string,url:string,label:string}
 */
function support_contact(): array {
	$contact = array(
		'email' => SUPPORT_EMAIL,
		'url'   => SUPPORT_URL,
		'label' => 'isudev.com',
	);

	/**
	 * Filters the developer's support contact: `email`, `url` and the link `label`.
	 *
	 * @param array $contact Default support@isudev.com / https://isudev.com.
	 */
	$filtered = \apply_filters( 'isudev_library/dashboard/support_contact', $contact );
	$filtered = \is_array( $filtered ) ? $filtered : array();

	$email = \is_string( $filtered['email'] ?? null ) && \is_email( $filtered['email'] ) ? $filtered['email'] : $contact['email'];
	$url   = \is_string( $filtered['url'] ?? null ) && '' !== \esc_url_raw( $filtered['url'] ) ? \esc_url_raw( $filtered['url'] ) : $contact['url'];
	$label = \is_string( $filtered['label'] ?? null ) && '' !== $filtered['label'] ? $filtered['label'] : $contact['label'];

	return array(
		'email' => $email,
		'url'   => $url,
		'label' => $label,
	);
}

/**
 * Register the active widgets for users allowed to see them.
 *
 * @return void
 */
function register_widgets(): void {
	if ( widget_enabled( 'contentOverview' ) && \current_user_can( widget_capability( 'contentOverview' ) ) ) {
		\wp_add_dashboard_widget(
			'isudev-content-overview',
			\esc_html__( 'Content overview', 'isudev-library' ),
			__NAMESPACE__ . '\\ContentOverview\\render'
		);
	}

	if ( widget_enabled( 'support' ) && \current_user_can( widget_capability( 'support' ) ) ) {
		\wp_add_dashboard_widget(
			'isudev-support',
			// Plain text: core reuses the title in Screen Options and in the box's aria labels. The icon is CSS.
			\esc_html__( 'Support', 'isudev-library' ),
			__NAMESPACE__ . '\\Support\\render',
			null,
			null,
			'side'
		);
	}
}

/**
 * Widget styles and the form script, on the dashboard only.
 *
 * @param string $hook_suffix Admin page.
 * @return void
 */
function enqueue_assets( string $hook_suffix ): void {
	if ( 'index.php' !== $hook_suffix ) {
		return;
	}

	$overview = widget_enabled( 'contentOverview' );
	$support  = widget_enabled( 'support' );

	if ( ! $overview && ! $support ) {
		return;
	}

	\wp_enqueue_style(
		'isudev-library-dashboard',
		\IsuDevLibrary\URL . 'assets/dashboard/dashboard.css',
		array( 'dashicons' ),
		\IsuDevLibrary\VERSION
	);

	if ( $support && get()['support']['form'] ) {
		Support\enqueue_script();
	}
}

/**
 * REST routes: the panel's settings and the support form.
 *
 * @return void
 */
function register_routes(): void {
	\register_rest_route(
		'isudev-library/v1',
		'/dashboard',
		array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => __NAMESPACE__ . '\\rest_get',
				'permission_callback' => '\\IsuDevLibrary\\REST\\permission_check',
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => __NAMESPACE__ . '\\rest_save',
				'permission_callback' => '\\IsuDevLibrary\\REST\\permission_check',
			),
		)
	);

	\register_rest_route(
		'isudev-library/v1',
		'/dashboard/support',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\Support\\rest_send',
			'permission_callback' => static fn(): bool => widget_enabled( 'support' ) && get()['support']['form'] && \current_user_can( widget_capability( 'support' ) ),
			'args'                => Support\request_args(),
		)
	);
}

/**
 * Settings payload for the panel: the settings plus the post types to choose from.
 *
 * @return \WP_REST_Response
 */
function rest_get(): \WP_REST_Response {
	$post_types = array();

	foreach ( ContentOverview\candidate_post_types() as $object ) {
		$post_types[] = array(
			'slug'  => $object->name,
			'label' => (string) $object->labels->name,
		);
	}

	return new \WP_REST_Response(
		array(
			'settings'   => get(),
			'postTypes'  => $post_types,
			'support'    => support_contact(),
			'adminEmail' => (string) \get_option( 'admin_email' ),
		),
		200
	);
}

/**
 * Save the settings from the panel.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response
 */
function rest_save( \WP_REST_Request $request ): \WP_REST_Response {
	$params = $request->get_json_params();

	return new \WP_REST_Response( array( 'settings' => save( \is_array( $params ) ? ( $params['settings'] ?? array() ) : array() ) ), 200 );
}
