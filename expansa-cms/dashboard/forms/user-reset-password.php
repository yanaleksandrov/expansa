<?php

use Expansa\Facades\Form;
use Expansa\Facades\Safe;

$resetToken = Safe::trim($_GET['token'] ?? '');
$hasToken   = preg_match('/^[a-f0-9]{64}$/i', $resetToken) === 1;
$fields     = [
    [
        'name'        => 'title',
        'type'        => 'header',
        'label'       => t('Reset Password'),
        'class'       => 't-center',
        'instruction' => $hasToken
            ? t('Choose a new password for your account.')
            : t('Enter the email address that you used to register. We will send you an email that will allow you to reset your password.'),
        'attributes'  => ['u-prop' => 'title'],
    ],
];

if ($hasToken) {
    $fields[] = [
        'type'       => 'hidden',
        'name'       => 'token',
        'attributes' => [
            'value'  => $resetToken,
            'u-prop' => 'token'
        ],
    ];
    $fields[] = [
        'type'        => 'password',
        'name'        => 'password',
        'error'       => 'password',
        'label'       => t('New Password'),
        'class'       => 'field field--lg',
        'instruction' => t('At least 12 characters.'),
        'validator'   => '',
        'attributes'  => [
            'placeholder'  => t('Enter a new password'),
            'u-prop'       => 'password',
            'required'     => true,
            'autocomplete' => 'new-password',
        ],
        'switcher'    => 1,
        'generator'   => 1,
        'indicator'   => 0,
        'characters'  => [
            'lowercase' => 2,
            'uppercase' => 2,
            'special'   => 2,
            'length'    => 12,
            'digit'     => 2,
        ],
    ];
} else {
    $fields[] = [
        'type'        => 'email',
        'name'        => 'email',
        'error'       => 'email',
        'label'       => t('Your email'),
        'class'       => 'field field--lg',
        'instruction' => '',
        'attributes'  => [
            'placeholder'    => t('Enter your email address'),
            'u-prop'         => 'email',
            'required'       => true,
            'autocomplete'   => 'email',
        ],
    ];
}

$fields[] = [
    'type'       => 'submit',
    'name'       => 'submit',
    'label'      => $hasToken ? t('Save New Password') : t('Send Me Instructions'),
    'attributes' => [
        'class'     => 'btn btn--lg btn--primary btn--full',
        'disabled'  => true,
        ':disabled' => $hasToken ? 'password.trim().length < 8' : '!/\S+@\S+\.\S+/.test(email)',
    ],
];

return Form::enqueue(
    'user-reset-password',
    [
        'class'           => 'dg g-6',
        'u-data'          => '{token: ' . json_encode($hasToken ? $resetToken : '') . '}',
        '@submit.prevent' => '$ajax.post("user/reset-password")',
    ],
    $fields,
);
