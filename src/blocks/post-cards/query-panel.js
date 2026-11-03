/**
 * WordPress dependencies
 */
import {
	FormTokenField,
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

const EXCLUDED_TYPES = ['attachment', 'wp_block', 'wp_navigation'];

function TermsControl({ taxonomy, label, value, onChange }) {
	const [search, setSearch] = useState('');
	const valueKey = value.join(',');

	const { selected, suggestions } = useSelect(
		(select) => {
			const { getEntityRecords } = select(coreStore);
			return {
				selected: valueKey
					? (getEntityRecords('taxonomy', taxonomy, {
							include: valueKey.split(',').map(Number),
							per_page: 100,
						}) ?? [])
					: [],
				suggestions:
					getEntityRecords('taxonomy', taxonomy, {
						search,
						per_page: 20,
					}) ?? [],
			};
		},
		[taxonomy, valueKey, search]
	);

	const byName = new Map(
		[...selected, ...suggestions].map((term) => [
			decodeEntities(term.name),
			term.id,
		])
	);

	return (
		<FormTokenField
			label={label}
			value={selected.map((term) => decodeEntities(term.name))}
			suggestions={suggestions.map((term) => decodeEntities(term.name))}
			onInputChange={setSearch}
			onChange={(names) =>
				onChange(
					names
						.map((name) => byName.get(name))
						.filter((id) => Number.isInteger(id))
				)
			}
			__experimentalShowHowTo={false}
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}

/**
 * Query settings of `isudev/post-cards`, used by its query slots.
 *
 * @param {Object}   props
 * @param {Object}   props.query    `query` attribute.
 * @param {Function} props.onChange Receives changed keys.
 * @return {Element} Panel.
 */
export default function QueryPanel({ query, onChange }) {
	const postType = query.postType || 'post';

	const { postTypes, taxonomies } = useSelect(
		(select) => {
			const { getPostTypes, getTaxonomies } = select(coreStore);
			return {
				postTypes: (getPostTypes({ per_page: -1 }) ?? []).filter(
					(type) =>
						type.viewable && !EXCLUDED_TYPES.includes(type.slug)
				),
				taxonomies: (
					getTaxonomies({ type: postType, per_page: -1 }) ?? []
				).filter((taxonomy) => taxonomy.visibility?.publicly_queryable),
			};
		},
		[postType]
	);

	const taxonomy = taxonomies.find((item) => item.slug === query.taxonomy);

	return (
		<PanelBody
			title={__('Query slots', 'isudev-library')}
			initialOpen={false}
		>
			<p className="isudev-post-cards__query-help">
				{__(
					'Cards set to "Next post from the query" are filled from these settings, in order, skipping hand-picked posts.',
					'isudev-library'
				)}
			</p>
			<SelectControl
				label={__('Post type', 'isudev-library')}
				value={postType}
				options={postTypes.map((type) => ({
					value: type.slug,
					label: type.labels?.singular_name || type.name,
				}))}
				onChange={(next) =>
					onChange({ postType: next, taxonomy: '', terms: [] })
				}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			{taxonomies.length > 0 && (
				<SelectControl
					label={__('Filter by', 'isudev-library')}
					value={query.taxonomy || ''}
					options={[
						{ value: '', label: __('No filter', 'isudev-library') },
						...taxonomies.map((item) => ({
							value: item.slug,
							label: item.name,
						})),
					]}
					onChange={(next) => onChange({ taxonomy: next, terms: [] })}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			)}
			{taxonomy && (
				<TermsControl
					taxonomy={taxonomy.slug}
					label={taxonomy.name}
					value={query.terms ?? []}
					onChange={(terms) => onChange({ terms })}
				/>
			)}
			<SelectControl
				label={__('Order by', 'isudev-library')}
				value={`${query.orderBy || 'date'}/${query.order || 'desc'}`}
				options={[
					{
						value: 'date/desc',
						label: __('Newest first', 'isudev-library'),
					},
					{
						value: 'date/asc',
						label: __('Oldest first', 'isudev-library'),
					},
					{
						value: 'title/asc',
						label: __('Title A → Z', 'isudev-library'),
					},
					{
						value: 'title/desc',
						label: __('Title Z → A', 'isudev-library'),
					},
					{
						value: 'modified/desc',
						label: __('Recently updated', 'isudev-library'),
					},
					{
						value: 'menu_order/asc',
						label: __('Page order', 'isudev-library'),
					},
					{
						value: 'rand/desc',
						label: __('Random', 'isudev-library'),
					},
				]}
				onChange={(next) => {
					const [orderBy, order] = next.split('/');
					onChange({ orderBy, order });
				}}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			<TextControl
				type="number"
				min={0}
				label={__('Skip first posts', 'isudev-library')}
				value={query.offset ?? 0}
				onChange={(next) =>
					onChange({ offset: Math.max(0, parseInt(next, 10) || 0) })
				}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			<ToggleControl
				label={__('Leave out the current post', 'isudev-library')}
				checked={query.excludeCurrent !== false}
				onChange={(next) => onChange({ excludeCurrent: next })}
				__nextHasNoMarginBottom
			/>
		</PanelBody>
	);
}
