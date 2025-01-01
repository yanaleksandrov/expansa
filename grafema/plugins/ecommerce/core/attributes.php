<?php
use Grafema\I18n;

/**
 * Form for create & edit emails.
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'attribute-editor',
	[
		'class' => 'dg g-3 p-5 pt-4',
	],
	[
		[
			'type'          => 'group',
			'name'          => 'values-group',
			'label'         => '',
			'class'         => 'dg attributes-form',
			'label_class'   => '',
			'content_class' => 'dg g-3 gtc-4',
			'fields'        => [
				[
					'type'        => 'text',
					'name'        => 'name',
					'label'       => I18n::_t( 'Attribute Name' ),
					'class'       => 'field ga-4',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Name for the attribute (shown on the front-end)' ),
					'tooltip'     => '',
					'copy'        => 0,

					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'required' => 1,
					],
				],
				[
					'type'        => 'text',
					'name'        => 'slug',
					'label'       => I18n::_t( 'Slug' ),
					'class'       => 'field ga-4',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Unique slug/reference for the attribute; must be no more than 28 characters' ),
					'tooltip'     => '',
					'copy'        => 0,

					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'required' => 1,
					],
				],
				[
					'type'        => 'textarea',
					'name'        => 'description',
					'label'       => I18n::_t( 'Description' ),
					'class'       => 'field ga-4',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [],
				],
				[
					'type'        => 'select',
					'name'        => 'type',
					'label'       => I18n::_t( 'Type' ),
					'class'       => 'field ga-3',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Determines how this attribute\'s values are displayed' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'    => $user->locale ?? '',
					],
					'options'     => [
						'select' => I18n::_t( 'Dropdown List' ),
						'button' => I18n::_t( 'Button' ),
						'color'  => I18n::_t( 'Color' ),
						'image'  => I18n::_t( 'Image' ),
					],
				],
				[
					'type'        => 'text',
					'name'        => 'unit',
					'label'       => I18n::_t( 'Unit' ),
					'class'       => 'field ga-1',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'E.g.: kg, inch or lbs' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [],
				],
			],
		],
		[
			'type'          => 'group',
			'name'          => 'values-group',
			'label'         => '',
			'class'         => 'dg attributes-form',
			'label_class'   => '',
			'content_class' => 'dg g-3 gtc-1',
			'fields'        => [
				[
					'type'        => 'select',
					'name'        => 'assignments',
					'label'       => I18n::_t( 'Category assignments' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Some types of attributes are applicable only to certain product groups. For example, the size of the monitor is only for electronics.' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'    => $user->locale ?? '',
					],
					'options'     => [
						''       => I18n::_t( 'Any products categories' ),
						'button' => I18n::_t( 'Some category' ),
					],
				],
			],
		],
		[
			'type'        => 'checkbox',
			'name'        => 'unique',
			'label'       => '',
			'class'       => 'field field--ui',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => '',
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [],
			'options'     => [
				'unique' => [
					'content'     => I18n::_t( 'Uniqueness' ),
					'icon'        => 'ph ph-number-one',
					'description' => I18n::_t( 'Activate it if the attribute value can only be single, such as serial number, license or certificate number.' ),
					'checked'     => false,
				],
			],
		],
		[
			'type'        => 'checkbox',
			'name'        => 'filterable',
			'label'       => '',
			'class'       => 'field field--ui',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => '',
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [],
			'options'     => [
				'filterable' => [
					'content'     => I18n::_t( 'Filterable' ),
					'icon'        => 'ph ph-funnel',
					'description' => I18n::_t( 'Activate it if you need to show it in the product filter' ),
					'checked'     => false,
				],
			],
		],
	]
);