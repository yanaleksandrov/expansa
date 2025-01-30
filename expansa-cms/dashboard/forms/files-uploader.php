<?php
/**
 * Form for build custom fields
 *
 * @since 2025.1
 */
return \Expansa\Facades\Form::enqueue(
	'files-uploader',
	[
		'class'  => 'dg g-7 p-7',
	    'x-data' => '{percent: 0, uploader: null}',
    ],
	[
		[
			'type'        => 'uploader',
			'name'        => 'files',
			'label'       => '',
			'class'       => '',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => t( 'Click to upload or drag & drop' ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'@change'  => '$ajax("media/upload", $el.files, e => percent = e.percent).then()',
				'multiple' => true,
			],
		],
		[
			'name'     => 'progress',
			'type'     => 'custom',
			'callback' => fn () => '<div class="progress" :style="{\'--expansa-progress\':`${percent}%`}"></div>',
		],
		[
			'type'        => 'textarea',
			'name'        => 'urls',
			'label'       => t( 'Or upload from external URL' ),
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
				'placeholder' => t( 'Each URL must be from a new line' ),
				'@change'     => '$ajax("media/grab", {urls}).then(response => files = response)',
				'x-textarea'  => 19,
			],
		],
	]
);