<?php

use app\Post\Status;
use app\Post\Type;
use Expansa\Safe;

$samples  = Safe::array( $__data['samples'] ?? [] );
$filepath = Safe::attribute( $__data['filepath'] ?? '' );
if ( empty( $samples ) ) {
    return;
}

return Dashboard\Form::enqueue(
	'import-fields',
	fields: [
		[
			'type'          => 'group',
			'name'          => 'data',
			'label'         => t( 'Required Data' ),
			'class'         => '',
			'label_class'   => '',
			'content_class' => '',
			'fields'        => [
				[
					'type'        => 'select',
					'name'        => 'type',
					'label'       => t( 'Post type' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => t( 'Sample: <samp>%s</samp>', 'pages' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [],
					'options'     => Type::options(),
				],
				[
					'type'        => 'select',
					'name'        => 'status',
					'label'       => t( 'Post status' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => t( 'Set default post status, if not specified' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [],
					'options'     => Status::get(),
				],
				[
					'type'        => 'select',
					'name'        => 'author',
					'label'       => t( 'Post author' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => t( 'Set post author, if not specified' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [],
					'options'     => [
						'1' => 'Yan Aleksandrov',
					],
				],
			],
		],
		[
			'type'          => 'group',
			'name'          => 'map-data',
			'label'         => t( 'Map Data' ),
			'class'         => '',
			'label_class'   => '',
			'content_class' => '',
			'fields'        => array_map(fn($index, $sample) => [
				'type'        => 'select',
				'label'       => '',
				'name'        => 'map[' . $index . ']',
				'value'       => '',
				'placeholder' => '',
				'class'       => 'dg g-1 ga-2',
				'reset'       => 0,
				'required'    => 0,
				'copy'        => 0,
				'before'      => '',
				'after'       => '',
				'tooltip'     => '',
				'instruction' => t( 'Sample: %s', '<samp>' . $sample . '</samp>' ),
				'attributes'  => [],
				'conditions'  => [],
				'options'     => [
					''         => t( 'No import' ),
					'optgroup' => [
						'label'   => t( 'Main fields' ),
						'options' => [
							'name'     => t( 'Post ID' ),
							'author'   => t( 'Author ID' ),
							'views'    => t( 'Views count' ),
							'type'     => t( 'Type' ),
							'title'    => t( 'Title' ),
							'content'  => t( 'Content' ),
							'created'  => t( 'Created at' ),
							'modified' => t( 'Modified at' ),
							'status'   => t( 'Status' ),
						],
					],
				],
			], array_keys($samples), $samples),
		],
		[
			'name'     => 'custom',
			'type'     => 'custom',
			'callback' => fn () => '<input type="hidden" value="' . $filepath . '" name="filename">',
		],
	]
);