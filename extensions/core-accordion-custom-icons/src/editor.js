/**
 * Accordion toggle icons, editor side. Mirrors hooks.php: three attributes on
 * core/accordion, an inspector panel, and a canvas preview that paints the
 * icons as a CSS mask from custom properties on the accordion wrapper (no DOM
 * writes, so it survives remounts and works inside the iframed canvas).
 */

/* global globalThis */

/**
 * External dependencies
 */
import { getLocalizedIcons } from '@isudev/gutenberg/components/Icon';
import { IconSelect } from '@isudev/gutenberg/components/IconSelect';

/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { Notice, PanelBody, RangeControl } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

const BLOCK = 'core/accordion';

// Published by hooks.php enqueue_editor(); the fallbacks only cover a missing inline script.
const CONFIG = {
	closed: 'chevronDown',
	open: 'chevronUp',
	size: 24,
	minSize: 8,
	maxSize: 128,
	...(globalThis.isudevAccordionIcons || {}),
};

const icons = getLocalizedIcons();

/**
 * Registry entry for a name.
 *
 * @param {string} name Icon name.
 * @return {Object|undefined} Definition with `icon` and `label`.
 */
const findIcon = (name) => icons.find((entry) => entry.name === name);

/**
 * Mirrors clamp_size() in hooks.php.
 *
 * @param {*}      value    Candidate size.
 * @param {number} fallback Used for anything that is not a positive number.
 * @return {number} Clamped size.
 */
const clampSize = (value, fallback) => {
	const size = Math.round(Number(value));

	if (!Number.isFinite(size) || size <= 0) {
		return fallback;
	}

	return Math.max(CONFIG.minSize, Math.min(CONFIG.maxSize, size));
};

/**
 * CSS `url()` for an icon: inline SVG becomes a data URI, anything else is a URL.
 *
 * @param {string} name Icon name.
 * @return {string} CSS value, or '' for an unknown icon.
 */
const maskUrl = (name) => {
	const markup = `${findIcon(name)?.icon || ''}`.trim();

	if (!markup) {
		return '';
	}

	if (markup.toLowerCase().startsWith('<svg')) {
		return `url("data:image/svg+xml,${encodeURIComponent(markup)}")`;
	}

	return `url(${JSON.stringify(markup)})`;
};

/**
 * The icon to use for a state: the attribute if the registry knows it, else the default.
 *
 * @param {string} value    Attribute value.
 * @param {string} fallback Default name.
 * @return {string} Icon name.
 */
const resolveName = (value, fallback) =>
	value && findIcon(value) ? value : fallback;

addFilter(
	'blocks.registerBlockType',
	'isudev-library/core-accordion-custom-icons/attributes',
	(settings, name) => {
		if (BLOCK !== name) {
			return settings;
		}

		const inherited = settings.getEditWrapperProps;

		return {
			...settings,
			attributes: {
				...settings.attributes,
				isudevClosedIcon: { type: 'string' },
				isudevOpenIcon: { type: 'string' },
				isudevIconSize: { type: 'number' },
			},
			getEditWrapperProps(attributes) {
				const props = inherited ? inherited(attributes) : {};
				const closed = maskUrl(
					resolveName(attributes.isudevClosedIcon, CONFIG.closed)
				);
				const open = maskUrl(
					resolveName(attributes.isudevOpenIcon, CONFIG.open)
				);

				if (!closed || !open) {
					return props;
				}

				return {
					...props,
					className: [props.className, 'has-isudev-accordion-icons']
						.filter(Boolean)
						.join(' '),
					style: {
						...(props.style || {}),
						'--isudev-accordion-icon-closed': closed,
						'--isudev-accordion-icon-open': open,
						'--isudev-accordion-icon-size': `${clampSize(
							attributes.isudevIconSize,
							CONFIG.size
						)}px`,
					},
				};
			},
		};
	}
);

/**
 * Placeholder naming the default, so an empty choice reads as "inherit".
 *
 * @param {string} name Default icon name.
 * @return {string} Placeholder text.
 */
const defaultLabel = (name) =>
	sprintf(
		/* translators: %s: icon name. */
		__('Default: %s', 'isudev-library'),
		findIcon(name)?.label || name
	);

/**
 * Adds the panel to core/accordion's BlockEdit.
 *
 * @param {Function} BlockEdit Original edit component.
 * @return {Function} Wrapped edit component.
 */
const withIconControls = (BlockEdit) => (props) => {
	if (BLOCK !== props.name) {
		return <BlockEdit {...props} />;
	}

	const { attributes, setAttributes } = props;

	return (
		<>
			<BlockEdit {...props} />
			<InspectorControls>
				<PanelBody
					title={__('Toggle icons', 'isudev-library')}
					initialOpen={false}
				>
					{false === attributes.showIcon && (
						<Notice status="info" isDismissible={false}>
							{__(
								'Icons are switched off for this accordion. Turn "Show icon" back on to see them.',
								'isudev-library'
							)}
						</Notice>
					)}
					<IconSelect
						label={__('Closed icon', 'isudev-library')}
						placeholder={defaultLabel(CONFIG.closed)}
						defaultIcons={icons}
						value={attributes.isudevClosedIcon || ''}
						onChange={(value) =>
							setAttributes({
								isudevClosedIcon: value || undefined,
							})
						}
					/>
					<IconSelect
						label={__('Open icon', 'isudev-library')}
						placeholder={defaultLabel(CONFIG.open)}
						defaultIcons={icons}
						value={attributes.isudevOpenIcon || ''}
						onChange={(value) =>
							setAttributes({
								isudevOpenIcon: value || undefined,
							})
						}
					/>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Icon size (px)', 'isudev-library')}
						min={CONFIG.minSize}
						max={CONFIG.maxSize}
						allowReset
						resetFallbackValue={undefined}
						initialPosition={CONFIG.size}
						value={attributes.isudevIconSize}
						onChange={(value) =>
							setAttributes({
								isudevIconSize:
									undefined === value
										? undefined
										: clampSize(value, CONFIG.size),
							})
						}
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};

addFilter(
	'editor.BlockEdit',
	'isudev-library/core-accordion-custom-icons/controls',
	withIconControls
);
