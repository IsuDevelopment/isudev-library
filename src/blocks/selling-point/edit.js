/**
 * External dependencies
 */
import { BlockLinkControl } from '@isudev/gutenberg/controls/BlockLinkControl';
import { MediaControl } from '@isudev/gutenberg/controls/MediaControl';
import { getLocalizedIcons } from '@isudev/gutenberg/components/Icon';
import { IconSelect } from '@isudev/gutenberg/components/IconSelect';

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
	PanelBody,
	ToggleControl,
	ToolbarButton,
	ToolbarDropdownMenu,
	ToolbarGroup,
} from '@wordpress/components';
import { check, image as imageIcon, stretchWide } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { asArray, getBlockConfig } from '../../utils/config';

// Config keys live on the parent block; see render.php for why.
const PARENT_BLOCK_NAME = 'isudev/selling-points';

const SIZE_OPTIONS = [
	{ value: 'size-20', label: __('20%', 'isudev-library') },
	{ value: 'size-25', label: __('25%', 'isudev-library') },
	{ value: 'size-33', label: __('33.33%', 'isudev-library') },
	{ value: 'size-50', label: __('50%', 'isudev-library') },
	{ value: 'size-75', label: __('75%', 'isudev-library') },
	{ value: 'size-100', label: __('100%', 'isudev-library') },
];

const defaultIcons = getLocalizedIcons();

/**
 * Editor UI for a single selling point.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {Object}   props.context       Block context from the parent.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes, context }) {
	const {
		icon,
		showIcon,
		media = {},
		itemWidth,
		title,
		badgeText,
		showBadge,
		description,
		link = {},
		showLinkText,
		linkText,
	} = attributes;

	const namespace = context?.['isudev/sellingPointsNamespace'] ?? '';
	const renderTitlesAsHeadings =
		context?.['isudev/sellingPointsRenderTitlesAsHeadings'] ?? false;
	const headingLevel = context?.['isudev/sellingPointsHeadingLevel'] ?? 2;

	const hasIcon = getBlockConfig(
		PARENT_BLOCK_NAME,
		'features.hasIcon',
		true,
		namespace
	);
	const allowedIcons = asArray(
		getBlockConfig(
			PARENT_BLOCK_NAME,
			'features.allowedIcons',
			null,
			namespace
		),
		null
	);
	const hasImage = getBlockConfig(
		PARENT_BLOCK_NAME,
		'features.hasImage',
		false,
		namespace
	);
	const hasDescription = getBlockConfig(
		PARENT_BLOCK_NAME,
		'features.hasDescription',
		true,
		namespace
	);
	const hasLink = getBlockConfig(
		PARENT_BLOCK_NAME,
		'features.hasLink',
		true,
		namespace
	);
	const hasBadge = getBlockConfig(
		PARENT_BLOCK_NAME,
		'features.hasBadge',
		true,
		namespace
	);

	const widthClass = itemWidth ? `is-${itemWidth}` : '';
	const selectedSizeLabel =
		SIZE_OPTIONS.find((option) => option.value === itemWidth)?.label ||
		__('Default', 'isudev-library');

	const blockProps = useBlockProps({
		className: [
			'isudev-selling-point',
			widthClass,
			hasIcon && showIcon && icon ? 'has-icon' : '',
			hasImage && media?.id ? 'has-image' : '',
			link?.url ? 'has-link' : '',
		]
			.filter(Boolean)
			.join(' '),
	});

	const TitleTag = renderTitlesAsHeadings ? `h${headingLevel}` : 'div';

	return (
		<>
			<BlockControls group="block">
				<ToolbarDropdownMenu
					label={__('Width', 'isudev-library')}
					text={selectedSizeLabel}
					controls={SIZE_OPTIONS.map((option) => ({
						title: option.label,
						icon: itemWidth === option.value ? check : stretchWide,
						onClick: () =>
							setAttributes({ itemWidth: option.value }),
					}))}
				/>
			</BlockControls>

			{hasIcon && (
				<BlockControls group="other">
					<ToolbarGroup>
						<ToolbarButton
							icon={imageIcon}
							isPressed={showIcon}
							label={
								showIcon
									? __('Hide icon', 'isudev-library')
									: __('Show icon', 'isudev-library')
							}
							onClick={() =>
								setAttributes({ showIcon: !showIcon })
							}
						/>
					</ToolbarGroup>
				</BlockControls>
			)}

			<BlockLinkControl
				value={link}
				onChange={(next) => setAttributes({ link: next })}
				showUnlinkButton
				addLabel={__('Add link', 'isudev-library')}
				editLabel={__('Edit link', 'isudev-library')}
			/>

			{(hasIcon || hasBadge || hasLink) && (
				<InspectorControls>
					<PanelBody
						title={__('Settings', 'isudev-library')}
						initialOpen
					>
						{hasIcon && (
							<ToggleControl
								__nextHasNoMarginBottom
								label={__('Show icon', 'isudev-library')}
								checked={showIcon}
								onChange={() =>
									setAttributes({ showIcon: !showIcon })
								}
							/>
						)}
						{hasBadge && (
							<ToggleControl
								__nextHasNoMarginBottom
								label={__('Show badge', 'isudev-library')}
								checked={showBadge !== false}
								onChange={() =>
									setAttributes({
										showBadge: showBadge === false,
									})
								}
							/>
						)}
						{hasLink && (
							<ToggleControl
								__nextHasNoMarginBottom
								label={__('Show link text', 'isudev-library')}
								checked={!!showLinkText}
								onChange={(value) =>
									setAttributes({ showLinkText: value })
								}
							/>
						)}
					</PanelBody>
				</InspectorControls>
			)}

			<div {...blockProps}>
				{hasIcon && showIcon && (
					<div className="isudev-selling-point__icon">
						<IconSelect
							label={__('Icon', 'isudev-library')}
							defaultIcons={defaultIcons}
							icons={allowedIcons ?? undefined}
							value={icon}
							onChange={(next) => setAttributes({ icon: next })}
						/>
					</div>
				)}

				{hasBadge && showBadge !== false && (
					<RichText
						tagName="span"
						className="isudev-selling-point__badge"
						value={badgeText}
						onChange={(value) =>
							setAttributes({ badgeText: value })
						}
						allowedFormats={[]}
						disableLineBreaks
						withoutInteractiveFormatting
						placeholder={__('Add badge text', 'isudev-library')}
					/>
				)}

				{hasImage && (
					<MediaControl
						value={media}
						onChange={(next) => setAttributes({ media: next })}
						onRemove={() => setAttributes({ media: {} })}
						sources={{ featured: false }}
						toolbar={false}
						sidebar={false}
						canvas={{
							className: 'isudev-selling-point__figure',
							placeholderLabel: __('Image', 'isudev-library'),
							placeholderInstructions: __(
								'Upload an image for this point.',
								'isudev-library'
							),
						}}
					/>
				)}

				<RichText
					tagName={TitleTag}
					className="isudev-selling-point__title"
					value={title}
					onChange={(value) => setAttributes({ title: value })}
					allowedFormats={[]}
					placeholder={__('Add title…', 'isudev-library')}
				/>

				{hasDescription && (
					<RichText
						tagName="p"
						className="isudev-selling-point__description"
						value={description}
						onChange={(value) =>
							setAttributes({ description: value })
						}
						placeholder={__('Add description', 'isudev-library')}
					/>
				)}

				{hasLink && !!showLinkText && (
					<RichText
						tagName="span"
						className="isudev-selling-point__link"
						value={linkText}
						onChange={(value) => setAttributes({ linkText: value })}
						allowedFormats={[]}
						disableLineBreaks
						withoutInteractiveFormatting
						placeholder={__('Add link text', 'isudev-library')}
					/>
				)}
			</div>
		</>
	);
}
