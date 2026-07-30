/**
 * WordPress dependencies
 */
import { Card, CardBody, Spinner, ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

export default function SettingsTab({ diagnostics }) {
	const [settings, setSettings] = useEntityProp(
		'root',
		'site',
		'isudev_library_settings'
	);

	const configPath = () => {
		if (!diagnostics) {
			return '';
		}
		if (diagnostics.configChild) {
			return diagnostics.configChild;
		}
		if (diagnostics.configParent) {
			return diagnostics.configParent;
		}
		return __('Not found — using block defaults.', 'isudev-library');
	};

	return (
		<div className="isudev-admin__settings">
			<Card size="small">
				<CardBody>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__(
							'Load base --isudev-* tokens',
							'isudev-library'
						)}
						help={__(
							'Turn off when your theme provides the design tokens itself.',
							'isudev-library'
						)}
						checked={Boolean(settings?.loadBaseTokens)}
						onChange={(next) =>
							setSettings({ ...settings, loadBaseTokens: next })
						}
					/>
				</CardBody>
			</Card>

			<Card size="small">
				<CardBody>
					<h2>{__('Diagnostics', 'isudev-library')}</h2>
					{!diagnostics ? (
						<Spinner />
					) : (
						<ul className="isudev-admin__diagnostics">
							<li>
								{__('Version:', 'isudev-library')}{' '}
								<code>{diagnostics.version}</code>
							</li>
							<li>
								{__('Blocks discovered:', 'isudev-library')}{' '}
								{diagnostics.discovered}
							</li>
							<li>
								{__('Blocks registered:', 'isudev-library')}{' '}
								{diagnostics.registered}
							</li>
							<li>
								{__('isudev.json:', 'isudev-library')}{' '}
								<code>{configPath()}</code>
							</li>
						</ul>
					)}
				</CardBody>
			</Card>
		</div>
	);
}
