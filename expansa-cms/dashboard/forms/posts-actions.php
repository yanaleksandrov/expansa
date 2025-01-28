<?php
/**
 * Posts actions.
 *
 * @since 2025.1
 */
return Expansa\Form::enqueue(
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
				''      => t( 'Bulk Actions' ),
				'edit'  => t( 'Edit' ),
				'trash' => t( 'Move to trash' ),
				'copy'  => t( 'Copy' ),
			],
		],
		[
			'type'        => 'button',
			'name'        => 'uid',
			'label'       => t( '%s Apply', '<i class="ph ph-paper-plane-tilt"></i>' ),
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