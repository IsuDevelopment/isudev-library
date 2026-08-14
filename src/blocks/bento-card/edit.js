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
import { PanelBody, RangeControl } from '@wordpress/components';
import { useCallback, useRef, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { asArray, getBlockConfig } from '../../utils/config';

const BLOCK_NAME = 'isudev/bento-card';

const DEFAULT_ALLOWED_BLOCKS = [
	'core/heading',
	'core/paragraph',
	'core/image',
	'core/buttons',
	'core/list',
];
const DEFAULT_TEMPLATE = [];

/**
 * Editor UI for a single bento card, including drag-to-resize handles for
 * its column and row span.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {string}   props.clientId      This block instance's client id.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes, clientId }) {
	const { gridColumn, gridRow, _namespace: namespace = '' } = attributes;
	const [breakpoint, setBreakpoint] = useBreakpoint({
		syncToEditor: true,
		syncFromEditor: true,
	});
	const [isResizing, setIsResizing] = useState(false);
	const [ghostSize, setGhostSize] = useState(null);
	const cardRef = useRef(null);

	const { parentColumnsObj, parentClientId } = useSelect(
		(select) => {
			const { getBlockParents, getBlockAttributes } =
				select('core/block-editor');
			const parents = getBlockParents(clientId);

			if (parents.length > 0) {
				const parentId = parents[parents.length - 1];
				const parentAttrs = getBlockAttributes(parentId);

				return {
					parentColumnsObj: parentAttrs?.columns ?? { desktop: 4 },
					parentClientId: parentId,
				};
			}

			return { parentColumnsObj: { desktop: 4 }, parentClientId: null };
		},
		[clientId]
	);

	const { updateBlockAttributes } = useDispatch('core/block-editor');

	/**
	 * Column span for the active breakpoint.
	 */
	const getColSpan = useCallback(() => {
		return gridColumn[breakpoint]?.span ?? gridColumn.desktop?.span ?? 1;
	}, [gridColumn, breakpoint]);

	/**
	 * Row span for the active breakpoint.
	 */
	const getRowSpan = useCallback(() => {
		return gridRow[breakpoint]?.span ?? gridRow.desktop?.span ?? 1;
	}, [gridRow, breakpoint]);

	/**
	 * Grow the parent grid's column count when a card's span at this
	 * breakpoint now exceeds it.
	 */
	const expandParentColumns = useCallback(
		(value) => {
			if (!parentClientId) {
				return;
			}

			const currentMax =
				parentColumnsObj[breakpoint] ?? parentColumnsObj.desktop ?? 4;

			if (value > currentMax) {
				updateBlockAttributes(parentClientId, {
					columns: { ...parentColumnsObj, [breakpoint]: value },
				});
			}
		},
		[parentClientId, parentColumnsObj, breakpoint, updateBlockAttributes]
	);

	const setColSpan = useCallback(
		(value) => {
			setAttributes({
				gridColumn: {
					...gridColumn,
					[breakpoint]: {
						...(gridColumn[breakpoint] || { start: 'auto' }),
						span: value,
					},
				},
			});
			expandParentColumns(value);
		},
		[gridColumn, breakpoint, setAttributes, expandParentColumns]
	);

	const setRowSpan = useCallback(
		(value) => {
			setAttributes({
				gridRow: {
					...gridRow,
					[breakpoint]: {
						...(gridRow[breakpoint] || { start: 'auto' }),
						span: value,
					},
				},
			});
		},
		[gridRow, breakpoint, setAttributes]
	);

	/**
	 * Drag-to-resize handler for the card's right edge (column span).
	 *
	 * @param {MouseEvent} event
	 */
	const handleResizeRight = useCallback(
		(event) => {
			event.preventDefault();
			event.stopPropagation();
			setIsResizing(true);

			const card = cardRef.current;
			if (!card) {
				return;
			}

			const ownerDoc = card.ownerDocument;
			const parentGrid = card.closest('.isudev-bento-grid');
			if (!parentGrid) {
				return;
			}

			const gridRect = parentGrid.getBoundingClientRect();
			const gridComputedStyle =
				ownerDoc.defaultView.getComputedStyle(parentGrid);
			// The actual rendered column count — the inline style sets
			// gridTemplateColumns for the active breakpoint.
			const cols =
				gridComputedStyle.gridTemplateColumns.split(' ').length ||
				parentColumnsObj?.desktop ||
				4;
			const cellWidth = gridRect.width / cols;
			const startX = event.clientX;
			const startSpan = getColSpan();
			const cardRect = card.getBoundingClientRect();
			const startWidth = cardRect.width;
			const startHeight = cardRect.height;

			setGhostSize({ width: startWidth, height: startHeight });
			let currentSpan = startSpan;

			const onMouseMove = (moveEvent) => {
				const deltaX = moveEvent.clientX - startX;
				setGhostSize({
					width: startWidth + deltaX,
					height: startHeight,
				});

				// No hard cap — exceeding the parent's columns expands the grid.
				const spanDelta = Math.round(deltaX / cellWidth);
				const newSpan = Math.max(1, startSpan + spanDelta);
				if (newSpan !== currentSpan) {
					currentSpan = newSpan;
					setColSpan(newSpan);
				}
			};

			const onMouseUp = () => {
				setIsResizing(false);
				setGhostSize(null);
				ownerDoc.removeEventListener('mousemove', onMouseMove);
				ownerDoc.removeEventListener('mouseup', onMouseUp);
			};

			ownerDoc.addEventListener('mousemove', onMouseMove);
			ownerDoc.addEventListener('mouseup', onMouseUp);
		},
		[getColSpan, setColSpan, parentColumnsObj]
	);

	/**
	 * Drag-to-resize handler for the card's bottom edge (row span).
	 *
	 * @param {MouseEvent} event
	 */
	const handleResizeBottom = useCallback(
		(event) => {
			event.preventDefault();
			event.stopPropagation();
			setIsResizing(true);

			const card = cardRef.current;
			if (!card) {
				return;
			}

			const ownerDoc = card.ownerDocument;
			const cardRect = card.getBoundingClientRect();
			const rowHeight = cardRect.height / getRowSpan();
			const startY = event.clientY;
			const startSpan = getRowSpan();
			const startWidth = cardRect.width;
			const startHeight = cardRect.height;

			setGhostSize({ width: startWidth, height: startHeight });
			let currentSpan = startSpan;

			const onMouseMove = (moveEvent) => {
				const deltaY = moveEvent.clientY - startY;
				setGhostSize({
					width: startWidth,
					height: startHeight + deltaY,
				});

				const spanDelta = Math.round(deltaY / (rowHeight || 100));
				const newSpan = Math.max(1, Math.min(6, startSpan + spanDelta));
				if (newSpan !== currentSpan) {
					currentSpan = newSpan;
					setRowSpan(newSpan);
				}
			};

			const onMouseUp = () => {
				setIsResizing(false);
				setGhostSize(null);
				ownerDoc.removeEventListener('mousemove', onMouseMove);
				ownerDoc.removeEventListener('mouseup', onMouseUp);
			};

			ownerDoc.addEventListener('mousemove', onMouseMove);
			ownerDoc.addEventListener('mouseup', onMouseUp);
		},
		[getRowSpan, setRowSpan]
	);

	const colSpan = getColSpan();
	const rowSpan = getRowSpan();

	/*
	 * CSS custom properties matching the frontend PHP output. Inline
	 * grid-column/grid-row are needed because the editor shows one
	 * breakpoint at a time — media queries in style.scss won't apply here.
	 */
	const cardStyle = {
		'--grid-col-desktop': `span ${gridColumn.desktop?.span ?? 1}`,
		'--grid-row-desktop': `span ${gridRow.desktop?.span ?? 1}`,
		...(gridColumn.tablet?.span && {
			'--grid-col-tablet': `span ${gridColumn.tablet.span}`,
		}),
		...(gridRow.tablet?.span && {
			'--grid-row-tablet': `span ${gridRow.tablet.span}`,
		}),
		...(gridColumn.mobile?.span && {
			'--grid-col-mobile': `span ${gridColumn.mobile.span}`,
		}),
		...(gridRow.mobile?.span && {
			'--grid-row-mobile': `span ${gridRow.mobile.span}`,
		}),
		gridColumn: `span ${colSpan}`,
		gridRow: `span ${rowSpan}`,
	};

	const blockProps = useBlockProps({
		ref: cardRef,
		className: `isudev-bento-card${isResizing ? ' is-resizing' : ''}`,
		style: cardStyle,
	});

	// asArray: a mistyped list in isudev.json must not TypeError the editor.
	const allowedBlocks = asArray(
		getBlockConfig(
			BLOCK_NAME,
			'allowedBlocks',
			DEFAULT_ALLOWED_BLOCKS,
			namespace
		),
		DEFAULT_ALLOWED_BLOCKS
	);
	const template = asArray(
		getBlockConfig(BLOCK_NAME, 'template', DEFAULT_TEMPLATE, namespace),
		DEFAULT_TEMPLATE
	);
	const templateLock = getBlockConfig(
		BLOCK_NAME,
		'templateLock',
		false,
		namespace
	);

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'isudev-bento-card__content' },
		{
			allowedBlocks,
			template: template.length > 0 ? template : undefined,
			templateLock,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Card position', 'isudev-library')}>
					<BreakpointSwitcher
						value={breakpoint}
						onChange={setBreakpoint}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						label={__('Column span', 'isudev-library')}
						value={colSpan}
						onChange={setColSpan}
						min={1}
						max={6}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						label={__('Row span', 'isudev-library')}
						value={rowSpan}
						onChange={setRowSpan}
						min={1}
						max={6}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div {...innerBlocksProps} />

				{ghostSize && (
					<div
						className="isudev-bento-card__ghost"
						style={{
							width: `${Math.max(40, ghostSize.width)}px`,
							height: `${Math.max(40, ghostSize.height)}px`,
						}}
					/>
				)}

				{/* A drag handle, not a static separator — mouse-only interaction is
				    acceptable here because RangeControl above offers the same
				    resize as a keyboard-operable fallback. */}
				{/* eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions */}
				<div
					className="isudev-bento-card__resize-handle isudev-bento-card__resize-handle--right"
					onMouseDown={handleResizeRight}
					role="separator"
					aria-orientation="vertical"
					aria-label={__('Resize card width', 'isudev-library')}
					tabIndex={0}
				/>

				{/* eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions */}
				<div
					className="isudev-bento-card__resize-handle isudev-bento-card__resize-handle--bottom"
					onMouseDown={handleResizeBottom}
					role="separator"
					aria-orientation="horizontal"
					aria-label={__('Resize card height', 'isudev-library')}
					tabIndex={0}
				/>
			</div>
		</>
	);
}
