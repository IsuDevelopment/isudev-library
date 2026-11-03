/**
 * WordPress dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

export default function Edit({ attributes, setAttributes }) {
	const { name, tagName } = attributes;
	const blockProps = useBlockProps({ className: 'isudev-card-slot' });

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Slot', 'isudev-library')}>
					<TextControl
						label={__('Slot name', 'isudev-library')}
						help={
							name
								? sprintf(
										/* translators: %s: filter name. */
										__(
											'Filled by the %s filter.',
											'isudev-library'
										),
										`isudev_library/post_card/slot/${name}`
									)
								: __(
										'Lowercase letters, numbers, - and _.',
										'isudev-library'
									)
						}
						value={name}
						onChange={(next) =>
							setAttributes({
								name: next
									.toLowerCase()
									.replace(/[^a-z0-9_-]/g, ''),
							})
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={__('HTML element', 'isudev-library')}
						value={tagName}
						options={['div', 'p', 'span'].map((tag) => ({
							value: tag,
							label: `<${tag}>`,
						}))}
						onChange={(next) => setAttributes({ tagName: next })}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<span className="isudev-card-slot__label">
					{name
						? /* translators: %s: slot name. */
							sprintf(__('Slot: %s', 'isudev-library'), name)
						: __('Slot without a name', 'isudev-library')}
				</span>
			</div>
		</>
	);
}
