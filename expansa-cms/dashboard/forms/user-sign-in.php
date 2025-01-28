<?php

use Expansa\Url;

/**
 * Sign In form
 *
 * @since 2025.1
 */
return Expansa\Form::enqueue(
	'user-sign-in',
	[
		'class'           => 'dg g-6',
		'@submit.prevent' => '$ajax("user/sign-in").then()',
		'x-data'          => '',
	],
	[
		[
			'type'        => 'header',
			'label'       => t( 'Welcome to Expansa' ),
			'name'        => 'title',
			'class'       => '',
			'instruction' => t( 'Sign in to access to your dashboard and site settings.' ),
		],
		[
			'type'        => 'text',
			'name'        => 'login',
			'label'       => t( 'Login or email' ),
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
				'placeholder' => t( 'Enter login or email' ),
			],
		],
		[
			'type'        => 'password',
			'name'        => 'password',
			'label'       => t( 'Password' ),
			'class'       => 'field field--lg',
			'label_class' => '',
			'reset'       => 0,
			'before'      => '',
			'after'       => '',
			'instruction' => t( 'Forgot your password? You can [reset it here](:resetPasswordLink)', url( '/dashboard/reset-password' ) ),
			'tooltip'     => '',
			'copy'        => 0,
			'validator'   => '',
			'conditions'  => [],
			'attributes'  => [
				'placeholder' => t( 'Password' ),
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
			'label'       => t( 'Remember me on this device' ),
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
			'label'      => t( 'Sign In' ),
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