/**
 * External dependencies
 */
import { PostPickerControl } from '@isudev/gutenberg/controls/PostPickerControl';
import { ResponsiveControl } from '@isudev/gutenberg/controls/ResponsiveControl';

/**
 * WordPress dependencies
 */
import {
	BlockContextProvider,
	BlockControls,
	InnerBlocks,
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';
import { parse } from '@wordpress/blocks';
import {
	Button,
	Disabled,
	Notice,
	PanelBody,
	Placeholder,
	RangeControl,
	SelectControl,
	Spinner,
	ToolbarButton,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { RawHTML, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	cardSpans,
	resolveOptions,
	spanStyle,
	variantsFor,
} from '../../utils/post-cards/data';
import CardOptionsPanel from '../../utils/post-cards/options-panel';
import pickerLabels from '../../utils/post-cards/picker-labels';
import { requestPreview, usePreview } from '../../utils/post-cards/preview';
import { useWrapper } from '../../utils/post-cards/wrapper-context';

const MAX_SPAN = 6;

export default function Edit({ attributes, setAttributes, context, clientId }) {
	const { source, postId, variant, cardOptions, layout } = attributes;
	const wrapper = useWrapper(clientId, context);
	const isSlot = source === 'query';
	const inQueryLoop =
		!isSlot && !postId && context.queryId !== undefined && !!context.postId;
	const needsPost = !isSlot && !postId && !inQueryLoop;
	const { replaceInnerBlocks } = useDispatch(blockEditorStore);
	const [detachError, setDetachError] = useState('');

	const item = needsPost
		? null
		: {
				attributes: { source, postId, variant, cardOptions },
				context: {
					postId: inQueryLoop ? context.postId : undefined,
					queryId: inQueryLoop ? context.queryId : undefined,
					'isudev/cardsOptions': context['isudev/cardsOptions'],
					'isudev/cardsVariant': context['isudev/cardsVariant'],
				},
				wrapper:
					isSlot && wrapper && wrapper.index > -1
						? wrapper
						: undefined,
			};

	const { status, data } = usePreview(item);
	const hasPost = data && !data.empty;
	const postType = hasPost ? data.postType : null;
	const spans = cardSpans(attributes, context);

	const blockProps = useBlockProps({
		className: [
			...(hasPost ? data.classes : ['isudev-post-card']),
			'is-editor-preview',
		].join(' '),
		style: spanStyle(spans),
	});

	const columns = Number(context['isudev/cardsColumns']) || MAX_SPAN;
	const variants = variantsFor(postType);
	const inherited = resolveOptions(
		context['isudev/cardsOptions'] ?? {},
		{},
		postType ?? ''
	);

	const choosePost = (value) =>
		setAttributes({ postId: Number(value?.id ?? value) || 0 });

	const detach = () => {
		setDetachError('');
		requestPreview({ ...item, withTemplate: true }).then((result) => {
			if (!result?.template) {
				setDetachError(
					__(
						'This card uses a PHP template, which cannot be edited as blocks. Edit the template file in the theme instead.',
						'isudev-library'
					)
				);
				return;
			}
			replaceInnerBlocks(clientId, parse(result.template), false);
			setAttributes({ layout: 'custom' });
		});
	};

	const attach = () => {
		replaceInnerBlocks(clientId, [], false);
		setAttributes({ layout: 'template' });
	};

	const picker = (label, variantName = 'primary') => (
		<PostPickerControl
			value={postId || null}
			onChange={choosePost}
			multiple={false}
			labels={pickerLabels(true)}
		>
			{({ open }) => (
				<Button variant={variantName} onClick={open}>
					{label}
				</Button>
			)}
		</PostPickerControl>
	);

	const inspector = (
		<InspectorControls>
			<PanelBody title={__('Card', 'isudev-library')}>
				{wrapper && (
					<SelectControl
						label={__('Source', 'isudev-library')}
						value={source}
						options={[
							{
								value: 'post',
								label: __('Hand-picked post', 'isudev-library'),
							},
							{
								value: 'query',
								label: __(
									'Next post from the query',
									'isudev-library'
								),
							},
						]}
						help={
							isSlot
								? __(
										'Filled from the Post Cards query, skipping hand-picked posts.',
										'isudev-library'
									)
								: undefined
						}
						onChange={(next) => setAttributes({ source: next })}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				)}
				{!isSlot && !inQueryLoop && (
					<div className="isudev-post-card__inspector-row">
						{picker(
							postId
								? __('Replace post', 'isudev-library')
								: __('Choose post', 'isudev-library'),
							'secondary'
						)}
					</div>
				)}
				<SelectControl
					label={__('Template', 'isudev-library')}
					value={variant}
					options={[
						{
							value: '',
							label: wrapper
								? __('Same as Post Cards', 'isudev-library')
								: __(
										'Default for the post type',
										'isudev-library'
									),
						},
						...variants,
					]}
					help={
						variants.length
							? undefined
							: __(
									'The theme has no template variants for this post type.',
									'isudev-library'
								)
					}
					onChange={(next) => setAttributes({ variant: next })}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
				<div className="isudev-post-card__inspector-row">
					{layout === 'custom' ? (
						<Button variant="secondary" onClick={attach}>
							{__('Use the template again', 'isudev-library')}
						</Button>
					) : (
						<Button
							variant="secondary"
							onClick={detach}
							disabled={!hasPost}
						>
							{__("Edit this card's layout", 'isudev-library')}
						</Button>
					)}
				</div>
				{detachError && (
					<Notice status="warning" isDismissible={false}>
						{detachError}
					</Notice>
				)}
			</PanelBody>
			{wrapper && (
				<PanelBody title={__('Width', 'isudev-library')}>
					<ResponsiveControl
						attrName="span"
						label={__('Columns spanned', 'isudev-library')}
						attributes={attributes}
						setAttributes={setAttributes}
					>
						{({ value, inheritedValue, onChange }) => (
							<RangeControl
								min={1}
								max={Math.max(columns, 1)}
								value={value ?? inheritedValue ?? 1}
								onChange={onChange}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						)}
					</ResponsiveControl>
				</PanelBody>
			)}
			<CardOptionsPanel
				level="card"
				postType={postType}
				value={cardOptions}
				inherited={inherited}
				onChange={(next) => setAttributes({ cardOptions: next })}
			/>
		</InspectorControls>
	);

	let body;

	if (needsPost) {
		body = (
			<Placeholder
				label={__('Post Card', 'isudev-library')}
				instructions={__(
					'Choose the post this card shows.',
					'isudev-library'
				)}
			>
				{picker(__('Choose post', 'isudev-library'))}
				{wrapper && (
					<Button
						variant="secondary"
						onClick={() => setAttributes({ source: 'query' })}
					>
						{__('Fill from the query', 'isudev-library')}
					</Button>
				)}
			</Placeholder>
		);
	} else if (!data && status === 'loading') {
		body = <Spinner />;
	} else if (status === 'error') {
		body = (
			<Placeholder label={__('Post Card', 'isudev-library')}>
				{__('The card preview could not be loaded.', 'isudev-library')}
			</Placeholder>
		);
	} else if (!hasPost) {
		body = (
			<Placeholder label={__('Post Card', 'isudev-library')}>
				{isSlot
					? __(
							'No post left for this query slot. It renders nothing on the page.',
							'isudev-library'
						)
					: __(
							'This post is not published or no longer exists. The card renders nothing on the page.',
							'isudev-library'
						)}
			</Placeholder>
		);
	} else if (layout === 'custom') {
		body = (
			<BlockContextProvider
				value={{
					postId: data.postId,
					postType: data.postType,
					'isudev/cardOptions': resolveOptions(
						context['isudev/cardsOptions'] ?? {},
						cardOptions,
						data.postType
					),
				}}
			>
				<div className="isudev-post-card__inner">
					<InnerBlocks templateLock={false} />
				</div>
			</BlockContextProvider>
		);
	} else {
		body = (
			<Disabled>
				<RawHTML className="isudev-post-card__preview">
					{data.html}
				</RawHTML>
			</Disabled>
		);
	}

	return (
		<>
			{!isSlot && !inQueryLoop && postId > 0 && (
				<BlockControls group="other">
					<PostPickerControl
						value={postId}
						onChange={choosePost}
						multiple={false}
						labels={pickerLabels(true)}
					>
						{({ open }) => (
							<ToolbarButton onClick={open}>
								{__('Replace post', 'isudev-library')}
							</ToolbarButton>
						)}
					</PostPickerControl>
				</BlockControls>
			)}
			{inspector}
			<div {...blockProps}>{body}</div>
		</>
	);
}
