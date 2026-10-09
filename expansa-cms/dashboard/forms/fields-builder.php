<?php
/**
 * Form for build custom fields
 *
 * @since 2025.1
 */
return \Expansa\Builders\Form::enqueue(
	'fields-builder',
	[
		'class'           => 'builder',
		'u-data'          => 'builder',
		'@submit.prevent' => 'submit()',
	],
	[
		[
			'name'       => 'builder',
			'error'      => 'builder',
			'type'       => 'builder',
			'attributes' => [
                'u-prop' => 'builder',
            ],
		],
	]
);