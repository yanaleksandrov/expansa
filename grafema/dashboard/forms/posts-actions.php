<?php
use Grafema\I18n;

/**
 * Posts actions.
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'posts-actions',
	[
		'class'           => 'df fww g-1',
		'x-data'          => '{email: ""}',
		'@submit.prevent' => '$ajax("items/options")',
	],
	[
		[
			'type'        => 'select',
			'name'        => 'action',
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
			'attributes'  => [],
			'options' => [
				''      => I18n::_t( 'Bulk Actions' ),
				'edit'  => I18n::_t( 'Edit' ),
				'trash' => I18n::_t( 'Move to trash' ),
				'copy'  => I18n::_t( 'Copy' ),
			],
		],
		[
			'type'        => 'button',
			'name'        => 'uid',
			'label'       => I18n::_f( '%s Apply', '<i class="ph ph-paper-plane-tilt"></i>' ),
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