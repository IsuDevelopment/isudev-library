<?php
/**
 * Site logo field on Settings → General.
 *
 * The field is a second door onto the theme's `custom_logo` theme mod, not a
 * second place to store a logo: saving writes the theme mod, and reading
 * prefers it. Anything already reading `custom_logo` — `get_custom_logo()`,
 * `core/site-logo` — keeps working untouched.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Extensions\SiteLogoOption;

defined( 'ABSPATH' ) || exit;

const OPTION = 'isudev_library_website_logo';

/**
 * Hook registration. Called by Extensions::load() when this extension is enabled.
 *
 * @return void
 */
function boot(): void {
	\add_action( 'admin_init', __NAMESPACE__ . '\\register_field' );
	\add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );
}

/**
 * Register the setting and its field.
 *
 * @return void
 */
function register_field(): void {
	\register_setting(
		'general',
		OPTION,
		array(
			'type'              => 'integer',
			'default'           => 0,
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize',
			'show_in_rest'      => false,
		)
	);

	\add_settings_field(
		OPTION,
		\__( 'Site logo', 'isudev-library' ),
		__NAMESPACE__ . '\\render_field',
		'general',
		'default',
		array( 'label_for' => 'isudev-website-logo-id' )
	);
}

/**
 * Store the chosen attachment and mirror it into the theme mod.
 *
 * @param mixed $value Submitted attachment ID.
 * @return int
 */
function sanitize( $value ): int {
	$logo_id = \absint( $value );

	// 0 means "no logo": remove_theme_mod, not set_theme_mod( 0 ), so the theme
	// falls back exactly as it would had a logo never been set.
	if ( 0 === $logo_id ) {
		\remove_theme_mod( 'custom_logo' );

		return 0;
	}

	\set_theme_mod( 'custom_logo', $logo_id );

	return $logo_id;
}

/**
 * The attachment ID currently in use.
 *
 * The theme mod wins: the site editor and the customizer both write it, and
 * this option is only a mirror of what they last set.
 *
 * @return int
 */
function current_logo_id(): int {
	$from_theme = \absint( \get_theme_mod( 'custom_logo', 0 ) );

	return $from_theme > 0 ? $from_theme : \absint( \get_option( OPTION, 0 ) );
}

/**
 * Render the field.
 *
 * @return void
 */
function render_field(): void {
	$logo_id = current_logo_id();
	$src     = $logo_id > 0 ? \wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	?>
	<div class="isudev-website-logo">
		<input
			type="hidden"
			id="isudev-website-logo-id"
			name="<?php echo \esc_attr( OPTION ); ?>"
			value="<?php echo \esc_attr( (string) $logo_id ); ?>"
		/>

		<div id="isudev-website-logo-preview" style="margin-block-end: 0.5rem;">
			<?php if ( \is_string( $src ) && '' !== $src ) : ?>
				<img src="<?php echo \esc_url( $src ); ?>" alt="" style="display: block; max-width: 240px; height: auto;" />
			<?php endif; ?>
		</div>

		<button type="button" class="button" id="isudev-website-logo-select">
			<?php \esc_html_e( 'Select logo', 'isudev-library' ); ?>
		</button>

		<button type="button" class="button" id="isudev-website-logo-remove" <?php \disabled( 0, $logo_id ); ?>>
			<?php \esc_html_e( 'Remove logo', 'isudev-library' ); ?>
		</button>

		<p class="description">
			<?php \esc_html_e( 'The same logo the site editor uses. Changing it here changes it everywhere.', 'isudev-library' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Load the media modal and the picker script on Settings → General only.
 *
 * @param string $hook_suffix Current admin page.
 * @return void
 */
function enqueue( string $hook_suffix ): void {
	if ( 'options-general.php' !== $hook_suffix ) {
		return;
	}

	\wp_enqueue_media();

	// A handle with no src: this is a few lines against an admin page that
	// already loads the media library, not a file worth a second request.
	\wp_register_script( 'isudev-website-logo', false, array( 'media-editor', 'media-views' ), \IsuDevLibrary\VERSION, true );
	\wp_enqueue_script( 'isudev-website-logo' );

	\wp_add_inline_script(
		'isudev-website-logo',
		\sprintf( 'window.isudevWebsiteLogoL10n = %s;', (string) \wp_json_encode( l10n() ) ),
		'before'
	);

	\wp_add_inline_script( 'isudev-website-logo', script() );
}

/**
 * Strings the picker script needs.
 *
 * @return array
 */
function l10n(): array {
	return array(
		'title'  => \__( 'Select logo', 'isudev-library' ),
		'button' => \__( 'Use as site logo', 'isudev-library' ),
	);
}

/**
 * The picker script.
 *
 * @return string
 */
function script(): string {
	return <<<'JS'
( function () {
	var input = document.getElementById( 'isudev-website-logo-id' );
	var preview = document.getElementById( 'isudev-website-logo-preview' );
	var select = document.getElementById( 'isudev-website-logo-select' );
	var remove = document.getElementById( 'isudev-website-logo-remove' );

	if ( ! input || ! preview || ! select || ! remove || ! window.wp || ! window.wp.media ) {
		return;
	}

	var l10n = window.isudevWebsiteLogoL10n || {};
	var frame;

	select.addEventListener( 'click', function () {
		if ( ! frame ) {
			frame = window.wp.media( {
				title: l10n.title,
				button: { text: l10n.button },
				library: { type: 'image' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var sizes = attachment.sizes || {};
				var image = document.createElement( 'img' );

				image.src = sizes.medium ? sizes.medium.url : attachment.url;
				image.alt = '';
				image.style.display = 'block';
				image.style.maxWidth = '240px';
				image.style.height = 'auto';

				input.value = attachment.id;
				preview.replaceChildren( image );
				remove.disabled = false;
			} );
		}

		frame.open();
	} );

	remove.addEventListener( 'click', function () {
		input.value = '0';
		preview.replaceChildren();
		remove.disabled = true;
	} );
}() );
JS;
}
