/**
 * WordPress dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useReviewAccounts from '../../utils/use-review-accounts';

const SLIDER_MODES = [
	{ label: __('Continuous scroll', 'isudev-library'), value: 'continuous' },
	{ label: __('Classic', 'isudev-library'), value: 'classic' },
];

/**
 * Editor UI for the Google Reviews list/carousel block.
 *
 * This block renders in PHP from live database data the editor cannot
 * preview, so the canvas shows a settings summary rather than the reviews
 * themselves — the same approach `isudev/google-reviews-header` and
 * `isudev/google-reviews-badge` use for the same reason.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const {
		reviewCount,
		reviewTextLength,
		enableSlider,
		sliderMode,
		accountId,
	} = attributes;
	const { accounts, accountsError } = useReviewAccounts();

	const accountOptions = [
		{ label: __('All accounts', 'isudev-library'), value: '' },
		...accounts.map((account) => ({
			label: sprintf(
				/* translators: 1: account name, 2: number of available reviews. */
				__('%1$s (%2$d reviews)', 'isudev-library'),
				account.name,
				account.reviewCount
			),
			value: account.id,
		})),
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Review settings', 'isudev-library')}>
					<RangeControl
						__nextHasNoMarginBottom
						label={__('Number of reviews', 'isudev-library')}
						value={reviewCount}
						onChange={(value) =>
							setAttributes({ reviewCount: value })
						}
						min={1}
						max={100}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={__('Review text length', 'isudev-library')}
						type="number"
						value={reviewTextLength}
						onChange={(value) =>
							setAttributes({
								reviewTextLength: Number(value),
							})
						}
						help={__(
							'Number of characters shown before the "Read more" button.',
							'isudev-library'
						)}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						label={__('Google account', 'isudev-library')}
						value={accountId}
						options={accountOptions}
						onChange={(value) =>
							setAttributes({ accountId: value })
						}
						help={
							accountsError
								? __(
										'Could not load the account list.',
										'isudev-library'
									)
								: undefined
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Enable carousel', 'isudev-library')}
						checked={enableSlider}
						onChange={(value) =>
							setAttributes({ enableSlider: value })
						}
					/>
					{enableSlider && (
						<SelectControl
							__nextHasNoMarginBottom
							label={__('Carousel mode', 'isudev-library')}
							value={sliderMode}
							options={SLIDER_MODES}
							onChange={(value) =>
								setAttributes({ sliderMode: value })
							}
						/>
					)}
				</PanelBody>
			</InspectorControls>
			<div
				{...useBlockProps({
					className: 'isudev-google-reviews-editor',
				})}
			>
				<strong>{__('Google Reviews', 'isudev-library')}</strong>
				<p>
					{enableSlider
						? sprintf(
								/* translators: %s: selected carousel mode. */
								__('Carousel: %s', 'isudev-library'),
								SLIDER_MODES.find(
									(mode) => mode.value === sliderMode
								)?.label || sliderMode
							)
						: __('List, no carousel', 'isudev-library')}
				</p>
				<p>
					{sprintf(
						/* translators: %d: maximum number of reviews. */
						__('Up to %d reviews.', 'isudev-library'),
						reviewCount
					)}
				</p>
				<p>
					{sprintf(
						/* translators: %d: maximum visible review text length. */
						__('Review excerpt: %d characters.', 'isudev-library'),
						reviewTextLength
					)}
				</p>
			</div>
		</>
	);
}
