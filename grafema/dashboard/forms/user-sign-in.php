<?php
use Grafema\I18n;
use Grafema\Url;

/**
 * Sign In form
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'user-sign-in',
	[
		'class'           => 'dg g-6',
		'@submit.prevent' => '$ajax("user/sign-in").then()',
		'x-data'          => '',
	],
	[
		[
			'type'        => 'header',
			'label'       => I18n::_t( 'Welcome to Grafema' ),
			'name'        => 'title',
			'class'       => '',
			'instruction' => I18n::_t( 'Sign in to access to your dashboard and site settings.' ),
		],
		[
			'type'        => 'text',
			'name'        => 'login',
			'label'       => I18n::_t( 'Login or email' ),
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
				'placeholder' => I18n::_t( 'Enter login or email' ),
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
			'instruction' => I18n::_f( 'Forgot your password? You can [reset it here](:resetPasswordLink)', Url::site( '/dashboard/reset-password' ) ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'placeholder' => I18n::_t( 'Password' ),
				'required'    => 1,
			],
			'switcher'    => 1,
			'generator'   => 0,
			'indicator'   => 0,
			'characters'  => [],
		],
		[
			'type'        => 'checkbox',
			'name'        => 'remember',
			'label'       => I18n::_t( 'Remember me on this device' ),
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
				'checked' => true,
			],
			'options' => [],
		],
		[
			'type'        => 'submit',
			'name'        => 'sign-in',
			'label'      => I18n::_t( 'Sign In' ),
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
				':disabled' => '!login.trim() || !password.trim()',
			],
		],
	]
);