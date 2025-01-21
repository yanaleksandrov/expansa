<?php
use Expansa\I18n;

/**
 * Reset password form
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'user-reset-password',
	[
		'class'           => 'dg g-6',
		'x-data'          => '{email: ""}',
		'@submit.prevent' => '$ajax("user/reset-password")',
	],
	[
		[
			'name'        => 'title',
			'type'        => 'header',
			'label'       => I18n::_t( 'Reset password' ),
			'class'       => 't-center',
			'instruction' => I18n::_t( 'Enter the email address that you used to register. We will send you an email that will allow you to reset your password.' ),
		],
		[
			'type'        => 'email',
			'name'        => 'email',
			'label'       => I18n::_t( 'Your email' ),
			'class'       => 'field field--lg',
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
				'placeholder'    => I18n::_t( 'Enter your email address' ),
				'required'       => true,
				'x-autocomplete' => '',
			],
		],
		[
			'type'        => 'submit',
			'name'        => 'sign-in',
			'label'       => I18n::_t( 'Send me instructions' ),
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
				'class'     => 'btn btn--lg btn--primary btn--full',
				'disabled'  => '',
				':disabled' => '!/\S+@\S+\.\S+/.test(email)',
			],
		],
	]
);