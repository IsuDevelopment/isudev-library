<?php
/**
 * Checks for the pure parts of disable-feeds, disable-posts and redirect-authors.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/extensions/disable-feeds/hooks.php';
require_once dirname( __DIR__, 2 ) . '/extensions/disable-posts/hooks.php';
require_once dirname( __DIR__, 2 ) . '/extensions/redirect-authors/hooks.php';

use function IsuDevLibrary\Extensions\DisableFeeds\feed_type;
use function IsuDevLibrary\Extensions\DisableFeeds\without_feed;
use function IsuDevLibrary\Extensions\DisablePosts\targets_posts;
use function IsuDevLibrary\Extensions\DisablePosts\without_methods;
use function IsuDevLibrary\Extensions\RedirectAuthors\users_route_id;

$isudev_feed_types = array( 'rdf', 'rss', 'rss2', 'atom' );

// feed_type(): resolves like WP_Query + do_feed().
Checks::is( 'feeds: /feed/ is the default feed', feed_type( 'feed', 'rss2' ), 'rss2' );
Checks::is( 'feeds: empty is the default feed', feed_type( '', 'rss2' ), 'rss2' );
Checks::is( 'feeds: comments- prefix dropped', feed_type( 'comments-atom', 'rss2' ), 'atom' );
Checks::is( 'feeds: leading underscores dropped', feed_type( '__rdf', 'rss2' ), 'rdf' );
Checks::is( 'feeds: a plugin feed keeps its name', feed_type( 'podcast', 'rss2' ), 'podcast' );

// without_feed(): disabled feeds become a 404 request.
Checks::is(
	'feeds: a disabled feed becomes error=404 without feed vars',
	without_feed( array( 'feed' => 'feed', 'withcomments' => '1', 'category_name' => 'news' ), $isudev_feed_types, 'rss2' ),
	array( 'category_name' => 'news', 'error' => '404' )
);
Checks::is(
	'feeds: a feed outside the list passes',
	without_feed( array( 'feed' => 'podcast' ), $isudev_feed_types, 'rss2' ),
	array( 'feed' => 'podcast' )
);
Checks::is(
	'feeds: a non-feed request passes',
	without_feed( array( 'pagename' => 'about' ), $isudev_feed_types, 'rss2' ),
	array( 'pagename' => 'about' )
);
Checks::is(
	'feeds: a non-string feed var passes',
	without_feed( array( 'feed' => array( 'rss2' ) ), $isudev_feed_types, 'rss2' ),
	array( 'feed' => array( 'rss2' ) )
);

// targets_posts(): what the main query means by post type.
Checks::true( 'posts: empty post_type means posts', targets_posts( '' ) );
Checks::true( 'posts: null post_type means posts', targets_posts( null ) );
Checks::true( 'posts: post means posts', targets_posts( 'post' ) );
Checks::true( 'posts: [post] means posts', targets_posts( array( 'post' ) ) );
Checks::is( 'posts: page is not posts', targets_posts( 'page' ), false );
Checks::is( 'posts: any is not posts', targets_posts( 'any' ), false );
Checks::is( 'posts: [post, book] is not only posts', targets_posts( array( 'post', 'book' ) ), false );

// without_methods(): only the named keys go.
Checks::is(
	'posts: xmlrpc methods removed, others kept',
	without_methods(
		array(
			'wp.getPost'   => 'this:wp_getPost',
			'wp.getPages'  => 'this:wp_getPages',
			'pingback.ping' => 'this:pingback_ping',
		),
		array( 'wp.getPost', 'metaWeblog.newPost' )
	),
	array(
		'wp.getPages'   => 'this:wp_getPages',
		'pingback.ping' => 'this:pingback_ping',
	)
);

// users_route_id(): which REST routes read users.
Checks::is( 'authors: collection route', users_route_id( '/wp/v2/users' ), 0 );
Checks::is( 'authors: collection route, trailing slash', users_route_id( '/wp/v2/users/' ), 0 );
Checks::is( 'authors: single user route', users_route_id( '/wp/v2/users/12' ), 12 );
Checks::is( 'authors: /me is not matched', users_route_id( '/wp/v2/users/me' ), null );
Checks::is( 'authors: application passwords are not matched', users_route_id( '/wp/v2/users/12/application-passwords' ), null );
Checks::is( 'authors: other routes are not matched', users_route_id( '/wp/v2/pages' ), null );
