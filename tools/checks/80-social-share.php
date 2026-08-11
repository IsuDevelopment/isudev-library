<?php
/**
 * Checks for the pure parts of the social share network button block.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/src/blocks/social-share-network/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\SocialShareNetwork\default_label;
use function IsuDevLibrary\Blocks\SocialShareNetwork\icon_name;
use function IsuDevLibrary\Blocks\SocialShareNetwork\is_action_network;
use function IsuDevLibrary\Blocks\SocialShareNetwork\network_classes;
use function IsuDevLibrary\Blocks\SocialShareNetwork\share_url;

$permalink = 'https://example.com/post/';
$title     = 'Hello World';

/*
 * share_url(): the nine networks whose share URL just needs the permalink
 * (and sometimes the title) percent-encoded into a fixed host. Checking that
 * the raw permalink never appears literally in the query string is the part
 * that silently breaks if rawurlencode() is ever swapped for urlencode() or
 * dropped altogether.
 */
$encoded_permalink = rawurlencode( $permalink );
$encoded_title      = rawurlencode( $title );

$facebook_url = share_url( 'facebook', $permalink, $title );
Checks::is( 'share_url: facebook starts with the expected host', 0 === strpos( $facebook_url, 'https://www.facebook.com/sharer/sharer.php?' ), true );
Checks::is( 'share_url: facebook percent-encodes the permalink', strpos( $facebook_url, $encoded_permalink ) !== false, true );
Checks::is( 'share_url: facebook never contains the raw permalink', strpos( $facebook_url, $permalink ) === false, true );

$x_url = share_url( 'x', $permalink, $title );
Checks::is( 'share_url: x starts with the expected host', 0 === strpos( $x_url, 'https://x.com/intent/post?' ), true );
Checks::is( 'share_url: x percent-encodes the permalink', strpos( $x_url, $encoded_permalink ) !== false, true );
Checks::is( 'share_url: x percent-encodes the title', strpos( $x_url, $encoded_title ) !== false, true );
Checks::is( 'share_url: x never contains the raw permalink', strpos( $x_url, $permalink ) === false, true );

$linkedin_url = share_url( 'linkedin', $permalink, $title );
Checks::is( 'share_url: linkedin starts with the expected host', 0 === strpos( $linkedin_url, 'https://www.linkedin.com/sharing/share-offsite/?' ), true );
Checks::is( 'share_url: linkedin percent-encodes the permalink', strpos( $linkedin_url, $encoded_permalink ) !== false, true );
Checks::is( 'share_url: linkedin never contains the raw permalink', strpos( $linkedin_url, $permalink ) === false, true );

$whatsapp_url = share_url( 'whatsapp', $permalink, $title );
Checks::is( 'share_url: whatsapp starts with the expected host', 0 === strpos( $whatsapp_url, 'https://wa.me/?text=' ), true );
Checks::is( 'share_url: whatsapp percent-encodes the permalink', strpos( $whatsapp_url, $encoded_permalink ) !== false, true );
Checks::is( 'share_url: whatsapp never contains the raw permalink', strpos( $whatsapp_url, $permalink ) === false, true );

$bluesky_url = share_url( 'bluesky', $permalink, $title );
Checks::is( 'share_url: bluesky starts with the expected host', 0 === strpos( $bluesky_url, 'https://bsky.app/intent/compose?text=' ), true );
Checks::is( 'share_url: bluesky percent-encodes the permalink', strpos( $bluesky_url, $encoded_permalink ) !== false, true );
Checks::is( 'share_url: bluesky never contains the raw permalink', strpos( $bluesky_url, $permalink ) === false, true );

$threads_url = share_url( 'threads', $permalink, $title );
Checks::is( 'share_url: threads starts with the expected host', 0 === strpos( $threads_url, 'https://www.threads.net/intent/post?text=' ), true );
Checks::is( 'share_url: threads percent-encodes the permalink', strpos( $threads_url, $encoded_permalink ) !== false, true );
Checks::is( 'share_url: threads never contains the raw permalink', strpos( $threads_url, $permalink ) === false, true );

$mastodon_url = share_url( 'mastodon', $permalink, $title );
Checks::is( 'share_url: mastodon starts with the expected host', 0 === strpos( $mastodon_url, 'https://mastodon.social/share?text=' ), true );
Checks::is( 'share_url: mastodon percent-encodes the permalink', strpos( $mastodon_url, $encoded_permalink ) !== false, true );
Checks::is( 'share_url: mastodon never contains the raw permalink', strpos( $mastodon_url, $permalink ) === false, true );

$substack_url = share_url( 'substack', $permalink, $title );
Checks::is( 'share_url: substack starts with the expected host', 0 === strpos( $substack_url, 'https://substack.com/share?url=' ), true );
Checks::is( 'share_url: substack percent-encodes the permalink', strpos( $substack_url, $encoded_permalink ) !== false, true );
Checks::is( 'share_url: substack never contains the raw permalink', strpos( $substack_url, $permalink ) === false, true );

/*
 * share_url( 'email', … ): templates are resolved by render.php and handed in
 * already containing %title%/%url% placeholders; share_url() substitutes them
 * and builds the mailto: URL.
 */
$email_url = share_url(
	'email',
	$permalink,
	$title,
	array(
		'subject' => 'New post: %title%',
		'body'    => 'Read %title% at %url%',
	)
);
Checks::is( 'share_url: email yields a mailto: URL', 0 === strpos( $email_url, 'mailto:?' ), true );
Checks::is( 'share_url: email substitutes %title% into the subject', strpos( $email_url, rawurlencode( 'New post: Hello World' ) ) !== false, true );
Checks::is( 'share_url: email substitutes %title% and %url% into the body', strpos( $email_url, rawurlencode( 'Read Hello World at ' . $permalink ) ) !== false, true );

/*
 * share_url( 'email', … ) without templates still resolves to a mailto: URL
 * using the built-in translated defaults, rather than an empty string.
 */
Checks::is( 'share_url: email without templates still yields a mailto: URL', 0 === strpos( share_url( 'email', $permalink, $title ), 'mailto:?' ), true );

/*
 * link, system and print: rendered as <button>, not <a>, so their "URL" is
 * either the raw permalink (unused as an href) or empty.
 */
Checks::is( 'share_url: link is the permalink', share_url( 'link', $permalink, $title ), $permalink );
Checks::is( 'share_url: system is the permalink', share_url( 'system', $permalink, $title ), $permalink );
Checks::is( 'share_url: print is empty', share_url( 'print', $permalink, $title ), '' );

Checks::is( 'share_url: an unknown network is empty', share_url( 'carrier-pigeon', $permalink, $title ), '' );

/*
 * icon_name(): the default map, an override winning, and the unknown case.
 */
Checks::is( 'icon_name: facebook resolves to the default map entry', icon_name( 'facebook' ), 'socialFacebook' );
Checks::is( 'icon_name: system resolves to the share icon', icon_name( 'system' ), 'share' );
Checks::is(
	'icon_name: an override wins over the default',
	icon_name( 'facebook', array( 'facebook' => 'customFacebookIcon' ) ),
	'customFacebookIcon'
);
Checks::is(
	'icon_name: an override for a different network is ignored',
	icon_name( 'facebook', array( 'x' => 'customXIcon' ) ),
	'socialFacebook'
);
Checks::is( 'icon_name: an unknown network is empty', icon_name( 'carrier-pigeon' ), '' );

/*
 * is_action_network(): true for the three buttons, false for a sample of the
 * anchors.
 */
Checks::true( 'is_action_network: link is an action', is_action_network( 'link' ) );
Checks::true( 'is_action_network: print is an action', is_action_network( 'print' ) );
Checks::true( 'is_action_network: system is an action', is_action_network( 'system' ) );
Checks::is( 'is_action_network: facebook is not an action', is_action_network( 'facebook' ), false );
Checks::is( 'is_action_network: x is not an action', is_action_network( 'x' ), false );
Checks::is( 'is_action_network: email is not an action', is_action_network( 'email' ), false );

/*
 * default_label(): a known network resolves to a non-empty string, an
 * unknown network resolves to ''.
 */
Checks::is( 'default_label: facebook is non-empty', '' !== default_label( 'facebook' ), true );
Checks::is( 'default_label: system is non-empty', '' !== default_label( 'system' ), true );
Checks::is( 'default_label: an unknown network is empty', default_label( 'carrier-pigeon' ), '' );

/*
 * network_classes(): network and position classes are always present;
 * --with-label appears only when the label is visible.
 */
$classes_with_label = network_classes( 'facebook', 'before', true );
Checks::true( 'network_classes: contains the network class', in_array( 'isudev-share__network--facebook', $classes_with_label, true ) );
Checks::true( 'network_classes: contains the label-position class', in_array( 'isudev-share__network--label-before', $classes_with_label, true ) );
Checks::true( 'network_classes: gains --with-label when the label shows', in_array( 'isudev-share__network--with-label', $classes_with_label, true ) );

$classes_without_label = network_classes( 'facebook', 'before', false );
Checks::is(
	'network_classes: --with-label is absent when the label is hidden',
	in_array( 'isudev-share__network--with-label', $classes_without_label, true ),
	false
);
