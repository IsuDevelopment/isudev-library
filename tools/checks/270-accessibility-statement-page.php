<?php
/**
 * Checks for the accessibility statement templates (extensions/accessibility-statement-page/template.php).
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Escape for HTML text.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( string $text ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Test-only shim for a WordPress function.
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Translate (unchanged) and escape for HTML text.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain. Ignored.
	 * @return string
	 */
	function esc_html__( string $text, string $domain = 'default' ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Test-only shim for a WordPress function.
		unset( $domain );
		return esc_html( $text );
	}
}

require_once dirname( __DIR__, 2 ) . '/extensions/accessibility-statement-page/template.php';

use function IsuDevLibrary\Extensions\AccessibilityStatementPage\template_data;
use function IsuDevLibrary\Extensions\AccessibilityStatementPage\template_eu;
use function IsuDevLibrary\Extensions\AccessibilityStatementPage\template_pl;

$isudev_a11y_data = array(
	'entity'    => 'Urząd <b>Gminy</b> & "Test"',
	'site_name' => 'Gmina Test',
	'url'       => 'https://example.test/',
	'date'      => '2026-10-16',
);
$isudev_a11y_pl   = template_pl( $isudev_a11y_data );
$isudev_a11y_eu   = template_eu( $isudev_a11y_data );

// Every gov.pl identifier appears exactly once (a11y-ocena and a11y-audytor are optional).
foreach ( array( 'a11y-wstep', 'a11y-podmiot', 'a11y-url', 'a11y-data-publikacja', 'a11y-data-aktualizacja', 'a11y-status', 'a11y-data-sporzadzenie', 'a11y-kontakt', 'a11y-osoba', 'a11y-email', 'a11y-telefon', 'a11y-procedura', 'a11y-architektura', 'a11y-aplikacje' ) as $isudev_a11y_id ) {
	Checks::is( 'a11y statement pl: id ' . $isudev_a11y_id . ' once', substr_count( $isudev_a11y_pl, 'id="' . $isudev_a11y_id . '"' ), 1 );
}

// Each block anchor matches an id on the element that follows it.
preg_match_all( '/\{[^}]*"anchor":"([a-z0-9-]+)"[^}]*\} -->\n<[a-z0-9]+[^>]* id="([a-z0-9-]+)"/', $isudev_a11y_pl, $isudev_a11y_anchors, PREG_SET_ORDER );
Checks::is( 'a11y statement pl: six block anchors', count( $isudev_a11y_anchors ), 6 );
foreach ( $isudev_a11y_anchors as $isudev_a11y_anchor ) {
	Checks::is( 'a11y statement pl: anchor ' . $isudev_a11y_anchor[1] . ' matches its id', $isudev_a11y_anchor[2], $isudev_a11y_anchor[1] );
}

foreach ( array( 'pl' => $isudev_a11y_pl, 'eu' => $isudev_a11y_eu ) as $isudev_a11y_key => $isudev_a11y_markup ) {
	Checks::is(
		'a11y statement ' . $isudev_a11y_key . ': block delimiters balance',
		preg_match_all( '/<!-- wp:[a-z-]+/', $isudev_a11y_markup ),
		preg_match_all( '/<!-- \/wp:[a-z-]+ -->/', $isudev_a11y_markup )
	);
	Checks::true( 'a11y statement ' . $isudev_a11y_key . ': entity is escaped', false !== strpos( $isudev_a11y_markup, 'Urząd &lt;b&gt;Gminy&lt;/b&gt; &amp; &quot;Test&quot;' ) && false === strpos( $isudev_a11y_markup, '<b>' ) );
	Checks::true( 'a11y statement ' . $isudev_a11y_key . ': links the site', false !== strpos( $isudev_a11y_markup, 'href="https://example.test/"' ) );
	Checks::true( 'a11y statement ' . $isudev_a11y_key . ': no h1 (the page title is the h1)', false === strpos( $isudev_a11y_markup, '<h1' ) );
}

Checks::true(
	'a11y statement pl: mandatory intro formula',
	false !== strpos( $isudev_a11y_pl, ' zobowiązuje się zapewnić dostępność swojej strony internetowej zgodnie z przepisami ustawy z dnia 4 kwietnia 2019 r. o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych. Oświadczenie w sprawie dostępności ma zastosowanie do strony internetowej ' )
);
Checks::true(
	'a11y statement pl: status formula',
	false !== strpos( $isudev_a11y_pl, '<p id="a11y-status">Strona internetowa jest częściowo zgodna z ustawą z dnia 4 kwietnia 2019 r. o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych z powodu niezgodności lub wyłączeń wymienionych poniżej.</p>' )
);
Checks::true( 'a11y statement pl: preparation date filled', false !== strpos( $isudev_a11y_pl, '<span id="a11y-data-sporzadzenie">2026-10-16</span>' ) );
Checks::true( 'a11y statement pl: 7 days and 2 months', false !== strpos( $isudev_a11y_pl, 'w ciągu 7 dni' ) && false !== strpos( $isudev_a11y_pl, 'dłuższy niż 2 miesiące' ) );
Checks::true( 'a11y statement pl: links the Ombudsman', false !== strpos( $isudev_a11y_pl, 'href="https://bip.brpo.gov.pl/"' ) );
Checks::true( 'a11y statement pl: placeholders marked', false !== strpos( $isudev_a11y_pl, '[UZUPEŁNIJ: ' ) );
Checks::true( 'a11y statement eu: placeholders marked', false !== strpos( $isudev_a11y_eu, '[FILL IN: ' ) );
Checks::true( 'a11y statement eu: no gov.pl ids', false === strpos( $isudev_a11y_eu, 'a11y-' ) );

// template_data(): a malformed date falls back to the placeholder, non-strings to ''.
Checks::is( 'a11y statement data: bad date dropped', template_data( array( 'date' => '16.10.2026' ) )['date'], '' );
Checks::is( 'a11y statement data: non-string entity dropped', template_data( array( 'entity' => array( 'x' ) ) )['entity'], '' );
Checks::true( 'a11y statement pl: empty data still yields placeholders', false !== strpos( template_pl( array() ), '<span id="a11y-podmiot">[UZUPEŁNIJ: nazwa podmiotu publicznego]</span>' ) );
