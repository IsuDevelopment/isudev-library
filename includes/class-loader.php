<?php
/**
 * Block loader. The only place in this plugin that calls register_block_type().
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary;

defined( 'ABSPATH' ) || exit;

/**
 * Registers enabled blocks and boots their PHP.
 */
class Loader {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function boot(): void {
		// Priority 5: before the default 10, so blocks exist for anything that
		// inspects the registry on init.
		\add_action( 'init', array( __CLASS__, 'register' ), 5 );
	}

	/**
	 * Absolute path to a block's compiled metadata directory.
	 *
	 * This maps `src/blocks/<slug>/` to `build/blocks/<slug>/` because wp-scripts
	 * derives the entry name from the path relative to the source directory.
	 *
	 * @param string $slug Block slug.
	 * @return string
	 */
	public static function block_build_path( string $slug ): string {
		return PATH . 'build/blocks/' . $slug;
	}

	/**
	 * Register every enabled block.
	 *
	 * @return void
	 */
	public static function register(): void {
		$manifest = PATH . 'build/blocks-manifest.php';

		// Metadata collection is a read optimisation only: it caches block.json
		// contents, it does not register anything. Safe to skip when absent.
		if ( \function_exists( 'wp_register_block_metadata_collection' ) && \is_readable( $manifest ) ) {
			\wp_register_block_metadata_collection( PATH . 'build', $manifest );
		}

		foreach ( Registry::blocks() as $slug => $block ) {
			if ( ! $block['enabled'] ) {
				continue;
			}

			$build_path = self::block_build_path( $slug );

			/*
			 * Bail before running any of the block's side effects. Requiring its
			 * bootstrap files or attaching its variations for a block that then
			 * cannot be registered would leave half-initialised state behind:
			 * a bootstrap file that adds a REST route or a filter assuming its
			 * own block type exists would still have run.
			 */
			if ( ! \is_readable( $build_path . '/block.json' ) ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Block "%s" has no compiled metadata. Run npm run build.', $slug ) ),
					'1.0.0'
				);
				continue;
			}

			$block_dir = PATH . 'src/blocks/' . $slug . '/';

			foreach ( $block['bootstrap'] as $relative ) {
				$file = self::contained_path( $block_dir, $relative );

				if ( '' === $file ) {
					\_doing_it_wrong(
						__METHOD__,
						\esc_html( \sprintf( 'Block "%1$s" declares a bootstrap file that is missing or outside its own directory: %2$s', $slug, $relative ) ),
						'1.0.0'
					);
					continue;
				}

				require_once $file;
			}

			if ( $block['variations'] ) {
				Variations\attach( $block['name'] );
			}

			\register_block_type( $build_path );
		}
	}

	/**
	 * Resolve a path relative to a directory, refusing anything that escapes it.
	 *
	 * Descriptor `bootstrap` entries are first-party, so this is not a security
	 * boundary — anyone who can edit `block.php` can already run code. It exists
	 * to catch a mistyped relative path, which would otherwise silently load a
	 * different block's file.
	 *
	 * Public because it is exercised directly by tools/checks/35-loader.php.
	 *
	 * @param string $root     Absolute directory the path must stay inside, with trailing slash.
	 * @param string $relative Path declared in the descriptor, relative to $root.
	 * @return string Absolute readable path, or '' when missing or out of bounds.
	 */
	public static function contained_path( string $root, string $relative ): string {
		$resolved = \realpath( $root . \ltrim( $relative, '/' ) );
		$base     = \realpath( $root );

		if ( false === $resolved || false === $base ) {
			return '';
		}

		// The separator matters: it stops `blocks/site-header-evil` from passing
		// a prefix test against `blocks/site-header`.
		if ( 0 !== \strpos( $resolved, $base . \DIRECTORY_SEPARATOR ) ) {
			return '';
		}

		return \is_readable( $resolved ) ? $resolved : '';
	}
}
