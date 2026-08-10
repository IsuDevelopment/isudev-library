/**
 * External dependencies
 */
import { BlockLinkControl } from '@isudev/gutenberg/controls/BlockLinkControl';
import { LinkPickerControl } from '@isudev/gutenberg/controls/LinkPickerControl';
import { MediaControl } from '@isudev/gutenberg/controls/MediaControl';

/**
 * WordPress dependencies
 */
import {
	BlockControls,
	HeadingLevelDropdown,
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	Placeholder,
	ToggleControl,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from './icon';

const classNames = (...classes) => classes.filter(Boolean).join(' ');
const HEADING_LEVELS = [2, 3, 4, 5, 6];
// 0 is the dropdown's "Paragraph" entry, which this block renders as a div.
const HEADING_OPTIONS = [0, ...HEADING_LEVELS];

export default function Edit({ attributes, setAttributes }) {
	const {
		link = {},
		media = {},
		hasCustomTitle = false,
		customTitle = '',
		showAdditionalText = false,
		additionalText = '',
		renderAsHeading = true,
		headingLevel = 3,
		readMoreText = '',
		showReadMoreBadge = false,
		showFeaturedImage = true,
	} = attributes;

	// Mirror render.php: only a post-type link resolves to an entity record.
	const linkedPost = useSelect(
		(select) => {
			if (link?.kind !== 'post-type' || !link?.id || !link?.type) {
				return null;
			}
			return select(coreStore).getEntityRecord(
				'postType',
				link.type,
				link.id
			);
		},
		[link?.kind, link?.id, link?.type]
	);

	const postTitle = decodeEntities(
		typeof linkedPost?.title === 'string'
			? linkedPost.title
			: linkedPost?.title?.rendered || ''
	);

	const hasOwnMedia = Boolean(media?.id || media?.url);
	const thumbnailId = linkedPost?.featured_media || 0;

	// The fallback image is only fetched when the author has not chosen one.
	const thumbnail = useSelect(
		(select) => {
			if (!showFeaturedImage || hasOwnMedia || !thumbnailId) {
				return null;
			}
			return select(coreStore).getMedia(thumbnailId);
		},
		[showFeaturedImage, hasOwnMedia, thumbnailId]
	);

	/*
	 * MediaControl draws whatever value it is given. Hand it the author's own
	 * media when there is any, otherwise a value synthesised from the linked
	 * post's thumbnail, so the canvas shows the inherited image instead of an
	 * empty placeholder. Writes always go to the real `media` attribute.
	 */
	const inheritedMedia = thumbnail
		? {
				id: thumbnailId,
				url:
					thumbnail.media_details?.sizes?.medium?.source_url ||
					thumbnail.source_url,
				alt: thumbnail.alt_text || '',
				type: 'image',
				source: 'attachment',
			}
		: {};

	const displayMedia = hasOwnMedia ? media : inheritedMedia;

	const imageUrl = displayMedia.url || '';

	const title =
		(hasCustomTitle && customTitle.trim() !== '' && customTitle) ||
		postTitle ||
		link?.title ||
		link?.url ||
		'';

	const level = HEADING_LEVELS.includes(Number(headingLevel))
		? Number(headingLevel)
		: 3;
	const TitleTag = renderAsHeading ? `h${level}` : 'div';

	const enableCustomTitle = useCallback(() => {
		setAttributes({
			hasCustomTitle: true,
			customTitle: customTitle || title,
		});
	}, [customTitle, title, setAttributes]);

	const setCustomTitleEnabled = useCallback(
		(value) => {
			setAttributes({
				hasCustomTitle: value,
				...(value && !customTitle ? { customTitle: title } : {}),
			});
		},
		[customTitle, title, setAttributes]
	);

	const blockProps = useBlockProps({
		className: classNames(
			'wp-block-isudev-read-more',
			showFeaturedImage && imageUrl && 'has-image',
			showReadMoreBadge && 'has-read-more-badge',
			link?.type && `is-link-type-${link.type}`
		),
	});

	if (!link?.url) {
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
			</div>
		);
	}

	return (
		<>
			{/* No unlink action: a card without a destination renders as
			    nothing, so removing the link would silently blank the block. */}
			<BlockLinkControl
				value={link}
				onChange={(next) => setAttributes({ link: next })}
				addLabel={__('Add link', 'isudev-library')}
				editLabel={__('Edit link', 'isudev-library')}
			/>

			<BlockControls group="block">
				<HeadingLevelDropdown
					options={HEADING_OPTIONS}
					value={renderAsHeading ? level : 0}
					onChange={(next) =>
						setAttributes(
							next === 0
								? { renderAsHeading: false }
								: {
										renderAsHeading: true,
										headingLevel: next,
									}
						)
					}
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={__('Read More settings', 'isudev-library')}>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Show image', 'isudev-library')}
						checked={showFeaturedImage}
						onChange={(value) =>
							setAttributes({ showFeaturedImage: value })
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Read more badge', 'isudev-library')}
						help={__(
							'Show a small label above the title.',
							'isudev-library'
						)}
						checked={showReadMoreBadge}
						onChange={(value) =>
							setAttributes({ showReadMoreBadge: value })
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Use custom title', 'isudev-library')}
						help={__(
							'Otherwise the linked page title is used.',
							'isudev-library'
						)}
						checked={hasCustomTitle}
						onChange={setCustomTitleEnabled}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Additional text', 'isudev-library')}
						help={__(
							'Show supporting text below the title.',
							'isudev-library'
						)}
						checked={showAdditionalText}
						onChange={(value) =>
							setAttributes({ showAdditionalText: value })
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="read-more-inner">
					{showFeaturedImage && (
						<MediaControl
							value={displayMedia}
							onChange={(next) => setAttributes({ media: next })}
							onRemove={() => setAttributes({ media: {} })}
							sources={{ featured: false }}
							toolbar={false}
							canvas={{
								className: 'read-more-image',
								placeholderLabel: __(
									'Card image',
									'isudev-library'
								),
								placeholderInstructions: __(
									'Upload an image, or leave it empty to inherit the linked post\u2019s featured image.',
									'isudev-library'
								),
								// Removing is only meaningful for the author's
								// own pick; on an inherited thumbnail it would
								// clear nothing and redraw the same image.
								actions: { remove: hasOwnMedia },
								previewProps: { aspectRatio: '159 / 119' },
							}}
							sidebar={{
								title: __('Card image', 'isudev-library'),
							}}
						/>
					)}

					<div className="read-more-content">
						{showReadMoreBadge && (
							<RichText
								tagName="span"
								className="wp-block-button__read_more"
								allowedFormats={['core/text-color']}
								withoutInteractiveFormatting
								placeholder={__('Read more', 'isudev-library')}
								value={readMoreText}
								onChange={(value) =>
									setAttributes({ readMoreText: value })
								}
							/>
						)}

						{hasCustomTitle ? (
							<RichText
								tagName={TitleTag}
								className="read-more-title"
								allowedFormats={[]}
								withoutInteractiveFormatting
								placeholder={__(
									'Custom title',
									'isudev-library'
								)}
								value={customTitle}
								onChange={(value) =>
									setAttributes({ customTitle: value })
								}
							/>
						) : (
							<TitleTag className="read-more-title">
								<button
									type="button"
									className="read-more-title__edit-button"
									onClick={enableCustomTitle}
								>
									{title || __('Untitled', 'isudev-library')}
								</button>
							</TitleTag>
						)}

						{showAdditionalText && (
							<RichText
								tagName="p"
								className="read-more-additional-text"
								allowedFormats={['core/text-color']}
								withoutInteractiveFormatting
								placeholder={__(
									'Additional text',
									'isudev-library'
								)}
								value={additionalText}
								onChange={(value) =>
									setAttributes({ additionalText: value })
								}
							/>
						)}
					</div>
				</div>
			</div>
		</>
	);
}
