/**
 * WordPress dependencies
 */
import {
	BlockControls,
	HeadingLevelDropdown,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { asArray, getBlockConfig } from '../../utils/config';

const BLOCK_NAME = 'isudev/selling-points';
const ALLOWED_BLOCKS = ['isudev/selling-point'];
const DEFAULT_TEMPLATE = [
	['isudev/selling-point'],
	['isudev/selling-point'],
	['isudev/selling-point'],
];

/**
 * Editor UI for the selling points wrapper.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const {
		animateIn,
		iconSize,
		renderTitlesAsHeadings,
		headingLevel,
		_namespace: namespace = '',
	} = attributes;

	const hasIcon = getBlockConfig(
		BLOCK_NAME,
		'features.hasIcon',
		true,
		namespace
	);
	const template = asArray(
		getBlockConfig(BLOCK_NAME, 'template', DEFAULT_TEMPLATE, namespace),
		DEFAULT_TEMPLATE
	);

	const blockProps = useBlockProps({
		className: `isudev-selling-points${animateIn ? ' has-animate-in is-visible' : ''}`,
		style: {
			'--isudev-selling-point-default-span': getBlockConfig(
				BLOCK_NAME,
				'defaultColumns',
				12,
				namespace
			),
		},
	});

	const innerBlocksProps = useInnerBlocksProps(blockProps, {
		template,
		allowedBlocks: ALLOWED_BLOCKS,
		orientation: 'horizontal',
	});

	return (
		<>
			{renderTitlesAsHeadings && (
				<BlockControls group="block">
					<HeadingLevelDropdown
						options={[2, 3, 4, 5, 6]}
						value={headingLevel}
						onChange={(newLevel) =>
							setAttributes({ headingLevel: newLevel })
						}
					/>
				</BlockControls>
			)}
			<InspectorControls>
				<PanelBody title={__('Settings', 'isudev-library')}>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Animate in', 'isudev-library')}
						checked={!!animateIn}
						onChange={(value) =>
							setAttributes({ animateIn: value })
						}
						help={__(
							'Reveal points as they scroll into view.',
							'isudev-library'
						)}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__(
							'Render titles as headings',
							'isudev-library'
						)}
						checked={!!renderTitlesAsHeadings}
						onChange={(value) =>
							setAttributes({ renderTitlesAsHeadings: value })
						}
						help={__(
							'Otherwise each title is a plain, non-heading element.',
							'isudev-library'
						)}
					/>
					{hasIcon && (
						<RangeControl
							__nextHasNoMarginBottom
							label={__('Icon size', 'isudev-library')}
							value={iconSize}
							min={8}
							max={256}
							onChange={(value) =>
								setAttributes({ iconSize: value })
							}
							allowReset
							resetFallbackValue={28}
						/>
					)}
				</PanelBody>
			</InspectorControls>
			<div {...innerBlocksProps} />
		</>
	);
}
