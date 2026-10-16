<?php
/**
 * Checks for the pure parts of the client dashboard.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

$isudev_dash = dirname( __DIR__, 2 ) . '/includes/dashboard/';

require_once $isudev_dash . 'settings.php';
require_once $isudev_dash . 'content-overview.php';
require_once $isudev_dash . 'support.php';

use IsuDevLibrary\Dashboard;
use IsuDevLibrary\Dashboard\ContentOverview;
use IsuDevLibrary\Dashboard\Support;

$isudev_valid_email = static fn( string $email ): bool => false !== strpos( $email, '@' );

Checks::is( 'dash normalize: garbage gives defaults', Dashboard\normalize( 'x', $isudev_valid_email ), Dashboard\defaults() );
Checks::is( 'dash defaults: feature off', Dashboard\defaults()['enabled'], false );
Checks::is(
	'dash normalize: coerces and filters',
	Dashboard\normalize(
		array(
			'enabled'         => 1,
			'contentOverview' => array(
				'enabled'         => 0,
				'hiddenPostTypes' => array( 'page', 'page', 'Bad Slug', 7, 'product' ),
			),
			'support'         => array(
				'form'        => false,
				'clientEmail' => ' client@example.com ',
			),
		),
		$isudev_valid_email
	),
	array(
		'enabled'         => true,
		'contentOverview' => array(
			'enabled'         => false,
			'hiddenPostTypes' => array( 'page', 'product' ),
		),
		'support'         => array(
			'enabled'     => true,
			'form'        => false,
			'clientEmail' => 'client@example.com',
		),
	)
);
Checks::is( 'dash normalize: invalid email dropped', Dashboard\normalize( array( 'support' => array( 'clientEmail' => 'nope' ) ), $isudev_valid_email )['support']['clientEmail'], '' );

Checks::is(
	'dash order: posts, pages, others by position, media last',
	array_keys(
		ContentOverview\order_rows(
			array(
				'attachment' => array( 'position' => 0 ),
				'product'    => array( 'position' => 56 ),
				'event'      => array( 'position' => 25 ),
				'page'       => array( 'position' => 20 ),
				'post'       => array( 'position' => 5 ),
				'team'       => array( 'position' => 25 ),
			)
		)
	),
	array( 'post', 'page', 'event', 'team', 'product', 'attachment' )
);

Checks::is( 'dash icon: dashicon kept', ContentOverview\icon( 'dashicons-cart', 'product' ), array( 'type' => 'dashicon', 'value' => 'dashicons-cart' ) );
Checks::is( 'dash icon: svg data uri is an image', ContentOverview\icon( 'data:image/svg+xml;base64,AAA', 'x' )['type'], 'image' );
Checks::is( 'dash icon: url is an image', ContentOverview\icon( 'https://example.com/i.png', 'x' )['type'], 'image' );
Checks::is( 'dash icon: page fallback', ContentOverview\icon( null, 'page' )['value'], 'dashicons-admin-page' );
Checks::is( 'dash icon: junk falls back', ContentOverview\icon( 'javascript:alert(1)', 'event' )['value'], 'dashicons-admin-post' );

$isudev_mail = Support\mail_html(
	'Heading <b>',
	'Intro',
	array(
		'subject'     => 'Sub <script>',
		'type'        => 'Bug',
		'email'       => 'a@b.c',
		'description' => "Line 1\nLine <2>",
	),
	array( 'Site' => 'https://x.test/' )
);
Checks::is( 'dash mail: no raw script tag', str_contains( $isudev_mail, '<script>' ), false );
Checks::true( 'dash mail: subject escaped', str_contains( $isudev_mail, 'Sub &lt;script&gt;' ) );
Checks::true( 'dash mail: description keeps line breaks', str_contains( $isudev_mail, 'Line 1<br />' ) );
Checks::true( 'dash mail: description escaped', str_contains( $isudev_mail, 'Line &lt;2&gt;' ) );
