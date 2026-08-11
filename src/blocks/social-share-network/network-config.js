/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Per-network editor metadata: toolbar/selector title, RichText placeholder
 * and (for `system`) an inspector notice explaining the Web Share API.
 *
 * Keys are the same twelve networks as NETWORKS in
 * social-share-network/inc/render-helpers.php — keep the two lists in sync.
 */
export const networkConfig = {
	facebook: {
		title: __('Facebook', 'isudev-library'),
		defaultLabel: __('Share on Facebook', 'isudev-library'),
	},
	x: {
		title: __('X (Twitter)', 'isudev-library'),
		defaultLabel: __('Share on X', 'isudev-library'),
	},
	linkedin: {
		title: __('LinkedIn', 'isudev-library'),
		defaultLabel: __('Share on LinkedIn', 'isudev-library'),
	},
	whatsapp: {
		title: __('WhatsApp', 'isudev-library'),
		defaultLabel: __('Share on WhatsApp', 'isudev-library'),
	},
	bluesky: {
		title: __('Bluesky', 'isudev-library'),
		defaultLabel: __('Share on Bluesky', 'isudev-library'),
	},
	threads: {
		title: __('Threads', 'isudev-library'),
		defaultLabel: __('Share on Threads', 'isudev-library'),
	},
	mastodon: {
		title: __('Mastodon', 'isudev-library'),
		defaultLabel: __('Share on Mastodon', 'isudev-library'),
	},
	substack: {
		title: __('Substack', 'isudev-library'),
		defaultLabel: __('Share on Substack', 'isudev-library'),
	},
	email: {
		title: __('Email', 'isudev-library'),
		defaultLabel: __('Send email', 'isudev-library'),
	},
	system: {
		title: __('System Share', 'isudev-library'),
		defaultLabel: __('Share', 'isudev-library'),
		description: __(
			'Uses the native device share dialog (Web Share API), letting users choose their preferred app. Replaces platform-specific buttons. No third-party scripts — GDPR safe.',
			'isudev-library'
		),
	},
	link: {
		title: __('Copy Link', 'isudev-library'),
		defaultLabel: __('Copy link', 'isudev-library'),
	},
	print: {
		title: __('Print', 'isudev-library'),
		defaultLabel: __('Print page', 'isudev-library'),
	},
};
