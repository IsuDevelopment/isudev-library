/**
 * Button icon, editor side. Mirrors inc/attributes.php (resolve, wrapper_classes) — there is
 * no JS test runner, so change both together. The canvas preview is a CSS mask on the block
 * wrapper (getEditWrapperProps), never a DOM change; its CSS is button-icon.css.
 */

/* global globalThis */

/**
 * External dependencies
 */
import { Icon, getLocalizedIcons } from '@isudev/gutenberg/components/Icon';
import { IconPicker } from '@isudev/gutenberg/components/IconPicker';
import { IconSelect } from '@isudev/gutenberg/components/IconSelect';

/**
 * WordPress dependencies
 */
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import {
	Dropdown,
	Notice,
	PanelBody,
	RangeControl,
	ToggleControl,
	ToolbarButton,
	// Only exported as __experimental in WordPress 7.1; no stable equivalent exists yet.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { symbol } from '@wordpress/icons';

const BLOCK = 'core/button';

// `window.isudevButtonIcon` from hooks.php: normalize_settings() plus minSize/maxSize.
const config = {
	icons: null,
	defaultPosition: 'after',
	defaultSize: null,
	readT2: true,
	minSize: 8,
	maxSize: 128,
	...(globalThis.isudevButtonIcon ?? {}),
};

const OWN_ATTRIBUTES = {
	isudevIcon: { type: 'string' },
	isudevIconPosition: { type: 'string', enum: ['before', 'after'] },
	isudevIconSize: { type: 'integer' },
	isudevIconOnly: { type: 'boolean' },
};

// T2's names, registered only so a resave keeps them until the button is migrated.
const T2_ATTRIBUTES = {
	icon: { type: 'string' },
	iconSize: { type: 'number' },
	iconPosition: { type: 'string' },
	hideText: { type: 'boolean' },
};

const T2_MAP = {
	isudevIcon: 'icon',
	isudevIconPosition: 'iconPosition',
	isudevIconSize: 'iconSize',
	isudevIconOnly: 'hideText',
};

const POSITIONS = {
	before: 'before',
	after: 'after',
	left: 'before',
	right: 'after',
};

let registry = null;

/**
 * The shared icon registry, read once the editor is running.
 *
 * @return {Array} Icon definitions.
 */
function icons() {
	if (null === registry) {
		registry = getLocalizedIcons();
	}

	return registry;
}

const isSet = (value) => value !== undefined && value !== null;

/**
 * Own attribute, else T2's (when enabled), else undefined. Mirrors the closure in resolve().
 *
 * @param {Object} attributes Block attributes.
 * @param {string} key        Own attribute name.
 * @return {*} Raw value.
 */
function pick(attributes, key) {
	if (isSet(attributes[key])) {
		return attributes[key];
	}

	const legacy = attributes[T2_MAP[key]];

	return config.readT2 && isSet(legacy) ? legacy : undefined;
}

/**
 * Clamp a size like sanitize_size(); null when not a number.
 *
 * @param {*} value Raw value.
 * @return {number|null} Size in px.
 */
function sanitizeSize(value) {
	if (typeof value === 'boolean' || !isSet(value) || value === '') {
		return null;
	}

	const number = Math.round(Number(value));

	if (!Number.isFinite(number)) {
		return null;
	}

	return Math.max(config.minSize, Math.min(config.maxSize, number));
}

/**
 * What the button shows. Mirrors resolve() in inc/attributes.php.
 *
 * @param {Object} attributes Block attributes.
 * @return {{icon: string, position: string, size: (number|null), iconOnly: boolean}} Resolved values.
 */
function resolve(attributes = {}) {
	const icon = pick(attributes, 'isudevIcon');
	const size = sanitizeSize(pick(attributes, 'isudevIconSize'));
	const iconOnly = pick(attributes, 'isudevIconOnly');

	return {
		icon:
			typeof icon === 'string' &&
			/^[A-Za-z0-9][A-Za-z0-9_/-]{0,63}$/.test(icon.trim())
				? icon.trim()
				: '',
		position:
			POSITIONS[pick(attributes, 'isudevIconPosition')] ??
			POSITIONS[config.defaultPosition] ??
			'after',
		size: size ?? sanitizeSize(config.defaultSize),
		iconOnly: [true, 1, '1', 'true'].includes(iconOnly),
	};
}

/**
 * Attribute changes that move a T2 button onto our attributes and drop T2's.
 *
 * @param {Object} attributes Block attributes.
 * @return {Object} Changes; empty when nothing is left to migrate.
 */
function migration(attributes) {
	const changes = {};

	for (const [own, legacy] of Object.entries(T2_MAP)) {
		if (!isSet(attributes[legacy])) {
			continue;
		}

		if (config.readT2 && !isSet(attributes[own])) {
			changes[own] =
				own === 'isudevIconPosition'
					? POSITIONS[attributes[legacy]]
					: attributes[legacy];
		}

		changes[legacy] = undefined;
	}

	if (isSet(changes.isudevIconSize)) {
		changes.isudevIconSize =
			sanitizeSize(changes.isudevIconSize) ?? undefined;
	}

	return changes;
}

/**
 * Standalone SVG for the mask, as a CSS url(); '' for an unknown icon.
 *
 * @param {string} name Icon name.
 * @return {string} CSS value.
 */
function maskUrl(name) {
	const definition = icons().find((item) => item.name === name);
	const graphic =
		typeof definition?.icon === 'string' ? definition.icon.trim() : '';

	if (graphic.startsWith('<svg')) {
		const svg = graphic.includes('xmlns=')
			? graphic
			: graphic.replace(
					'<svg',
					'<svg xmlns="http://www.w3.org/2000/svg"'
				);

		return `url("data:image/svg+xml,${encodeURIComponent(svg)}")`;
	}

	return /^(https?:)?\/\//.test(graphic)
		? `url(${JSON.stringify(graphic)})`
		: '';
}

/**
 * Plain text of the button label (rich-text value or string).
 *
 * @param {*} text `text` attribute.
 * @return {string} Text without markup.
 */
function plainText(text) {
	return String(text ?? '')
		.replace(/<[^>]*>/g, '')
		.replace(/&nbsp;/g, ' ')
		.trim();
}

/**
 * The controls, shared by the toolbar dropdown and the inspector panel.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {boolean}  props.inToolbar     Render the always-open grid instead of the select button.
 * @return {Element} Controls.
 */
function IconOptions({ attributes, setAttributes, inToolbar }) {
	const resolved = resolve(attributes);
	const update = (changes) =>
		setAttributes({ ...migration(attributes), ...changes });
	const iconProps = {
		defaultIcons: icons(),
		icons: Array.isArray(config.icons) ? config.icons : undefined,
		value: resolved.icon,
		onChange: (name) => update({ isudevIcon: name || undefined }),
		label: __('Icon', 'isudev-library'),
		searchLabel: __('Search icons', 'isudev-library'),
		searchPlaceholder: __('Search icons', 'isudev-library'),
		noResultsMessage: __('No icons found.', 'isudev-library'),
		clearLabel: __('Remove icon', 'isudev-library'),
	};
	const ownSize = sanitizeSize(pick(attributes, 'isudevIconSize'));

	return (
		<>
			{inToolbar ? (
				<IconPicker {...iconProps} />
			) : (
				<IconSelect
					{...iconProps}
					placeholder={__('Select icon', 'isudev-library')}
				/>
			)}
			{resolved.icon && (
				<>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						isBlock
						label={__('Icon position', 'isudev-library')}
						value={resolved.position}
						onChange={(value) =>
							update({ isudevIconPosition: POSITIONS[value] })
						}
					>
						<ToggleGroupControlOption
							value="before"
							label={__('Before text', 'isudev-library')}
						/>
						<ToggleGroupControlOption
							value="after"
							label={__('After text', 'isudev-library')}
						/>
					</ToggleGroupControl>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Icon size', 'isudev-library')}
						help={__(
							'In pixels. Reset to follow the theme default.',
							'isudev-library'
						)}
						value={ownSize ?? undefined}
						min={config.minSize}
						max={config.maxSize}
						initialPosition={sanitizeSize(config.defaultSize) ?? 24}
						allowReset
						onChange={(value) =>
							update({
								isudevIconSize:
									sanitizeSize(value) ?? undefined,
							})
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Show icon only', 'isudev-library')}
						help={__(
							'The text is hidden on the page but still read by screen readers.',
							'isudev-library'
						)}
						checked={resolved.iconOnly}
						onChange={(value) => update({ isudevIconOnly: value })}
					/>
					{resolved.iconOnly && !plainText(attributes.text) && (
						<Notice status="warning" isDismissible={false}>
							{__(
								'Type the button text: it is the accessible label screen readers announce, and a button without text is not shown on the page.',
								'isudev-library'
							)}
						</Notice>
					)}
				</>
			)}
		</>
	);
}

/**
 * Toolbar dropdown and inspector panel for core/button.
 *
 * @param {Function} BlockEdit Wrapped edit component.
 * @return {Function} Filtered edit component.
 */
const withIconControls = (BlockEdit) =>
	function IsudevButtonIconEdit(props) {
		if (BLOCK !== props.name) {
			return <BlockEdit {...props} />;
		}

		const { attributes, setAttributes, isSelected } = props;
		const resolved = resolve(attributes);

		return (
			<>
				<BlockEdit {...props} />
				{isSelected && (
					<>
						<BlockControls group="other">
							<Dropdown
								popoverProps={{ placement: 'bottom-start' }}
								renderToggle={({ isOpen, onToggle }) => (
									<ToolbarButton
										icon={
											resolved.icon ? (
												<Icon
													name={resolved.icon}
													defaultIcons={icons()}
													size={24}
												/>
											) : (
												symbol
											)
										}
										label={__(
											'Button icon',
											'isudev-library'
										)}
										onClick={onToggle}
										aria-expanded={isOpen}
										aria-haspopup="true"
									/>
								)}
								renderContent={() => (
									<div
										style={{
											display: 'flex',
											flexDirection: 'column',
											gap: '16px',
											padding: '8px',
											width: '288px',
										}}
									>
										<IconOptions
											attributes={attributes}
											setAttributes={setAttributes}
											inToolbar
										/>
									</div>
								)}
							/>
						</BlockControls>
						<InspectorControls>
							<PanelBody
								title={__('Icon', 'isudev-library')}
								initialOpen={!!resolved.icon}
							>
								<div
									style={{
										display: 'flex',
										flexDirection: 'column',
										gap: '16px',
									}}
								>
									<IconOptions
										attributes={attributes}
										setAttributes={setAttributes}
									/>
								</div>
							</PanelBody>
						</InspectorControls>
					</>
				)}
			</>
		);
	};

/**
 * Attributes, plus the canvas preview on the wrapper (classes match wrapper_classes()).
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 * @return {Object} Settings.
 */
function addAttributes(settings, name) {
	if (BLOCK !== name) {
		return settings;
	}

	const attributes = { ...settings.attributes, ...OWN_ATTRIBUTES };

	if (config.readT2) {
		for (const [key, schema] of Object.entries(T2_ATTRIBUTES)) {
			attributes[key] = attributes[key] ?? schema;
		}
	}

	const inherited = settings.getEditWrapperProps;

	return {
		...settings,
		attributes,
		getEditWrapperProps(blockAttributes) {
			const props = inherited ? inherited(blockAttributes) || {} : {};
			const resolved = resolve(blockAttributes);
			const url = resolved.icon ? maskUrl(resolved.icon) : '';

			if (!url) {
				return props;
			}

			const classes = [
				props.className,
				'has-isudev-icon',
				`has-isudev-icon-${resolved.position}`,
				resolved.iconOnly && 'is-isudev-icon-only',
				'is-isudev-icon-preview',
			];

			return {
				...props,
				className: classes.filter(Boolean).join(' '),
				style: {
					...(props.style || {}),
					'--isudev-button-icon': url,
					...(resolved.size
						? { '--isudev-button-icon-size': `${resolved.size}px` }
						: {}),
				},
			};
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'isudev-library/button-icon/attributes',
	addAttributes
);

addFilter(
	'editor.BlockEdit',
	'isudev-library/button-icon/controls',
	withIconControls
);
