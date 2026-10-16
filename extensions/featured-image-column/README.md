# Featured image column

Adds a thumbnail column at the start of list screens, right after the
checkbox: the featured image of each post, and — when you tell it where to
look — the profile image of each user.

## Where it appears

- **Post list screens** (Posts, Pages, Products, any custom post type) whose
  post type has an admin UI **and** supports featured images
  (`'supports' => array( 'thumbnail' )`). A post type without that support never
  gets the column.
- **Users screen**, only when a user meta key is configured (see `users.metaKey`
  below). WordPress has no profile image of its own — the avatar next to the
  username comes from Gravatar — so the column needs to know which meta field a
  theme or plugin stores the image in.

The header is an image icon with a screen-reader label. A row without an image
shows an em dash. The thumbnail links to the post's edit screen for users who
may edit it; it is decorative (empty `alt`) and skipped by the keyboard, because
the title next to it is the real link.

## Sizes from `isudev.json`

Every screen uses a display box: `aspectRatio` (`"auto"` or `"W/H"`), `maxHeight`
and `width` in pixels (clamped to 16–400). The image fills the box with
`object-fit: cover`. Defaults: `4/3`, 48 px high, 64 px wide.

```json
{
	"library": {
		"extensions": {
			"featured-image-column": {
				"enabled": true,
				"default": { "aspectRatio": "4/3", "maxHeight": 48, "width": 64 },
				"postTypes": {
					"product": { "aspectRatio": "1/1", "maxHeight": 56, "width": 56 },
					"page": false
				},
				"users": { "metaKey": "profile_image", "aspectRatio": "1/1", "width": 40 }
			}
		}
	}
}
```

- `default` applies to every screen.
- `postTypes.<type>` overrides it for one post type; `false` removes the column
  from that post type.
- `users` overrides it for the Users screen; `metaKey` names the user meta field
  holding an **attachment ID or an image URL**. Without `metaKey` there is no
  user column.

An invalid value (a typo, `0/1`, anything that is not a ratio) is ignored and the
default stays.

## Hooks

### `isudev_library/extensions/featured_image_column/post_types`

The post types that get the column.

```php
add_filter(
	'isudev_library/extensions/featured_image_column/post_types',
	fn( array $post_types ): array => array_diff( $post_types, array( 'attachment' ) )
);
```

### `isudev_library/extensions/featured_image_column/box`

The display box for one screen, after `isudev.json`. `$screen` is a post type
slug or `users`.

```php
add_filter(
	'isudev_library/extensions/featured_image_column/box',
	function ( array $box, string $screen ): array {
		if ( 'team-member' === $screen ) {
			$box['aspectRatio'] = '3/4';
			$box['maxHeight']   = 64;
		}
		return $box;
	},
	10,
	2
);
```

### `isudev_library/extensions/featured_image_column/image_size`

The registered image size requested. Default `thumbnail`; the browser picks from
`srcset`.

```php
add_filter( 'isudev_library/extensions/featured_image_column/image_size', fn() => 'medium' );
```

### `isudev_library/extensions/featured_image_column/user_meta_key`

The user meta key, when you prefer code over `isudev.json`. Return `''` to remove
the user column.

```php
add_filter( 'isudev_library/extensions/featured_image_column/user_meta_key', fn() => 'avatar_id' );
```

### `isudev_library/extensions/featured_image_column/user_image`

The image of one user: an attachment ID, an image URL, or `''` for none. Use it
when the image is not a plain meta value — an ACF field, a plugin's array.

```php
add_filter(
	'isudev_library/extensions/featured_image_column/user_image',
	function ( $value, int $user_id ) {
		$avatar = get_user_meta( $user_id, 'simple_local_avatar', true );
		return is_array( $avatar ) ? (int) ( $avatar['media_id'] ?? 0 ) : $value;
	},
	10,
	2
);
```

## When it shows nothing

- **No column on a post type:** the post type does not support `thumbnail`, it
  is set to `false` under `postTypes`, or the `post_types` filter removed it.
  Screen Options can also hide the column per user.
- **Column but only dashes:** those posts have no featured image, or the
  attachment was deleted.
- **No column on Users:** no `users.metaKey` and no `user_meta_key` filter.
- **Users column with dashes:** the meta field is empty, or holds something
  other than an attachment ID or an `http(s)` URL — use the `user_image` filter.
