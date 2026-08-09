/**
 * WordPress dependencies
 */
import {
	Card,
	CardBody,
	Flex,
	FlexBlock,
	FlexItem,
	ToggleControl,
} from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Human-readable explanation of where a block's state comes from.
 *
 * @param {Object} block Block payload from the REST endpoint.
 * @return {string} Explanation shown under the toggle.
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
			return __(
				'Disabling removes the block entirely — no editor or frontend assets load, and blocks already inserted in content render as nothing.',
				'isudev-library'
			);
	}
}

export default function BlockCard({ block, onToggle }) {
	const dependentsWarning =
		block.enabled && block.dependents.length > 0
			? sprintf(
					/* translators: %s: comma-separated list of block slugs. */
					__('Disabling this also disables: %s', 'isudev-library'),
					block.dependents.join(', ')
				)
			: '';

	return (
		<Card className="isudev-admin__card" size="small">
			<CardBody>
				<Flex align="flex-start" gap={4}>
					<FlexBlock>
						<h2 className="isudev-admin__card-title">
							{block.title}
						</h2>
						<p className="isudev-admin__card-name">
							<code>{block.name}</code>
						</p>
						{block.description && <p>{block.description}</p>}
					</FlexBlock>
					<FlexItem>
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
						{dependentsWarning && (
							<p className="isudev-admin__card-warning">
								{dependentsWarning}
							</p>
						)}
					</FlexItem>
				</Flex>
			</CardBody>
		</Card>
	);
}
