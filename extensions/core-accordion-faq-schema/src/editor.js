/**
 * Accordion FAQ structured data, editor side: the `isudevFaqSchema` attribute
 * on core/accordion (also registered in hooks.php) and its switch.
 */

/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const BLOCK = 'core/accordion';

addFilter(
	'blocks.registerBlockType',
	'isudev-library/core-accordion-faq-schema/attribute',
	(settings, name) =>
		BLOCK !== name
			? settings
			: {
					...settings,
					attributes: {
						...settings.attributes,
						isudevFaqSchema: { type: 'boolean', default: false },
					},
				}
);

/**
 * Adds the panel to core/accordion's BlockEdit.
 *
 * @param {Function} BlockEdit Original edit component.
 * @return {Function} Wrapped edit component.
 */
const withFaqControl = (BlockEdit) => (props) => {
	if (BLOCK !== props.name) {
		return <BlockEdit {...props} />;
	}

	const { attributes, setAttributes } = props;

	return (
		<>
			<BlockEdit {...props} />
			<InspectorControls>
				<PanelBody
					title={__('FAQ structured data', 'isudev-library')}
					initialOpen={false}
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__(
							'Publish as FAQ structured data',
							'isudev-library'
						)}
						help={__(
							'Adds every item of this accordion to the page as a question and answer in FAQPage JSON-LD. Use it only for real questions with one answer each.',
							'isudev-library'
						)}
						checked={!!attributes.isudevFaqSchema}
						onChange={(value) =>
							setAttributes({ isudevFaqSchema: value })
						}
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};

addFilter(
	'editor.BlockEdit',
	'isudev-library/core-accordion-faq-schema/control',
	withFaqControl
);
