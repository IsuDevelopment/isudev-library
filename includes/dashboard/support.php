<?php
/**
 * Client dashboard: the Support widget and its request form.
 *
 * The form posts to REST. The request goes to the developer's support address
 * as an HTML email with Reply-To set to the client, and a copy goes to the
 * client as confirmation that it was sent.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Dashboard\Support;

use IsuDevLibrary\Dashboard;

use const IsuDevLibrary\URL;
use const IsuDevLibrary\VERSION;

defined( 'ABSPATH' ) || exit;

const SCRIPT_HANDLE = 'isudev-library-dashboard-support';
const MAX_LENGTH    = 5000;

/**
 * Problem types offered in the form.
 *
 * @return array slug => label.
 */
function problem_types(): array {
	$types = array(
		'bug'     => \__( 'Bug / something broken', 'isudev-library' ),
		'content' => \__( 'Content or editing question', 'isudev-library' ),
		'access'  => \__( 'Access or login', 'isudev-library' ),
		'feature' => \__( 'Feature request', 'isudev-library' ),
		'other'   => \__( 'Other', 'isudev-library' ),
	);

	/**
	 * Filters the problem types offered in the support form.
	 *
	 * @param array $types slug => label.
	 */
	$filtered = \apply_filters( 'isudev_library/dashboard/support/problem_types', $types );

	return \is_array( $filtered ) && array() !== $filtered ? $filtered : $types;
}

/**
 * Default address in the form's Email field: the client email from the panel,
 * else the current user's.
 *
 * @return string
 */
function default_email(): string {
	$client = Dashboard\get()['support']['clientEmail'];

	if ( '' !== $client ) {
		return $client;
	}

	$user = \wp_get_current_user();

	return $user instanceof \WP_User ? (string) $user->user_email : '';
}

/**
 * Widget body.
 *
 * @return void
 */
function render(): void {
	$contact  = Dashboard\support_contact();
	$settings = Dashboard\get()['support'];

	echo '<ul class="isudev-dash__rows">';

	\printf(
		'<li class="isudev-dash__row is-contact"><span class="isudev-dash__tile" aria-hidden="true"><span class="dashicons dashicons-admin-site-alt3"></span></span><span class="isudev-dash__main"><span class="isudev-dash__label">%1$s</span><a class="isudev-dash__value" href="%2$s" target="_blank" rel="noopener">%3$s<span class="screen-reader-text"> %4$s</span></a></span></li>',
		\esc_html__( 'Developer', 'isudev-library' ),
		\esc_url( $contact['url'] ),
		\esc_html( $contact['label'] ),
		/* translators: Accessibility text for a link that opens in a new tab. */
		\esc_html__( '(opens in a new tab)', 'isudev-library' )
	);

	\printf(
		'<li class="isudev-dash__row is-contact"><span class="isudev-dash__tile" aria-hidden="true"><span class="dashicons dashicons-email-alt"></span></span><span class="isudev-dash__main"><span class="isudev-dash__label">%1$s</span><a class="isudev-dash__value" href="%2$s">%3$s</a></span></li>',
		\esc_html__( 'Email', 'isudev-library' ),
		\esc_url( 'mailto:' . $contact['email'] ),
		\esc_html( $contact['email'] )
	);

	echo '</ul>';

	if ( ! $settings['form'] ) {
		return;
	}

	$fields = array(
		'email'       => \esc_attr( default_email() ),
		'subject'     => '',
		'description' => '',
	);

	echo '<div class="isudev-dash__support">';

	echo '<div class="isudev-dash__notice" role="status" aria-live="polite"></div>';

	\printf(
		'<button type="button" class="button isudev-dash__toggle" aria-expanded="false" aria-controls="isudev-support-form"><span class="dashicons dashicons-edit" aria-hidden="true"></span> <span class="isudev-dash__toggle-label">%s</span></button>',
		\esc_html__( 'Show form', 'isudev-library' )
	);

	echo '<form id="isudev-support-form" class="isudev-dash__form" hidden novalidate>';

	\printf(
		'<p><label for="isudev-support-email">%1$s</label><input type="email" id="isudev-support-email" name="email" value="%2$s" required autocomplete="email"></p>',
		\esc_html__( 'Your email', 'isudev-library' ),
		$fields['email'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
	);

	\printf(
		'<p><label for="isudev-support-subject">%s</label><input type="text" id="isudev-support-subject" name="subject" required maxlength="200"></p>',
		\esc_html__( 'Subject', 'isudev-library' )
	);

	echo '<p><label for="isudev-support-type">' . \esc_html__( 'Problem type', 'isudev-library' ) . '</label><select id="isudev-support-type" name="type">';
	foreach ( problem_types() as $slug => $label ) {
		\printf( '<option value="%1$s">%2$s</option>', \esc_attr( (string) $slug ), \esc_html( (string) $label ) );
	}
	echo '</select></p>';

	\printf(
		'<p><label for="isudev-support-description">%s</label><textarea id="isudev-support-description" name="description" rows="4" required maxlength="%d"></textarea></p>',
		\esc_html__( 'Description', 'isudev-library' ),
		(int) MAX_LENGTH
	);

	\printf(
		'<p class="isudev-dash__form-actions"><button type="submit" class="button button-primary">%1$s</button> <button type="button" class="button-link isudev-dash__cancel">%2$s</button></p>',
		\esc_html__( 'Send request', 'isudev-library' ),
		\esc_html__( 'Cancel', 'isudev-library' )
	);

	echo '</form></div>';
}

/**
 * The form script and its strings.
 *
 * @return void
 */
function enqueue_script(): void {
	\wp_enqueue_script(
		SCRIPT_HANDLE,
		URL . 'assets/dashboard/support.js',
		array( 'wp-api-fetch' ),
		VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	\wp_add_inline_script(
		SCRIPT_HANDLE,
		'window.isudevDashboardSupport = ' . \wp_json_encode(
			array(
				'path'    => '/isudev-library/v1/dashboard/support',
				'strings' => array(
					'show'     => \__( 'Show form', 'isudev-library' ),
					'hide'     => \__( 'Hide form', 'isudev-library' ),
					'sending'  => \__( 'Sending…', 'isudev-library' ),
					'send'     => \__( 'Send request', 'isudev-library' ),
					'required' => \__( 'Fill in your email, the subject and the description.', 'isudev-library' ),
					'failed'   => \__( 'The request could not be sent. Try again, or write to us directly.', 'isudev-library' ),
				),
			)
		) . ';',
		'before'
	);
}

/**
 * REST arguments for the send route.
 *
 * @return array
 */
function request_args(): array {
	return array(
		'email'       => array(
			'type'     => 'string',
			'required' => true,
			'format'   => 'email',
		),
		'subject'     => array(
			'type'      => 'string',
			'required'  => true,
			'minLength' => 1,
			'maxLength' => 200,
		),
		'type'        => array(
			'type'    => 'string',
			'default' => 'other',
		),
		'description' => array(
			'type'      => 'string',
			'required'  => true,
			'minLength' => 1,
			'maxLength' => MAX_LENGTH,
		),
	);
}

/**
 * Facts about the site sent with every request, so support does not have to ask.
 *
 * @return array label => value.
 */
function site_facts(): array {
	$user  = \wp_get_current_user();
	$theme = \wp_get_theme();
	$names = \wp_roles()->get_names();
	$roles = $user instanceof \WP_User
		? \implode( ', ', \array_map( static fn( $role ): string => \translate_user_role( (string) ( $names[ $role ] ?? $role ) ), (array) $user->roles ) )
		: '';

	return array(
		\__( 'Site', 'isudev-library' )      => \home_url( '/' ),
		\__( 'Sent by', 'isudev-library' )   => $user instanceof \WP_User ? \sprintf( '%1$s (%2$s)', $user->display_name, $roles ) : '',
		\__( 'WordPress', 'isudev-library' ) => (string) \get_bloginfo( 'version' ),
		\__( 'PHP', 'isudev-library' )       => PHP_VERSION,
		\__( 'Theme', 'isudev-library' )     => \sprintf( '%1$s %2$s', $theme->get( 'Name' ), $theme->get( 'Version' ) ),
		\__( 'Plugin', 'isudev-library' )    => 'IsuDev Library ' . VERSION,
	);
}

/**
 * The email body: a single-column table layout with inline styles, which is
 * what mail clients render consistently. Every value is escaped here.
 *
 * @param string $heading Line above the request.
 * @param string $intro   Paragraph under the heading.
 * @param array  $request subject, type (label), email, description.
 * @param array  $facts   label => value, shown under the request.
 * @return string
 */
function mail_html( string $heading, string $intro, array $request, array $facts ): string {
	$row = static fn( string $label, string $value ): string => \sprintf(
		'<tr><td style="padding:6px 0;color:#646970;font-size:13px;width:120px;vertical-align:top;">%1$s</td><td style="padding:6px 0;color:#1d2327;font-size:14px;vertical-align:top;">%2$s</td></tr>',
		\esc_html( $label ),
		$value
	);

	$details = $row( \__( 'Subject', 'isudev-library' ), \esc_html( (string) $request['subject'] ) )
		. $row( \__( 'Problem type', 'isudev-library' ), \esc_html( (string) $request['type'] ) )
		. $row( \__( 'Reply to', 'isudev-library' ), \esc_html( (string) $request['email'] ) );

	$facts_html = '';
	foreach ( $facts as $label => $value ) {
		$facts_html .= $row( (string) $label, \esc_html( (string) $value ) );
	}

	return '<!doctype html><html><body style="margin:0;padding:0;background:#f0f0f1;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0f1;padding:24px 12px;"><tr><td align="center">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border:1px solid #c3c4c7;border-radius:4px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;">'
		. '<tr><td style="padding:20px 24px;border-bottom:1px solid #f0f0f1;"><h1 style="margin:0;font-size:18px;line-height:1.4;color:#1d2327;font-weight:600;">' . \esc_html( $heading ) . '</h1>'
		. '<p style="margin:8px 0 0;font-size:14px;line-height:1.5;color:#646970;">' . \esc_html( $intro ) . '</p></td></tr>'
		. '<tr><td style="padding:16px 24px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $details . '</table></td></tr>'
		. '<tr><td style="padding:0 24px 16px;"><div style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;padding:12px 14px;font-size:14px;line-height:1.6;color:#1d2327;">' . \nl2br( \esc_html( (string) $request['description'] ) ) . '</div></td></tr>'
		. '<tr><td style="padding:12px 24px 20px;border-top:1px solid #f0f0f1;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $facts_html . '</table></td></tr>'
		. '</table></td></tr></table></body></html>';
}

/**
 * Send the request: one email to support, a copy to the client.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response|\WP_Error
 */
function rest_send( \WP_REST_Request $request ) {
	$user_id  = \get_current_user_id();
	$throttle = 'isudev_support_sent_' . $user_id;

	if ( false !== \get_transient( $throttle ) ) {
		return new \WP_Error( 'isudev_support_throttled', \__( 'A request was sent a moment ago. Wait a minute before sending another.', 'isudev-library' ), array( 'status' => 429 ) );
	}

	$email   = \sanitize_email( (string) $request->get_param( 'email' ) );
	$subject = \sanitize_text_field( (string) $request->get_param( 'subject' ) );
	// Kept as typed — a pasted error message or HTML snippet is exactly what support needs.
	// mail_html() escapes it; only invalid UTF-8 and stray carriage returns go.
	$description = \trim( \str_replace( "\r", '', \wp_check_invalid_utf8( (string) $request->get_param( 'description' ), true ) ) );
	$types       = problem_types();
	$type_slug   = (string) $request->get_param( 'type' );
	$type        = (string) ( $types[ $type_slug ] ?? $types['other'] ?? \reset( $types ) );

	if ( ! \is_email( $email ) || '' === $subject || '' === $description ) {
		return new \WP_Error( 'isudev_support_invalid', \__( 'Fill in your email, the subject and the description.', 'isudev-library' ), array( 'status' => 400 ) );
	}

	$contact = Dashboard\support_contact();
	$site    = \wp_specialchars_decode( (string) \get_bloginfo( 'name' ), ENT_QUOTES );
	$data    = array(
		'subject'     => $subject,
		'type'        => $type,
		'email'       => $email,
		'description' => $description,
	);
	$facts   = site_facts();
	$html    = array( 'Content-Type: text/html; charset=UTF-8' );

	$sent = \wp_mail(
		$contact['email'],
		/* translators: 1: site name, 2: request subject. */
		\sprintf( \__( '[Support form] %1$s: %2$s', 'isudev-library' ), $site, $subject ),
		mail_html(
			/* translators: %s: site name. */
			\sprintf( \__( 'Support request from %s', 'isudev-library' ), $site ),
			\__( 'Sent from the Support form on the WordPress dashboard.', 'isudev-library' ),
			$data,
			$facts
		),
		\array_merge( $html, array( 'Reply-To: ' . $email ) )
	);

	if ( ! $sent ) {
		return new \WP_Error( 'isudev_support_failed', \__( 'The request could not be sent. Try again, or write to us directly.', 'isudev-library' ), array( 'status' => 500 ) );
	}

	\set_transient( $throttle, 1, MINUTE_IN_SECONDS );

	// The copy is a courtesy: the request already reached support, so a failure here is not reported as one.
	\wp_mail(
		$email,
		/* translators: %s: request subject. */
		\sprintf( \__( 'We received your support request: %s', 'isudev-library' ), $subject ),
		mail_html(
			\__( 'Your request has been sent', 'isudev-library' ),
			/* translators: %s: support email address. */
			\sprintf( \__( 'This is a copy of what you sent. We will reply to this address; you can also write to %s.', 'isudev-library' ), $contact['email'] ),
			$data,
			array( \__( 'Site', 'isudev-library' ) => \home_url( '/' ) )
		),
		\array_merge( $html, array( 'Reply-To: ' . $contact['email'] ) )
	);

	return new \WP_REST_Response(
		array(
			'sent'    => true,
			/* translators: %s: email address. */
			'message' => \sprintf( \__( 'Thanks — your request has been sent. A copy went to %s.', 'isudev-library' ), $email ),
		),
		200
	);
}
