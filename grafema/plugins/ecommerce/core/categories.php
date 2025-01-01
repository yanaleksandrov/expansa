<?php
use Grafema\I18n;

/**
 * Form for create & edit emails.
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'attributes',
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
			'content_class' => 'dg g-3 gtc-1',
			'fields'        => [
				[
					'type'        => 'text',
					'name'        => 'name',
					'label'       => I18n::_t( 'Category Name' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'The name is how it appears on your site.' ),
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
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'required' => 1,
					],
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
					'name'        => 'type',
					'label'       => I18n::_t( 'The parent category of the product' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.' ),
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
					'type'        => 'media',
					'name'        => 'image',
					'label'       => I18n::_t( 'Image' ),
					'class'       => '',
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
					],
				],
				[
					'type'        => 'textarea',
					'name'        => 'description',
					'label'       => I18n::_t( 'Description' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'The description is not prominent by default; however, some themes may show it.' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [],
				],
			],
		],
	]
);