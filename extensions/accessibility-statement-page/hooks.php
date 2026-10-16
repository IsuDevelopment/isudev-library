<?php
/**
 * Accessibility statement page: a page setting modelled on core's Privacy Policy page.
 *
 * Settings screen, post state, capability guard, block bindings source, meta tag and a
 * Site Health test. Everything else is in README.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\AccessibilityStatementPage;

use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const OPTION        = 'isudev_library_accessibility_statement_page';
const MENU_SLUG     = 'isudev-accessibility-statement';
const ACTION_SET    = 'isudev_accessibility_statement_set';
const ACTION_CREATE = 'isudev_accessibility_statement_create';
const SOURCE        = 'isudev/accessibility-statement';
const EDITOR_HANDLE = 'isudev-library-accessibility-statement';
const STATUSES      = array( 'publish', 'draft', 'pending', 'future', 'private' );
const REVIEW_AFTER  = 365 * 24 * 3600; // YEAR_IN_SECONDS; a literal so tools/check.php can load this file.

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'init', __NAMESPACE__ . '\\register_setting' );
	\add_action( 'init', __NAMESPACE__ . '\\register_binding_source' );

	\add_action( 'admin_menu', __NAMESPACE__ . '\\add_settings_page' );
	\add_action( 'admin_post_' . ACTION_SET, __NAMESPACE__ . '\\handle_set' );
	\add_action( 'admin_post_' . ACTION_CREATE, __NAMESPACE__ . '\\handle_create' );

	\add_filter( 'display_post_states', __NAMESPACE__ . '\\add_post_state', 10, 2 );
	\add_filter( 'map_meta_cap', __NAMESPACE__ . '\\map_meta_cap', 10, 4 );
	\add_action( 'deleted_post', __NAMESPACE__ . '\\forget_deleted_page', 10, 1 );

	\add_filter( 'render_block', __NAMESPACE__ . '\\hide_unresolved_binding', 10, 2 );
	\add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor' );
	\add_action( 'wp_head', __NAMESPACE__ . '\\print_meta_tag', 5 );

	\add_filter( 'site_status_tests', __NAMESPACE__ . '\\add_site_health_test' );
}

/**
 * Register the option so update_option() always runs it through sanitize_page_id().
 *
 * @return void
 */
function register_setting(): void {
	\register_setting(
		'isudev_library_accessibility_statement',
		OPTION,
		array(
			'type'              => 'integer',
			'description'       => \__( 'ID of the page with the accessibility statement.', 'isudev-library' ),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_page_id',
			'show_in_rest'      => false,
			'default'           => 0,
		)
	);
}

/**
 * Whether an ID points at a page that may be the statement (not trashed, not another post type).
 *
 * @param int $page_id Page ID.
 * @return bool
 */
function is_valid_page( int $page_id ): bool {
	$post = $page_id > 0 ? \get_post( $page_id ) : null;

	return $post instanceof \WP_Post && 'page' === $post->post_type && \in_array( $post->post_status, STATUSES, true );
}

/**
 * Option sanitizer: an existing page ID, or 0.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function sanitize_page_id( $value ): int {
	$page_id = \is_numeric( $value ) ? (int) $value : 0;

	return is_valid_page( $page_id ) ? $page_id : 0;
}

/**
 * The stored statement page ID, whatever its status. 0 when none.
 *
 * @return int
 */
function page_id(): int {
	$value = \get_option( OPTION, 0 );

	return \is_numeric( $value ) ? \max( 0, (int) $value ) : 0;
}

/**
 * The statement page, when it exists and is published.
 *
 * @return \WP_Post|null
 */
function published_page(): ?\WP_Post {
	$id   = page_id();
	$post = $id ? \get_post( $id ) : null;

	return $post instanceof \WP_Post && 'page' === $post->post_type && 'publish' === $post->post_status ? $post : null;
}

/**
 * Public URL of the statement page. Empty until it is published.
 *
 * @return string
 */
function url(): string {
	$post = published_page();
	$url  = $post ? (string) \get_permalink( $post ) : '';

	/**
	 * Filters the URL of the accessibility statement page.
	 *
	 * @param string $url     URL, empty when no page is published.
	 * @param int    $page_id Stored page ID (0 when none).
	 */
	$url = \apply_filters( 'isudev_library/extensions/accessibility_statement_page/url', $url, page_id() );

	return \is_string( $url ) ? $url : '';
}

/**
 * Title of the published statement page, as plain text. Empty until it is published.
 *
 * @return string
 */
function title(): string {
	$post = published_page();

	return $post ? \wp_strip_all_tags( \get_the_title( $post ) ) : '';
}

/**
 * Link to the statement page, wrapped in $before/$after. Empty until it is published.
 *
 * @param string $before Markup before the link.
 * @param string $after  Markup after the link.
 * @return string
 */
function statement_link( string $before = '', string $after = '' ): string {
	$url   = url();
	$title = title();
	$link  = '' !== $url && '' !== $title
		? \sprintf( '<a class="isudev-accessibility-statement-link" href="%s">%s</a>', \esc_url( $url ), \esc_html( $title ) )
		: '';

	/**
	 * Filters the accessibility statement link markup.
	 *
	 * @param string $link Link markup, empty when no page is published.
	 * @param string $url  Page URL.
	 */
	$link = \apply_filters( 'isudev_library/extensions/accessibility_statement_page/link', $link, $url );

	return \is_string( $link ) && '' !== $link ? $before . $link . $after : '';
}

/**
 * Whether the main query is for the statement page.
 *
 * @return bool
 */
function is_statement_page(): bool {
	global $wp_query;

	$id = page_id();

	return $id > 0 && $wp_query instanceof \WP_Query && $wp_query->is_page( $id );
}

/**
 * Extra capability needed to edit (`edit`) or delete (`delete`) the statement page.
 *
 * @param string $action `edit` or `delete`.
 * @return string Capability, or '' for none beyond core's.
 */
function required_capability( string $action ): string {
	/**
	 * Filters the capability needed, on top of core's page capabilities, to edit the
	 * accessibility statement page. Default '' (editors keep editing it). Return
	 * 'manage_options' to lock it like core's Privacy Policy page.
	 *
	 * @param string $capability Capability or ''.
	 */
	$edit = \apply_filters( 'isudev_library/extensions/accessibility_statement_page/edit_capability', '' );

	/**
	 * Filters the capability needed, on top of core's page capabilities, to trash or
	 * delete the accessibility statement page. Default 'manage_options'.
	 *
	 * @param string $capability Capability or ''.
	 */
	$delete = \apply_filters( 'isudev_library/extensions/accessibility_statement_page/delete_capability', 'manage_options' );

	$capability = 'edit' === $action ? $edit : $delete;

	return \is_string( $capability ) ? \sanitize_key( $capability ) : '';
}

/**
 * Require the extra capability for editing or deleting the statement page.
 *
 * Trashing goes through delete_post as well.
 *
 * @param array  $caps    Primitive capabilities.
 * @param string $cap     Meta capability.
 * @param int    $user_id User ID.
 * @param array  $args    Arguments; [0] is the post ID.
 * @return array
 */
function map_meta_cap( $caps, $cap, $user_id, $args ): array {
	$caps = \is_array( $caps ) ? $caps : array();

	$action = array(
		'edit_post'   => 'edit',
		'edit_page'   => 'edit',
		'delete_post' => 'delete',
		'delete_page' => 'delete',
	)[ $cap ] ?? '';

	if ( '' === $action || ! \is_array( $args ) || ! isset( $args[0] ) || ! \is_numeric( $args[0] ) ) {
		return $caps;
	}

	$id = page_id();

	if ( 0 === $id || (int) $args[0] !== $id ) {
		return $caps;
	}

	$extra = required_capability( $action );

	if ( '' !== $extra && ! \in_array( $extra, $caps, true ) ) {
		$caps[] = $extra;
	}

	return $caps;
}

/**
 * Clear the option once its page is deleted permanently. A trashed page keeps it, as core does.
 *
 * @param int $post_id Deleted post ID.
 * @return void
 */
function forget_deleted_page( $post_id ): void {
	if ( 0 < (int) $post_id && page_id() === (int) $post_id ) {
		\update_option( OPTION, 0 );
	}
}

/**
 * "Accessibility statement" next to the page title in the Pages list.
 *
 * @param array    $states Post states.
 * @param \WP_Post $post   Post.
 * @return array
 */
function add_post_state( $states, $post ): array {
	$states = \is_array( $states ) ? $states : array();

	if ( $post instanceof \WP_Post && 0 < $post->ID && page_id() === (int) $post->ID ) {
		$states['isudev_accessibility_statement'] = \_x( 'Accessibility statement', 'page label', 'isudev-library' );
	}

	return $states;
}

/**
 * Settings → Accessibility statement.
 *
 * @return void
 */
function add_settings_page(): void {
	\add_options_page(
		\__( 'Accessibility statement', 'isudev-library' ),
		\__( 'Accessibility statement', 'isudev-library' ),
		'manage_options',
		MENU_SLUG,
		__NAMESPACE__ . '\\render_settings_page'
	);
}

/**
 * URL of the settings screen, optionally with a result code.
 *
 * @param string $result Result code shown as a notice.
 * @return string
 */
function settings_url( string $result = '' ): string {
	$url = \admin_url( 'options-general.php?page=' . MENU_SLUG );

	return '' === $result ? $url : \add_query_arg( 'isudev_a11y', $result, $url );
}

/**
 * Templates offered by "Create a page".
 *
 * @return array<string,array{label:string,title:string,content:string}>
 */
function templates(): array {
	$data = array(
		'entity'    => \wp_specialchars_decode( (string) \get_option( 'blogname' ), ENT_QUOTES ),
		'site_name' => \wp_specialchars_decode( (string) \get_option( 'blogname' ), ENT_QUOTES ),
		'url'       => \home_url( '/' ),
		'date'      => \current_time( 'Y-m-d' ),
	);

	$templates = array(
		'pl' => array(
			'label'   => \__( 'Poland: public-sector declaration (gov.pl structure, in Polish)', 'isudev-library' ),
			'title'   => 'Deklaracja dostępności',
			'content' => template_pl( $data ),
		),
		'eu' => array(
			'label'   => \__( 'EU model accessibility statement', 'isudev-library' ),
			'title'   => \__( 'Accessibility statement', 'isudev-library' ),
			'content' => template_eu( $data ),
		),
	);

	/**
	 * Filters the templates offered when creating the accessibility statement page.
	 *
	 * @param array $templates Map of key => array( 'label', 'title', 'content' ) — content is block markup.
	 * @param array $data      Values the default templates were filled with: entity, site_name, url, date.
	 */
	$filtered = \apply_filters( 'isudev_library/extensions/accessibility_statement_page/templates', $templates, $data );

	if ( ! \is_array( $filtered ) ) {
		return $templates;
	}

	$valid = array();

	foreach ( $filtered as $key => $template ) {
		if ( \is_string( $key ) && \is_array( $template ) && \is_string( $template['label'] ?? null ) && \is_string( $template['title'] ?? null ) && \is_string( $template['content'] ?? null ) ) {
			$valid[ \sanitize_key( $key ) ] = $template;
		}
	}

	return $valid ? $valid : $templates;
}

/**
 * Template preselected on the settings screen: Polish for a Polish site, EU otherwise.
 *
 * @param array $templates Available templates.
 * @return string
 */
function default_template( array $templates ): string {
	$default = 0 === \strpos( \get_locale(), 'pl' ) ? 'pl' : 'eu';

	/**
	 * Filters the template preselected on Settings → Accessibility statement.
	 *
	 * @param string $default Template key. Default `pl` on a Polish site, `eu` otherwise.
	 */
	$default = \apply_filters( 'isudev_library/extensions/accessibility_statement_page/default_template', $default );

	if ( \is_string( $default ) && isset( $templates[ $default ] ) ) {
		return $default;
	}

	return (string) \array_key_first( $templates );
}

/**
 * End a request from someone who may not change the setting.
 *
 * @param string $action Nonce action.
 * @return void
 */
function verify_request( string $action ): void {
	if ( ! \current_user_can( 'manage_options' ) ) {
		\wp_die( \esc_html__( 'Sorry, you are not allowed to manage the accessibility statement.', 'isudev-library' ), '', array( 'response' => 403 ) );
	}

	\check_admin_referer( $action );
}

/**
 * Save the chosen page (admin-post.php).
 *
 * @return void
 */
function handle_set(): void {
	verify_request( ACTION_SET );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in verify_request().
	$page_id  = isset( $_POST['page_id'] ) ? \absint( \wp_unslash( $_POST['page_id'] ) ) : 0;
	$previous = page_id();

	if ( $page_id > 0 && ! is_valid_page( $page_id ) ) {
		\wp_safe_redirect( settings_url( 'invalid' ) );
		exit;
	}

	\update_option( OPTION, $page_id );

	if ( $page_id > 0 ) {
		$result = 'set';
	} else {
		$result = $previous ? 'cleared' : 'none';
	}

	\wp_safe_redirect( settings_url( $result ) );
	exit;
}

/**
 * Create a draft page from a template and open it in the editor (admin-post.php).
 *
 * @return void
 */
function handle_create(): void {
	verify_request( ACTION_CREATE );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in verify_request().
	$key       = isset( $_POST['template'] ) ? \sanitize_key( \wp_unslash( $_POST['template'] ) ) : '';
	$templates = templates();
	$template  = $templates[ $key ] ?? $templates[ default_template( $templates ) ];

	$page_id = \wp_insert_post(
		array(
			'post_title'   => $template['title'],
			'post_status'  => 'draft',
			'post_type'    => 'page',
			'post_content' => $template['content'],
		),
		true
	);

	if ( \is_wp_error( $page_id ) || ! $page_id ) {
		\wp_safe_redirect( settings_url( 'create-failed' ) );
		exit;
	}

	\update_option( OPTION, (int) $page_id );

	\wp_safe_redirect( \admin_url( 'post.php?post=' . (int) $page_id . '&action=edit' ) );
	exit;
}

/**
 * Notice for a result code passed back by the handlers.
 *
 * @return void
 */
function render_result_notice(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
	$code = isset( $_GET['isudev_a11y'] ) ? \sanitize_key( \wp_unslash( $_GET['isudev_a11y'] ) ) : '';

	$notices = array(
		'set'           => array( 'success', \__( 'Accessibility statement page updated.', 'isudev-library' ) ),
		'cleared'       => array( 'success', \__( 'Accessibility statement page removed.', 'isudev-library' ) ),
		'none'          => array( 'info', \__( 'No accessibility statement page is set.', 'isudev-library' ) ),
		'invalid'       => array( 'error', \__( 'That page cannot be used. Choose a page that is published or a draft.', 'isudev-library' ) ),
		'create-failed' => array( 'error', \__( 'The accessibility statement page could not be created.', 'isudev-library' ) ),
	);

	if ( isset( $notices[ $code ] ) ) {
		\wp_admin_notice(
			\esc_html( $notices[ $code ][1] ),
			array(
				'type'        => $notices[ $code ][0],
				'dismissible' => true,
			)
		);
	}
}

/**
 * Settings → Accessibility statement screen.
 *
 * @return void
 */
function render_settings_page(): void {
	if ( ! \current_user_can( 'manage_options' ) ) {
		return;
	}

	$page_id   = page_id();
	$page      = $page_id ? \get_post( $page_id ) : null;
	$templates = templates();
	$selected  = default_template( $templates );
	$exists    = $page instanceof \WP_Post && 'page' === $page->post_type && 'trash' !== $page->post_status;
	?>
	<div class="wrap">
		<h1><?php \esc_html_e( 'Accessibility statement', 'isudev-library' ); ?></h1>
		<?php
		render_result_notice();

		if ( $page_id && ! $page instanceof \WP_Post ) {
			\wp_admin_notice( \esc_html__( 'The selected accessibility statement page does not exist. Create or select another page.', 'isudev-library' ), array( 'type' => 'error' ) );
		} elseif ( $page instanceof \WP_Post && 'trash' === $page->post_status ) {
			\wp_admin_notice(
				\sprintf(
					/* translators: %s: URL of the Pages trash. */
					\__( 'The selected accessibility statement page is in the Trash. Create or select another page, or <a href="%s">restore it</a>.', 'isudev-library' ),
					\esc_url( \admin_url( 'edit.php?post_status=trash&post_type=page' ) )
				),
				array( 'type' => 'error' )
			);
		}
		?>
		<p><?php \esc_html_e( 'Choose the page that holds your accessibility statement, or create a draft from a template and complete it. Link to it from every page, usually in the footer.', 'isudev-library' ); ?></p>

		<?php if ( $exists ) : ?>
			<p>
				<strong>
				<?php
				$is_published = 'publish' === $page->post_status;
				echo \wp_kses(
					\sprintf(
						$is_published
							/* translators: 1: edit URL, 2: view URL. */
							? \__( '<a href="%1$s">Edit</a> or <a href="%2$s">view</a> the accessibility statement.', 'isudev-library' )
							/* translators: 1: edit URL, 2: preview URL. */
							: \__( '<a href="%1$s">Edit</a> or <a href="%2$s">preview</a> the accessibility statement. It is not published yet.', 'isudev-library' ),
						\esc_url( (string) \get_edit_post_link( $page, 'raw' ) ),
						\esc_url( $is_published ? (string) \get_permalink( $page ) : (string) \get_preview_post_link( $page ) )
					),
					array( 'a' => array( 'href' => true ) )
				);
				?>
				</strong>
			</p>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="isudev-a11y-template"><?php \esc_html_e( 'Create a new page', 'isudev-library' ); ?></label></th>
				<td>
					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="<?php echo \esc_attr( ACTION_CREATE ); ?>" />
						<?php \wp_nonce_field( ACTION_CREATE ); ?>
						<select id="isudev-a11y-template" name="template">
							<?php foreach ( $templates as $key => $template ) : ?>
								<option value="<?php echo \esc_attr( $key ); ?>" <?php \selected( $key, $selected ); ?>><?php echo \esc_html( $template['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php \submit_button( \__( 'Create draft', 'isudev-library' ), 'secondary', 'submit', false ); ?>
						<p class="description"><?php \esc_html_e( 'Creates a draft page pre-filled from the template and opens it in the editor. Replace every placeholder in square brackets before publishing.', 'isudev-library' ); ?></p>
					</form>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="isudev-a11y-page"><?php \esc_html_e( 'Use an existing page', 'isudev-library' ); ?></label></th>
				<td>
					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="<?php echo \esc_attr( ACTION_SET ); ?>" />
						<?php
						\wp_nonce_field( ACTION_SET );
						// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Core markup; the args are constants.
						\wp_dropdown_pages(
							array(
								'name'              => 'page_id',
								'id'                => 'isudev-a11y-page',
								'show_option_none'  => \esc_html__( '— Select —', 'isudev-library' ),
								'option_none_value' => '0',
								'selected'          => $exists ? (int) $page_id : 0,
								'post_status'       => STATUSES,
							)
						);
						// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
						\submit_button( \__( 'Use this page', 'isudev-library' ), 'primary', 'submit', false );
						?>
					</form>
				</td>
			</tr>
		</table>
	</div>
	<?php
}

/**
 * Field values the binding source offers, by key.
 *
 * @return array<string,string>
 */
function binding_values(): array {
	$url   = url();
	$title = title();

	return array(
		'url'   => $url,
		'title' => $title,
		'link'  => statement_link(),
	);
}

/**
 * Register the `isudev/accessibility-statement` block bindings source.
 *
 * @return void
 */
function register_binding_source(): void {
	if ( ! \function_exists( 'register_block_bindings_source' ) || \get_block_bindings_source( SOURCE ) ) {
		return;
	}

	\register_block_bindings_source(
		SOURCE,
		array(
			'label'              => \__( 'Accessibility statement', 'isudev-library' ),
			'get_value_callback' => __NAMESPACE__ . '\\get_binding_value',
		)
	);
}

/**
 * Value for a bound attribute: args.key is `url`, `title` or `link` (HTML, for rich text).
 *
 * @param array $source_args Binding args.
 * @return string|null Null keeps the block's own value.
 */
function get_binding_value( array $source_args ): ?string {
	$key    = \is_string( $source_args['key'] ?? null ) ? $source_args['key'] : 'url';
	$values = binding_values();

	return isset( $values[ $key ] ) && '' !== $values[ $key ] ? $values[ $key ] : null;
}

/**
 * Render nothing for a block bound to this source while no statement is published, so a
 * footer button never links to its placeholder.
 *
 * @param string $content Rendered block.
 * @param array  $block   Parsed block.
 * @return string
 */
function hide_unresolved_binding( $content, $block ): string {
	$content  = (string) $content;
	$bindings = \is_array( $block ) ? ( $block['attrs']['metadata']['bindings'] ?? null ) : null;

	if ( ! \is_array( $bindings ) ) {
		return $content;
	}

	foreach ( $bindings as $binding ) {
		if ( \is_array( $binding ) && SOURCE === ( $binding['source'] ?? '' ) ) {
			return '' === url() ? '' : $content;
		}
	}

	return $content;
}

/**
 * Editor side of the binding source: values and the field list for the Attributes panel.
 *
 * @return void
 */
function enqueue_editor(): void {
	\wp_enqueue_script(
		EDITOR_HANDLE,
		\plugins_url( 'editor.js', __FILE__ ),
		array( 'wp-blocks' ),
		VERSION,
		true
	);

	\wp_add_inline_script(
		EDITOR_HANDLE,
		'window.isudevAccessibilityStatement = ' . \wp_json_encode(
			array(
				'source' => SOURCE,
				'values' => binding_values(),
				'fields' => array(
					array(
						'label' => \__( 'Statement URL', 'isudev-library' ),
						'args'  => array( 'key' => 'url' ),
						'type'  => 'string',
					),
					array(
						'label' => \__( 'Statement title', 'isudev-library' ),
						'args'  => array( 'key' => 'title' ),
						'type'  => 'string',
					),
					array(
						'label' => \__( 'Link to the statement', 'isudev-library' ),
						'args'  => array( 'key' => 'link' ),
						'type'  => 'string',
					),
				),
			)
		) . ';',
		'before'
	);
}

/**
 * `<meta name="deklaracja-dostępności">` from the gov.pl technical conditions (good practice).
 *
 * @return void
 */
function print_meta_tag(): void {
	/**
	 * Filters whether the `deklaracja-dostępności` meta tag is printed in <head>.
	 *
	 * @param bool $print Default true on a Polish site (locale pl_*), false otherwise.
	 */
	$print = (bool) \apply_filters( 'isudev_library/extensions/accessibility_statement_page/meta_tag', 0 === \strpos( \get_locale(), 'pl' ) );
	$url   = $print ? url() : '';

	if ( '' !== $url ) {
		\printf( '<meta name="deklaracja-dostępności" content="%s" />' . "\n", \esc_url( $url ) );
	}
}

/**
 * Register the Site Health test.
 *
 * @param array $tests Site Health tests.
 * @return array
 */
function add_site_health_test( $tests ): array {
	$tests = \is_array( $tests ) ? $tests : array();

	$tests['direct']['isudev_accessibility_statement'] = array(
		'label' => \__( 'Accessibility statement', 'isudev-library' ),
		'test'  => __NAMESPACE__ . '\\site_health_test',
	);

	return $tests;
}

/**
 * Markers of unfinished template text.
 *
 * @return string[]
 */
function placeholder_markers(): array {
	/**
	 * Filters the strings that mark unfinished template text in a published statement.
	 *
	 * @param string[] $markers Default `[UZUPEŁNIJ`, `[FILL IN`.
	 */
	$markers = \apply_filters( 'isudev_library/extensions/accessibility_statement_page/placeholder_markers', array( '[UZUPEŁNIJ', '[FILL IN' ) );

	return \is_array( $markers ) ? \array_values( \array_filter( $markers, static fn( $m ): bool => \is_string( $m ) && '' !== $m ) ) : array();
}

/**
 * Site Health: the statement exists, is published, is finished and was reviewed within a year.
 *
 * @return array
 */
function site_health_test(): array {
	$settings = '<p><a href="' . \esc_url( settings_url() ) . '">' . \esc_html__( 'Manage the accessibility statement', 'isudev-library' ) . '</a></p>';
	$result   = array(
		'label'       => \__( 'An accessibility statement is published', 'isudev-library' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => \__( 'Accessibility', 'isudev-library' ),
			'color' => 'blue',
		),
		'description' => '<p>' . \esc_html__( 'Public-sector sites in the EU must publish an accessibility statement and review it at least once a year. Many other sites must describe the accessibility of their services under the European Accessibility Act.', 'isudev-library' ) . '</p>',
		'actions'     => $settings,
		'test'        => 'isudev_accessibility_statement',
	);

	$post = published_page();

	if ( ! $post ) {
		$result['status'] = 'recommended';
		$result['label']  = page_id()
			? \__( 'The accessibility statement page is not published', 'isudev-library' )
			: \__( 'No accessibility statement page is set', 'isudev-library' );

		return $result;
	}

	foreach ( placeholder_markers() as $marker ) {
		if ( false !== \strpos( $post->post_content, $marker ) ) {
			$result['status'] = 'recommended';
			$result['label']  = \__( 'The published accessibility statement still contains template placeholders', 'isudev-library' );

			return $result;
		}
	}

	$modified = (int) \get_post_modified_time( 'U', true, $post );

	if ( $modified > 0 && \time() - $modified > REVIEW_AFTER ) {
		$result['status'] = 'recommended';
		$result['label']  = \__( 'The accessibility statement has not been updated for over a year', 'isudev-library' );
	}

	return $result;
}
