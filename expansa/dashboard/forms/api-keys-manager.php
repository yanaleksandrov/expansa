<?php
use Expansa\I18n;

/**
 * API Keys manager form.
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'api-keys-manager',
	[
		'class'           => 'dg g-7 p-7',
		'x-data'          => '',
		'@submit.prevent' => "\$ajax('post/create')",
	],
	[
		[
			'type'       => 'hidden',
			'name'       => 'post-type',
			'attributes' => [
				'value' => 'api-keys',
			],
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
					'type'        => 'text',
					'name'        => 'app-name',
					'label'       => I18n::_t( 'App name' ),
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
					'label'       => I18n::_t( 'Status' ),
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
						'publish' => I18n::_t( 'Active' ),
						'draft'   => I18n::_t( 'Disabled' ),
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
					'label'       => I18n::_t( 'Requests limits' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Max request limits for this API key' ),
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
					'label'       => I18n::_t( 'Limits period' ),
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
						'second' => I18n::_t( 'per second' ),
						'minute' => I18n::_t( 'per minute' ),
						'hour'   => I18n::_t( 'per hour' ),
						'day'    => I18n::_t( 'per day' ),
						'week'   => I18n::_t( 'per week' ),
						'month'  => I18n::_t( 'per month' ),
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
					'label'       => I18n::_t( 'Start date' ),
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
				],
				[
					'type'        => 'date',
					'name'        => 'end-date',
					'label'       => I18n::_t( 'End date' ),
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
				],
			],
		],
		[
			'type'        => 'url',
			'name'        => 'sites',
			'label'       => I18n::_t( 'Allowed websites' ),
			'class'       => '',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => I18n::_t( 'The list of sites from which it is allowed to accept requests. Empty, so everyone is allowed.' ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'value'       => '',
				'placeholder' => 'e.g: https://google.com',
			],
		],
		[
			'type'     => 'custom',
			'callback' => function() {
				?>
				<div class="df jcsb g-2">
					<button type="button" class="btn btn--outline" @click="$dialog.close()"><?php I18n::t( 'Cancel' ); ?></button>
					<button type="submit" class="btn btn--primary" :disabled="appName === ''">
						<i class="ph ph-plug"></i> <?php I18n::t( 'Save project' ); ?>
					</button>
				</div>
				<?php
			},
		],
	]
);