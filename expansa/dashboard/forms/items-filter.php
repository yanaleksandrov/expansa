<?php
use Expansa\I18n;

/**
 * Posts actions.
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'items-filter',
	[
		'class'           => 'table__filter',
		'x-show'          => 'showFilter === true',
		'x-cloak'         => true,
		'@submit.prevent' => '$ajax("items/filter")',
	],
	[
		[
			'type'        => 'search',
			'name'        => 's',
			'label'       => '',
			'class'       => 'field field--sm field--outline',
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
				'placeholder' => I18n::_t( 'e.g. search text' ),
			],
		],
		[
			'type'        => 'submit',
			'name'        => 'submit',
			'label'       => I18n::_t( 'Apply filter' ),
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
				'class' => 'btn btn--sm btn--primary',
			],
		],
	]
);