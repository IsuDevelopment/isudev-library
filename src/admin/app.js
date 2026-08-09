/**
 * WordPress dependencies
 */
import { TabPanel } from '@wordpress/components';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import BlocksTab from './components/blocks-tab';
import SettingsTab from './components/settings-tab';

export default function App() {
	const [blocks, setBlocks] = useState(null);
	const [diagnostics, setDiagnostics] = useState(null);
	const [error, setError] = useState('');

	const load = useCallback(() => {
		apiFetch({ path: '/isudev-library/v1/blocks' })
			.then((response) => {
				setBlocks(response.blocks);
				setDiagnostics(response.diagnostics);
				// Clear any earlier failure. Without this a transient error leaves a
				// permanently visible banner that outlives the problem it described.
				setError('');
			})
			.catch((err) => setError(err.message));
	}, []);

	useEffect(load, [load]);

	const tabs = [
		{ name: 'blocks', title: __('Blocks', 'isudev-library') },
		{ name: 'settings', title: __('Settings', 'isudev-library') },
	];

	return (
		<div className="isudev-admin">
			<h1 className="isudev-admin__title">
				{__('IsuDev Library', 'isudev-library')}
			</h1>

			{error && (
				<div className="notice notice-error">
					<p>{error}</p>
				</div>
			)}

			<TabPanel className="isudev-admin__tabs" tabs={tabs}>
				{(tab) =>
					tab.name === 'blocks' ? (
						<BlocksTab
							blocks={blocks}
							onChanged={load}
							onError={setError}
						/>
					) : (
						<SettingsTab diagnostics={diagnostics} />
					)
				}
			</TabPanel>
		</div>
	);
}
