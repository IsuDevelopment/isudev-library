/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Default layout variations for the Bento Grid block.
 *
 * Each variation defines:
 * - name: Unique identifier
 * - title: Display label
 * - icon: SVG string for the visual preview
 * - innerBlocks: Array of [blockName, attributes, innerBlocks] tuples
 *
 * A theme can add, remove or override these from `isudev.json`
 * (`isudev/bento-grid`'s `customVariations` key — see
 * guides/bento-grid.md) or via the `isudevLibrary.bentoGrid.variations` JS
 * filter.
 *
 * @return {Array} Array of variation objects.
 */
export function getDefaultVariations() {
	return [
		{
			name: 'layout-1',
			title: __('Layout 1', 'isudev-library'),
			icon: `<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
				<rect x="1" y="1" width="30" height="46" rx="2" fill="currentColor"/>
				<rect x="33" y="1" width="14" height="22" rx="2" fill="currentColor"/>
				<rect x="33" y="25" width="14" height="22" rx="2" fill="currentColor"/>
			</svg>`,
			innerBlocks: [
				// Hero card: 4 cols × 2 rows (desktop), 2×1 (tablet), 1×1 (mobile)
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-1',
						gridColumn: {
							desktop: { start: 'auto', span: 4 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				// Side cards: 2 cols × 1 row (desktop), 2×1 (tablet), 1×1 (mobile)
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-1',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-1',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
			],
		},
		{
			name: 'layout-2',
			title: __('Layout 2', 'isudev-library'),
			icon: `<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
				<rect x="1" y="1" width="14" height="46" rx="2" fill="currentColor"/>
				<rect x="17" y="1" width="14" height="46" rx="2" fill="currentColor"/>
				<rect x="33" y="1" width="14" height="46" rx="2" fill="currentColor"/>
			</svg>`,
			innerBlocks: [
				// 3 equal cards: 2 cols × 2 rows each — fills 6 columns
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-2',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-2',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-2',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
			],
		},
		{
			name: 'layout-3',
			title: __('Layout 3', 'isudev-library'),
			icon: `<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
				<rect x="1" y="1" width="22" height="22" rx="2" fill="currentColor"/>
				<rect x="25" y="1" width="22" height="22" rx="2" fill="currentColor"/>
				<rect x="1" y="25" width="22" height="22" rx="2" fill="currentColor"/>
				<rect x="25" y="25" width="22" height="22" rx="2" fill="currentColor"/>
			</svg>`,
			innerBlocks: [
				// 4 cards: 3 cols × 2 rows each — 2 per row on 6-column grid
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-3',
						gridColumn: {
							desktop: { start: 'auto', span: 3 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-3',
						gridColumn: {
							desktop: { start: 'auto', span: 3 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-3',
						gridColumn: {
							desktop: { start: 'auto', span: 3 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-3',
						gridColumn: {
							desktop: { start: 'auto', span: 3 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
			],
		},
		{
			name: 'layout-4',
			title: __('Layout 4', 'isudev-library'),
			icon: `<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
				<rect x="1" y="1" width="14" height="22" rx="2" fill="currentColor"/>
				<rect x="17" y="1" width="14" height="22" rx="2" fill="currentColor"/>
				<rect x="33" y="1" width="14" height="22" rx="2" fill="currentColor"/>
				<rect x="1" y="25" width="14" height="22" rx="2" fill="currentColor"/>
				<rect x="17" y="25" width="14" height="22" rx="2" fill="currentColor"/>
				<rect x="33" y="25" width="14" height="22" rx="2" fill="currentColor"/>
			</svg>`,
			innerBlocks: [
				// 6 equal cards: 2 cols × 1 row each — 3 per row on 6-column grid
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-4',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-4',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-4',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-4',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-4',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-4',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
			],
		},
		{
			name: 'layout-5',
			title: __('Layout 5', 'isudev-library'),
			icon: `<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
				<rect x="1" y="1" width="14" height="46" rx="2" fill="currentColor"/>
				<rect x="17" y="1" width="14" height="22" rx="2" fill="currentColor"/>
				<rect x="33" y="1" width="14" height="22" rx="2" fill="currentColor"/>
				<rect x="17" y="25" width="14" height="22" rx="2" fill="currentColor"/>
				<rect x="33" y="25" width="14" height="22" rx="2" fill="currentColor"/>
			</svg>`,
			innerBlocks: [
				// Tall left card: 2 cols × 2 rows
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-5',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				// 4 smaller cards: 2 cols × 1 row each (2 columns × 2 rows on right side)
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-5',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-5',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-5',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
				[
					'isudev/bento-card',
					{
						_namespace: 'layout-5',
						gridColumn: {
							desktop: { start: 'auto', span: 2 },
							tablet: { start: 'auto', span: 2 },
							mobile: { start: 'auto', span: 1 },
						},
						gridRow: {
							desktop: { start: 'auto', span: 1 },
							tablet: { start: 'auto', span: 1 },
							mobile: { start: 'auto', span: 1 },
						},
					},
				],
			],
		},
	];
}
