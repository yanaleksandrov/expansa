<?php
/**
 * Form for filter posts
 *
 * @since 2025.1
 */
return \Expansa\Facades\Form::enqueue(
	'posts-filter',
	[
		'class'    => 'dg g-7 p-8',
		'@change'  => '$ajax("posts/filter")',
		'x-sticky' => '',
	],
	[
		[
			'type'        => 'progress',
			'label'       => t( 'Storage' ),
			'name'        => 'progress',
			'value'       => 75,
			'placeholder' => '',
			'class'       => '',
			'label_class' => 'df aic fs-12 t-muted',
			'reset'       => 1,
			'required'    => 0,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => t( '25% used of 2GB' ),
			'attributes'  => [
				'placeholder' => t( 'e.g. image name' ),
			],
			'conditions'  => [],
			'max'         => 100,
			'min'         => 0,
			'speed'       => 500,
		],
		[
			'type'        => 'search',
			'label'       => t( 'Search' ),
			'name'        => 's',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--outline',
			'label_class' => 'df jcsb fs-12 fw-400 t-muted',
			'reset'       => 1,
			'required'    => 0,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => '',
			'attributes'  => [
				'placeholder' => t( 'e.g. image name' ),
			],
			'conditions'  => [],
		],
		[
			'type'        => 'checkbox',
			'label'       => t( 'File types' ),
			'name'        => 'types',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--outline',
			'label_class' => 'df jcsb fs-12 fw-400 t-muted',
			'reset'       => 1,
			'required'    => 0,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => '',
			'attributes'  => [],
			'conditions'  => [],
			'options' => [
				'svg'    => t( 'SVG %s', '<i class="badge badge--blue-lt ml-auto">56</i>' ),
				'images' => t( 'Images %s', '<i class="badge badge--blue-lt ml-auto">670</i>' ),
				'video'  => t( 'Video %s', '<i class="badge badge--blue-lt ml-auto">35</i>' ),
				'audio'  => t( 'Audio %s', '<i class="badge badge--blue-lt ml-auto">147</i>' ),
				'zip'    => t( 'ZIP %s', '<i class="badge badge--blue-lt ml-auto">74</i>' ),
			],
		],
		[
			'type'        => 'select',
			'label'       => t( 'Author' ),
			'name'        => 'authors',
			'value'       => '',
			'placeholder' => '',
			'class'       => 'field field--sm field--outline',
			'label_class' => 'df jcsb fs-12 fw-400 t-muted',
			'reset'       => 0,
			'required'    => 0,
			'copy'        => 0,
			'before'      => '',
			'after'       => '',
			'tooltip'     => '',
			'instruction' => '',
			'attributes'  => [],
			'conditions'  => [],
			'options' => [
				''                => t( 'Select an author' ),
				'user-registered' => t( 'New user registered' ),
			],
		],
	]
);