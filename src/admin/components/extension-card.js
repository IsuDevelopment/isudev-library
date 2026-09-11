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
 * Explanation for why an extension's toggle is locked.
 *
 * @param {Object} extension Extension payload from the REST endpoint.
 * @return {string} Explanation shown under the toggle, or '' when unlocked.
 */
function stateNotice(extension) {
	switch (extension.source) {
		case 'code':
			return __(
				'Managed in isudev.json — change it there.',
				'isudev-library'
			);
		case 'unavailable':
			return sprintf(
				/* translators: %s: name of the required plugin. */
				__('Needs %s to be active.', 'isudev-library'),
				extension.requiresLabel
			);
		default:
			return '';
	}
}

export default function ExtensionCard({ extension, onToggle }) {
	return (
		<div className="isudev-admin__card">
			<Flex align="flex-start" gap={4}>
				<FlexBlock>
					<Flex
						className="isudev-admin__card-heading"
						gap={2}
						justify="flex-start"
					>
						<h3 className="isudev-admin__card-title">
							{extension.title}
						</h3>
						{extension.requiresLabel && (
							<span className="isudev-admin__badge">
								{extension.requiresLabel}
							</span>
						)}
					</Flex>
					{extension.description && (
						<p className="isudev-admin__card-description">
							{extension.description}
						</p>
					)}
				</FlexBlock>
				<FlexItem className="isudev-admin__card-controls">
					<ToggleControl
						__nextHasNoMarginBottom
						label={
							extension.enabled
								? __('Enabled', 'isudev-library')
								: __('Disabled', 'isudev-library')
						}
						checked={extension.enabled}
						disabled={extension.locked}
						help={stateNotice(extension)}
						onChange={(next) => onToggle(extension, next)}
					/>
				</FlexItem>
			</Flex>
		</div>
	);
}
