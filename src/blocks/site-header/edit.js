/**
 * WordPress dependencies
 */
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	Spinner,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const ALLOWED_END_BLOCKS = [
	'core/buttons',
	'core/button',
	'core/search',
	'core/navigation',
	'core/paragraph',
	'core/social-links',
];

/**
 * Editor UI for the accessible site header.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const {
		menuRef,
		logoSource,
		logoUrl,
		sticky,
		whiteHeaderBodyClass,
		ariaLabel,
	} = attributes;

	const [menuOptions, setMenuOptions] = useState(null);

	useEffect(() => {
		let cancelled = false;
		const load = async () => {
			const options = [
				{ label: __('Select a menu…', 'isudev-library'), value: '' },
			];
			try {
				const locations = await apiFetch({
					path: '/wp/v2/menu-locations',
				});
				Object.values(locations || {}).forEach((loc) => {
					options.push({
						label: `${__('Location', 'isudev-library')}: ${loc.description || loc.name}`,
						value: `location:${loc.name}`,
					});
				});
			} catch (e) {
				// Locations endpoint unavailable — fall through to menus.
			}
			try {
				const menus = await apiFetch({
					path: '/wp/v2/menus?per_page=100',
				});
				(menus || []).forEach((menu) => {
					options.push({
						label: `${__('Menu', 'isudev-library')}: ${menu.name}`,
						value: `id:${menu.id}`,
					});
				});
			} catch (e) {
				// Menus endpoint unavailable.
			}
			if (!cancelled) {
				setMenuOptions(options);
			}
		};
		load();
		return () => {
			cancelled = true;
		};
	}, []);

	const blockProps = useBlockProps({
		className: 'isudev-header isudev-header--editor',
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Menu', 'isudev-library')}>
					{menuOptions === null ? (
						<Spinner />
					) : (
						<SelectControl
							label={__('Navigation source', 'isudev-library')}
							value={menuRef}
							options={menuOptions}
							onChange={(value) =>
								setAttributes({ menuRef: value })
							}
							__nextHasNoMarginBottom
						/>
					)}
					<TextControl
						label={__('Nav ARIA label', 'isudev-library')}
						value={ariaLabel}
						onChange={(value) =>
							setAttributes({ ariaLabel: value })
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelBody title={__('Logo', 'isudev-library')}>
					<SelectControl
						label={__('Logo source', 'isudev-library')}
						value={logoSource}
						options={[
							{
								label: __(
									'Site logo / title',
									'isudev-library'
								),
								value: 'site',
							},
							{
								label: __('Custom image URL', 'isudev-library'),
								value: 'custom',
							},
							{
								label: __('None', 'isudev-library'),
								value: 'none',
							},
						]}
						onChange={(value) =>
							setAttributes({ logoSource: value })
						}
						__nextHasNoMarginBottom
					/>
					{logoSource === 'custom' && (
						<TextControl
							label={__('Logo image URL', 'isudev-library')}
							value={logoUrl}
							onChange={(value) =>
								setAttributes({ logoUrl: value })
							}
							__nextHasNoMarginBottom
						/>
					)}
				</PanelBody>
				<PanelBody title={__('Behavior', 'isudev-library')}>
					<ToggleControl
						label={__('Sticky header', 'isudev-library')}
						checked={sticky}
						onChange={(value) => setAttributes({ sticky: value })}
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={__('White-header body class', 'isudev-library')}
						help={__(
							'When <body> has this class, the header is transparent until scrolled.',
							'isudev-library'
						)}
						value={whiteHeaderBodyClass}
						onChange={(value) =>
							setAttributes({ whiteHeaderBodyClass: value })
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="isudev-header__inner">
					<span className="isudev-header__logo isudev-header__logo--text">
						{logoSource === 'none'
							? __('(no logo)', 'isudev-library')
							: __('Logo', 'isudev-library')}
					</span>
					<span className="isudev-header__editor-note">
						{menuRef
							? `${__('Menu', 'isudev-library')}: ${menuRef}`
							: __('No menu selected', 'isudev-library')}
					</span>
					<div className="isudev-header__end">
						<InnerBlocks
							allowedBlocks={ALLOWED_END_BLOCKS}
							template={[['core/buttons']]}
							templateLock={false}
						/>
					</div>
				</div>
			</div>
		</>
	);
}
