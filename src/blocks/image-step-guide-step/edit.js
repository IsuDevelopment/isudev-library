/**
 * External dependencies
 */
import { MediaControl } from '@isudev/gutenberg/controls/MediaControl';

/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { asArray, getBlockConfig } from '../../utils/config';

// Config keys live on the parent block; see render.php for why.
const PARENT_BLOCK_NAME = 'isudev/image-step-guide';

const DEFAULT_ALLOWED_BLOCKS = [
	'core/paragraph',
	'core/list',
	'core/buttons',
	'core/heading',
];

const DEFAULT_TEMPLATE = [
	[
		'core/heading',
		{
			placeholder: __('Add step title…', 'isudev-library'),
			level: 3,
		},
	],
	[
		'core/paragraph',
		{
			placeholder: __('Add step description…', 'isudev-library'),
		},
	],
];

/**
 * Editor UI for a single image step guide step.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {Object}   props.context       Block context from the parent.
 * @param {string}   props.clientId      This block instance's client id.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes, context, clientId }) {
	const { media = {}, labelPrefix = '', labelHidden = false } = attributes;

	const namespace = context?.['isudev/imageStepGuideNamespace'] ?? '';

	// asArray: a mistyped list in isudev.json must not TypeError the editor.
	const allowedBlocks = asArray(
		getBlockConfig(
			PARENT_BLOCK_NAME,
			'allowedStepBlocks',
			DEFAULT_ALLOWED_BLOCKS,
			namespace
		),
		DEFAULT_ALLOWED_BLOCKS
	);
	const template = asArray(
		getBlockConfig(
			PARENT_BLOCK_NAME,
			'stepTemplate',
			DEFAULT_TEMPLATE,
			namespace
		),
		DEFAULT_TEMPLATE
	);

	const labelDisabled = getBlockConfig(
		PARENT_BLOCK_NAME,
		'stepLabel.disable',
		false,
		namespace
	);
	const configuredPrefix = getBlockConfig(
		PARENT_BLOCK_NAME,
		'stepLabel.prefix',
		'',
		namespace
	);
	const configuredVisible = getBlockConfig(
		PARENT_BLOCK_NAME,
		'stepLabel.visible',
		true,
		namespace
	);

	/*
	 * The frontend numbers steps with a pure CSS counter (see
	 * image-step-guide/style.scss), which the editor iframe suppresses for
	 * this label (image-step-guide/editor.scss) because it cannot reflect a
	 * step being reordered without a reflow the counter won't trigger here.
	 * This computes the same number from block order instead, live.
	 */
	const stepNumber = useSelect(
		(select) => {
			const { getBlockRootClientId, getBlockOrder } =
				select('core/block-editor');
			const rootClientId = getBlockRootClientId(clientId);
			const order = getBlockOrder(rootClientId) || [];
			const index = order.indexOf(clientId);

			return index >= 0 ? index + 1 : 1;
		},
		[clientId]
	);

	const effectivePrefix =
		labelPrefix || configuredPrefix || __('Step', 'isudev-library');
	const showLabel = !labelDisabled && configuredVisible && !labelHidden;

	const blockProps = useBlockProps({
		className: 'isudev-image-step-guide-step',
	});

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'isudev-image-step-guide-step__content-inner' },
		{
			allowedBlocks,
			template,
			templateLock: false,
			orientation: 'vertical',
		}
	);

	return (
		<>
			{!labelDisabled && (
				<InspectorControls>
					<PanelBody
						title={__('Label override', 'isudev-library')}
						initialOpen={false}
					>
						<TextControl
							__nextHasNoMarginBottom
							label={__('Label prefix', 'isudev-library')}
							value={labelPrefix}
							placeholder={effectivePrefix}
							onChange={(value) =>
								setAttributes({ labelPrefix: value })
							}
							help={__(
								'Leave empty to use the guide default.',
								'isudev-library'
							)}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={__(
								"Hide this step's label",
								'isudev-library'
							)}
							checked={labelHidden}
							onChange={(value) =>
								setAttributes({ labelHidden: value })
							}
						/>
					</PanelBody>
				</InspectorControls>
			)}

			<div {...blockProps}>
				<div className="isudev-image-step-guide-step__media">
					<MediaControl
						value={media}
						onChange={(next) => setAttributes({ media: next })}
						onRemove={() => setAttributes({ media: {} })}
						sources={{ featured: false }}
						toolbar={false}
						canvas={{
							className: 'isudev-image-step-guide-step__image',
							placeholderLabel: __(
								'Step image',
								'isudev-library'
							),
							placeholderInstructions: __(
								'Upload an image for this step.',
								'isudev-library'
							),
						}}
						sidebar={{
							title: __('Step image', 'isudev-library'),
						}}
					/>
				</div>

				<div className="isudev-image-step-guide-step__content">
					{showLabel && (
						<span className="isudev-image-step-guide-step__label">
							{`${effectivePrefix} ${stepNumber}`}
						</span>
					)}
					<div {...innerBlocksProps} />
				</div>
			</div>
		</>
	);
}
