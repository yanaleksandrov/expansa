<?php

use App\Post\Status;
use App\Post\Type;
use Expansa\Facades\Safe;

$samples  = Safe::array( $__data['samples'] ?? [] );
$filepath = Safe::attribute( $__data['filepath'] ?? '' );
if ( empty( $samples ) ) {
    return;
}

return \Expansa\Facades\Form::enqueue(
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
					'attributes'  => [ 'u-prop' => 'type' ],
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
					'instruction' => t( 'Default post status, if none is specified' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [ 'u-prop' => 'status' ],
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
					'instruction' => t( 'Default post author, if none is specified' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [ 'u-prop' => 'author' ],
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
				'instruction' => t( 'Sample: %s', '<samp>' . escape( $sample ) . '</samp>' ),
				'attributes'  => [ 'u-prop' => 'map.' . $index ],
				'conditions'  => [],
				'options'     => [
					''         => t( 'Don\'t import' ),
					'optgroup' => [
						'label'   => t( 'Main fields' ),
						'options' => [
							'name'     => t( 'Post ID' ),
							'author'   => t( 'Author ID' ),
							'views'    => t( 'View count' ),
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
			'name'       => 'custom',
			'type'       => 'custom',
			'callback'   => fn () => '<input type="hidden" value="' . $filepath . '" name="filename">',
			'attributes' => [
                'u-prop' => 'custom'
            ],
		],
	]
);