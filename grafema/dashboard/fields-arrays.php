<?php
[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ] = ( new Sanitizer(
	$args ?? [],
	[
		'name'        => 'name',
		'label'       => 'trim',
		'class'       => 'class:field',
		'label_class' => 'class:field-label',
		'reset'       => 'bool:false',
		'before'      => 'trim',
		'after'       => 'trim',
		'instruction' => 'trim',
		'tooltip'     => 'attribute',
		'copy'        => 'bool:false',
		'conditions'  => 'array',
		'attributes'  => 'array',
		// select, checkbox, radio
		'options'     => 'array',
		// progress
		'max'         => 'absint:0',
		'min'         => 'absint:0',
		'value'       => 'absint:100',
		'speed'       => 'absint:1000',
		// password
		'switcher'    => 'bool:true',
		'indicator'   => 'bool:true',
		'generator'   => 'bool:true',
		'characters'  => 'array',
		// details: dropdown with button
		'content'     => 'trim',
		// uploader
		'max_size'    => 'trim:' . ini_get( 'upload_max_filesize' ),
	]
) )->values();

// field array
[
	'type'        => 'textarea',
	'name'        => 'uid',
	'label'       => I18n::_t( 'Label' ),
	'class'       => '', // for checkboxes & radio: field--simple, field--image, field--described, field--buttons
	'label_class' => '',
	'reset'       => 0,
	'before'      => '',
	'after'       => '',
	'instruction' => '',
	'tooltip'     => '',
	'copy'        => 0,
	'validator'   => '',
	'conditions'  => [],
	'attributes'  => [
		'value'       => '',
		'placeholder' => '',
	],
	// password
	'switcher'    => 1,
	'generator'   => 0,
	'indicator'   => 0,
	'characters'  => [
		'lowercase' => 2,
		'uppercase' => 2,
		'special'   => 2,
		'length'    => 12,
		'digit'     => 2,
	],
	// progress
	'min'         => 0,
	'max'         => 100,
	'speed'       => 500,
	// details: dropdown with button
	'content'     => '',
	// simple select/checkbox/radio options & toggle with 2 options
	'options'     => [
		'one' => I18n::_t( '#1' ),
		'two' => I18n::_t( '#2' ),
	],
	// select/checkbox/radio options with additional data
	'options'     => [
		'one' => [
			'content'     => I18n::_t( '#1' ),
			'icon'        => 'ph ph-image-square',
			'image'       => 'path to image',
			'description' => '',
		],
		'two' => [
			'content'     => I18n::_t( '#2' ),
			'icon'        => 'ph ph-image-square',
			'image'       => 'path to image',
			'description' => '',
		],
	],
	// select options with optgroups
	'options'     => [
		'optgroup1' => [
			'label'   => I18n::_t( 'Optgroup Label' ),
			'options' => [
				'one' => [
					'content'     => I18n::_t( '#1' ),
					'icon'        => 'ph ph-image-square',
					'image'       => 'path to image',
					'description' => '',
				],
			],
		],
	],
];

// header
[
	'type'        => 'header',
	'name'        => 'title',
	'label'       => I18n::_t( 'Welcome to Grafema' ),
	'class'       => '',
	'instruction' => '',
];

// group
[
	'type'          => 'group',
	'name'          => 'manage',
	'label'         => I18n::_t( 'Welcome to Grafema' ),
	'class'         => '',
	'label_class'   => '',
	'content_class' => '',
	'fields'        => [],
];

// step
[
	'type'       => 'step',
	'name'       => 'manage',
	'step'       => 'tab',
	'content'    => '',
	'attributes' => [],
];

// tabs
//'class_menu'    => '', // css class for tab navigation menu
//'class_button'  => '', // css class for tab menu button
//'class_content' => '', // css class for tab content
[
	'type'          => 'tab',
	'label'         => I18n::_t( 'General' ),
	'name'          => 'general',
	'class_menu'    => '',
	'class_button'  => '',
	'class_content' => '',
	'instruction'   => '',
	'caption'       => '',
	'icon'          => 'ph ph-cube',
	'fields'        => [],
];
