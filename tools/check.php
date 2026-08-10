<?php
/**
 * Plain-PHP check runner for pure functions. No WordPress, no database.
 *
 * Usage: php tools/check.php
 * Exit code: 0 when every check passes, 1 otherwise.
 *
 * These checks cover functions that must not call WordPress. They will be
 * replaced by PHPUnit once a test harness exists (see spec §17).
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

/**
 * Minimal assertion collector.
 */
final class Checks {

	/**
	 * Number of passing checks.
	 *
	 * @var int
	 */
	public static $passed = 0;

	/**
	 * Failure messages.
	 *
	 * @var array
	 */
	public static $failed = array();

	/**
	 * Assert strict equality.
	 *
	 * @param string $label    Human-readable check name.
	 * @param mixed  $actual   Value produced by the code under test.
	 * @param mixed  $expected Expected value.
	 * @return void
	 */
	public static function is( string $label, $actual, $expected ): void {
		if ( $actual === $expected ) {
			++self::$passed;
			return;
		}

		self::$failed[] = sprintf(
			"%s\n    expected: %s\n    actual:   %s",
			$label,
			var_export( $expected, true ),
			var_export( $actual, true )
		);
	}

	/**
	 * Assert the value is boolean true.
	 *
	 * @param string $label  Human-readable check name.
	 * @param mixed  $actual Value produced by the code under test.
	 * @return void
	 */
	public static function true( string $label, $actual ): void {
		self::is( $label, $actual, true );
	}
}

// Plugin files guard on ABSPATH. Define it so they can be required standalone.
defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( __DIR__ ) . '/' );

/*
 * Minimal WordPress shims.
 *
 * These exist so utils that legitimately use WordPress escaping, translation and
 * filters can still be exercised without a WordPress bootstrap. They are close
 * enough for assertions about escaping and default behaviour, and nothing more:
 * apply_filters() returns its value untouched, so checks always see defaults.
 */
if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Escape a value for an HTML attribute.
	 *
	 * @param string $text Value to escape.
	 * @return string Escaped value.
	 */
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Escape a URL for output.
	 *
	 * @param string $url URL to escape.
	 * @return string Escaped URL.
	 */
	function esc_url( string $url ): string {
		return htmlspecialchars( strip_tags( $url ), ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Return a string unchanged, standing in for translation.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain. Ignored.
	 * @return string The text.
	 */
	function __( string $text, string $domain = 'default' ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Test-only shim for a WordPress function.
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Return the filtered value unchanged, standing in for the hook system.
	 *
	 * @param string $hook_name Hook name. Ignored.
	 * @param mixed  $value     Value to filter.
	 * @return mixed The value.
	 */
	function apply_filters( string $hook_name, $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Test-only shim for a WordPress function.
		unset( $hook_name );
		return $value;
	}
}

$files = glob( __DIR__ . '/checks/*.php' );
$files = is_array( $files ) ? $files : array();
sort( $files );

foreach ( $files as $file ) {
	require_once $file;
}

$failed = count( Checks::$failed );

foreach ( Checks::$failed as $message ) {
	fwrite( STDERR, 'FAIL  ' . $message . "\n" );
}

printf(
	"%d passed, %d failed (%d check files)\n",
	Checks::$passed,
	$failed,
	count( $files )
);

exit( $failed > 0 ? 1 : 0 );
