/**
 * External dependencies
 */
import { MediaPreview } from '@isudev/gutenberg/components/MediaPreview';

/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useCardPost from '../../utils/post-cards/use-card-post';

export default function Edit({ attributes, setAttributes, context }) {
	const { sizeSlug, aspectRatio } = attributes;
	const { post, options } = useCardPost(context);
	const custom = options.customImage;
	const mediaId = custom?.id ? 0 : post?.featured_media || 0;

	const { media, sizes } = useSelect(
		(select) => ({
			media: mediaId ? select(coreStore).getMedia(mediaId) : null,
			sizes: select(blockEditorStore).getSettings().imageSizes ?? [],
		}),
		[mediaId]
	);

	const url = custom?.url
		? custom.url
		: media?.media_details?.sizes?.[sizeSlug]?.source_url ||
			media?.source_url ||
			'';

	const blockProps = useBlockProps({
		className: 'isudev-card-image',
		style: aspectRatio ? { aspectRatio } : undefined,
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Image', 'isudev-library')}>
					<SelectControl
						label={__('Image size', 'isudev-library')}
						value={sizeSlug}
						options={sizes.map((size) => ({
							value: size.slug,
							label: size.name,
						}))}
						onChange={(next) => setAttributes({ sizeSlug: next })}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={__('Aspect ratio', 'isudev-library')}
						help={__(
							'For example 16/9 or 4/5. Empty keeps the image’s own.',
							'isudev-library'
						)}
						value={aspectRatio}
						onChange={(next) =>
							setAttributes({ aspectRatio: next })
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<figure {...blockProps}>
				{url ? (
					<MediaPreview
						value={{ url, type: 'image', alt: '' }}
						focalPoint={custom?.focalPoint}
						className="isudev-card-image__img"
					/>
				) : (
					<div className="isudev-card-image__placeholder">
						{__('Card image', 'isudev-library')}
					</div>
				)}
			</figure>
		</>
	);
}
