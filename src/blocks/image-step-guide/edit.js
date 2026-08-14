/**
 * WordPress dependencies
 */
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { fullscreen } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getBlockConfig } from '../../utils/config';

const BLOCK_NAME = 'isudev/image-step-guide';
const STEP_BLOCK_NAME = 'isudev/image-step-guide-step';

const DEFAULT_TEMPLATE = [[STEP_BLOCK_NAME], [STEP_BLOCK_NAME]];

/**
 * Editor UI for the image step guide wrapper.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const {
		displayStyle,
		useImageLightbox,
		_namespace: namespace = '',
	} = attributes;

	const displayStyleDisabled = getBlockConfig(
		BLOCK_NAME,
		'displayStyle.disable',
		false,
		namespace
	);
	const configuredDisplayStyle =
		getBlockConfig(BLOCK_NAME, 'displayStyle.value', 'list', namespace) ===
		'grid'
			? 'grid'
			: 'list';
	const effectiveDisplayStyle = displayStyleDisabled
		? configuredDisplayStyle
		: displayStyle;

	const lightboxDisabled = getBlockConfig(
		BLOCK_NAME,
		'imageLightbox.disable',
		false,
		namespace
	);

	const blockProps = useBlockProps({
		className: [
			'isudev-image-step-guide',
			`is-display-${effectiveDisplayStyle}`,
			useImageLightbox ? 'has-lightbox-images' : '',
		]
			.filter(Boolean)
			.join(' '),
	});

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'isudev-image-step-guide__items' },
		{
			allowedBlocks: [STEP_BLOCK_NAME],
			template: DEFAULT_TEMPLATE,
			templateLock: false,
		}
	);

	return (
		<>
			{!lightboxDisabled && (
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon={fullscreen}
							label={__(
								'Enlarge images on click',
								'isudev-library'
							)}
							isPressed={useImageLightbox}
							onClick={() =>
								setAttributes({
									useImageLightbox: !useImageLightbox,
								})
							}
						/>
					</ToolbarGroup>
				</BlockControls>
			)}

			{!lightboxDisabled && (
				<InspectorControls>
					<PanelBody title={__('Images', 'isudev-library')}>
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Enlarge on click', 'isudev-library')}
							checked={useImageLightbox}
							onChange={(value) =>
								setAttributes({ useImageLightbox: value })
							}
							help={__(
								'Open a larger version of the image when it is clicked.',
								'isudev-library'
							)}
						/>
					</PanelBody>
				</InspectorControls>
			)}

			{!displayStyleDisabled && (
				<InspectorControls group="styles">
					<PanelBody title={__('Layout', 'isudev-library')}>
						<SelectControl
							__nextHasNoMarginBottom
							label={__('Display style', 'isudev-library')}
							value={displayStyle}
							options={[
								{
									label: __('List', 'isudev-library'),
									value: 'list',
								},
								{
									label: __('Grid', 'isudev-library'),
									value: 'grid',
								},
							]}
							onChange={(value) =>
								setAttributes({ displayStyle: value })
							}
						/>
					</PanelBody>
				</InspectorControls>
			)}

			<div {...blockProps}>
				<div {...innerBlocksProps} />
			</div>
		</>
	);
}
