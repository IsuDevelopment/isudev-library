<?php
// This file is generated. Do not modify it manually.
return array(
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
