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
import ExtensionsTab from './components/extensions-tab';
import SettingsTab from './components/settings-tab';

export default function App() {
	const [blocks, setBlocks] = useState(null);
	const [diagnostics, setDiagnostics] = useState(null);
	const [extensions, setExtensions] = useState(null);
	const [categories, setCategories] = useState(null);
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

	const loadExtensions = useCallback(() => {
		apiFetch({ path: '/isudev-library/v1/extensions' })
			.then((response) => {
				setExtensions(response.extensions);
				setCategories(response.categories);
				setError('');
			})
			.catch((err) => setError(err.message));
	}, []);

	useEffect(load, [load]);
	useEffect(loadExtensions, [loadExtensions]);

	const tabs = [
		{ name: 'blocks', title: __('Blocks', 'isudev-library') },
		{ name: 'extensions', title: __('Extensions', 'isudev-library') },
		{ name: 'settings', title: __('Settings', 'isudev-library') },
	];

	const renderTab = (tab) => {
		if (tab.name === 'blocks') {
			return (
				<BlocksTab
					blocks={blocks}
					onChanged={load}
					onError={setError}
				/>
			);
		}

		if (tab.name === 'extensions') {
			return (
				<ExtensionsTab
					extensions={extensions}
					categories={categories}
					onChanged={loadExtensions}
					onError={setError}
				/>
			);
		}

		return <SettingsTab diagnostics={diagnostics} />;
	};

	return (
		<div className="isudev-admin">
			<div className="isudev-admin__intro">
				<h1 className="isudev-admin__title">
					{__('IsuDev Library', 'isudev-library')}
				</h1>
				<p className="isudev-admin__subtitle">
					{__(
						'Blocks to build pages with, and extensions that change how WordPress itself behaves. Everything here can be switched off.',
						'isudev-library'
					)}
				</p>
			</div>

			{error && (
				<div className="notice notice-error">
					<p>{error}</p>
				</div>
			)}

			<TabPanel className="isudev-admin__tabs" tabs={tabs}>
				{renderTab}
			</TabPanel>
		</div>
	);
}
