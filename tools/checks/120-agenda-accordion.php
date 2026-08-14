<?php
/**
 * Checks for the pure parts of the agenda accordion blocks.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__, 2 ) . '/src/blocks/agenda-accordion/inc/render-helpers.php';
require_once dirname( __DIR__, 2 ) . '/src/blocks/agenda-accordion-item/inc/render-helpers.php';

use function IsuDevLibrary\Blocks\AgendaAccordion\behavior_attributes;
use function IsuDevLibrary\Blocks\AgendaAccordionItem\heading_tag;
use function IsuDevLibrary\Blocks\AgendaAccordionItem\item_classes;

/*
 * behavior_attributes(): data-allow-multiple only when multiple is allowed;
 * data-allow-toggle whenever either flag is set, because multiple open items
 * are always individually toggleable.
 */
Checks::is( 'behavior_attributes: multiple implies toggle', behavior_attributes( true, false ), array(
	'data-allow-multiple' => 'true',
	'data-allow-toggle'   => 'true',
) );
Checks::is( 'behavior_attributes: single, toggle off', behavior_attributes( false, false ), array() );
Checks::is( 'behavior_attributes: single, toggle on', behavior_attributes( false, true ), array(
	'data-allow-toggle' => 'true',
) );

/*
 * heading_tag(): every valid level maps to itself; anything else falls back
 * to h3.
 */
foreach ( array( 1, 2, 3, 4, 5, 6 ) as $level ) {
	Checks::is( "heading_tag: level {$level}", heading_tag( $level ), 'h' . $level );
}
Checks::is( 'heading_tag: an invalid level falls back to h3', heading_tag( 9 ), 'h3' );
Checks::is( 'heading_tag: zero falls back to h3', heading_tag( 0 ), 'h3' );

/*
 * item_classes(): the base class is always present; has-no-content appears
 * only when the item has nothing to expand.
 */
Checks::is( 'item_classes: with content', item_classes( true ), array( 'isudev-agenda-accordion-item' ) );
Checks::is( 'item_classes: without content', item_classes( false ), array( 'isudev-agenda-accordion-item', 'has-no-content' ) );
