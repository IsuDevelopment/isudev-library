/**
 * WordPress dependencies
 */
import {
	Flex,
	FlexBlock,
	FlexItem,
	ToggleControl,
} from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Explanation for why a block's toggle is locked.
 *
 * The common, removable case needs no per-card repetition of the
 * page-level "disabling removes it entirely" notice — only the locked
 * states, which differ block to block, are worth calling out here.
 *
 * @param {Object} block Block payload from the REST endpoint.
 * @return {string} Explanation shown under the toggle, or '' when unlocked.
 */
function stateNotice(block) {
	switch (block.source) {
		case 'code':
			return __(
				'Managed in isudev.json — change it there.',
				'isudev-library'
			);
		case 'always_on':
			return __(
				'Always enabled — this block cannot be turned off.',
				'isudev-library'
			);
		case 'dependency':
			return sprintf(
				/* translators: %s: comma-separated list of block slugs. */
				__('Requires: %s. Enable those first.', 'isudev-library'),
				block.requires.join(', ')
			);
		default:
			return '';
	}
}

export default function BlockCard({ block, onToggle, isParent, compact }) {
	const TitleTag = compact ? 'h4' : 'h2';

	return (
		<div className={`isudev-admin__card${compact ? ' is-compact' : ''}`}>
			<Flex align="flex-start" gap={4}>
				<FlexBlock>
					<Flex
						className="isudev-admin__card-heading"
						gap={2}
						justify="flex-start"
					>
						<TitleTag className="isudev-admin__card-title">
							{block.title}
						</TitleTag>
						<span className="isudev-admin__badge">
							<code>{block.name}</code>
						</span>
						{isParent && (
							<span className="isudev-admin__badge is-parent">
								{__('Parent', 'isudev-library')}
							</span>
						)}
					</Flex>
					{block.description && (
						<p className="isudev-admin__card-description">
							{block.description}
						</p>
					)}
				</FlexBlock>
				<FlexItem className="isudev-admin__card-controls">
					<ToggleControl
						__nextHasNoMarginBottom
						label={
							block.enabled
								? __('Enabled', 'isudev-library')
								: __('Disabled', 'isudev-library')
						}
						checked={block.enabled}
						disabled={block.locked}
						help={stateNotice(block)}
						onChange={(next) => onToggle(block, next)}
					/>
				</FlexItem>
			</Flex>
		</div>
	);
}
