<?php
/**
 * Render helpers for the agenda accordion wrapper block.
 *
 * Pure functions only — no get_block_config(). render.php gathers that
 * context and passes it in.
 *
 * @package IsuDevLibrary\Blocks\AgendaAccordion
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\AgendaAccordion;

defined( 'ABSPATH' ) || exit;

/**
 * Build the wrapper's `data-*` behaviour attributes. Pure.
 *
 * `view.js` reads these to decide whether more than one item may stay open
 * at once, and whether an open item may be closed again.
 *
 * @param bool $allow_multiple Whether more than one item may be open at once.
 * @param bool $allow_toggle   Whether an open item may be closed again. Only
 *                              meaningful when `$allow_multiple` is false —
 *                              with multiple open items, toggling closed is
 *                              always possible.
 * @return array<string,string> Attribute name => 'true', present only when true.
 */
function behavior_attributes( bool $allow_multiple, bool $allow_toggle ): array {
	$attributes = array();

	if ( $allow_multiple ) {
		$attributes['data-allow-multiple'] = 'true';
	}

	if ( $allow_multiple || $allow_toggle ) {
		$attributes['data-allow-toggle'] = 'true';
	}

	return $attributes;
}
