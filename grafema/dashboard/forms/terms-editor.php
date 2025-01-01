<?php
use Grafema\I18n;

/**
 * Form for terms editor
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'terms-editor',
	[
		'class'           => 'dg g-6',
		'@submit.prevent' => '$ajax("posts/filter")',
		'x-sticky'        => '',
	],
	[
		[
			'name'     => 'instructions',
			'type'     => 'custom',
			'callback' => function() {
				?>
				<h6><?php I18n::t( 'Add New Category' ); ?></h6>
				<?php
			},
		],
		[
			'type'        => 'text',
			'label'       => I18n::_t( 'Name' ),
			'name'        => 'title',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--outline',
			'label_class' => '',
			'reset'       => 0,
			'required'    => 1,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => I18n::_t( 'The name is how it appears on your site' ),
			'attributes'  => [
				'@input' => 'slug = $safe.slug(title)',
			],
			'conditions'  => [],
		],
		[
			'type'        => 'text',
			'label'       => I18n::_t( 'Slug' ),
			'name'        => 'slug',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--outline',
			'label_class' => '',
			'reset'       => 0,
			'required'    => 1,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => I18n::_t( 'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ),
			'attributes'  => [],
			'conditions'  => [],
		],
		[
			'type'        => 'select',
			'label'       => I18n::_t( 'Parent category' ),
			'name'        => 'parent',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--outline',
			'label_class' => '',
			'reset'       => 0,
			'required'    => 0,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => I18n::_t( 'Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.' ),
			'attributes'  => [],
			'conditions'  => [],
			'options' => [
				'' => I18n::_t( 'None' ),
			],
		],
		[
			'type'        => 'textarea',
			'label'       => I18n::_t( 'Short description' ),
			'name'        => 'description',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--outline',
			'label_class' => '',
			'reset'       => 0,
			'required'    => 1,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => I18n::_t( 'The description is not prominent by default; however, some themes may show it.' ),
			'attributes'  => [],
			'conditions'  => [],
		],
		[
			'type'        => 'submit',
			'name'        => 'add',
			'label'       => I18n::_t( 'Add New Category' ),
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
				'class' => 'btn btn--primary',
			],
		],
	]
);