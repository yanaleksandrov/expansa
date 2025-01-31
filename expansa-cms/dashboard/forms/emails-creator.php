<?php

use Expansa\Facades\Safe;

/**
 * Form for create & edit emails.
 *
 * @since 2025.1
 */
return \Expansa\Facades\Form::enqueue(
	'emails-creator',
	[
		'@submit.window' => '$ajax("import-email")',
		'x-data'         => sprintf( "tab('%s')", Safe::prop( $_GET['tab'] ?? 'general' ) ),
	],
	[
		[
			'type'          => 'tab',
			'label'         => t( 'General' ),
			'name'          => 'general',
			'class_menu'    => '',
			'class_button'  => '',
			'class_content' => 'p-7',
			'instruction'   => '',
			'caption'       => '',
			'icon'          => 'ph ph-envelope-simple-open',
			'fields'        => [
				[
					'type'        => 'select',
					'name'        => 'event',
					'label'       => t( 'Events' ),
					'class'       => '',
					'label_class' => 'df aic fs-12 t-muted',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'    => '',
						'required' => true,
					],
					'options' => [
						''                => t( 'Select an event' ),
						'user-registered' => t( 'New user registered' ),
					],
				],
				[
					'type'        => 'text',
					'name'        => 'subject',
					'label'       => t( 'Email subject' ),
					'class'       => '',
					'label_class' => 'df aic fs-12 t-muted',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'       => '',
						'required'    => true,
						'placeholder' => t( 'Email subject' ),
					],
				],
				[
					'type'        => 'text',
					'name'        => 'title',
					'label'       => t( 'Title' ),
					'class'       => '',
					'label_class' => 'df aic fs-12 t-muted',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'       => t( 'Your are registered' ),
						'required'    => true,
						'placeholder' => t( 'Mail title...' ),
					],
				],
				[
					'type'        => 'text',
					'name'        => 'subtitle',
					'label'       => t( 'Subtitle' ),
					'class'       => '',
					'label_class' => 'df aic fs-12 t-muted',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'       => t( 'We have received a new registration request. Read the instructions.' ),
						'required'    => true,
						'placeholder' => t( 'Subtitle' ),
					],
				],
				[
					'type'        => 'textarea',
					'name'        => 'content',
					'label'       => t( 'Content' ),
					'class'       => '',
					'label_class' => 'df aic fs-12 t-muted',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'       => '',
						'rows'        => 1,
						'required'    => true,
						'placeholder' => t( 'N/A' ),
					],
				],
				[
					'type'        => 'textarea',
					'name'        => 'recipients',
					'label'       => t( 'Recipient(s)' ),
					'class'       => '',
					'label_class' => 'df aic fs-12 t-muted',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => t( 'Enter recipients for this email. Each recipient email from a new line.' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'       => '',
						'rows'        => 1,
						'required'    => true,
						'placeholder' => t( 'N/A' ),
					],
				],
			],
		],
		[
			'type'          => 'tab',
			'label'         => t( 'Global' ),
			'name'          => 'global',
			'class_menu'    => '',
			'class_button'  => '',
			'class_content' => '',
			'instruction'   => '',
			'caption'       => '',
			'icon'          => 'ph ph-faders',
			'fields'        => [
				[
					'type'        => 'textarea',
					'name'        => 'bottom',
					'label'       => t( 'Mail bottom content' ),
					'class'       => '',
					'label_class' => 'df aic fs-12 t-muted',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'       => t( 'Team, PO Box 16122, Collins Street West, <a href="#" target="_blank">Victoria 8007, Australia</a>' ),
						'placeholder' => t( 'N/A' ),
					],
				],
				[
					'type'        => 'textarea',
					'name'        => 'footer',
					'label'       => t( 'Footer content' ),
					'class'       => '',
					'label_class' => 'df aic fs-12 t-muted',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'       => t( 'This message is generated automatically. Don\'t reply it. All rights reserved © 2024' ),
						'placeholder' => t( 'N/A' ),
					],
				],
			],
		],
		[
			'type'          => 'tab',
			'label'         => t( 'Test' ),
			'name'          => 'test',
			'class_menu'    => '',
			'class_button'  => '',
			'class_content' => '',
			'instruction'   => '',
			'caption'       => '',
			'icon'          => 'ph ph-test-tube',
			'fields'        => [
				[
					'type'        => 'email',
					'name'        => 'email',
					'label'       => t( 'Send To' ),
					'class'       => '',
					'label_class' => 'df aic fs-12 t-muted',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => t( 'Enter recipients for this email. Each recipient email from a new line.' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [],
				],
				[
					'type'        => 'submit',
					'name'        => 'check',
					'label'       => t( 'Send a test email' ),
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
						'type'      => 'button',
						'class'     => 'btn',
						'@click'    => '$ajax("email/send", {email: email})',
						':disabled' => '!email',
					],
				],
			],
		],
		[
			'type'          => 'tab',
			'label'         => t( 'Placeholders' ),
			'name'          => 'placeholders',
			'class_menu'    => '',
			'class_button'  => '',
			'class_content' => '',
			'instruction'   => '',
			'caption'       => '',
			'icon'          => 'ph ph-brackets-curly',
			'fields'        => [
				[
					'name'     => 'instructions',
					'type'     => 'custom',
					'callback' => function() {
						?>
						<div class="dg g-2">
							<div><code>:name</code> <?php echo t( 'User display name if email found in users' ); ?></div>
							<div><code>:link</code> <?php echo t( 'Link to page' ); ?></div>
							<div><code>:admin_email</code> <?php echo t( 'The site admin email' ); ?></div>
							<div><code>:site_title</code> <?php echo t( 'The site title' ); ?></div>
							<div><code>:unsubscribe_url</code> <?php echo t( 'Url to unsubscribe from emails' ); ?></div>
							<div><code>:unsubscribe_link</code> <?php echo t( 'Clickable link to unsubscribe from emails' ); ?></div>
							<div><code>:year</code> <?php echo t( 'Current year' ); ?></div>
							<div><code>:date</code> <?php echo t( 'Current date in "24 August, 2024" format' ); ?></div>
						</div>
						<?php
					},
				],
			],
		],
	]
);