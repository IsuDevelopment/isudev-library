/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import BlockCard from './block-card';

export default function BlocksTab({ blocks, onChanged, onError }) {
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

	return (
		<div className="isudev-admin__blocks">
			{blocks.map((block) => (
				<BlockCard key={block.slug} block={block} onToggle={toggle} />
			))}
		</div>
	);
}
