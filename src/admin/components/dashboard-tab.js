/**
 * WordPress dependencies
 */
import {
	Button,
	CheckboxControl,
	Notice,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const PATH = '/isudev-library/v1/dashboard';

/**
 * Settings for the client dashboard widgets: the feature, Content overview
 * (which post types show) and Support (form, client email).
 *
 * Saved with one explicit request, not per toggle: the settings are one
 * option, so a single write cannot race itself.
 *
 * @return {Element} Tab content.
 */
export default function DashboardTab() {
	const [data, setData] = useState(null);
	const [settings, setSettings] = useState(null);
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	useEffect(() => {
		apiFetch({ path: PATH })
			.then((response) => {
				setData(response);
				setSettings(response.settings);
			})
			.catch((err) =>
				setNotice({ status: 'error', message: err.message })
			);
	}, []);

	if (!settings) {
		return notice ? (
			<Notice status={notice.status} isDismissible={false}>
				{notice.message}
			</Notice>
		) : (
			<Spinner />
		);
	}

	const update = (group, key, value) => {
		setNotice(null);
		setSettings((current) =>
			group
				? { ...current, [group]: { ...current[group], [key]: value } }
				: { ...current, [key]: value }
		);
	};

	const hidden = settings.contentOverview.hiddenPostTypes;

	const setVisible = (slug, visible) =>
		update(
			'contentOverview',
			'hiddenPostTypes',
			visible ? hidden.filter((item) => item !== slug) : [...hidden, slug]
		);

	const save = () => {
		setSaving(true);
		apiFetch({ path: PATH, method: 'POST', data: { settings } })
			.then((response) => {
				setSettings(response.settings);
				setNotice({
					status: 'success',
					message: __('Dashboard settings saved.', 'isudev-library'),
				});
			})
			.catch((err) =>
				setNotice({ status: 'error', message: err.message })
			)
			.finally(() => setSaving(false));
	};

	const off = !settings.enabled;

	return (
		<div className="isudev-admin__dashboard">
			<p className="isudev-admin__note">
				{__(
					'Widgets on the WordPress dashboard for the people who run the site: what content there is, and how to reach support.',
					'isudev-library'
				)}
			</p>

			{notice && (
				<Notice status={notice.status} onRemove={() => setNotice(null)}>
					{notice.message}
				</Notice>
			)}

			<div className="isudev-admin__card isudev-admin__dash-card">
				<ToggleControl
					__nextHasNoMarginBottom
					label={__('Dashboard widgets', 'isudev-library')}
					help={__(
						'Off: nothing is added to the dashboard.',
						'isudev-library'
					)}
					checked={settings.enabled}
					onChange={(value) => update(null, 'enabled', value)}
				/>
			</div>

			<div
				className={`isudev-admin__group isudev-admin__dash-group${
					off ? ' is-parent-disabled' : ''
				}`}
			>
				<div className="isudev-admin__card isudev-admin__dash-card">
					<h3 className="isudev-admin__card-title">
						{__('Content overview', 'isudev-library')}
					</h3>
					<p className="isudev-admin__card-description">
						{__(
							'One row per content type with its icon, counts by status and View all / Add new.',
							'isudev-library'
						)}
					</p>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Show Content overview', 'isudev-library')}
						checked={settings.contentOverview.enabled}
						disabled={off}
						onChange={(value) =>
							update('contentOverview', 'enabled', value)
						}
					/>
					<fieldset
						className="isudev-admin__checkboxes"
						disabled={off || !settings.contentOverview.enabled}
					>
						<legend>
							{__('Content types shown', 'isudev-library')}
						</legend>
						{data.postTypes.map((type) => (
							<CheckboxControl
								__nextHasNoMarginBottom
								key={type.slug}
								label={type.label}
								checked={!hidden.includes(type.slug)}
								onChange={(value) =>
									setVisible(type.slug, value)
								}
							/>
						))}
					</fieldset>
				</div>

				<div className="isudev-admin__card isudev-admin__dash-card">
					<h3 className="isudev-admin__card-title">
						{__('Support', 'isudev-library')}
					</h3>
					<p className="isudev-admin__card-description">
						{sprintf(
							/* translators: 1: website, 2: email address. */
							__(
								'Shows %1$s and %2$s, and a form that emails a request there with a copy to the sender.',
								'isudev-library'
							),
							data.support.label,
							data.support.email
						)}
					</p>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Show Support', 'isudev-library')}
						checked={settings.support.enabled}
						disabled={off}
						onChange={(value) =>
							update('support', 'enabled', value)
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Support request form', 'isudev-library')}
						checked={settings.support.form}
						disabled={off || !settings.support.enabled}
						onChange={(value) => update('support', 'form', value)}
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						type="email"
						label={__('Client email', 'isudev-library')}
						help={__(
							'Prefilled in the form and used for the copy. Empty: the signed-in user’s own email.',
							'isudev-library'
						)}
						placeholder={data.adminEmail}
						value={settings.support.clientEmail}
						disabled={off || !settings.support.enabled}
						onChange={(value) =>
							update('support', 'clientEmail', value)
						}
					/>
				</div>
			</div>

			<Button
				variant="primary"
				isBusy={saving}
				disabled={saving}
				onClick={save}
			>
				{__('Save', 'isudev-library')}
			</Button>
		</div>
	);
}
