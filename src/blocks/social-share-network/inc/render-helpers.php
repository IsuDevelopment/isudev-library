<?php
/**
 * Render helpers for the social share network button block.
 *
 * Pure functions only — no get_permalink(), no get_the_title(), no config
 * reads. render.php gathers that context and passes it in.
 *
 * @package IsuDevLibrary\Blocks\SocialShareNetwork
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SocialShareNetwork;

defined( 'ABSPATH' ) || exit;

/**
 * Every network this block understands, in the order they were added.
 *
 * @var string[]
 */
const NETWORKS = array( 'facebook', 'x', 'linkedin', 'whatsapp', 'bluesky', 'threads', 'mastodon', 'substack', 'link', 'email', 'print', 'system' );

/**
 * Map each network to its default icon registry name. Pure.
 *
 * @return array<string,string> Network => icon registry name.
 */
function default_icon_names(): array {
	return array(
		'facebook' => 'socialFacebook',
		'x'        => 'socialX',
		'linkedin' => 'socialLinkedin',
		'whatsapp' => 'socialWhatsapp',
		'bluesky'  => 'socialBluesky',
		'threads'  => 'socialThreads',
		'mastodon' => 'socialMastodon',
		'substack' => 'socialSubstack',
		'email'    => 'email',
		'link'     => 'link',
		'print'    => 'print',
		'system'   => 'share',
	);
}

/**
 * Resolve the icon registry name for a network. Pure.
 *
 * An `$overrides` entry (from the `icons` config key) wins over the default.
 * An unknown network resolves to an empty string.
 *
 * @param string $network   Network slug.
 * @param array  $overrides Network => icon registry name overrides.
 * @return string Icon registry name, or '' for an unknown network.
 */
function icon_name( string $network, array $overrides = array() ): string {
	$defaults = default_icon_names();

	if ( ! isset( $defaults[ $network ] ) ) {
		return '';
	}

	$override = $overrides[ $network ] ?? null;

	return \is_string( $override ) && '' !== $override ? $override : $defaults[ $network ];
}

/**
 * Default accessible label for a network. Pure.
 *
 * @param string $network Network slug.
 * @return string Translated label, or '' for an unknown network.
 */
function default_label( string $network ): string {
	$labels = array(
		'facebook' => \__( 'Share on Facebook', 'isudev-library' ),
		'x'        => \__( 'Share on X', 'isudev-library' ),
		'linkedin' => \__( 'Share on LinkedIn', 'isudev-library' ),
		'whatsapp' => \__( 'Share on WhatsApp', 'isudev-library' ),
		'bluesky'  => \__( 'Share on Bluesky', 'isudev-library' ),
		'threads'  => \__( 'Share on Threads', 'isudev-library' ),
		'mastodon' => \__( 'Share on Mastodon', 'isudev-library' ),
		'substack' => \__( 'Share on Substack', 'isudev-library' ),
		'system'   => \__( 'Share', 'isudev-library' ),
		'email'    => \__( 'Send email', 'isudev-library' ),
		'link'     => \__( 'Copy link', 'isudev-library' ),
		'print'    => \__( 'Print page', 'isudev-library' ),
	);

	return $labels[ $network ] ?? '';
}

/**
 * Whether a network performs an action rather than linking somewhere. Pure.
 *
 * Action networks render a <button type="button">; the rest render <a href>.
 *
 * @param string $network Network slug.
 * @return bool
 */
function is_action_network( string $network ): bool {
	return \in_array( $network, array( 'link', 'print', 'system' ), true );
}

/**
 * Whether a network is useless without a permalink. Pure.
 *
 * `print` prints whatever is on screen and `system` hands
 * `window.location.href` to the Web Share API, so neither needs a permalink.
 * Every other network puts one into a URL or onto the clipboard, and with an
 * empty permalink would render a control that shares nothing — a button whose
 * href is `…/sharer.php?u=`. render.php renders nothing instead.
 *
 * @param string $network Network slug.
 * @return bool
 */
function needs_permalink( string $network ): bool {
	return ! \in_array( $network, array( 'print', 'system' ), true );
}

/**
 * Build the share URL for a network. Pure.
 *
 * $templates carries already-resolved config strings (email subject/body);
 * reading them out of isudev.json happens in render.php, not here.
 *
 * @param string $network   Network slug.
 * @param string $permalink Post permalink.
 * @param string $title     Post title.
 * @param array  $templates Optional 'subject' and 'body' templates for email.
 * @return string Share URL, or '' for print and an unknown network.
 */
function share_url( string $network, string $permalink, string $title, array $templates = array() ): string {
	switch ( $network ) {
		case 'facebook':
			return 'https://www.facebook.com/sharer/sharer.php?u=' . \rawurlencode( $permalink );

		case 'x':
			return 'https://x.com/intent/post?url=' . \rawurlencode( $permalink ) . '&text=' . \rawurlencode( $title );

		case 'linkedin':
			return 'https://www.linkedin.com/sharing/share-offsite/?url=' . \rawurlencode( $permalink );

		case 'whatsapp':
			return 'https://wa.me/?text=' . \rawurlencode( \sprintf( '%s %s', $title, $permalink ) );

		case 'bluesky':
			return 'https://bsky.app/intent/compose?text=' . \rawurlencode( \sprintf( '%s %s', $title, $permalink ) );

		case 'threads':
			return 'https://www.threads.net/intent/post?text=' . \rawurlencode( \sprintf( '%s %s', $title, $permalink ) );

		case 'mastodon':
			return 'https://mastodon.social/share?text=' . \rawurlencode( \sprintf( '%s %s', $title, $permalink ) );

		case 'substack':
			return 'https://substack.com/share?url=' . \rawurlencode( $permalink );

		case 'email':
			$subject_template = isset( $templates['subject'] ) && \is_string( $templates['subject'] )
				? $templates['subject']
				// translators: %title% is the post title.
				: \__( 'Have a look at this article: %title%', 'isudev-library' );
			$body_template = isset( $templates['body'] ) && \is_string( $templates['body'] )
				? $templates['body']
				// translators: %title% is the post title, %url% is the post permalink.
				: \__( 'You might be interested in this: %title%, %url%', 'isudev-library' );

			$subject = \str_replace( array( '%title%', '%url%' ), array( $title, $permalink ), $subject_template );
			$body    = \str_replace( array( '%title%', '%url%' ), array( $title, $permalink ), $body_template );

			return 'mailto:?subject=' . \rawurlencode( $subject ) . '&body=' . \rawurlencode( $body );

		case 'link':
		case 'system':
			return $permalink;

		case 'print':
			return '';

		default:
			return '';
	}
}

/**
 * Build the network control's class list. Pure, and deliberately unsanitized.
 *
 * The caller passes each name through sanitize_html_class(), which is a
 * WordPress function and would break this file's purity.
 *
 * @param string $network       Network slug.
 * @param string $label_position 'before' or 'after'.
 * @param bool   $show_label    Whether the label is visible.
 * @return array
 */
function network_classes( string $network, string $label_position, bool $show_label ): array {
	$classes = array(
		'isudev-share__network',
		'isudev-share__network--' . $network,
		'isudev-share__network--label-' . $label_position,
	);

	if ( $show_label ) {
		$classes[] = 'isudev-share__network--with-label';
	}

	return $classes;
}
