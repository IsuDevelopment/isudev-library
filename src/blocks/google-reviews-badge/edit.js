/**
 * WordPress dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useReviewAccounts from '../../utils/use-review-accounts';

/**
 * Editor UI for the Google Reviews badge block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor markup.
 */
export default function Edit({ attributes, setAttributes }) {
	const { accountId, useLightText } = attributes;
	const { accounts, accountsError } = useReviewAccounts();
	const selectedAccount = accounts.find(
		(account) => account.id === accountId
	);
	const numericRating = Math.min(
		5,
		Math.max(0, Number(selectedAccount?.averageRating || 0))
	);
	const filledStars = Math.round(numericRating);
	const stars = `${'★'.repeat(filledStars)}${'☆'.repeat(5 - filledStars)}`;
	const rating = numericRating.toLocaleString(undefined, {
		minimumFractionDigits: 1,
		maximumFractionDigits: 1,
	});
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

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Badge settings', 'isudev-library')}>
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
						label={__('Use light text', 'isudev-library')}
						checked={useLightText}
						onChange={(value) =>
							setAttributes({ useLightText: value })
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div
				{...useBlockProps({
					className: `isudev-google-reviews-badge${useLightText ? ' has-light-text' : ''}`,
				})}
			>
				<span
					className="isudev-google-reviews-badge__google-logo"
					aria-hidden="true"
				/>
				<div className="isudev-google-reviews-badge__content">
					<div className="isudev-google-reviews-badge__rating">
						<strong>{rating}</strong>
						<span
							className="isudev-google-reviews-badge__stars"
							aria-hidden="true"
						>
							{stars}
						</span>
					</div>
					<span className="isudev-google-reviews-badge__count">
						{sprintf(
							/* translators: %d: total number of Google ratings. */
							__('%d reviews on Google', 'isudev-library'),
							selectedAccount?.totalReviewCount || 0
						)}
					</span>
				</div>
			</div>
		</>
	);
}
