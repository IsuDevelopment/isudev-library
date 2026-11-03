/**
 * WordPress dependencies
 */
import {
	BlockControls,
	HeadingLevelDropdown,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useCardPost from '../../utils/post-cards/use-card-post';

export default function Edit({ attributes, setAttributes, context }) {
	const { level, isLink, stretchLink } = attributes;
	const { post, options, hasPost } = useCardPost(context);
	const Tag = `h${level >= 1 && level <= 6 ? level : 3}`;
	const blockProps = useBlockProps({ className: 'isudev-card-title' });

	let title = options.customTitle;
	if (!title) {
		title = hasPost
			? decodeEntities(post?.title?.rendered ?? '')
			: __('Card title', 'isudev-library');
	}

	return (
		<>
			<BlockControls group="block">
				<HeadingLevelDropdown
					value={level}
					onChange={(next) => setAttributes({ level: next })}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={__('Link', 'isudev-library')}>
					<ToggleControl
						label={__('Link to the post', 'isudev-library')}
						checked={isLink}
						onChange={(next) => setAttributes({ isLink: next })}
						__nextHasNoMarginBottom
					/>
					{isLink && (
						<ToggleControl
							label={__(
								'Make the whole card clickable',
								'isudev-library'
							)}
							help={__(
								'Stretches this link over the card. Turn it off when the card has another main link.',
								'isudev-library'
							)}
							checked={stretchLink}
							onChange={(next) =>
								setAttributes({ stretchLink: next })
							}
							__nextHasNoMarginBottom
						/>
					)}
				</PanelBody>
			</InspectorControls>
			<Tag {...blockProps}>
				{isLink ? (
					// eslint-disable-next-line jsx-a11y/anchor-is-valid -- preview only, not navigable in the editor.
					<a
						className="isudev-card-title__link"
						href="#isudev-card-title"
						onClick={(event) => event.preventDefault()}
					>
						{title}
					</a>
				) : (
					title
				)}
			</Tag>
		</>
	);
}
