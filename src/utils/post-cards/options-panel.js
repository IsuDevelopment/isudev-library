/**
 * Inspector panel generated from the card option registry
 * (`isudev_library/post_card/register_options`). Values live in `cardOptions`; an
 * absent key means "inherit".
 */

/**
 * External dependencies
 */
import { MediaPickerControl } from '@isudev/gutenberg/controls/MediaPickerControl';
import { MediaPreview } from '@isudev/gutenberg/components/MediaPreview';

/**
 * WordPress dependencies
 */
import {
	BaseControl,
	Button,
	PanelBody,
	RadioControl,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { applyFilters } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { optionsFor } from './data';

function inheritLabel(def, inherited) {
	if (def.type === 'boolean') {
		return inherited
			? __('Inherit (on)', 'isudev-library')
			: __('Inherit (off)', 'isudev-library');
	}
	const choice = def.choices?.find((c) => c.value === inherited);
	return choice
		? /* translators: %s: inherited value. */
			sprintf(__('Inherit (%s)', 'isudev-library'), choice.label)
		: __('Inherit', 'isudev-library');
}

function OptionControl({ def, value, inherited, canInherit, onChange }) {
	const help = def.help || undefined;
	const id = useInstanceId(OptionControl, 'isudev-card-option');

	if (def.type === 'boolean' && !canInherit) {
		return (
			<ToggleControl
				label={def.label}
				help={help}
				checked={value ?? def.default ?? false}
				onChange={onChange}
				__nextHasNoMarginBottom
			/>
		);
	}

	if (def.type === 'boolean' || def.type === 'select') {
		const choices =
			def.type === 'boolean'
				? [
						{ value: 'true', label: __('On', 'isudev-library') },
						{ value: 'false', label: __('Off', 'isudev-library') },
					]
				: def.choices;
		const options = [
			...(canInherit
				? [{ value: '', label: inheritLabel(def, inherited) }]
				: [{ value: '', label: __('Default', 'isudev-library') }]),
			...choices,
		];
		const current =
			value === undefined || value === null ? '' : String(value);
		const parse = (next) => {
			if (next === '') {
				return undefined;
			}
			return def.type === 'boolean' ? next === 'true' : next;
		};
		if (def.control === 'radio') {
			return (
				<RadioControl
					label={def.label}
					help={help}
					selected={current}
					options={options}
					onChange={(next) => onChange(parse(next))}
				/>
			);
		}

		return (
			<SelectControl
				label={def.label}
				help={help}
				value={current}
				options={options}
				onChange={(next) => onChange(parse(next))}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		);
	}

	if (def.type === 'text') {
		const Control =
			def.control === 'textarea' ? TextareaControl : TextControl;
		return (
			<Control
				label={def.label}
				help={help}
				value={value ?? ''}
				placeholder={
					typeof inherited === 'string' ? inherited : undefined
				}
				onChange={(next) => onChange(next === '' ? undefined : next)}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		);
	}

	if (def.type === 'media') {
		return (
			<BaseControl
				id={id}
				label={def.label}
				help={help}
				__nextHasNoMarginBottom
			>
				{value?.url && (
					<MediaPreview value={value} aspectRatio="16 / 9" />
				)}
				<MediaPickerControl
					value={value ?? {}}
					onChange={(media) =>
						onChange({
							id: media.id,
							url: media.url,
							alt: media.alt,
						})
					}
				>
					{({ open, action }) => (
						<div className="isudev-card-options__media-actions">
							<Button variant="secondary" onClick={open}>
								{action === 'replace'
									? __('Replace image', 'isudev-library')
									: __('Choose image', 'isudev-library')}
							</Button>
							{value?.url && (
								<Button
									variant="tertiary"
									isDestructive
									onClick={() => onChange(undefined)}
								>
									{__('Remove', 'isudev-library')}
								</Button>
							)}
						</div>
					)}
				</MediaPickerControl>
			</BaseControl>
		);
	}

	return null;
}

/**
 * @param {Object}         props
 * @param {'card'|'cards'} props.level     Card or wrapper.
 * @param {string|null}    props.postType  Card post type, null when unknown.
 * @param {Object}         props.value     `cardOptions`.
 * @param {Object}         props.inherited Values the card inherits (wrapper's resolved values).
 * @param {Function}       props.onChange  Receives the new `cardOptions`.
 * @return {Element|null} Panel, or null when no option applies.
 */
export default function CardOptionsPanel({
	level,
	postType,
	value = {},
	inherited = {},
	onChange,
}) {
	const defs = optionsFor(level, postType);

	if (!defs.length) {
		return null;
	}

	const set = (key, next) => {
		const copy = { ...value };
		if (next === undefined) {
			delete copy[key];
		} else {
			copy[key] = next;
		}
		onChange(copy);
	};

	return (
		<PanelBody
			title={
				level === 'cards'
					? __('Options for every card', 'isudev-library')
					: __('Card options', 'isudev-library')
			}
			initialOpen={level === 'card'}
		>
			{level === 'card' && !postType && (
				<p className="isudev-card-options__note">
					{__(
						'Options limited to some post types apply only when this card shows one of them.',
						'isudev-library'
					)}
				</p>
			)}
			{defs.map((def) => {
				const props = {
					def,
					value: value[def.key],
					inherited: inherited[def.key] ?? def.default,
					canInherit: level === 'card' && def.scope === 'both',
					onChange: (next) => set(def.key, next),
				};

				/**
				 * Swap the control of one card option.
				 *
				 * @param {Element|null} control Default control.
				 * @param {Object}       props   `{ def, value, inherited, canInherit, onChange }`.
				 * @param {string}       level   `card` or `cards`.
				 */
				const control = applyFilters(
					'isudevLibrary.postCard.optionControl',
					<OptionControl {...props} />,
					props,
					level
				);

				return <div key={def.key}>{control}</div>;
			})}
		</PanelBody>
	);
}
