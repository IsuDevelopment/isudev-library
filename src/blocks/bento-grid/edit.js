/**
 * External dependencies
 */
import { BreakpointSwitcher } from '@isudev/gutenberg/components/BreakpointSwitcher';
import { useBreakpoint } from '@isudev/gutenberg/hooks/useBreakpoint';

/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	Placeholder,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import { select, useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { useCallback, useMemo, useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { asArray, getBlockConfig } from '../../utils/config';
import { getDefaultVariations } from './variations';

const ALLOWED_BLOCKS = ['isudev/bento-card'];

/**
 * Editor UI for the bento grid wrapper.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {string}   props.clientId      This block instance's client id.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes, clientId }) {
	const { columns, gap, minCardHeight, hasChosenVariation } = attributes;
	const [breakpoint, setBreakpoint] = useBreakpoint({
		syncToEditor: true,
		syncFromEditor: true,
	});
	const [showGridGuide, setShowGridGuide] = useState(true);
	const { replaceInnerBlocks, updateBlockAttributes } =
		useDispatch('core/block-editor');

	/**
	 * Column count for the active breakpoint.
	 */
	const getColumns = useCallback(
		() => columns[breakpoint] ?? columns.desktop,
		[columns, breakpoint]
	);

	/**
	 * Set the column count for the active breakpoint, clamping any card
	 * whose span at this breakpoint now exceeds it.
	 */
	const setColumns = useCallback(
		(value) => {
			setAttributes({
				columns: { ...columns, [breakpoint]: value },
			});

			select('core/block-editor')
				.getBlocks(clientId)
				.forEach((block) => {
					const cardGridColumn = block.attributes.gridColumn;
					const deviceSpan = cardGridColumn?.[breakpoint]?.span;

					if (deviceSpan && deviceSpan > value) {
						updateBlockAttributes(block.clientId, {
							gridColumn: {
								...cardGridColumn,
								[breakpoint]: {
									...cardGridColumn[breakpoint],
									span: value,
								},
							},
						});
					}
				});
		},
		[columns, breakpoint, setAttributes, clientId, updateBlockAttributes]
	);

	/**
	 * Gap, in pixels, for the active breakpoint.
	 */
	const getGap = useCallback(
		() => parseInt(gap[breakpoint] ?? gap.desktop, 10),
		[gap, breakpoint]
	);

	/**
	 * Set the gap for the active breakpoint.
	 */
	const setGap = useCallback(
		(value) => {
			setAttributes({ gap: { ...gap, [breakpoint]: String(value) } });
		},
		[gap, breakpoint, setAttributes]
	);

	const onSelectVariation = useCallback(
		(variation) => {
			const innerBlocks = variation.innerBlocks.map((blockData) =>
				createBlock(
					blockData[0],
					blockData[1] || {},
					blockData[2] || []
				)
			);

			replaceInnerBlocks(clientId, innerBlocks, false);
			setAttributes({ hasChosenVariation: true });
		},
		[clientId, replaceInnerBlocks, setAttributes]
	);

	const onResetVariation = useCallback(() => {
		replaceInnerBlocks(clientId, [], false);
		setAttributes({ hasChosenVariation: false });
	}, [clientId, replaceInnerBlocks, setAttributes]);

	/*
	 * CSS custom properties matching the frontend PHP output. Inline styles
	 * are needed because the editor viewport doesn't correspond to the
	 * breakpoint being edited — media queries won't help here.
	 */
	const gridStyle = {
		'--bento-cols-desktop': columns.desktop,
		'--bento-cols-tablet': columns.tablet ?? columns.desktop,
		'--bento-cols-mobile': columns.mobile ?? columns.desktop,
		'--bento-gap-desktop': `${parseInt(gap.desktop, 10)}px`,
		'--bento-gap-tablet': `${parseInt(gap.tablet ?? gap.desktop, 10)}px`,
		'--bento-gap-mobile': `${parseInt(gap.mobile ?? gap.desktop, 10)}px`,
		'--bento-min-card-height': `${minCardHeight}px`,
		display: 'grid',
		gridTemplateColumns: `repeat(${getColumns()}, 1fr)`,
		gridAutoRows: `minmax(${minCardHeight}px, auto)`,
		gridAutoFlow: 'dense',
		gap: `${getGap()}px`,
	};

	const blockProps = useBlockProps({
		className: 'isudev-bento-grid',
		style: gridStyle,
	});

	const { children: innerBlocksChildren, ...gridProps } = useInnerBlocksProps(
		blockProps,
		{
			allowedBlocks: ALLOWED_BLOCKS,
			orientation: 'horizontal',
			renderAppender: hasChosenVariation ? undefined : false,
		}
	);

	if (!hasChosenVariation) {
		return (
			<div {...blockProps} style={{ display: 'block' }}>
				<VariationPicker onSelect={onSelectVariation} />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Grid settings', 'isudev-library')}>
					<BreakpointSwitcher
						value={breakpoint}
						onChange={setBreakpoint}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						label={__('Columns', 'isudev-library')}
						value={getColumns()}
						onChange={setColumns}
						min={1}
						max={6}
					/>
					{applyFilters(
						'isudevLibrary.bentoGrid.showGapControl',
						true
					) && (
						<RangeControl
							__nextHasNoMarginBottom
							label={__('Gap (px)', 'isudev-library')}
							value={getGap()}
							onChange={setGap}
							min={0}
							max={48}
							step={4}
						/>
					)}
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Grid guide', 'isudev-library')}
						help={__(
							'Shows the column grid in the editor. Does not affect the frontend.',
							'isudev-library'
						)}
						checked={showGridGuide}
						onChange={setShowGridGuide}
					/>
				</PanelBody>
				{applyFilters(
					'isudevLibrary.bentoGrid.showCardSizeControl',
					true
				) && (
					<PanelBody
						title={__('Card size', 'isudev-library')}
						initialOpen
					>
						<RangeControl
							__nextHasNoMarginBottom
							label={__(
								'Min. card height (px)',
								'isudev-library'
							)}
							value={minCardHeight}
							onChange={(value) =>
								setAttributes({ minCardHeight: value })
							}
							min={50}
							max={500}
							step={10}
						/>
					</PanelBody>
				)}
			</InspectorControls>

			<InspectorControls group="advanced">
				<Button
					variant="secondary"
					isDestructive
					onClick={onResetVariation}
				>
					{__('Reset layout', 'isudev-library')}
				</Button>
			</InspectorControls>

			<div {...gridProps}>
				{showGridGuide && (
					<div
						className="isudev-bento-grid__guide-overlay"
						style={{
							gridTemplateColumns: `repeat(${getColumns()}, 1fr)`,
							gap: `${getGap()}px`,
						}}
						aria-hidden="true"
					>
						{Array.from({ length: getColumns() * 8 }, (_, i) => (
							<div
								key={`ghost-cell-${i}`}
								className="isudev-bento-grid__ghost-cell"
							/>
						))}
					</div>
				)}
				{innerBlocksChildren}
			</div>
		</>
	);
}

/**
 * Layout picker shown until an author chooses a starting variation.
 *
 * @param {Object}   props          Component props.
 * @param {Function} props.onSelect Callback when a variation is selected.
 * @return {Element} Placeholder markup.
 */
function VariationPicker({ onSelect }) {
	/*
	 * Merge built-in layouts with any the theme registered under
	 * isudev/bento-grid's `customVariations` key in isudev.json, then let
	 * `isudevLibrary.bentoGrid.variations` filter the merged result.
	 */
	const variations = useMemo(() => {
		const libraryVariations = asArray(
			getBlockConfig('isudev/bento-grid', 'customVariations', []),
			[]
		).map((variation) => ({
			...variation,
			innerBlocks: (variation.innerBlocks || []).map((card) =>
				Array.isArray(card) ? card : ['isudev/bento-card', card]
			),
		}));

		const defaults = getDefaultVariations();
		const mergedDefaults = defaults.filter(
			(builtIn) =>
				!libraryVariations.some(
					(custom) => custom.name === builtIn.name
				)
		);

		return applyFilters('isudevLibrary.bentoGrid.variations', [
			...mergedDefaults,
			...libraryVariations,
		]);
	}, []);

	return (
		<Placeholder
			icon="grid-view"
			label={__('Bento Grid', 'isudev-library')}
			instructions={__(
				'Choose a layout to get started:',
				'isudev-library'
			)}
		>
			<div className="isudev-bento-grid__variations">
				{variations.map((variation) => (
					<button
						key={variation.name}
						className="isudev-bento-grid__variation-button"
						onClick={() => onSelect(variation)}
						title={variation.title}
						type="button"
					>
						<VariationIcon icon={variation.icon} />
						<span className="isudev-bento-grid__variation-label">
							{variation.title}
						</span>
					</button>
				))}
			</div>
		</Placeholder>
	);
}

/**
 * Renders a variation icon, supporting three formats: inline SVG (starts
 * with "<"), a URL (starts with "/" or "http"), or a Dashicon name.
 *
 * @param {Object} props      Component props.
 * @param {string} props.icon Icon source.
 * @return {Element} Icon markup.
 */
function VariationIcon({ icon }) {
	if (!icon) {
		return (
			<span className="isudev-bento-grid__variation-icon dashicons dashicons-grid-view" />
		);
	}

	if (icon.startsWith('/') || icon.startsWith('http')) {
		return (
			<span className="isudev-bento-grid__variation-icon">
				<img src={icon} alt="" width="48" height="48" />
			</span>
		);
	}

	if (icon.startsWith('dashicons-')) {
		return (
			<span
				className={`isudev-bento-grid__variation-icon dashicons ${icon}`}
			/>
		);
	}

	// Inline SVG, trusted theme/plugin configuration, not user input.
	return (
		// eslint-disable-next-line react/no-danger
		<span
			className="isudev-bento-grid__variation-icon"
			dangerouslySetInnerHTML={{ __html: icon }}
		/>
	);
}
