# Disable emoji images

On every page, WordPress prints an inline script that tests whether the
browser can draw the newest emoji and, if not, swaps each emoji for an image
from `s.w.org`. Every current browser and operating system draws emoji
itself, so on most visits the script only costs bytes and a main-thread
task. This extension removes all of it.

## What it changes

| Where | Removed core hook |
| --- | --- |
| Site `<head>` | `print_emoji_detection_script` on `wp_head` (7); `wp_enqueue_emoji_styles` on `wp_enqueue_scripts` (the `wp-emoji-styles` inline style); legacy `print_emoji_styles` on `wp_print_styles` |
| Embeds (`/embed/`) | `print_emoji_detection_script` on `embed_head`; `wp_enqueue_emoji_styles` on `enqueue_embed_scripts` |
| Admin | `print_emoji_detection_script` on `admin_print_scripts`; `wp_enqueue_emoji_styles` on `admin_enqueue_scripts`; `print_emoji_styles` on `admin_print_styles` |
| Feeds | `wp_staticize_emoji` on `the_content_feed` and `comment_text_rss` |
| E-mail | `wp_staticize_emoji_for_email` on `wp_mail` |
| Classic editor | the `wpemoji` TinyMCE plugin (`tiny_mce_plugins`) |

Front-end hooks are removed when the extension boots (`init` 0). The admin
ones are added by `wp-admin/includes/admin-filters.php`, which core loads
*after* `init`, so they are removed on `admin_init`.

Emoji are still emoji: the characters stay in the content and the visitor's
system font draws them. Feeds and e-mails carry the characters instead of
`<img>` tags — every modern mail and feed client shows them.

## What it does not do

- There is no `s.w.org` DNS prefetch to remove: core 7.x no longer adds one
  in `wp_resource_hints()`, and the emoji CDN URL (`emoji_svg_url`) is only
  read by the detection script removed above.
- Emoji typed into content are not converted or stripped.
- A very old browser without colour-emoji support shows a blank box instead
  of the image fallback.

No filters and no `isudev.json` settings.

## What it relies on

Core function names `print_emoji_detection_script`, `wp_enqueue_emoji_styles`,
`print_emoji_styles`, `wp_staticize_emoji`, `wp_staticize_emoji_for_email`
and their hooks in `wp-includes/default-filters.php` and
`wp-admin/includes/admin-filters.php`. `remove_action()` must name the same
priority core used — `wp_head` is the only one not at 10. If emoji come back
after a core update, compare these two files with the table above.

## How to check it still works

View the source of any page and of `/wp-admin/`: no
`<script id="wp-emoji-settings">`, no `wp-emoji-loader` module, no
`wp-emoji-styles-inline-css` and no `img.wp-smiley` rule.
