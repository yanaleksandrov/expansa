<?php
/**
 * Form for build custom fields
 *
 * @since 2025.1
 */
return Expansa\Facades\Form::enqueue(
	'files-uploader',
	[
		'class'  => 'dg g-6 p-6',
	    'u-data' => '{percent: 0, uploader: null}',
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
				'u-prop'   => 'files',
				'@change'  => '$ajax.post("media/upload", $el.files, e => percent = e.percent).then(({uploaded}) => uploaded && window.location.reload())',
				'multiple' => true,
			],
		],
		[
			'name'       => 'progress',
			'type'       => 'custom',
			'callback'   => fn () => '<div class="progress" :style="{\'--expansa-progress\':`${percent}%`}"></div>',
			'attributes' => [
                'u-prop' => 'progress'
            ],
		],
		[
			'type'        => 'textarea',
			'name'        => 'urls',
			'label'       => t( 'Or upload from an external URL' ),
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
				'u-prop'      => 'urls',
				'placeholder' => t( 'Enter one URL per line' ),
				'@change'     => '$ajax.post("media/grab", {urls}).then(({uploaded}) => uploaded && window.location.reload())',
				'u-textarea'  => 19,
			],
		],
	]
);
