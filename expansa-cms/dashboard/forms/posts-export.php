<?php
/**
 * Form for build custom fields
 *
 * @since 2025.1
 */
return Expansa\Facades\Form::enqueue(
	'posts-export',
	[
		'class'           => 'card card-border px-7 pb-7 g-7',
		'@submit.prevent' => '$ajax.post("posts/export").then(response => output = response.output)',
		'u-data'          => '{posts:[]}',
	],
	[
		[
			'name'        => 'title',
			'type'        => 'header',
			'class'       => 'pt-7 px-7 t-center',
			'label'       => t( 'Export posts' ),
			'instruction' => t( 'Choose which content to export and the file format' ),
			'attributes'  => [
				'u-prop' => 'title'
			],
		],
		[
			'type'        => 'select',
			'name'        => 'types[]',
			'label'       => t( 'Post types' ),
			'class'       => '',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => t( 'Choose which types of posts you want to export' ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'u-prop'   => 'types',
				'value'    => 'pages',
				'multiple' => true,
			],
			'options' => [
				'pages' => t( 'Pages' ),
				'files' => t( 'Media' ),
			],
		],
		[
			'type'        => 'radio',
			'name'        => 'format',
			'label'       => t( 'File format' ),
			'class'       => 'field field--grid',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => t( 'Choose the format that best fits how you\'ll use the data' ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'u-prop' => 'format',
				'value'  => 'csv',
			],
			'options'     => [
				'csv' => [
					'icon'        => 'ph ph-file-csv',
					'image'       => url( 'dashboard/assets/images/dashboard-light.svg' ),
					'content'     => t( 'CSV file' ),
					'description' => t( 'A simple format for spreadsheet apps' ),
				],
				'json' => [
					'icon'        => 'ph ph-file-txt',
					'image'       => url( 'dashboard/assets/images/dashboard-dark.svg' ),
					'content'     => t( 'JSON file' ),
					'description' => t( 'A universal format for exchanging data across platforms' ),
				],
			],
		],
		[
			'type'     => 'custom',
			'callback' => function () {
				?>
				<button type="submit" class="btn btn--primary"><?php echo t( 'Export posts' ); ?></button>
				<?php
			},
		],
	]
);