<?php
use Expansa\I18n;

/**
 * Sign Up form
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'user-sign-up',
	[
		'class'           => 'dg g-6',
		'x-data'          => '',
		'@submit.prevent' => "\$ajax('user/sign-up')",
	],
	[
		[
			'type'        => 'header',
			'label'       => I18n::_t( 'Create new account' ),
			'name'        => 'title',
			'class'       => '',
			'instruction' => I18n::_t( 'After creating an account, more platform features will be available to you' ),
		],
		[
			'type'        => 'email',
			'name'        => 'email',
			'label'       => I18n::_t( 'User Email' ),
			'class'       => 'field field--lg',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => I18n::_t( 'Notifications will be sent to this email' ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'placeholder' => I18n::_t( 'Enter user email' ),
				'@change'     => "login = email.split('@')[0]",
				'required'    => true,
			],
		],
		[
			'type'        => 'text',
			'name'        => 'login',
			'label'       => I18n::_t( 'User Login' ),
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
				'placeholder' => I18n::_t( 'Enter user login' ),
				'required'    => true,
			],
		],
		[
			'type'        => 'password',
			'name'        => 'password',
			'label'       => I18n::_t( 'Password' ),
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
				'placeholder' => I18n::_t( 'Password' ),
				'required'    => true,
			],
			'switcher'   => 1,
			'generator'  => 1,
			'indicator'  => 0,
			'characters' => [
				'lowercase' => 2,
				'uppercase' => 2,
				'special'   => 2,
				'length'    => 12,
				'digit'     => 2,
			],
		],
		[
			'type'        => 'submit',
			'name'        => 'sign-up',
			'label'      => I18n::_t( 'Sign Up' ),
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
				':disabled' => '![login, email, password].every(i => i.trim())',
			],
		],
	]
);