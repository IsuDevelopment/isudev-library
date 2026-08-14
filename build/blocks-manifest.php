<?php
// This file is generated. Do not modify it manually.
return array(
	'agenda-accordion' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/agenda-accordion',
		'version' => '1.0.0',
		'title' => 'Agenda Accordion',
		'category' => 'design',
		'icon' => 'list-view',
		'description' => 'A container for accordion items: any number of isudev/agenda-accordion-item children. By IsuDev.',
		'textdomain' => 'isudev-library',
		'attributes' => array(
			'_namespace' => array(
				'type' => 'string',
				'default' => '',
				'description' => 'Variation identifier injected by IsuDevLibrary\\Variations. Must be declared here or isActive matching never resolves.'
			)
		),
		'providesContext' => array(
			'isudev/agendaAccordionNamespace' => '_namespace'
		),
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'blockGap' => true,
				'margin' => true,
				'padding' => true
			)
		),
		'selectors' => array(
			'root' => '.isudev-agenda-accordion',
			'spacing' => array(
				'padding' => '.isudev-agenda-accordion button.isudev-agenda-accordion__trigger, .isudev-agenda-accordion-item__inner-container'
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'agenda-accordion-item' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/agenda-accordion-item',
		'version' => '1.0.0',
		'title' => 'Agenda Accordion Item',
		'parent' => array(
			'isudev/agenda-accordion'
		),
		'category' => 'design',
		'icon' => 'list-view',
		'description' => 'A single accordion item: a trigger with a title, optional date and excerpt, and collapsible content. Lives only inside isudev/agenda-accordion. By IsuDev.',
		'textdomain' => 'isudev-library',
		'attributes' => array(
			'agendaDate' => array(
				'type' => 'string',
				'default' => ''
			),
			'title' => array(
				'type' => 'string',
				'default' => ''
			),
			'excerpt' => array(
				'type' => 'string',
				'default' => ''
			),
			'level' => array(
				'type' => 'number',
				'default' => 3
			),
			'allowTagsInTitle' => array(
				'type' => 'boolean',
				'default' => false
			),
			'defaultOpen' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'usesContext' => array(
			'isudev/agendaAccordionNamespace'
		),
		'supports' => array(
			'html' => false,
			'anchor' => true
		),
		'editorScript' => 'file:./index.js',
		'render' => 'file:./render.php'
	),
	'bento-card' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/bento-card',
		'version' => '1.0.0',
		'title' => 'Bento Card',
		'parent' => array(
			'isudev/bento-grid'
		),
		'category' => 'design',
		'icon' => 'screenoptions',
		'description' => 'A single card within a Bento Grid, spanning a configurable number of columns and rows per breakpoint. By IsuDev.',
		'keywords' => array(
			'bento',
			'card',
			'grid'
		),
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'background' => array(
				'backgroundImage' => true,
				'backgroundSize' => true
			),
			'color' => array(
				'background' => true,
				'text' => true,
				'gradients' => true
			),
			'spacing' => array(
				'padding' => true
			),
			'dimensions' => array(
				'minHeight' => true
			)
		),
		'attributes' => array(
			'_namespace' => array(
				'type' => 'string',
				'default' => '',
				'description' => 'Layout identifier set by the grid\'s variation picker — scopes isudev.json template and allowedBlocks lookups.'
			),
			'gridColumn' => array(
				'type' => 'object',
				'default' => array(
					'desktop' => array(
						'start' => 'auto',
						'span' => 2
					),
					'mobile' => array(
						'start' => 'auto',
						'span' => 1
					)
				)
			),
			'gridRow' => array(
				'type' => 'object',
				'default' => array(
					'desktop' => array(
						'start' => 'auto',
						'span' => 2
					),
					'mobile' => array(
						'start' => 'auto',
						'span' => 1
					)
				)
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'render' => 'file:./render.php'
	),
	'bento-grid' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/bento-grid',
		'version' => '1.0.0',
		'title' => 'Bento Grid',
		'category' => 'design',
		'icon' => 'grid-view',
		'description' => 'A responsive bento grid layout: any number of isudev/bento-card children, each spanning a configurable number of columns and rows. By IsuDev.',
		'keywords' => array(
			'bento',
			'grid',
			'layout'
		),
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'attributes' => array(
			'columns' => array(
				'type' => 'object',
				'default' => array(
					'desktop' => 6,
					'tablet' => 4,
					'mobile' => 2
				)
			),
			'gap' => array(
				'type' => 'object',
				'default' => array(
					'desktop' => '16',
					'tablet' => '12',
					'mobile' => '8'
				)
			),
			'minCardHeight' => array(
				'type' => 'number',
				'default' => 150
			),
			'hasChosenVariation' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'allowedBlocks' => array(
			'isudev/bento-card'
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'render' => 'file:./render.php'
	),
	'google-reviews' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/google-reviews',
		'version' => '1.0.0',
		'title' => 'Google Reviews',
		'category' => 'widgets',
		'icon' => 'star-filled',
		'description' => 'Displays reviews stored by the WP Google Review Slider plugin, as a card grid or a carousel. By IsuDev.',
		'keywords' => array(
			'reviews',
			'google',
			'testimonials'
		),
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'attributes' => array(
			'reviewCount' => array(
				'type' => 'integer',
				'default' => 20
			),
			'reviewTextLength' => array(
				'type' => 'integer',
				'default' => 220
			),
			'enableSlider' => array(
				'type' => 'boolean',
				'default' => true
			),
			'sliderMode' => array(
				'type' => 'string',
				'enum' => array(
					'continuous',
					'classic'
				),
				'default' => 'continuous'
			),
			'accountId' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'viewScript' => 'file:./view.js',
		'viewStyle' => 'file:./view.css',
		'render' => 'file:./render.php'
	),
	'google-reviews-badge' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/google-reviews-badge',
		'version' => '1.0.0',
		'title' => 'Google Reviews Badge',
		'category' => 'widgets',
		'icon' => 'star-filled',
		'description' => 'Displays a compact rating badge for a Google account, sourced from the WP Google Review Slider plugin. By IsuDev.',
		'keywords' => array(
			'reviews',
			'google',
			'rating',
			'badge'
		),
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'anchor' => true,
			'color' => array(
				'background' => true,
				'gradients' => false,
				'link' => false,
				'text' => false
			),
			'border' => array(
				'color' => true,
				'radius' => false,
				'style' => false,
				'width' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => false
			)
		),
		'attributes' => array(
			'accountId' => array(
				'type' => 'string',
				'default' => ''
			),
			'useLightText' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'render' => 'file:./render.php'
	),
	'google-reviews-header' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/google-reviews-header',
		'version' => '1.0.0',
		'title' => 'Google Reviews Header',
		'category' => 'widgets',
		'icon' => 'star-filled',
		'description' => 'Displays a Google account\'s rating, logo and a review button, sourced from the WP Google Review Slider plugin. By IsuDev.',
		'keywords' => array(
			'reviews',
			'google',
			'rating'
		),
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'attributes' => array(
			'accountId' => array(
				'type' => 'string',
				'default' => ''
			),
			'buttonText' => array(
				'type' => 'string',
				'default' => 'Rate us'
			),
			'header' => array(
				'type' => 'string',
				'default' => ''
			),
			'description' => array(
				'type' => 'string',
				'default' => ''
			),
			'logo' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'logoMaxHeight' => array(
				'type' => 'integer',
				'default' => 64
			),
			'hidePoweredByGoogle' => array(
				'type' => 'boolean',
				'default' => false
			),
			'renderAsHeading' => array(
				'type' => 'boolean',
				'default' => true
			),
			'headingLevel' => array(
				'type' => 'integer',
				'default' => 2
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'render' => 'file:./render.php'
	),
	'image-step-guide' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/image-step-guide',
		'version' => '1.0.0',
		'title' => 'Image Step Guide',
		'category' => 'widgets',
		'icon' => 'images-alt2',
		'description' => 'A container for step-by-step content: any number of isudev/image-step-guide-step children, each an image alongside editable inner blocks. By IsuDev.',
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'spacing' => array(
				'margin' => true,
				'padding' => true,
				'blockGap' => array(
					'sides' => array(
						'horizontal',
						'vertical'
					)
				)
			)
		),
		'attributes' => array(
			'_namespace' => array(
				'type' => 'string',
				'default' => '',
				'description' => 'Variation identifier injected by IsuDevLibrary\\Variations. Must be declared here or isActive matching never resolves.'
			),
			'displayStyle' => array(
				'type' => 'string',
				'enum' => array(
					'list',
					'grid'
				),
				'default' => 'list'
			),
			'useImageLightbox' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'providesContext' => array(
			'isudev/imageStepGuideNamespace' => '_namespace'
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'image-step-guide-step' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/image-step-guide-step',
		'version' => '1.0.0',
		'title' => 'Step',
		'parent' => array(
			'isudev/image-step-guide'
		),
		'category' => 'widgets',
		'icon' => 'cover-image',
		'description' => 'A single step: an image alongside editable inner blocks. Lives only inside isudev/image-step-guide. By IsuDev.',
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'anchor' => true,
			'color' => array(
				'background' => true,
				'text' => true
			),
			'border' => array(
				'color' => true,
				'width' => true,
				'style' => true
			)
		),
		'attributes' => array(
			'media' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'labelPrefix' => array(
				'type' => 'string',
				'default' => ''
			),
			'labelHidden' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'usesContext' => array(
			'isudev/imageStepGuideNamespace'
		),
		'editorScript' => 'file:./index.js',
		'render' => 'file:./render.php'
	),
	'read-more' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/read-more',
		'version' => '1.0.0',
		'title' => 'Read More Block',
		'category' => 'design',
		'icon' => 'arrow-right-alt',
		'description' => 'A linked card: title, optional image, optional badge and supporting text. Points anywhere — a post, a page or an external URL. By IsuDev.',
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'color' => array(
				'text' => true,
				'background' => true
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'style' => true,
					'width' => true
				)
			)
		),
		'attributes' => array(
			'_namespace' => array(
				'type' => 'string',
				'default' => '',
				'description' => 'Variation identifier injected by IsuDevLibrary\\Variations. Must be declared here or isActive matching never resolves.'
			),
			'link' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'media' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'hasCustomTitle' => array(
				'type' => 'boolean',
				'default' => false
			),
			'customTitle' => array(
				'type' => 'string',
				'default' => ''
			),
			'showAdditionalText' => array(
				'type' => 'boolean',
				'default' => false
			),
			'additionalText' => array(
				'type' => 'string',
				'default' => ''
			),
			'renderAsHeading' => array(
				'type' => 'boolean',
				'default' => true
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 3
			),
			'readMoreText' => array(
				'type' => 'string',
				'default' => ''
			),
			'showReadMoreBadge' => array(
				'type' => 'boolean',
				'default' => false
			),
			'showFeaturedImage' => array(
				'type' => 'boolean',
				'default' => true
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'render' => 'file:./render.php'
	),
	'selling-point' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/selling-point',
		'version' => '1.0.0',
		'title' => 'Selling Point',
		'parent' => array(
			'isudev/selling-points'
		),
		'category' => 'design',
		'icon' => 'index-card',
		'description' => 'A single selling point: an optional icon, image, badge, title, description and link. Lives only inside isudev/selling-points. By IsuDev.',
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'anchor' => true,
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true
			)
		),
		'attributes' => array(
			'icon' => array(
				'type' => 'string',
				'default' => ''
			),
			'showIcon' => array(
				'type' => 'boolean',
				'default' => true
			),
			'media' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'itemWidth' => array(
				'type' => 'string',
				'default' => ''
			),
			'title' => array(
				'type' => 'string',
				'default' => ''
			),
			'badgeText' => array(
				'type' => 'string',
				'default' => ''
			),
			'showBadge' => array(
				'type' => 'boolean',
				'default' => false
			),
			'description' => array(
				'type' => 'string',
				'default' => ''
			),
			'link' => array(
				'type' => 'object',
				'default' => array(
					
				)
			),
			'showLinkText' => array(
				'type' => 'boolean',
				'default' => false
			),
			'linkText' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'usesContext' => array(
			'isudev/sellingPointsHeadingLevel',
			'isudev/sellingPointsIconSize',
			'isudev/sellingPointsRenderTitlesAsHeadings',
			'isudev/sellingPointsNamespace'
		),
		'editorScript' => 'file:./index.js',
		'render' => 'file:./render.php'
	),
	'selling-points' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/selling-points',
		'version' => '1.0.0',
		'title' => 'Selling Points',
		'category' => 'design',
		'icon' => 'grid-view',
		'description' => 'A container for selling points: any number of isudev/selling-point children in a responsive grid. By IsuDev.',
		'textdomain' => 'isudev-library',
		'attributes' => array(
			'_namespace' => array(
				'type' => 'string',
				'default' => '',
				'description' => 'Variation identifier injected by IsuDevLibrary\\Variations. Must be declared here or isActive matching never resolves.'
			),
			'headingLevel' => array(
				'type' => 'number',
				'default' => 2
			),
			'iconSize' => array(
				'type' => 'number',
				'default' => 28
			),
			'animateIn' => array(
				'type' => 'boolean',
				'default' => false
			),
			'renderTitlesAsHeadings' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'providesContext' => array(
			'isudev/sellingPointsHeadingLevel' => 'headingLevel',
			'isudev/sellingPointsIconSize' => 'iconSize',
			'isudev/sellingPointsRenderTitlesAsHeadings' => 'renderTitlesAsHeadings',
			'isudev/sellingPointsNamespace' => '_namespace'
		),
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true,
			'color' => array(
				'background' => true,
				'gradients' => true,
				'text' => true,
				'heading' => true,
				'link' => true,
				'button' => false
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true
			)
		),
		'selectors' => array(
			'color' => array(
				'heading' => '.isudev-selling-point__title'
			)
		),
		'example' => array(
			'innerBlocks' => array(
				array(
					'name' => 'isudev/selling-point',
					'attributes' => array(
						'title' => 'First point',
						'description' => 'Did you know?'
					)
				),
				array(
					'name' => 'isudev/selling-point',
					'attributes' => array(
						'title' => 'Second point',
						'description' => 'Here is another fact'
					)
				),
				array(
					'name' => 'isudev/selling-point',
					'attributes' => array(
						'title' => 'Third point',
						'description' => 'And one more'
					)
				)
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'site-header' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/site-header',
		'version' => '1.0.0',
		'title' => 'Site Header Block',
		'category' => 'layout',
		'icon' => 'menu-alt',
		'description' => 'An accessibility-first site header: logo, disclosure navigation, mobile drawer, and an actions slot. By IsuDev.',
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'multiple' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'_namespace' => array(
				'type' => 'string',
				'default' => '',
				'description' => 'Variation identifier injected by IsuDevLibrary\\Variations. Must be declared here or isActive matching never resolves.'
			),
			'menuRef' => array(
				'type' => 'string',
				'default' => ''
			),
			'logoSource' => array(
				'type' => 'string',
				'default' => 'site'
			),
			'logoUrl' => array(
				'type' => 'string',
				'default' => ''
			),
			'sticky' => array(
				'type' => 'boolean',
				'default' => true
			),
			'whiteHeaderBodyClass' => array(
				'type' => 'string',
				'default' => 'is-white-header'
			),
			'ariaLabel' => array(
				'type' => 'string',
				'default' => 'Main'
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'editorStyle' => 'file:./index.css',
		'render' => 'file:./render.php'
	),
	'social-share' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/social-share',
		'version' => '1.0.0',
		'title' => 'Social Share',
		'category' => 'widgets',
		'icon' => 'share',
		'description' => 'A container for social share buttons: an optional prefix and any number of isudev/social-share-network children. By IsuDev.',
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'align' => array(
				'left',
				'center',
				'right',
				'wide',
				'full'
			)
		),
		'attributes' => array(
			'_namespace' => array(
				'type' => 'string',
				'default' => '',
				'description' => 'Variation identifier injected by IsuDevLibrary\\Variations. Must be declared here or isActive matching never resolves.'
			),
			'contentAlignment' => array(
				'type' => 'string',
				'default' => 'none'
			),
			'prefixText' => array(
				'type' => 'string',
				'default' => ''
			),
			'showPrefix' => array(
				'type' => 'boolean',
				'default' => false
			),
			'prefixPosition' => array(
				'type' => 'string',
				'default' => 'before'
			)
		),
		'providesContext' => array(
			'isudev/socialShareNamespace' => '_namespace'
		),
		'example' => array(
			'attributes' => array(
				'showPrefix' => true,
				'prefixText' => 'Share:',
				'prefixPosition' => 'before'
			),
			'innerBlocks' => array(
				array(
					'name' => 'isudev/social-share-network',
					'attributes' => array(
						'network' => 'facebook'
					)
				),
				array(
					'name' => 'isudev/social-share-network',
					'attributes' => array(
						'network' => 'x'
					)
				),
				array(
					'name' => 'isudev/social-share-network',
					'attributes' => array(
						'network' => 'linkedin'
					)
				),
				array(
					'name' => 'isudev/social-share-network',
					'attributes' => array(
						'network' => 'link'
					)
				)
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'social-share-network' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/social-share-network',
		'version' => '1.0.0',
		'title' => 'Social Share Network',
		'parent' => array(
			'isudev/social-share'
		),
		'category' => 'widgets',
		'icon' => 'share',
		'description' => 'A single social network share button. By IsuDev.',
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'align' => false,
			'reusable' => false
		),
		'attributes' => array(
			'network' => array(
				'type' => 'string',
				'enum' => array(
					'facebook',
					'x',
					'linkedin',
					'whatsapp',
					'bluesky',
					'threads',
					'mastodon',
					'substack',
					'link',
					'email',
					'print',
					'system'
				),
				'default' => 'facebook'
			),
			'label' => array(
				'type' => 'string',
				'default' => ''
			),
			'showLabel' => array(
				'type' => 'boolean',
				'default' => false
			),
			'labelPosition' => array(
				'type' => 'string',
				'enum' => array(
					'before',
					'after'
				),
				'default' => 'after'
			)
		),
		'usesContext' => array(
			'isudev/socialShareNamespace'
		),
		'example' => array(
			
		),
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'editorStyle' => 'file:./index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	),
	'toggle-blocks' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'isudev/toggle-blocks',
		'version' => '1.0.0',
		'title' => 'Toggle Content',
		'category' => 'design',
		'icon' => 'arrow-down-alt2',
		'description' => 'A collapsible toggle: a button that shows or hides any inner blocks. By IsuDev.',
		'textdomain' => 'isudev-library',
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'anchor' => true
		),
		'attributes' => array(
			'_namespace' => array(
				'type' => 'string',
				'default' => '',
				'description' => 'Variation identifier injected by IsuDevLibrary\\Variations. Must be declared here or isActive matching never resolves.'
			),
			'openText' => array(
				'type' => 'string',
				'default' => 'Open content'
			),
			'closeText' => array(
				'type' => 'string',
				'default' => 'Close content'
			),
			'openByDefault' => array(
				'type' => 'boolean',
				'default' => false
			),
			'scrollToContent' => array(
				'type' => 'boolean',
				'default' => false
			),
			'toggleSpeed' => array(
				'type' => 'number',
				'default' => 150
			),
			'togglePlacement' => array(
				'type' => 'string',
				'default' => 'bottom'
			),
			'buttonStyle' => array(
				'type' => 'string',
				'default' => ''
			)
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'wp-block-button',
			'file:./style-index.css'
		),
		'editorStyle' => 'file:./index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	)
);
