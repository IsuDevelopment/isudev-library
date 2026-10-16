/**
 * WordPress dependencies
 */
import { TabPanel } from '@wordpress/components';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
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

	// Toggle writes still in flight, and the tail of the queue they run in.
	const pending = useRef(0);
	const queue = useRef(Promise.resolve());

	// Bumped by every load and optimistic change, so a GET that started before
	// a newer one — or before a click — cannot overwrite fresher state.
	const blocksVersion = useRef(0);
	const extensionsVersion = useRef(0);

	const load = useCallback(() => {
		const version = ++blocksVersion.current;

		apiFetch({ path: '/isudev-library/v1/blocks' })
			.then((response) => {
				if (version !== blocksVersion.current || pending.current > 0) {
					return;
				}
				setBlocks(response.blocks);
				setDiagnostics(response.diagnostics);
				// Clear any earlier failure. Without this a transient error leaves a
				// permanently visible banner that outlives the problem it described.
				setError('');
			})
			.catch((err) => setError(err.message));
	}, []);

	const loadExtensions = useCallback(() => {
		const version = ++extensionsVersion.current;

		apiFetch({ path: '/isudev-library/v1/extensions' })
			.then((response) => {
				if (
					version !== extensionsVersion.current ||
					pending.current > 0
				) {
					return;
				}
				setExtensions(response.extensions);
				setCategories(response.categories);
				setError('');
			})
			.catch((err) => setError(err.message));
	}, []);

	/**
	 * Run a toggle write after every earlier one, then reload once idle.
	 *
	 * One at a time, never in parallel: a quick second click used to race the
	 * first and the panel showed whichever response landed last. The reload
	 * waits for the queue to drain so it cannot report a state that a write
	 * still in flight is about to change.
	 *
	 * @param {Function} request Returns the write's promise.
	 * @return {Promise<void>}
	 */
	const mutate = useCallback(
		(request) => {
			pending.current++;

			const run = queue.current.then(request);
			queue.current = run.catch(() => {});

			return run
				.catch((err) => setError(err.message))
				.finally(() => {
					pending.current--;
					if (pending.current === 0) {
						load();
						loadExtensions();
					}
				});
		},
		[load, loadExtensions]
	);

	const setBlockEnabled = useCallback((slug, enabled) => {
		++blocksVersion.current;
		setBlocks((current) =>
			current.map((block) =>
				block.slug === slug ? { ...block, enabled } : block
			)
		);
	}, []);

	const setExtensionEnabled = useCallback((slug, enabled) => {
		++extensionsVersion.current;
		setExtensions((current) =>
			current.map((extension) =>
				extension.slug === slug ? { ...extension, enabled } : extension
			)
		);
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
					mutate={mutate}
					onOptimistic={setBlockEnabled}
				/>
			);
		}

		if (tab.name === 'extensions') {
			return (
				<ExtensionsTab
					extensions={extensions}
					categories={categories}
					mutate={mutate}
					onOptimistic={setExtensionEnabled}
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
