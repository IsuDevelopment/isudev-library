/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

/**
 * Fetch the list of Google review accounts available to pick from, shared by
 * all three Google Reviews blocks' editor UIs.
 *
 * @return {{accounts: Array, accountsError: boolean}} Accounts and whether the fetch failed.
 */
export default function useReviewAccounts() {
	const [accounts, setAccounts] = useState([]);
	const [accountsError, setAccountsError] = useState(false);

	useEffect(() => {
		let isActive = true;

		apiFetch({ path: '/isudev-library/v1/google-review-accounts' })
			.then((response) => {
				if (isActive) {
					setAccounts(Array.isArray(response) ? response : []);
				}
			})
			.catch(() => {
				if (isActive) {
					setAccountsError(true);
				}
			});

		return () => {
			isActive = false;
		};
	}, []);

	return { accounts, accountsError };
}
