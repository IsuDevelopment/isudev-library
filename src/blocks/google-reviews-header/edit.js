/**
 * External dependencies
 */
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

const HEADING_LEVELS = [2, 3, 4, 5, 6];
const INLINE_FORMATS = [
	'core/bold',
	'core/italic',
	'core/strikethrough',
	'core/underline',
	'core/text-color',
	'core/subscript',
	'core/superscript',
	'core/keyboard',
];

/**
 * Editor UI for the Google Reviews header block.
 *
 * This block renders in PHP from live database data the editor cannot
 * preview, so the canvas shows a lightweight preview built from the same
 * data the REST endpoint returns, rather than the exact frontend markup.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const {
		accountId,
		buttonText,
		header,
		description,
		logo = {},
		logoMaxHeight,
		hidePoweredByGoogle,
		renderAsHeading,
		headingLevel,
	} = attributes;
	const { accounts, accountsError } = useReviewAccounts();
	const selectedAccount = accounts.find(
		(account) => account.id === accountId
	);
	const titleTag = renderAsHeading ? `h${headingLevel}` : 'div';
	const accountOptions = [
		{ label: __('Select a Google account', 'isudev-library'), value: '' },
		...accounts.map((account) => ({
			label: sprintf(
				/* translators: 1: account name, 2: total number of ratings. */
				__('%1$s (%2$d reviews)', 'isudev-library'),
				account.name,
				account.totalReviewCount
			),
			value: account.id,
		})),
	];

	const changeAccount = (value) => {
		const nextAccount = accounts.find((account) => account.id === value);
		const nextAttributes = { accountId: value };

		if (!header && nextAccount) {
			nextAttributes.header = nextAccount.name;
		}

		setAttributes(nextAttributes);
	};

	const numericRating = Number(selectedAccount?.averageRating || 0);
	const filledStars = Math.round(numericRating);
	const stars = `${'★'.repeat(filledStars)}${'☆'.repeat(5 - filledStars)}`;
	const rating = numericRating.toLocaleString(undefined, {
		minimumFractionDigits: 1,
		maximumFractionDigits: 1,
	});
	const blockProps = useBlockProps({
		className: 'isudev-google-reviews-header',
		style: {
			'--isudev-google-reviews-header-logo-max-height': `${logoMaxHeight}px`,
		},
	});

	return (
		<>
			{renderAsHeading && (
				<BlockControls group="block">
					<HeadingLevelDropdown
						options={HEADING_LEVELS}
						value={headingLevel}
						onChange={(value) =>
							setAttributes({ headingLevel: value })
						}
					/>
				</BlockControls>
			)}
			<InspectorControls>
				<PanelBody title={__('Header settings', 'isudev-library')}>
					<SelectControl
						__nextHasNoMarginBottom
						label={__('Google account', 'isudev-library')}
						value={accountId}
						options={accountOptions}
						onChange={changeAccount}
						help={
							accountsError
								? __(
										'Could not load the account list.',
										'isudev-library'
									)
								: undefined
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={__('Button text', 'isudev-library')}
						value={buttonText}
						onChange={(value) =>
							setAttributes({ buttonText: value })
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Hide "powered by Google"', 'isudev-library')}
						checked={hidePoweredByGoogle}
						onChange={(value) =>
							setAttributes({ hidePoweredByGoogle: value })
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Render title as heading', 'isudev-library')}
						checked={renderAsHeading}
						onChange={(value) =>
							setAttributes({ renderAsHeading: value })
						}
					/>
					{renderAsHeading && (
						<HeadingLevelDropdown
							options={HEADING_LEVELS}
							value={headingLevel}
							onChange={(value) =>
								setAttributes({ headingLevel: value })
							}
						/>
					)}
				</PanelBody>
				<PanelBody
					title={__('Company logo', 'isudev-library')}
					initialOpen={false}
				>
					<RangeControl
						__nextHasNoMarginBottom
						label={__('Maximum logo height (px)', 'isudev-library')}
						value={logoMaxHeight}
						onChange={(value) =>
							setAttributes({ logoMaxHeight: value })
						}
						min={16}
						max={256}
					/>
					<MediaControl
						value={logo}
						onChange={(next) => setAttributes({ logo: next })}
						onRemove={() => setAttributes({ logo: {} })}
						sources={{ featured: false }}
						toolbar={false}
						sidebar={false}
						canvas={{
							className:
								'isudev-google-reviews-header__logo-canvas',
							placeholderLabel: __('Logo', 'isudev-library'),
							placeholderInstructions: __(
								'Upload the company logo.',
								'isudev-library'
							),
						}}
					/>
				</PanelBody>
			</InspectorControls>

			<section {...blockProps}>
				<div className="isudev-google-reviews-header__logo">
					{logo?.url ? (
						<img
							className="isudev-google-reviews-header__logo-image"
							src={logo.url}
							alt={logo.alt || ''}
						/>
					) : (
						<span>{__('Logo', 'isudev-library')}</span>
					)}
				</div>
				<div className="isudev-google-reviews-header__main">
					<RichText
						tagName={titleTag}
						className="isudev-google-reviews-header__title"
						value={header}
						onChange={(value) => setAttributes({ header: value })}
						allowedFormats={INLINE_FORMATS}
						placeholder={__('Review header…', 'isudev-library')}
					/>
					<RichText
						tagName="p"
						className="isudev-google-reviews-header__description"
						value={description}
						onChange={(value) =>
							setAttributes({ description: value })
						}
						allowedFormats={INLINE_FORMATS}
						placeholder={__(
							'Optional text below the header…',
							'isudev-library'
						)}
					/>
					<div className="isudev-google-reviews-header__rating">
						<strong>{rating}</strong>
						<span
							className="isudev-google-reviews-header__stars"
							aria-hidden="true"
						>
							{stars}
						</span>
						<span>
							{sprintf(
								/* translators: %d: total number of ratings. */
								__('Based on %d reviews', 'isudev-library'),
								selectedAccount?.totalReviewCount || 0
							)}
						</span>
					</div>
				</div>
				<div className="isudev-google-reviews-header__actions">
					{!hidePoweredByGoogle && (
						<span className="isudev-google-reviews-header__powered">
							{__('powered by Google', 'isudev-library')}
						</span>
					)}
					<span className="isudev-google-reviews-header__button">
						{buttonText || __('Rate us', 'isudev-library')}
					</span>
				</div>
			</section>
		</>
	);
}
