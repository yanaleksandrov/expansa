<?php
/**
 * Form for build custom fields
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'fields-builder',
	[
		'class'           => 'builder',
		'x-data'          => 'builder',
		'@submit.prevent' => 'submit()',
	],
	[
		[
			'name' => 'builder',
			'type' => 'builder',
		],
	]
);