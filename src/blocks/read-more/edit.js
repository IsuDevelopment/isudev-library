/**
 * External dependencies
 */
import { LinkPickerControl } from '@isudev/gutenberg/controls/LinkPickerControl';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Button, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from './icon';

export default function Edit({ attributes, setAttributes }) {
	const { link } = attributes;
	const blockProps = useBlockProps({
		className: 'wp-block-isudev-read-more',
	});

	return (
		<div {...blockProps}>
			<LinkPickerControl
				value={link}
				onChange={(next) => setAttributes({ link: next })}
			>
				{({ anchorRef, open }) => (
					<div ref={anchorRef}>
						<Placeholder
							icon={icon}
							label={__('Read More', 'isudev-library')}
							instructions={__(
								'Choose where this card should link to.',
								'isudev-library'
							)}
						>
							<Button variant="primary" onClick={open}>
								{__('Pick link', 'isudev-library')}
							</Button>
						</Placeholder>
					</div>
				)}
			</LinkPickerControl>
			{link?.url ? <p>{link.url}</p> : null}
		</div>
	);
}
