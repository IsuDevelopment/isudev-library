<?php
/**
 * Checks that the shipped Polish translation is complete and compiled.
 *
 * Fails after a string change until `npm run i18n:make-pot`, the .po update
 * and `npm run i18n:build` have run. See "Translations" in AGENTS.md.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

$isudev_lang_dir = dirname( __DIR__, 2 ) . '/languages/';

/**
 * Msgids of a PO/POT file, keyed by "context\4msgid", mapped to whether the
 * entry has a non-empty translation.
 *
 * @param string $file Path.
 * @return array<string,bool>
 */
function isudev_po_entries( string $file ): array {
	$entries = array();
	$blocks  = preg_split( '/\n\s*\n/', (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.

	foreach ( $blocks as $block ) {
		$fields  = array();
		$current = null;

		foreach ( explode( "\n", $block ) as $line ) {
			if ( preg_match( '/^(msgctxt|msgid|msgid_plural|msgstr(?:\[\d\])?) "(.*)"$/', $line, $m ) ) {
				$current            = $m[1];
				$fields[ $current ] = $m[2];
			} elseif ( null !== $current && preg_match( '/^"(.*)"$/', $line, $m ) ) {
				$fields[ $current ] .= $m[1];
			}
		}

		if ( empty( $fields['msgid'] ) ) {
			continue;
		}

		$key = isset( $fields['msgctxt'] ) ? $fields['msgctxt'] . "\4" . $fields['msgid'] : $fields['msgid'];

		$entries[ $key ] = '' !== ( $fields['msgstr'] ?? $fields['msgstr[0]'] ?? '' );
	}

	return $entries;
}

$isudev_pot = isudev_po_entries( $isudev_lang_dir . 'isudev-library.pot' );
$isudev_po  = isudev_po_entries( $isudev_lang_dir . 'isudev-library-pl_PL.po' );

Checks::is( 'i18n: pl_PL.po has every string from the .pot', array_values( array_diff( array_keys( $isudev_pot ), array_keys( $isudev_po ) ) ), array() );
Checks::is( 'i18n: every pl_PL string is translated', array_keys( array_filter( $isudev_po, static fn( $t ) => ! $t ) ), array() );

foreach ( array( 'isudev-library-pl_PL.mo', 'isudev-library-pl_PL.l10n.php' ) as $isudev_compiled ) {
	Checks::true(
		'i18n: compiled translation is newer than the .po — ' . $isudev_compiled,
		is_readable( $isudev_lang_dir . $isudev_compiled ) && filemtime( $isudev_lang_dir . $isudev_compiled ) >= filemtime( $isudev_lang_dir . 'isudev-library-pl_PL.po' )
	);
}

// The admin script's JSON, named by md5 of its build/ path as WordPress looks it up.
Checks::true(
	'i18n: editor JSON exists under the build/ script hash',
	is_readable( $isudev_lang_dir . 'isudev-library-pl_PL-' . md5( 'build/admin/index.js' ) . '.json' )
);
