<?php
/**
 * Form for menu editor
 *
 * @since 2025.1
 */
return Expansa\Form::enqueue(
	'menu-item-editor',
	[
		'class'           => 'dg g-4',
		'@submit.prevent' => '$ajax("posts/filter")',
		'x-sticky'        => '',
	],
	[
		[
			'name'     => 'instructions',
			'type'     => 'custom',
			'callback' => function() {
				?>
				<h6><?php echo t( 'Menu item data' ); ?></h6>
				<?php
			},
		],
		[
			'type'        => 'text',
			'label'       => t( 'Navigation Label' ),
			'name'        => 'title',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--sm',
			'label_class' => 'df fs-13 t-muted',
			'reset'       => 0,
			'required'    => 0,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => '',
			'attributes'  => [
				'@input' => 'slug = $safe.slug(title)',
			],
			'conditions'  => [],
		],
		[
			'type'        => 'text',
			'label'       => t( 'CSS Classes (optional)' ),
			'name'        => 'classes',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--sm',
			'label_class' => 'df fs-13 t-muted',
			'reset'       => 0,
			'required'    => 0,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => '',
			'attributes'  => [],
			'conditions'  => [],
		],
		[
			'type'        => 'checkbox',
			'label'       => t( 'Open link in a new tab' ),
			'name'        => 'link-target',
			'value'       => '',
			'placeholder' => '',
			'class'       => '',
			'label_class' => 'fs-13 t-muted',
			'reset'       => 0,
			'required'    => 0,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => '',
			'attributes'  => [],
			'conditions'  => [],
		],
		[
			'type'        => 'textarea',
			'label'       => t( 'Description' ),
			'name'        => 'description',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--sm',
			'label_class' => 'df fs-13 t-muted',
			'reset'       => 0,
			'required'    => 0,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => t( 'The description will be displayed in the menu if the active theme supports it.' ),
			'attributes'  => [],
			'conditions'  => [],
		],
	]
);