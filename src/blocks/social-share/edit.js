/**
 * WordPress dependencies
 */
import {
	BlockAlignmentToolbar,
	BlockControls,
	InspectorControls,
	RichText,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getBlockConfig } from '../../utils/config';

const BLOCK_NAME = 'isudev/social-share';
const NETWORK_BLOCK_NAME = 'isudev/social-share-network';

/*
 * Every network this block understands, in the same order as the NETWORKS
 * constant in social-share-network/inc/render-helpers.php. Keep the two in
 * sync — there is no shared module and no JS test runner to catch drift.
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

const DEFAULT_TEMPLATE_NETWORKS = ['facebook', 'x', 'linkedin', 'link'];

export default function Edit({ attributes, setAttributes }) {
	const {
		contentAlignment,
		prefixText,
		showPrefix,
		prefixPosition = 'before',
		_namespace: namespace = '',
	} = attributes;

	const prefixDisable = getBlockConfig(
		BLOCK_NAME,
		'prefix.disable',
		false,
		namespace
	);
	const prefixAllowToggle = getBlockConfig(
		BLOCK_NAME,
		'prefix.allowToggle',
		true,
		namespace
	);
	const prefixAllowPosition = getBlockConfig(
		BLOCK_NAME,
		'prefix.allowPosition',
		true,
		namespace
	);
	const allowedNetworks = getBlockConfig(
		BLOCK_NAME,
		'allowedNetworks',
		ALL_NETWORKS,
		namespace
	);
	const defaultTemplateNetworks = getBlockConfig(
		BLOCK_NAME,
		'defaultTemplate',
		DEFAULT_TEMPLATE_NETWORKS,
		namespace
	);
	const allowAlignControl = getBlockConfig(
		BLOCK_NAME,
		'features.allowAlignControl',
		true,
		namespace
	);

	const isSingleNetwork = allowedNetworks.length === 1;
	const defaultTemplate = defaultTemplateNetworks
		.filter((network) => allowedNetworks.includes(network))
		.map((network) => [NETWORK_BLOCK_NAME, { network }]);
	const effectiveTemplate = isSingleNetwork
		? [[NETWORK_BLOCK_NAME, { network: allowedNetworks[0] }]]
		: defaultTemplate;

	const blockProps = useBlockProps({
		className: `isudev-share isudev-share--align-${contentAlignment} isudev-share--prefix-${prefixPosition}`,
	});

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'isudev-share__items' },
		{
			allowedBlocks: [NETWORK_BLOCK_NAME],
			template: effectiveTemplate,
			templateLock: isSingleNetwork ? 'all' : undefined,
			orientation: 'horizontal',
		}
	);

	return (
		<>
			{!prefixDisable && (
				<InspectorControls>
					<PanelBody title={__('Prefix settings', 'isudev-library')}>
						{prefixAllowToggle && (
							<ToggleControl
								__nextHasNoMarginBottom
								label={__('Show prefix text', 'isudev-library')}
								checked={showPrefix}
								onChange={(value) =>
									setAttributes({ showPrefix: value })
								}
							/>
						)}
						{showPrefix && prefixAllowPosition && (
							<SelectControl
								__nextHasNoMarginBottom
								label={__('Prefix position', 'isudev-library')}
								value={prefixPosition}
								options={[
									{
										label: __(
											'Above icons',
											'isudev-library'
										),
										value: 'above',
									},
									{
										label: __(
											'Before icons',
											'isudev-library'
										),
										value: 'before',
									},
									{
										label: __(
											'After icons',
											'isudev-library'
										),
										value: 'after',
									},
								]}
								onChange={(value) =>
									setAttributes({ prefixPosition: value })
								}
							/>
						)}
					</PanelBody>
				</InspectorControls>
			)}

			{allowAlignControl && (
				<BlockControls>
					<BlockAlignmentToolbar
						value={contentAlignment}
						onChange={(align) =>
							setAttributes({ contentAlignment: align })
						}
						controls={['left', 'center', 'right']}
					/>
				</BlockControls>
			)}

			<div {...blockProps}>
				<div className="isudev-share__content">
					{!prefixDisable &&
						showPrefix &&
						prefixPosition === 'above' && (
							<RichText
								tagName="p"
								className="isudev-share__prefix isudev-share__prefix--above"
								value={prefixText}
								onChange={(value) =>
									setAttributes({ prefixText: value })
								}
								placeholder={__('Share:', 'isudev-library')}
								allowedFormats={[]}
								multiline={false}
							/>
						)}
					<div className="isudev-share__row">
						{!prefixDisable &&
							showPrefix &&
							prefixPosition === 'before' && (
								<RichText
									tagName="span"
									className="isudev-share__prefix isudev-share__prefix--before"
									value={prefixText}
									onChange={(value) =>
										setAttributes({ prefixText: value })
									}
									placeholder={__('Share:', 'isudev-library')}
									allowedFormats={[]}
									multiline={false}
								/>
							)}
						<div {...innerBlocksProps} />
						{!prefixDisable &&
							showPrefix &&
							prefixPosition === 'after' && (
								<RichText
									tagName="span"
									className="isudev-share__prefix isudev-share__prefix--after"
									value={prefixText}
									onChange={(value) =>
										setAttributes({ prefixText: value })
									}
									placeholder={__('Share:', 'isudev-library')}
									allowedFormats={[]}
									multiline={false}
								/>
							)}
					</div>
				</div>
			</div>
		</>
	);
}
