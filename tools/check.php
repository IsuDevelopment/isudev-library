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
