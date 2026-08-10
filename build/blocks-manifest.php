<?php
// This file is generated. Do not modify it manually.
return array(
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
	)
);
