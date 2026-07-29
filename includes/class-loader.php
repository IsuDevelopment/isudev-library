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

			$block_dir = PATH . 'src/blocks/' . $slug . '/';

			foreach ( $block['bootstrap'] as $relative ) {
				$file = $block_dir . \ltrim( $relative, '/' );

				if ( ! \is_readable( $file ) ) {
					\_doing_it_wrong(
						__METHOD__,
						\esc_html( \sprintf( 'Block "%1$s" declares a missing bootstrap file: %2$s', $slug, $relative ) ),
						'1.0.0'
					);
					continue;
				}

				require_once $file;
			}

			if ( $block['variations'] ) {
				Variations\attach( $block['name'] );
			}

			$build_path = self::block_build_path( $slug );

			if ( ! \is_readable( $build_path . '/block.json' ) ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Block "%s" has no compiled metadata. Run npm run build.', $slug ) ),
					'1.0.0'
				);
				continue;
			}

			\register_block_type( $build_path );
		}
	}
}
