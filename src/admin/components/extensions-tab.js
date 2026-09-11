/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import ExtensionCard from './extension-card';

/**
 * Group extensions under their category, in the order the server declared.
 *
 * An extension whose category the server does not know still renders, in a
 * trailing group labelled with its raw slug — a typo in a descriptor should be
 * visible in the panel, not swallow the extension.
 *
 * @param {Array} extensions Extensions from the REST payload.
 * @param {Array} categories `{ slug, label }` in display order.
 * @return {Array} `{ slug, label, extensions }` groups, empty ones dropped.
 */
function groupByCategory(extensions, categories) {
	const known = categories.map((category) => category.slug);
	const unknown = extensions
		.map((extension) => extension.category)
		.filter((slug) => !known.includes(slug));

	return [
		...categories,
		...[...new Set(unknown)].map((slug) => ({ slug, label: slug })),
	]
		.map((category) => ({
			...category,
			extensions: extensions.filter(
				(extension) => extension.category === category.slug
			),
		}))
		.filter((category) => category.extensions.length > 0);
}

export default function ExtensionsTab({
	extensions,
	categories,
	onChanged,
	onError,
}) {
	const groups = useMemo(
		() =>
			extensions === null
				? []
				: groupByCategory(extensions, categories ?? []),
		[extensions, categories]
	);

	if (extensions === null) {
		return <Spinner />;
	}

	if (extensions.length === 0) {
		return <p>{__('No extensions found.', 'isudev-library')}</p>;
	}

	const toggle = (extension, enabled) => {
		apiFetch({
			path: `/isudev-library/v1/extensions/${extension.slug}`,
			method: 'POST',
			data: { enabled },
		})
			.then(onChanged)
			.catch((err) => onError(err.message));
	};

	const enabledCount = extensions.filter(
		(extension) => extension.enabled
	).length;

	return (
		<div className="isudev-admin__extensions">
			<p className="isudev-admin__note">
				{__(
					'Extensions change the admin or the front end as soon as they are enabled. A change takes effect on the next page load.',
					'isudev-library'
				)}
			</p>

			<div className="isudev-admin__summary">
				<p className="isudev-admin__summary-count">
					{sprintf(
						/* translators: 1: number of enabled extensions, 2: total number of extensions. */
						__('%1$d of %2$d extensions enabled', 'isudev-library'),
						enabledCount,
						extensions.length
					)}
				</p>
			</div>

			{groups.map((group) => (
				<div className="isudev-admin__group" key={group.slug}>
					<div className="isudev-admin__group-header">
						<span>{group.label}</span>
					</div>
					{group.extensions.map((extension) => (
						<ExtensionCard
							key={extension.slug}
							extension={extension}
							onToggle={toggle}
						/>
					))}
				</div>
			))}
		</div>
	);
}
