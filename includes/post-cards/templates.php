<?php
/**
 * Card template resolution: PHP templates, template parts and the plugin default.
 *
 * Lookup order and file contract: guides/post-cards.md → "Templates".
 *
 * @package IsuDevLibrary\PostCards
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\PostCards;

use const IsuDevLibrary\PATH;

defined( 'ABSPATH' ) || exit;

const PART_AREA   = 'isudev-card';
const PART_PREFIX = 'card-';
const PHP_DIR     = 'isudev/cards';

/**
 * Whether a string is a usable template slug. Pure.
 *
 * @param string $slug Candidate.
 * @return bool
 */
function is_valid_slug( string $slug ): bool {
	return 1 === \preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $slug );
}

/**
 * Slugs to try, most specific first. Pure.
 *
 * @param string $post_type Post type of the card.
 * @param string $variant   Variant name, '' for none.
 * @param string $preferred Slug returned by `isudev_library/post_card/template`, tried first.
 * @return string[]
 */
function candidate_slugs( string $post_type, string $variant, string $preferred = '' ): array {
	$slugs = array( $preferred );

	if ( '' !== $variant ) {
		$slugs[] = $post_type . '-' . $variant;
		$slugs[] = 'default-' . $variant;
	}

	$slugs[] = $post_type;
	$slugs[] = 'default';

	return \array_values( \array_unique( \array_filter( $slugs, __NAMESPACE__ . '\\is_valid_slug' ) ) );
}

/**
 * Split a template slug into post type and variant. Pure.
 *
 * Post types may contain hyphens, so the longest known post type prefix wins:
 * `customer-case-wide` with `customer-case` registered is that type, variant `wide`.
 *
 * @param string   $slug       Template slug.
 * @param string[] $post_types Known post type names.
 * @return array{postType:string,variant:string}|null Null when no known prefix matches.
 */
function split_slug( string $slug, array $post_types ): ?array {
	$prefixes = \array_merge( array( 'default' ), $post_types );

	\usort(
		$prefixes,
		static function ( string $a, string $b ): int {
			return \strlen( $b ) <=> \strlen( $a );
		}
	);

	foreach ( $prefixes as $prefix ) {
		if ( $slug === $prefix ) {
			return array(
				'postType' => $prefix,
				'variant'  => '',
			);
		}

		if ( 0 === \strpos( $slug, $prefix . '-' ) && \strlen( $slug ) > \strlen( $prefix ) + 1 ) {
			return array(
				'postType' => $prefix,
				'variant'  => (string) \substr( $slug, \strlen( $prefix ) + 1 ),
			);
		}
	}

	return null;
}

/**
 * Read the `Card:` header from a template's source. Pure.
 *
 * Accepts it in a PHP docblock (` * Card: Wide`), a line comment or an HTML
 * comment (`<!-- Card: Wide -->`), within the first 8 KB.
 *
 * @param string $source Template source.
 * @return string Label, '' when absent.
 */
function parse_card_label( string $source ): string {
	if ( 1 === \preg_match( '/^[ \t]*(?:<\?php)?[ \t]*(?:<!--|\/\*+|\*|\/\/|#)?[ \t]*Card:[ \t]*(.+?)[ \t]*(?:-->|\*\/)?[ \t]*$/mi', \substr( $source, 0, 8192 ), $match ) ) {
		return \trim( $match[1] );
	}

	return '';
}

/**
 * Human label for a variant without a header: `wide-image` → `Wide image`. Pure.
 *
 * @param string $variant Variant name.
 * @return string
 */
function humanize( string $variant ): string {
	return \ucfirst( \str_replace( array( '-', '_' ), ' ', $variant ) );
}

/*
 * WordPress adapters. Everything below may call WordPress functions.
 */

/**
 * Theme directories, child first.
 *
 * @return string[]
 */
function theme_dirs(): array {
	return \array_values( \array_unique( array( \get_stylesheet_directory(), \get_template_directory() ) ) );
}

/**
 * Absolute path of a PHP card template, child theme first.
 *
 * @param string $slug Template slug.
 * @return string '' when the theme has none.
 */
function find_php_template( string $slug ): string {
	return \locate_template( PHP_DIR . '/' . $slug . '.php' );
}

/**
 * A card template part, the Site Editor's saved version first.
 *
 * @param string $slug Template slug.
 * @return \WP_Block_Template|null
 */
function find_template_part( string $slug ): ?\WP_Block_Template {
	static $cache = array();

	if ( ! \array_key_exists( $slug, $cache ) ) {
		$part           = \get_block_template( \get_stylesheet() . '//' . PART_PREFIX . $slug, 'wp_template_part' );
		$cache[ $slug ] = $part instanceof \WP_Block_Template && '' !== \trim( (string) $part->content ) ? $part : null;
	}

	return $cache[ $slug ];
}

/**
 * Resolve the template for a card.
 *
 * @param string[] $slugs Output of candidate_slugs().
 * @return array{type:string,slug:string,path:string,content:string}
 *     `type` is `php`, `part` or `default`.
 */
function resolve_template( array $slugs ): array {
	foreach ( $slugs as $slug ) {
		$path = find_php_template( $slug );
		$part = find_template_part( $slug );

		if ( '' !== $path && null !== $part ) {
			report_duplicate( $slug );
		}

		if ( '' !== $path ) {
			return array(
				'type'    => 'php',
				'slug'    => $slug,
				'path'    => $path,
				'content' => '',
			);
		}

		if ( null !== $part ) {
			return array(
				'type'    => 'part',
				'slug'    => $slug,
				'path'    => '',
				'content' => (string) $part->content,
			);
		}
	}

	return array(
		'type'    => 'default',
		'slug'    => 'default',
		'path'    => '',
		'content' => default_template(),
	);
}

/**
 * The plugin's own card template.
 *
 * @return string Block markup.
 */
function default_template(): string {
	static $markup = null;

	if ( null === $markup ) {
		$file   = PATH . 'includes/post-cards/default-template.html';
		$markup = \is_readable( $file ) ? (string) \file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
	}

	return $markup;
}

/**
 * Warn once per slug that a PHP template shadows a template part.
 *
 * @param string $slug Template slug.
 * @return void
 */
function report_duplicate( string $slug ): void {
	static $reported = array();

	if ( isset( $reported[ $slug ] ) ) {
		return;
	}

	$reported[ $slug ] = true;

	\_doing_it_wrong(
		__FUNCTION__,
		\esc_html(
			\sprintf(
				'Card template "%1$s" exists as %2$s/%1$s.php and as the template part %3$s%1$s; the PHP template wins and Site Editor changes to the part are ignored. Keep one.',
				$slug,
				PHP_DIR,
				PART_PREFIX
			)
		),
		'1.19.0'
	);
}

/**
 * Card template part files in the theme, child first: slug => absolute path.
 *
 * @return array
 */
function theme_part_files(): array {
	$files = array();

	foreach ( theme_dirs() as $dir ) {
		$found = \glob( $dir . '/parts/' . PART_PREFIX . '*.html' );

		foreach ( \is_array( $found ) ? $found : array() as $path ) {
			$slug = (string) \substr( \basename( $path, '.html' ), \strlen( PART_PREFIX ) );

			if ( is_valid_slug( $slug ) && ! isset( $files[ $slug ] ) ) {
				$files[ $slug ] = $path;
			}
		}
	}

	return $files;
}

/**
 * PHP card templates in the theme, child first: slug => absolute path.
 *
 * @return array
 */
function theme_php_files(): array {
	$files = array();

	foreach ( theme_dirs() as $dir ) {
		$found = \glob( $dir . '/' . PHP_DIR . '/*.php' );

		foreach ( \is_array( $found ) ? $found : array() as $path ) {
			$slug = \basename( $path, '.php' );

			if ( is_valid_slug( $slug ) && ! isset( $files[ $slug ] ) ) {
				$files[ $slug ] = $path;
			}
		}
	}

	return $files;
}

/**
 * Read a template's `Card:` label.
 *
 * @param string $path Absolute path.
 * @return string
 */
function file_label( string $path ): string {
	$source = \is_readable( $path ) ? (string) \file_get_contents( $path, false, null, 0, 8192 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.

	return parse_card_label( $source );
}

/**
 * Every template the theme provides, for the editor's variant selects.
 *
 * Template parts created in the Site Editor count too: they live in the
 * database under the card area.
 *
 * @return array[] List of `{ slug, postType, variant, label, source }`.
 */
function available_templates(): array {
	$post_types = \array_keys( \get_post_types() );
	$found      = array();

	foreach ( theme_php_files() as $slug => $path ) {
		$found[ $slug ] = array(
			'slug'   => $slug,
			'label'  => file_label( $path ),
			'source' => 'php',
		);
	}

	$parts = \get_block_templates( array( 'area' => PART_AREA ), 'wp_template_part' );

	foreach ( \is_array( $parts ) ? $parts : array() as $part ) {
		if ( 0 !== \strpos( (string) $part->slug, PART_PREFIX ) ) {
			continue;
		}

		$slug = (string) \substr( (string) $part->slug, \strlen( PART_PREFIX ) );

		if ( ! is_valid_slug( $slug ) || isset( $found[ $slug ] ) ) {
			continue;
		}

		$found[ $slug ] = array(
			'slug'   => $slug,
			'label'  => (string) $part->title !== $part->slug ? (string) $part->title : '',
			'source' => 'part',
		);
	}

	$templates = array();

	foreach ( $found as $slug => $template ) {
		$split = split_slug( $slug, $post_types );

		if ( null === $split ) {
			continue;
		}

		if ( '' === $template['label'] ) {
			$template['label'] = '' === $split['variant'] ? humanize( $split['postType'] ) : humanize( $split['variant'] );
		}

		$templates[] = \array_merge( $template, $split );
	}

	return $templates;
}

/**
 * Register the "Cards" template part area.
 *
 * @param array $areas Areas from `default_wp_template_part_areas`.
 * @return array
 */
function register_part_area( array $areas ): array {
	$areas[] = array(
		'area'        => PART_AREA,
		'label'       => __( 'Cards', 'isudev-library' ),
		'description' => __( 'Post card templates used by the Post Card block, one per post type or variant.', 'isudev-library' ),
		'icon'        => 'layout',
		'area_tag'    => 'div',
	);

	return $areas;
}

/**
 * Declare theme `parts/card-*.html` files under the card area, so the Site
 * Editor lists them as cards without a theme.json entry.
 *
 * A part the theme already declares in theme.json is left as declared.
 *
 * @param \WP_Theme_JSON_Data $theme_json Theme origin data.
 * @return \WP_Theme_JSON_Data
 */
function declare_theme_parts( $theme_json ) {
	$files = theme_part_files();

	if ( array() === $files || ! \is_object( $theme_json ) || ! \method_exists( $theme_json, 'get_data' ) ) {
		return $theme_json;
	}

	$data     = $theme_json->get_data();
	$parts    = isset( $data['templateParts'] ) && \is_array( $data['templateParts'] ) ? $data['templateParts'] : array();
	$declared = \array_column( $parts, 'name' );

	foreach ( $files as $slug => $path ) {
		$name = PART_PREFIX . $slug;

		if ( \in_array( $name, $declared, true ) ) {
			continue;
		}

		$label   = file_label( $path );
		$parts[] = array(
			'name'  => $name,
			/* translators: %s: card template slug, e.g. "post-wide". */
			'title' => '' !== $label ? $label : \sprintf( __( 'Card: %s', 'isudev-library' ), $slug ),
			'area'  => PART_AREA,
		);
	}

	$data['templateParts'] = $parts;

	return $theme_json->update_with( $data );
}
