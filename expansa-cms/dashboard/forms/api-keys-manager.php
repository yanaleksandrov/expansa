<?php
/**
 * API Keys manager form.
 *
 * @since 2025.1
 */
return Expansa\Facades\Form::enqueue(
	'api-keys-manager',
	[
		'class'           => 'dg g-7 p-7',
		'@submit.prevent' => '$ajax.post("apikey/create", "", e => e.end && (apiKeys.unshift(e.raw?.post), $dialog.closes()))',
	],
	[
		[
			'type'          => 'group',
			'name'          => 'manage',
			'label'         => '',
			'class'         => 'dg g-7 gtc-4 sm:gtc-1',
			'label_class'   => '',
			'content_class' => '',
			'fields'        => [
				[
					'type'        => 'text',
					'name'        => 'title',
					'label'       => t( 'App name' ),
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
						'placeholder' => '',
						'u-prop'      => 'title',
						'required'    => true,
					],
				],
				[
					'type'        => 'select',
					'name'        => 'status',
					'label'       => t( 'Status' ),
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
					'attributes'  => [ 'u-prop' => 'status' ],
					'options'     => [
						'publish' => t( 'Active' ),
						'draft'   => t( 'Disabled' ),
					],
				],
			]
		],
		[
			'type'          => 'group',
			'name'          => 'manage',
			'label'         => '',
			'class'         => 'dg g-7 gtc-4 sm:gtc-1',
			'label_class'   => '',
			'content_class' => '',
			'fields'        => [
				[
					'type'        => 'number',
					'name'        => 'limits',
					'label'       => t( 'Request limit' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => t( 'Maximum number of requests for this API key' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'u-prop' => 'limits',
						'value'  => 10,
						'min'    => 1,
					],
				],
				[
					'type'        => 'select',
					'name'        => 'period',
					'label'       => t( 'Limit period' ),
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
						'u-prop' => 'period',
						'value'  => '',
					],
					'options'     => [
						'second' => t( 'per second' ),
						'minute' => t( 'per minute' ),
						'hour'   => t( 'per hour' ),
						'day'    => t( 'per day' ),
						'week'   => t( 'per week' ),
						'month'  => t( 'per month' ),
					],
				],
			]
		],
		[
			'type'          => 'group',
			'name'          => 'manage',
			'label'         => '',
			'class'         => 'dg g-7 gtc-4 sm:gtc-1',
			'label_class'   => '',
			'content_class' => '',
			'fields'        => [
				[
					'type'        => 'date',
					'name'        => 'start-date',
					'label'       => t( 'Start date' ),
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
						'u-prop' => 'startDate',
					],
				],
				[
					'type'        => 'date',
					'name'        => 'end-date',
					'label'       => t( 'End date' ),
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
						'u-prop' => 'endDate',
					],
				],
			],
		],
		[
			'type'        => 'textarea',
			'name'        => 'sites',
			'label'       => t( 'Allowed websites' ),
			'class'       => '',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => t( 'Domains allowed to send requests. Leave empty to allow requests from anywhere.' ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'u-prop' => 'sites',
				'value'       => '',
                'rows'        => 1,
				'placeholder' => 'e.g: https://google.com',
			],
		],
		[
			'type'     => 'custom',
			'callback' => function() {
				?>
				<div class="df jcsb g-2">
					<button type="button" class="btn btn--outline" @click="$dialog.close()"><?php echo t( 'Cancel' ); ?></button>
					<button type="submit" class="btn btn--primary" disabled :disabled="title === ''">
						<i class="ph ph-plug"></i> <?php echo t( 'Save key' ); ?>
					</button>
				</div>
				<?php
			},
		],
	]
);