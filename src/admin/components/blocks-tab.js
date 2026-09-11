/**
 * WordPress dependencies
 */
import { Button, Spinner } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import BlockCard from './block-card';

/**
 * Group top-level blocks with the inner blocks that require them.
 *
 * A block that nothing else `requires` is a group's own block; a block
 * that does appear in another's `requires` is nested under it instead of
 * listed again on its own. This mirrors the cascade Registry::blocks()
 * already resolves — `dependents` is exactly the list of slugs to nest.
 *
 * @param {Array} blocks Blocks from the REST payload.
 * @return {Array} `{ block, children }` groups, in `blocks` order.
 */
function groupBlocks(blocks) {
	const bySlug = new Map(blocks.map((block) => [block.slug, block]));
	const childSlugs = new Set(blocks.flatMap((block) => block.dependents));

	return blocks
		.filter((block) => !childSlugs.has(block.slug))
		.map((block) => ({
			block,
			children: block.dependents
				.map((slug) => bySlug.get(slug))
				.filter(Boolean),
		}));
}

export default function BlocksTab({ blocks, onChanged, onError }) {
	const groups = useMemo(
		() => (blocks === null ? [] : groupBlocks(blocks)),
		[blocks]
	);

	if (blocks === null) {
		return <Spinner />;
	}

	if (blocks.length === 0) {
		return (
			<p>{__('No blocks found. Run npm run build.', 'isudev-library')}</p>
		);
	}

	const toggle = (block, enabled) => {
		if (
			!enabled &&
			block.dependents.length > 0 &&
			// eslint-disable-next-line no-alert
			!window.confirm(
				__(
					'This also disables the blocks that depend on it. Continue?',
					'isudev-library'
				)
			)
		) {
			return;
		}

		apiFetch({
			path: `/isudev-library/v1/blocks/${block.slug}`,
			method: 'POST',
			data: { enabled },
		})
			.then(onChanged)
			.catch((err) => onError(err.message));
	};

	/**
	 * Enable or disable every unlocked block, one request at a time.
	 *
	 * Sequential, not Promise.all: the endpoint reads, mutates and writes
	 * back the whole options array per request, so concurrent requests
	 * racing that read-modify-write could silently drop one block's change.
	 *
	 * @param {boolean} enabled Target state for every unlocked block.
	 * @return {Promise<void>}
	 */
	const setAll = async (enabled) => {
		const targets = blocks.filter(
			(block) => !block.locked && block.enabled !== enabled
		);

		if (targets.length === 0) {
			return;
		}

		try {
			for (const block of targets) {
				// Sequential by design — see the docblock above.
				// eslint-disable-next-line no-await-in-loop
				await apiFetch({
					path: `/isudev-library/v1/blocks/${block.slug}`,
					method: 'POST',
					data: { enabled },
				});
			}
			onChanged();
		} catch (err) {
			onError(err.message);
		}
	};

	const enabledCount = blocks.filter((block) => block.enabled).length;

	return (
		<div className="isudev-admin__blocks">
			<p className="isudev-admin__note">
				{__(
					'Disabling a block removes it entirely — no editor or frontend assets load, and blocks already inserted in content render as nothing.',
					'isudev-library'
				)}
			</p>

			<div className="isudev-admin__summary">
				<p className="isudev-admin__summary-count">
					{sprintf(
						/* translators: 1: number of enabled blocks, 2: total number of blocks. */
						__('%1$d of %2$d blocks enabled', 'isudev-library'),
						enabledCount,
						blocks.length
					)}
				</p>
				<div className="isudev-admin__summary-actions">
					<Button variant="secondary" onClick={() => setAll(true)}>
						{__('Enable all', 'isudev-library')}
					</Button>
					<Button variant="secondary" onClick={() => setAll(false)}>
						{__('Disable all', 'isudev-library')}
					</Button>
				</div>
			</div>

			{groups.map(({ block, children }) => (
				<div className="isudev-admin__group" key={block.slug}>
					<BlockCard
						block={block}
						onToggle={toggle}
						isParent={children.length > 0}
					/>

					{children.length > 0 && (
						<div
							className={`isudev-admin__inner-blocks${
								block.enabled ? '' : ' is-parent-disabled'
							}`}
						>
							<div className="isudev-admin__group-header">
								<span>
									{__('Inner blocks', 'isudev-library')}
								</span>
								<span>
									{block.enabled
										? sprintf(
												/* translators: 1: number of enabled inner blocks, 2: total number of inner blocks. */
												__(
													'%1$d of %2$d enabled',
													'isudev-library'
												),
												children.filter(
													(child) => child.enabled
												).length,
												children.length
											)
										: __(
												'Unavailable while parent is disabled',
												'isudev-library'
											)}
								</span>
							</div>

							{children.map((child) => (
								<BlockCard
									key={child.slug}
									block={child}
									onToggle={toggle}
									compact
								/>
							))}
						</div>
					)}
				</div>
			))}
		</div>
	);
}
