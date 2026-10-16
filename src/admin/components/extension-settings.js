/**
 * WordPress dependencies
 */
import {
	Button,
	CheckboxControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * One settings field. A field the theme sets in isudev.json is shown disabled.
 *
 * @param {Object}   props          Props.
 * @param {Object}   props.field    Field from the REST payload.
 * @param {*}        props.value    Current local value.
 * @param {Function} props.onChange Receives the new value.
 * @return {Element} Control.
 */
function Field({ field, value, onChange }) {
	const help = field.locked
		? __('Managed in isudev.json — change it there.', 'isudev-library')
		: field.help;

	if ('boolean' === field.type) {
		return (
			<ToggleControl
				__nextHasNoMarginBottom
				label={field.label}
				help={help}
				checked={!!value}
				disabled={field.locked}
				onChange={onChange}
			/>
		);
	}

	if ('post_types' === field.type) {
		const selected = Array.isArray(value) ? value : [];

		return (
			<fieldset
				className="isudev-admin__checkboxes"
				disabled={field.locked}
			>
				<legend>{field.label}</legend>
				{field.choices.map((choice) => (
					<CheckboxControl
						__nextHasNoMarginBottom
						key={choice.value}
						label={choice.label}
						checked={selected.includes(choice.value)}
						onChange={(checked) =>
							onChange(
								checked
									? [...selected, choice.value]
									: selected.filter(
											(item) => item !== choice.value
										)
							)
						}
					/>
				))}
				{help && <p className="description">{help}</p>}
			</fieldset>
		);
	}

	return (
		<TextControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			type={'email' === field.type ? 'email' : 'text'}
			label={field.label}
			help={help}
			value={value ?? ''}
			disabled={field.locked}
			onChange={onChange}
		/>
	);
}

/**
 * The settings of one enabled extension, saved with one request.
 *
 * @param {Object}   props           Props.
 * @param {Object}   props.extension Extension from the REST payload.
 * @param {Function} props.onSave    Receives the values; returns a promise.
 * @return {Element} Settings form.
 */
export default function ExtensionSettings({ extension, onSave }) {
	const initial = Object.fromEntries(
		extension.settings.map((field) => [field.key, field.value])
	);
	const [values, setValues] = useState(initial);
	const [saving, setSaving] = useState(false);

	const editable = extension.settings.some((field) => !field.locked);
	const dirty = JSON.stringify(values) !== JSON.stringify(initial);

	const save = () => {
		setSaving(true);
		Promise.resolve(onSave(extension, values)).finally(() =>
			setSaving(false)
		);
	};

	return (
		<div className="isudev-admin__extension-settings">
			{extension.settings.map((field) => (
				<Field
					key={field.key}
					field={field}
					value={values[field.key]}
					onChange={(value) =>
						setValues((current) => ({
							...current,
							[field.key]: value,
						}))
					}
				/>
			))}
			{editable && (
				<Button
					variant="secondary"
					size="compact"
					isBusy={saving}
					disabled={saving || !dirty}
					onClick={save}
				>
					{__('Save settings', 'isudev-library')}
				</Button>
			)}
		</div>
	);
}
