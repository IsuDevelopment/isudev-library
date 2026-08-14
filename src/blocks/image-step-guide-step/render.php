<?php
/**
 * Server render for isudev/image-step-guide-step.
 *
 * Config keys read here are all under the parent's block name,
 * `isudev/image-step-guide`, not this block's own name — one feature, one
 * key, matching the isudev/social-share-network convention.
 *
 * @package IsuDevLibrary\Blocks\ImageStepGuideStep
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the step's own content).
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\Blocks\ImageStepGuideStep;

use function IsuDevLibrary\Config\get_block_config;

defined( 'ABSPATH' ) || exit;

$parent_block_name = 'isudev/image-step-guide';

$namespace = isset( $block->context['isudev/imageStepGuideNamespace'] ) && \is_string( $block->context['isudev/imageStepGuideNamespace'] )
	? $block->context['isudev/imageStepGuideNamespace']
	: '';

$media  = isset( $attributes['media'] ) && \is_array( $attributes['media'] ) ? $attributes['media'] : array();
$picked = pick_image( $media );

$rendered = render_image( $picked );

$label_disabled     = (bool) get_block_config( $parent_block_name, 'stepLabel.disable', false, $namespace );
$configured_prefix  = (string) get_block_config( $parent_block_name, 'stepLabel.prefix', '', $namespace );
$configured_visible = (bool) get_block_config( $parent_block_name, 'stepLabel.visible', true, $namespace );

$own_prefix   = isset( $attributes['labelPrefix'] ) && \is_string( $attributes['labelPrefix'] ) ? $attributes['labelPrefix'] : '';
$label_hidden = ! empty( $attributes['labelHidden'] );

$prefix = resolve_label_prefix( $own_prefix, $configured_prefix );
// translators: Default prefix for the step label, e.g. "Step" → "Step 1" (the number is added by a CSS counter).
$prefix = '' !== $prefix ? $prefix : \_x( 'Step', 'step label prefix', 'isudev-library' );

$show_label = ! $label_disabled && $configured_visible && ! $label_hidden;

$label_html = $show_label
	? \sprintf( '<span class="isudev-image-step-guide-step__label">%s</span>', \esc_html( $prefix ) )
	: '';

$wrapper_attributes = \get_block_wrapper_attributes(
	array( 'class' => 'isudev-image-step-guide-step' )
);

$markup = \sprintf(
	'<div %1$s><div class="isudev-image-step-guide-step__media">%2$s</div><div class="isudev-image-step-guide-step__content">%3$s<div class="isudev-image-step-guide-step__content-inner">%4$s</div></div></div>',
	$wrapper_attributes,
	$rendered['html'],
	$label_html,
	$content
);

/*
 * $wrapper_attributes escapes its own output; $rendered['html'] is built from
 * esc_url()/esc_attr() or wp_get_attachment_image(); $label_html escapes with
 * esc_html(); $content is inner-block markup, already rendered safely.
 */
echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each component is escaped at its source; see the comment above.
