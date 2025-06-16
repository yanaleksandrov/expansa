<?php

declare(strict_types=1);

namespace App\Api;

use Expansa\Facades\Json;
use Expansa\Facades\Mail;
use Expansa\Facades\Safe;
use Expansa\Facades\View;

class User
{
    /**
     * Create item.
     *
     * @url    POST api/user
     */
    public function create(): array
    {
        return [
            'method' => 'POST create user',
        ];
    }

    /**
     * Get all items.
     *
     * @url    GET api/user
     */
    public function index(): array
    {
        return [
            'method' => 'GET user list',
        ];
    }

    /**
     * Update item by ID.
     *
     * @url    PUT api/user/{id}
     */
    public function update(): void
    {
        $currentUser = \App\User::current();
        $userdata    = $_POST + [ 'id' => $currentUser->id ];
        $fields = Safe::data(
            $_POST,
            [
                'bio'     => 'trim',
                'toolbar' => 'bool',
                'format'  => 'text',
            ]
        )->apply();

        \App\User::update($userdata, function (\App\Field $field) {
            $fields = Safe::data(
                $_POST,
                [
                    'bio'     => 'trim',
                    'toolbar' => 'bool',
                    'format'  => 'text',
                ]
            )->apply();

            foreach ($fields as $key => $value) {
                $field->update($key, $value);
            }
        });

        echo Json::encode([
            'data' => [
                [
                    'target' => 'body',
                    'notify' => t('User is updated'),
                ],
            ],
        ]);
    }

    /**
     * Remove item by ID.
     *
     * @url    DELETE api/user/$id
     */
    public function delete(): array
    {
        return [
            'method' => 'DELETE remove user by ID',
        ];
    }

    /**
     * Check the compliance of the server with the minimum requirements.
     *
     * @url    GET api/user/sign-in
     */
    public static function signIn(): string
    {
        $user = \App\User::login($_POST);
        if ($user instanceof \Expansa\Debug\Error) {
            echo Json::encode([
                'data' => [
                    [
                        'target' => 'body',
                        'notify' => $user->get('user-login')[0],
                    ],
                ],
            ]);
        }

        return Json::encode([
            [
                'target'   => 'body',
                'method'   => 'redirect',
                'fragment' => url('dashboard'),
            ],
        ]);
    }

    /**
     * Sign up user.
     *
     * @since 2025.1
     */
    public static function signUp(): array|\App\User
    {
        $user = \App\User::add($_REQUEST ?? []);
        if ($user instanceof \App\User) {
            return [
                'signed-up' => true,
                [
                    'target'   => 'body',
                    'method'   => 'redirect',
                    'fragment' => url('sign-in'),
                ],
            ];
        }
        return $user;
    }

    /**
     * Reset user password.
     *
     * @since 2025.1
     */
    public static function resetPassword(): array
    {
        $email = Safe::email($_REQUEST['email'] ?? '');
        $user  = \App\User::get($email, 'email');
        if ($user instanceof \App\User) {
            $mail_is_sent = Mail::send(
                $email,
                t('Instructions for reset password'),
                View::make(EX_DASHBOARD . 'mails/wrapper', [
                    'body_template' => EX_DASHBOARD . 'mails/reset-password',
                ])
            );

            return [
                'mail-is-sent' => $mail_is_sent,
            ];
        }
        return $user;
    }

    /**
     * Reset user password.
     *
     * @since 2025.1
     */
    public static function passwordUpdate(): array
    {
    }
}
