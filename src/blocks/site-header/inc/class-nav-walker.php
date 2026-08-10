<?php
/**
 * Header navigation walker — APG Disclosure Navigation markup.
 *
 * @package IsuDevLibrary\Blocks\SiteHeader
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\SiteHeader;

use function IsuDevLibrary\Utils\get_icon;

defined( 'ABSPATH' ) || exit;

/**
 * Walker producing accessible disclosure markup for the header menu.
 */
class Nav_Walker extends \Walker_Nav_Menu {

	/**
	 * Panel id of the current top-level parent (set in start_el, used in start/end_lvl).
	 *
	 * @var string
	 */
	private $current_panel_id = '';

	/**
	 * Flag whether the current element has children (set before start_el runs).
	 *
	 * @param object $element           Menu item.
	 * @param array  $children_elements Child elements keyed by parent db_id.
	 * @param int    $max_depth         Max depth.
	 * @param int    $depth             Current depth.
	 * @param array  $args              Args.
	 * @param string $output            Output by reference.
	 * @return void
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
		if ( $element ) {
			$element->has_children = ! empty( $children_elements[ $element->db_id ] );
		}
		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}

	/**
	 * Open a submenu level.
	 *
	 * @param string $output Output by reference.
	 * @param int    $depth  Depth.
	 * @param array  $args   Args.
	 * @return void
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( 0 === $depth && '' !== $this->current_panel_id ) {
			$output .= '<div class="isudev-nav__panel" id="' . \esc_attr( $this->current_panel_id ) . '">';
			$output .= '<ul class="isudev-nav__sublist">';
			return;
		}
		$output .= '<ul class="isudev-nav__sublist isudev-nav__sublist--nested">';
	}

	/**
	 * Close a submenu level.
	 *
	 * @param string $output Output by reference.
	 * @param int    $depth  Depth.
	 * @param array  $args   Args.
	 * @return void
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		if ( 0 === $depth && '' !== $this->current_panel_id ) {
			$output                .= '</ul></div>';
			$this->current_panel_id = '';
			return;
		}
		$output .= '</ul>';
	}

	/**
	 * Render a menu item.
	 *
	 * @param string $output Output by reference.
	 * @param object $item   Menu item.
	 * @param int    $depth  Depth.
	 * @param array  $args   Args.
	 * @param int    $id     ID.
	 * @return void
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$is_top       = ( 0 === $depth );
		$has_children = ! empty( $item->has_children );
		$has_panel    = $is_top && $has_children;

		// A parent is "navigable" only when it has a real destination. A blank
		// URL or a "#" placeholder means the item is a label that only opens the
		// dropdown.
		$url        = ! empty( $item->url ) ? $item->url : '';
		$has_target = ( '' !== $url && '#' !== $url );

		$atts = '';
		if ( ! empty( $item->target ) ) {
			$atts .= ' target="' . \esc_attr( $item->target ) . '"';
		}
		if ( ! empty( $item->xfn ) ) {
			$atts .= ' rel="' . \esc_attr( $item->xfn ) . '"';
		}

		// Native menu-item Description field (Screen Options), not custom meta.
		$description = isset( $item->description ) ? (string) $item->description : '';

		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'isudev-nav__item';
		if ( $has_panel ) {
			$classes[] = 'has-submenu';
			if ( $has_target ) {
				$classes[] = 'has-link';
			}
		}
		$class_str = \implode( ' ', \array_filter( \array_map( 'sanitize_html_class', $classes ) ) );

		$output .= '<li class="' . \esc_attr( $class_str ) . '">';

		// Re-apply core's the_title filter, exactly as Walker_Nav_Menu does.
		$title = \apply_filters( 'the_title', $item->title, $item->ID ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook, not plugin-defined.

		// Top-level item with a submenu panel.
		if ( $has_panel ) {
			$panel_id               = 'isudev-submenu-' . (int) $item->ID;
			$this->current_panel_id = $panel_id;
			$chevron                = get_icon(
				'chevronDown',
				array(
					'size'  => 20,
					'class' => 'isudev-nav__chevron-icon',
				)
			);

			if ( $has_target ) {
				// Navigable parent → a real link PLUS a separate disclosure
				// toggle. Never overload one element with both navigation and
				// toggling.
				$output .= '<a class="isudev-nav__link" href="' . \esc_url( $url ) . '"' . $atts . '>';
				$output .= '<span class="isudev-nav__label">' . \esc_html( $title ) . '</span>';
				$output .= '</a>';

				$output .= '<button type="button" class="isudev-nav__toggle isudev-nav__toggle--split" aria-expanded="false" aria-controls="' . \esc_attr( $panel_id ) . '">';
				$output .= '<span class="isudev-sr-only">';
				/* translators: %s: parent menu item label. */
				$output .= \sprintf( \esc_html__( 'Show submenu for %s', 'isudev-library' ), \esc_html( $title ) );
				$output .= '</span>';
				$output .= '<span class="isudev-nav__chevron" aria-hidden="true">' . $chevron . '</span>';
				$output .= '</button>';
			} else {
				// Label-only parent (no destination) → pure disclosure button.
				$output .= '<button type="button" class="isudev-nav__toggle" aria-expanded="false" aria-controls="' . \esc_attr( $panel_id ) . '">';
				$output .= '<span class="isudev-nav__label">' . \esc_html( $title ) . '</span>';
				$output .= '<span class="isudev-nav__chevron" aria-hidden="true">' . $chevron . '</span>';
				$output .= '</button>';
			}
			return;
		}

		// Plain link: top-level item without a submenu, or a submenu item.
		$link_class = $is_top ? 'isudev-nav__link' : 'isudev-nav__sublink';
		$href       = '' !== $url ? $url : '#';
		$output    .= '<a class="' . \esc_attr( $link_class ) . '" href="' . \esc_url( $href ) . '"' . $atts . '>';
		$output    .= '<span class="' . ( $is_top ? 'isudev-nav__label' : 'isudev-nav__subtitle' ) . '">' . \esc_html( $title ) . '</span>';
		if ( '' !== $description ) {
			$output .= '<span class="isudev-nav__subdesc">' . \esc_html( $description ) . '</span>';
		}
		$output .= '</a>';
	}

	/**
	 * Close a menu item.
	 *
	 * @param string $output Output by reference.
	 * @param object $item   Menu item.
	 * @param int    $depth  Depth.
	 * @param array  $args   Args.
	 * @return void
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}
