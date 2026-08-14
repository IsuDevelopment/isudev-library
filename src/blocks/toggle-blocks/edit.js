/**
 * External dependencies
 */
import { Icon, getLocalizedIcons } from '@isudev/gutenberg/components/Icon';

/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	RichText,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getBlockConfig } from '../../utils/config';

const BLOCK_NAME = 'isudev/toggle-blocks';

const defaultIcons = getLocalizedIcons();

/**
 * Editor UI for the collapsible toggle block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const {
		openText,
		closeText,
		openByDefault,
		scrollToContent,
		toggleSpeed,
		togglePlacement,
		buttonStyle,
		_namespace: namespace = '',
	} = attributes;

	const iconPosition =
		getBlockConfig(BLOCK_NAME, 'iconPosition', 'right', namespace) ===
		'left'
			? 'left'
			: 'right';
	const openIconName = getBlockConfig(
		BLOCK_NAME,
		'icons.open',
		'',
		namespace
	);
	const closeIconName = getBlockConfig(
		BLOCK_NAME,
		'icons.close',
		'',
		namespace
	);
	// The editor always shows the "open" state of the button, so a close-only
	// icon still needs to appear somewhere — this mirrors render.php.
	const iconName = openIconName || closeIconName;

	const buttonStyles = useSelect(
		(select) => select('core/blocks')?.getBlockStyles('core/button') || [],
		[]
	);

	const blockProps = useBlockProps({
		className: `isudev-toggle isudev-toggle--placement-${togglePlacement}`,
	});

	const innerBlocksProps = useInnerBlocksProps({
		className: 'isudev-toggle__content-editor',
	});

	const buttonWrapperClasses = [
		'isudev-toggle__button-wrapper',
		'wp-block-button',
		`isudev-toggle__button-wrapper--placement-${togglePlacement}`,
	];

	if (buttonStyle) {
		buttonWrapperClasses.push(`is-style-${buttonStyle}`);
	}

	const toggleButton = (
		<div className={buttonWrapperClasses.join(' ')}>
			<div
				className={`isudev-toggle__button isudev-toggle__button--icon-${iconPosition} wp-block-button__link wp-element-button`}
			>
				<RichText
					tagName="span"
					className="isudev-toggle__text"
					value={openText}
					allowedFormats={[]}
					onChange={(value) => setAttributes({ openText: value })}
					placeholder={__('Enter toggle text…', 'isudev-library')}
				/>
				{iconName && (
					<span className="isudev-toggle__icons" aria-hidden="true">
						<span className="isudev-toggle__icon isudev-toggle__icon--open">
							<Icon
								name={iconName}
								defaultIcons={defaultIcons}
								size={20}
							/>
						</span>
					</span>
				)}
			</div>
		</div>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Settings', 'isudev-library')} initialOpen>
					<TextControl
						__nextHasNoMarginBottom
						label={__('Close text', 'isudev-library')}
						value={closeText}
						onChange={(value) =>
							setAttributes({ closeText: value })
						}
						help={__(
							'Shown on the button while the content is open.',
							'isudev-library'
						)}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Open by default', 'isudev-library')}
						checked={openByDefault}
						onChange={(value) =>
							setAttributes({ openByDefault: value })
						}
						help={__(
							'Expand the content when the page loads.',
							'isudev-library'
						)}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Scroll to content', 'isudev-library')}
						checked={scrollToContent}
						onChange={(value) =>
							setAttributes({ scrollToContent: value })
						}
						help={__(
							'Scroll the content into view when it opens.',
							'isudev-library'
						)}
					/>
					<p className="isudev-toggle-note">
						{__(
							'The toggle is always open in the editor so its content can be edited. It works as expected on the frontend.',
							'isudev-library'
						)}
					</p>
				</PanelBody>
			</InspectorControls>

			<InspectorControls group="styles">
				<PanelBody title={__('Style', 'isudev-library')} initialOpen>
					<SelectControl
						__nextHasNoMarginBottom
						label={__('Button placement', 'isudev-library')}
						value={togglePlacement}
						options={[
							{
								label: __('Top', 'isudev-library'),
								value: 'top',
							},
							{
								label: __('Bottom', 'isudev-library'),
								value: 'bottom',
							},
						]}
						onChange={(value) =>
							setAttributes({ togglePlacement: value })
						}
					/>

					<div className="isudev-toggle-button-style-select">
						<Button
							size="compact"
							variant={
								buttonStyle === '' ? 'primary' : 'secondary'
							}
							onClick={() => setAttributes({ buttonStyle: '' })}
						>
							{__('Default', 'isudev-library')}
						</Button>
						{buttonStyles.map((style) => (
							<Button
								key={style.name}
								size="compact"
								variant={
									buttonStyle === style.name
										? 'primary'
										: 'secondary'
								}
								onClick={() =>
									setAttributes({ buttonStyle: style.name })
								}
							>
								{style.label}
							</Button>
						))}
					</div>

					<RangeControl
						__nextHasNoMarginBottom
						label={__('Animation speed (ms)', 'isudev-library')}
						value={toggleSpeed}
						min={0}
						max={1000}
						step={10}
						allowReset
						resetFallbackValue={150}
						onChange={(value) =>
							setAttributes({
								toggleSpeed: Math.round(value ?? 150),
							})
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				{togglePlacement === 'top' && toggleButton}
				<div {...innerBlocksProps} />
				{togglePlacement !== 'top' && toggleButton}
			</div>
		</>
	);
}
