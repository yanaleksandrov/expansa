<?php

declare(strict_types=1);

namespace app\Api;

use Expansa\Mail;
use Expansa\Safe;
use Expansa\Url;
use Expansa\View;

class User
{
    /**
     * Endpoint name.
     */
    public string $endpoint = 'user';

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
    public function update(): array
    {
        $currentUser = \app\User::current();
        $userdata    = $_REQUEST + [ 'id' => $currentUser->id ];

        \app\User::update($userdata, function (\app\Field $field) {
            $fields = Safe::data(
                $_REQUEST,
                [
                    'bio'     => 'trim',
                    'toolbar' => 'bool',
                    'format'  => 'text',
                    'locale'  => 'locale',
                ]
            )->apply();

            foreach ($fields as $key => $value) {
                $field->mutate($key, $value);
            }
        });

        return [
            [
                'target'   => 'body',
                'method'   => 'notify',
                'fragment' => t('User is updated'),
            ],
        ];
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
    public static function signIn(): array
    {
        $user = \app\User::login($_POST);
        if ($user instanceof Expansa\Error) {
            return [
                [
                    'target'   => 'body',
                    'method'   => 'notify',
                    'fragment' => $user->getError('user-login'),
                ],
            ];
        }

        return [
            [
                'target'   => 'body',
                'method'   => 'redirect',
                'fragment' => Url::site('dashboard'),
            ],
        ];
    }

    /**
     * Sign up user.
     *
     * @since 2025.1
     */
    public static function signUp(): array
    {
        $user = \app\User::add($_REQUEST ?? []);
        if ($user instanceof \app\User) {
            return [
                'signed-up' => true,
                [
                    'target'   => 'body',
                    'method'   => 'redirect',
                    'fragment' => Url::sign_in(),
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
        $user  = \app\User::get($email, 'email');
        if ($user instanceof \app\User) {
            $mail_is_sent = Mail::send(
                $email,
                t('Instructions for reset password'),
                View::make(EX_DASHBOARD . 'views/mails/wrapper', [
                    'body_template' => EX_DASHBOARD . 'views/mails/reset-password',
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
