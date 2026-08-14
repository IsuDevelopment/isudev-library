/**
 * External dependencies
 */
import { Icon, getLocalizedIcons } from '@isudev/gutenberg/components/Icon';

/**
 * WordPress dependencies
 */
import {
	BlockControls,
	HeadingLevelDropdown,
	InspectorControls,
	RichText,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { asArray, getBlockConfig } from '../../utils/config';

// Config keys live on the parent block; see render.php for why.
const PARENT_BLOCK_NAME = 'isudev/agenda-accordion';

const HEADING_LEVELS = [1, 2, 3, 4, 5, 6];

const DEFAULT_ALLOWED_BLOCKS = [];
const DEFAULT_TEMPLATE = [
	[
		'core/paragraph',
		{ placeholder: __('Write description…', 'isudev-library') },
	],
];

const defaultIcons = getLocalizedIcons();

/**
 * Editor UI for a single agenda accordion item.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {Object}   props.context       Block context from the parent.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes, context }) {
	const { title, agendaDate, excerpt, level, defaultOpen } = attributes;

	const namespace = context?.['isudev/agendaAccordionNamespace'] ?? '';

	// asArray: a mistyped list in isudev.json must not TypeError the editor.
	const allowedBlocks = asArray(
		getBlockConfig(
			PARENT_BLOCK_NAME,
			'allowedBlocks',
			DEFAULT_ALLOWED_BLOCKS,
			namespace
		),
		DEFAULT_ALLOWED_BLOCKS
	);
	const template = asArray(
		getBlockConfig(
			PARENT_BLOCK_NAME,
			'template',
			DEFAULT_TEMPLATE,
			namespace
		),
		DEFAULT_TEMPLATE
	);
	const iconClosed = getBlockConfig(
		PARENT_BLOCK_NAME,
		'icons.closed',
		'chevronDown',
		namespace
	);

	const HeadingTag = `h${level}`;

	const blockProps = useBlockProps({
		className: 'isudev-agenda-accordion-item',
	});

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'isudev-agenda-accordion-item__inner-container' },
		{
			allowedBlocks: allowedBlocks.length > 0 ? allowedBlocks : null,
			template,
		}
	);

	return (
		<>
			<BlockControls group="block">
				<HeadingLevelDropdown
					options={HEADING_LEVELS}
					value={level}
					onChange={(newLevel) => setAttributes({ level: newLevel })}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={__('Settings', 'isudev-library')}>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Default open', 'isudev-library')}
						checked={!!defaultOpen}
						onChange={(value) =>
							setAttributes({ defaultOpen: value })
						}
						help={__(
							'Show this item as initially open.',
							'isudev-library'
						)}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<HeadingTag className="isudev-agenda-accordion__editor-title">
					<RichText
						className="isudev-agenda-accordion__editor-richtext"
						identifier="agendaDate"
						tagName="span"
						value={agendaDate}
						onChange={(value) =>
							setAttributes({ agendaDate: value })
						}
						aria-label={__('Agenda date text', 'isudev-library')}
						placeholder={__('Time or date…', 'isudev-library')}
						allowedFormats={[]}
						withoutInteractiveFormatting
						disableLineBreaks
					/>
					<RichText
						className="isudev-agenda-accordion__editor-richtext"
						identifier="title"
						tagName="span"
						value={title}
						onChange={(value) => setAttributes({ title: value })}
						aria-label={__('Heading text', 'isudev-library')}
						placeholder={__('Write heading…', 'isudev-library')}
						allowedFormats={[]}
						withoutInteractiveFormatting
						disableLineBreaks
					/>
					<span className="isudev-agenda-accordion__editor-icon">
						<Icon
							name={iconClosed}
							defaultIcons={defaultIcons}
							size={24}
						/>
					</span>
				</HeadingTag>
				<RichText
					className="isudev-agenda-accordion__editor-richtext"
					identifier="excerpt"
					tagName="p"
					value={excerpt}
					onChange={(value) => setAttributes({ excerpt: value })}
					aria-label={__('Excerpt text', 'isudev-library')}
					placeholder={__('Write excerpt…', 'isudev-library')}
					allowedFormats={[
						'core/bold',
						'core/italic',
						'core/underline',
					]}
				/>
				<div {...innerBlocksProps} />
			</div>
		</>
	);
}
