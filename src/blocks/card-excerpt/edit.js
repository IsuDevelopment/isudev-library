/**
 * WordPress dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useCardPost from '../../utils/post-cards/use-card-post';

const stripTags = (html) => decodeEntities(html.replace(/<[^>]*>/g, ' '));

export default function Edit({ attributes, setAttributes, context }) {
	const { words } = attributes;
	const { post, options, hasPost } = useCardPost(context);
	const blockProps = useBlockProps({ className: 'isudev-card-excerpt' });

	let text = options.customExcerpt;
	if (!text && hasPost) {
		const source =
			post?.excerpt?.raw ||
			stripTags(post?.excerpt?.rendered || post?.content?.rendered || '');
		const list = source.trim().split(/\s+/);
		text =
			list.slice(0, words).join(' ') + (list.length > words ? '…' : '');
	}
	if (!hasPost) {
		text = __(
			'The excerpt of the post shown in the card.',
			'isudev-library'
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Excerpt', 'isudev-library')}>
					<RangeControl
						label={__('Maximum words', 'isudev-library')}
						help={__(
							'Applies to an excerpt made from the content. A written excerpt is shown whole.',
							'isudev-library'
						)}
						min={5}
						max={100}
						value={words}
						onChange={(next) => setAttributes({ words: next })}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			{options.hideExcerpt ? (
				<div {...blockProps} hidden />
			) : (
				<p {...blockProps}>{text}</p>
			)}
		</>
	);
}
