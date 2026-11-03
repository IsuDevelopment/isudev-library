/**
 * External dependencies
 */
import { PostPickerControl } from '@isudev/gutenberg/controls/PostPickerControl';
import { ResponsiveControl } from '@isudev/gutenberg/controls/ResponsiveControl';
import { useCurrentPostId } from '@isudev/gutenberg/hooks/useCurrentPostId';

/**
 * WordPress dependencies
 */
import {
	BlockControls,
	InnerBlocks,
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { variantsFor } from '../../utils/post-cards/data';
import CardOptionsPanel from '../../utils/post-cards/options-panel';
import pickerLabels from '../../utils/post-cards/picker-labels';
import { WrapperProvider } from '../../utils/post-cards/wrapper-context';
import QueryPanel from './query-panel';

const CARD = 'isudev/post-card';
const TEMPLATE = [
	[CARD, { source: 'query' }],
	[CARD, { source: 'query' }],
	[CARD, { source: 'query' }],
];

export default function Edit({ attributes, setAttributes, clientId }) {
	const {
		layout,
		columns,
		columnsTablet,
		columnsMobile,
		query,
		variant,
		cardOptions,
		uniqueOnPage,
		label,
	} = attributes;
	const currentPostId = useCurrentPostId();
	const { insertBlocks, replaceBlock } = useDispatch(blockEditorStore);

	const blocks = useSelect(
		(select) => select(blockEditorStore).getBlocks(clientId),
		[clientId]
	);

	const cards = blocks.map((block) => ({
		source: block.attributes.source,
		postId: block.attributes.postId,
	}));
	const cardsKey = JSON.stringify(cards);

	const orderKey = blocks.map((block) => block.clientId).join(',');

	// "No repeats on this page": posts hand-picked in cards above this wrapper.
	// Posts that earlier wrappers' query slots take are only known on render.
	const alsoKey = useSelect(
		(select) => {
			if (!uniqueOnPage) {
				return '';
			}
			const { getBlock, getClientIdsWithDescendants } =
				select(blockEditorStore);
			const all = getClientIdsWithDescendants();
			return all
				.slice(0, all.indexOf(clientId))
				.map(getBlock)
				.filter(
					(block) =>
						block?.name === CARD &&
						block.attributes.source !== 'query' &&
						block.attributes.postId > 0
				)
				.map((block) => block.attributes.postId)
				.join(',');
		},
		[clientId, uniqueOnPage]
	);

	const wrapper = useMemo(
		() => ({
			attributes: { query },
			cards: JSON.parse(cardsKey),
			order: orderKey.split(','),
			currentPostId: currentPostId || 0,
			also: alsoKey ? alsoKey.split(',').map(Number) : [],
		}),
		[query, cardsKey, orderKey, currentPostId, alsoKey]
	);

	const addPosts = (ids) => {
		const present = new Set(cards.map((card) => card.postId));
		const fresh = (ids ?? [])
			.map((id) => Number(id?.id ?? id))
			.filter((id) => id && !present.has(id));
		const empty = blocks.filter(
			(block) =>
				block.attributes.source !== 'query' && !block.attributes.postId
		);

		fresh.forEach((postId, index) => {
			if (empty[index]) {
				replaceBlock(
					empty[index].clientId,
					createBlock(CARD, { ...empty[index].attributes, postId })
				);
			}
		});

		const rest = fresh.slice(empty.length);
		if (rest.length) {
			insertBlocks(
				rest.map((postId) => createBlock(CARD, { postId })),
				blocks.length,
				clientId,
				false
			);
		}
	};

	const addSlot = () =>
		insertBlocks(
			createBlock(CARD, { source: 'query' }),
			blocks.length,
			clientId,
			false
		);

	const blockProps = useBlockProps({
		className: `isudev-post-cards is-layout-${layout}`,
		style: {
			'--isudev-cards-columns': columns || 3,
			'--isudev-cards-columns-tablet': columnsTablet || columns || 3,
			'--isudev-cards-columns-mobile':
				columnsMobile || columnsTablet || columns || 3,
		},
	});

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'isudev-post-cards__grid' },
		{
			allowedBlocks: [CARD],
			template: TEMPLATE,
			orientation: 'horizontal',
			renderAppender: InnerBlocks.ButtonBlockAppender,
		}
	);

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<PostPickerControl
						value={[]}
						onChange={addPosts}
						labels={pickerLabels()}
					>
						{({ open }) => (
							<ToolbarButton onClick={open}>
								{__('Add posts', 'isudev-library')}
							</ToolbarButton>
						)}
					</PostPickerControl>
					<ToolbarButton onClick={addSlot}>
						{__('Add query slot', 'isudev-library')}
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={__('Layout', 'isudev-library')}>
					<SelectControl
						label={__('Display as', 'isudev-library')}
						value={layout}
						options={[
							{
								value: 'grid',
								label: __('Grid', 'isudev-library'),
							},
							{
								value: 'slider',
								label: __('Carousel', 'isudev-library'),
							},
						]}
						help={
							layout === 'slider'
								? __(
										'The carousel runs on the page; the editor shows the cards as a grid. Columns set how many cards are in view.',
										'isudev-library'
									)
								: undefined
						}
						onChange={(next) => setAttributes({ layout: next })}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<ResponsiveControl
						attrName="columns"
						label={__('Columns', 'isudev-library')}
						attributes={attributes}
						setAttributes={setAttributes}
					>
						{({ value, inheritedValue, onChange }) => (
							<RangeControl
								min={1}
								max={6}
								value={value ?? inheritedValue ?? 3}
								onChange={onChange}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						)}
					</ResponsiveControl>
					<TextControl
						label={__('Accessible name', 'isudev-library')}
						help={__(
							'Read out by screen readers for the list or carousel, e.g. "Latest news".',
							'isudev-library'
						)}
						value={label}
						onChange={(next) => setAttributes({ label: next })}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<QueryPanel
					query={query}
					onChange={(next) =>
						setAttributes({ query: { ...query, ...next } })
					}
				/>
				<PanelBody
					title={__('Cards', 'isudev-library')}
					initialOpen={false}
				>
					<SelectControl
						label={__('Template for every card', 'isudev-library')}
						value={variant}
						options={[
							{
								value: '',
								label: __(
									'Default for each post type',
									'isudev-library'
								),
							},
							...variantsFor(null),
						]}
						help={__(
							'A card without this variant for its post type uses its default template. Each card can choose its own.',
							'isudev-library'
						)}
						onChange={(next) => setAttributes({ variant: next })}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={__('No repeats on this page', 'isudev-library')}
						help={__(
							'Query slots skip posts already shown by cards earlier on the page. The editor preview only skips this block’s own hand-picked posts.',
							'isudev-library'
						)}
						checked={uniqueOnPage}
						onChange={(next) =>
							setAttributes({ uniqueOnPage: next })
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<CardOptionsPanel
					level="cards"
					postType={null}
					value={cardOptions}
					onChange={(next) => setAttributes({ cardOptions: next })}
				/>
			</InspectorControls>
			<div {...blockProps}>
				<WrapperProvider value={wrapper}>
					<div {...innerBlocksProps} />
				</WrapperProvider>
			</div>
		</>
	);
}
