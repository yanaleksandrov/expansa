<?php
use Grafema\I18n;
use Grafema\Url;

/**
 * Form for build custom fields
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'posts-export',
	[
		'class'           => 'card card-border px-8 pb-8 g-8',
		'@submit.prevent' => '$ajax("posts/export").then(response => output = response.output)',
		'x-data'          => '{posts:[]}',
	],
	[
		[
			'name'        => 'title',
			'type'        => 'header',
			'class'       => 'pt-8 px-8 t-center',
			'label'       => I18n::_t( 'Map fields for export' ),
			'instruction' => I18n::_t( 'Select fields from your CSV file that you want to map to fields in the posts, or that you want to ignore during import' ),
		],
		[
			'type'        => 'select',
			'name'        => 'types[]',
			'label'       => I18n::_t( 'Post types' ),
			'class'       => '',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => I18n::_t( 'Choose which types of posts you want to export' ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'value'    => 'pages',
				'multiple' => true,
			],
			'options' => [
				'pages' => I18n::_t( 'Pages' ),
				'media' => I18n::_t( 'Media' ),
			],
		],
		[
			'type'        => 'radio',
			'name'        => 'format',
			'label'       => I18n::_t( 'File format' ),
			'class'       => 'field field--grid',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => I18n::_t( 'Select the appropriate format for subsequent work' ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'value' => 'csv',
			],
			'options'     => [
				'csv' => [
					'icon'        => 'ph ph-file-csv',
					'image'       => Url::site( 'dashboard/assets/images/dashboard-light.svg' ),
					'content'     => I18n::_t( 'CSV file' ),
					'description' => I18n::_t( 'A clear format for apps that work with tables' ),
				],
				'json' => [
					'icon'        => 'ph ph-file-txt',
					'image'       => Url::site( 'dashboard/assets/images/dashboard-dark.svg' ),
					'content'     => I18n::_t( 'JSON file' ),
					'description' => I18n::_t( 'Universal format for cross-platform data exchange' ),
				],
			],
		],
		[
			'type'     => 'custom',
			'callback' => function () {
				?>
				<button type="submit" class="btn btn--primary btn--lg"><?php I18n::t( 'Export posts' ); ?></button>
				<?php
			},
		],
	]
);