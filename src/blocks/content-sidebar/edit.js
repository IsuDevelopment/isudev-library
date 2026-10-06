/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getBlockConfig } from '../../utils/config';

const BLOCK_NAME = 'isudev/content-sidebar';
const TEMPLATE = [
	['isudev/content-sidebar-main'],
	['isudev/content-sidebar-aside'],
];

/**
 * Read a lockable setting: `<key>.disable` hides the control and pins the
 * value to `<key>.value`. Mirrors effective_value() in inc/render-helpers.php.
 *
 * @param {string} key       Config key, e.g. `sticky`.
 * @param {*}      attribute Saved attribute value.
 * @param {*}      fallback  Default for `<key>.value`.
 * @return {{disabled: boolean, value: *}} Lock state and effective value.
 */
function lockable(key, attribute, fallback) {
	const disabled = !!getBlockConfig(BLOCK_NAME, `${key}.disable`, false);

	return {
		disabled,
		value: disabled
			? getBlockConfig(BLOCK_NAME, `${key}.value`, fallback)
			: attribute,
	};
}

/**
 * Editor UI for the content-with-sidebar wrapper.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const sidebarPosition = lockable(
		'sidebarPosition',
		attributes.sidebarPosition,
		'right'
	);
	const mobilePosition = lockable(
		'mobilePosition',
		attributes.mobilePosition,
		'bottom'
	);
	const sticky = lockable('sticky', attributes.sticky, true);

	const blockProps = useBlockProps({
		className: [
			'isudev-content-sidebar',
			`is-sidebar-${sidebarPosition.value === 'left' ? 'left' : 'right'}`,
			`is-mobile-sidebar-${mobilePosition.value === 'top' ? 'top' : 'bottom'}`,
			sticky.value ? 'has-sticky-sidebar' : '',
		]
			.filter(Boolean)
			.join(' '),
	});

	// templateLock 'all' keeps exactly one main column and one sidebar.
	const innerBlocksProps = useInnerBlocksProps(blockProps, {
		template: TEMPLATE,
		templateLock: 'all',
	});

	const hasControls =
		!sidebarPosition.disabled ||
		!mobilePosition.disabled ||
		!sticky.disabled;

	return (
		<>
			{hasControls && (
				<InspectorControls>
					<PanelBody title={__('Sidebar', 'isudev-library')}>
						{!sidebarPosition.disabled && (
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={__('Sidebar position', 'isudev-library')}
								value={sidebarPosition.value}
								options={[
									{
										label: __('Right', 'isudev-library'),
										value: 'right',
									},
									{
										label: __('Left', 'isudev-library'),
										value: 'left',
									},
								]}
								onChange={(value) =>
									setAttributes({ sidebarPosition: value })
								}
							/>
						)}
						{!mobilePosition.disabled && (
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={__('On mobile', 'isudev-library')}
								value={mobilePosition.value}
								options={[
									{
										label: __(
											'Below the content',
											'isudev-library'
										),
										value: 'bottom',
									},
									{
										label: __(
											'Above the content',
											'isudev-library'
										),
										value: 'top',
									},
								]}
								onChange={(value) =>
									setAttributes({ mobilePosition: value })
								}
							/>
						)}
						{!sticky.disabled && (
							<ToggleControl
								__nextHasNoMarginBottom
								label={__('Sticky sidebar', 'isudev-library')}
								help={__(
									'Keeps the sidebar in view while the content scrolls (wide screens).',
									'isudev-library'
								)}
								checked={!!sticky.value}
								onChange={(value) =>
									setAttributes({ sticky: value })
								}
							/>
						)}
					</PanelBody>
				</InspectorControls>
			)}
			<div {...innerBlocksProps} />
		</>
	);
}
