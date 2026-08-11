/**
 * External dependencies
 */
import { Icon, getLocalizedIcons } from '@isudev/gutenberg/components/Icon';

/**
 * WordPress dependencies
 */
import {
	BlockControls,
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	SelectControl,
	ToggleControl,
	ToolbarButton,
	ToolbarDropdownMenu,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { caption as captionIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { getBlockConfig } from '../../utils/config';
import { networkConfig } from './network-config';

// Config keys live on the parent block; see render.php for why.
const PARENT_BLOCK_NAME = 'isudev/social-share';

/*
 * Every network this block understands, in the same order as NETWORKS in
 * inc/render-helpers.php. Keep the two lists in sync.
 */
const ALL_NETWORKS = [
	'facebook',
	'x',
	'linkedin',
	'whatsapp',
	'bluesky',
	'threads',
	'mastodon',
	'substack',
	'link',
	'email',
	'print',
	'system',
];

/*
 * Mirrors default_icon_names() in inc/render-helpers.php. Keep the two in
 * sync — there is no shared module and no JS test runner to catch drift.
 */
const DEFAULT_ICON_NAMES = {
	facebook: 'socialFacebook',
	x: 'socialX',
	linkedin: 'socialLinkedin',
	whatsapp: 'socialWhatsapp',
	bluesky: 'socialBluesky',
	threads: 'socialThreads',
	mastodon: 'socialMastodon',
	substack: 'socialSubstack',
	email: 'email',
	link: 'link',
	print: 'print',
	system: 'share',
};

/**
 * Resolve the icon registry name for a network, honouring config overrides.
 *
 * @param {string} network   Network slug.
 * @param {Object} overrides Network => icon registry name overrides.
 * @return {string} Icon registry name, or '' for an unknown network.
 */
function resolveIconName(network, overrides) {
	return overrides?.[network] || DEFAULT_ICON_NAMES[network] || '';
}

const defaultIcons = getLocalizedIcons();

export default function Edit({ attributes, setAttributes }) {
	const { network, label, showLabel, labelPosition = 'after' } = attributes;

	const networkLabelDisable = getBlockConfig(
		PARENT_BLOCK_NAME,
		'networkLabel.disable',
		false
	);
	const networkLabelAllowToggle = getBlockConfig(
		PARENT_BLOCK_NAME,
		'networkLabel.allowToggle',
		true
	);
	const networkLabelAllowPosition = getBlockConfig(
		PARENT_BLOCK_NAME,
		'networkLabel.allowPosition',
		true
	);
	const allowedNetworks = getBlockConfig(
		PARENT_BLOCK_NAME,
		'allowedNetworks',
		ALL_NETWORKS
	);
	const icons = getBlockConfig(PARENT_BLOCK_NAME, 'icons', {});
	const iconsSize = getBlockConfig(PARENT_BLOCK_NAME, 'iconsSize', 24);

	const blockProps = useBlockProps({
		className: `isudev-share__network isudev-share__network--${network} isudev-share__network--label-${labelPosition}`,
	});

	const currentNetwork = networkConfig[network] ?? networkConfig.facebook;

	const networkOptions = ALL_NETWORKS.filter((n) =>
		allowedNetworks.includes(n)
	).map((n) => ({ label: networkConfig[n].title, value: n }));

	const toolbarControls = ALL_NETWORKS.filter((n) =>
		allowedNetworks.includes(n)
	).map((n) => ({
		title: networkConfig[n].title,
		isActive: network === n,
		icon: () => (
			<Icon
				name={resolveIconName(n, icons)}
				defaultIcons={defaultIcons}
				size={24}
			/>
		),
		onClick: () => setAttributes({ network: n }),
	}));

	return (
		<>
			<BlockControls>
				{toolbarControls.length > 1 && (
					<ToolbarDropdownMenu
						className="isudev-social-share-network-toolbar"
						icon={() => (
							<Icon
								name={resolveIconName(network, icons)}
								defaultIcons={defaultIcons}
								size={24}
							/>
						)}
						label={__('Select network', 'isudev-library')}
						controls={toolbarControls}
					/>
				)}
				{!networkLabelDisable && (
					<ToolbarButton
						icon={captionIcon}
						label={__('Toggle label', 'isudev-library')}
						isPressed={showLabel}
						onClick={() => setAttributes({ showLabel: !showLabel })}
					/>
				)}
			</BlockControls>
			<InspectorControls>
				<PanelBody title={__('Network settings', 'isudev-library')}>
					{networkOptions.length > 1 && (
						<SelectControl
							__nextHasNoMarginBottom
							label={__('Social network', 'isudev-library')}
							value={network}
							options={networkOptions}
							onChange={(value) =>
								setAttributes({ network: value })
							}
						/>
					)}
					{network === 'system' && currentNetwork.description && (
						<Notice status="info" isDismissible={false}>
							{currentNetwork.description}
						</Notice>
					)}
					{!networkLabelDisable && (
						<>
							{networkLabelAllowToggle && (
								<ToggleControl
									__nextHasNoMarginBottom
									label={__('Show label', 'isudev-library')}
									checked={showLabel}
									onChange={(value) =>
										setAttributes({ showLabel: value })
									}
									help={__(
										'Display the label visibly next to the icon.',
										'isudev-library'
									)}
								/>
							)}
							{networkLabelAllowPosition && showLabel && (
								<SelectControl
									__nextHasNoMarginBottom
									label={__(
										'Label position',
										'isudev-library'
									)}
									value={labelPosition}
									options={[
										{
											label: __(
												'Before icon',
												'isudev-library'
											),
											value: 'before',
										},
										{
											label: __(
												'After icon',
												'isudev-library'
											),
											value: 'after',
										},
									]}
									onChange={(value) =>
										setAttributes({
											labelPosition: value,
										})
									}
								/>
							)}
						</>
					)}
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				{!networkLabelDisable &&
					showLabel &&
					labelPosition === 'before' && (
						<RichText
							tagName="span"
							className="isudev-share__network-label"
							value={label}
							onChange={(value) =>
								setAttributes({ label: value })
							}
							placeholder={currentNetwork.defaultLabel}
							allowedFormats={[]}
							multiline={false}
						/>
					)}
				<span className="isudev-share__network-icon">
					<Icon
						name={resolveIconName(network, icons)}
						defaultIcons={defaultIcons}
						size={iconsSize}
					/>
				</span>
				{!networkLabelDisable &&
					showLabel &&
					labelPosition === 'after' && (
						<RichText
							tagName="span"
							className="isudev-share__network-label"
							value={label}
							onChange={(value) =>
								setAttributes({ label: value })
							}
							placeholder={currentNetwork.defaultLabel}
							allowedFormats={[]}
							multiline={false}
						/>
					)}
			</div>
		</>
	);
}
