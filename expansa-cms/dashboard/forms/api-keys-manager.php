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
		'@submit.prevent' => '$ajax("apikey/create", "", e => e.end && ($dialog.close(), apiKeys.unshift(e.raw?.post)))',
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
					'attributes'  => [],
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
					'label'       => t( 'Requests limits' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => t( 'Max request limits for this API key' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value' => 10,
						'min'   => 1,
					],
				],
				[
					'type'        => 'select',
					'name'        => 'period',
					'label'       => t( 'Limits period' ),
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
						'value' => '',
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
					'attributes'  => [],
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
					'attributes'  => [],
				],
			],
		],
		[
			'type'        => 'textarea',
			'name'        => 'sites',
			'label'       => t( 'Allowed website' ),
			'class'       => '',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => t( 'Site domain from which it is allowed to accept requests. Empty, so everyone is allowed.' ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
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
						<i class="ph ph-plug"></i> <?php echo t( 'Save project' ); ?>
					</button>
				</div>
				<?php
			},
		],
	]
);