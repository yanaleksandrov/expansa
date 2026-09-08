<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Debug\Error;
use Expansa\Facades\Mail;
use Expansa\Facades\Safe;
use Expansa\Facades\View;

final class UserService
{
    public function update(array $input): array
    {
        $currentUser = User::current();
        $userdata    = $input + ['id' => $currentUser->id];

        $fields = Safe::data($input, [
            'bio'     => 'trim',
            'toolbar' => 'bool',
            'format'  => 'text',
        ])->apply();

        $user = User::update($userdata);

        if ($user instanceof User) {
            foreach ($fields as $key => $value) {
                $user->field->update($key, $value);
            }
        }

        return [
            ['target' => 'body', 'notify' => t('User is updated')],
        ];
    }

    /**
     * Fixed: the old code fell through to also build (and, once wired to a real
     * dispatcher, send) a redirect fragment even after already echoing an error notify
     * on failed login — two responses for one request. Now an early return either way.
     * Also fixed the redirect fragment itself: it used `method`/`fragment` keys, but
     * the frontend's applyFragment() only recognises the action name as the key
     * itself (`redirect`), so this redirect never actually fired.
     */
    public function signIn(array $input): array
    {
        $user = User::login($input);

        if ($user instanceof Error) {
            return [
                ['target' => 'body', 'notify' => $user->get('user-login')[0]],
            ];
        }

        return [
            ['target' => 'body', 'redirect' => url('dashboard')],
        ];
    }

    /** Same redirect-fragment key fix as signIn() — was `method`/`fragment`, now `redirect`. */
    public function signUp(array $input): array|User
    {
        $user = User::add($input);

        if ($user instanceof User) {
            return [
                'signed-up' => true,
                ['target' => 'body', 'redirect' => url('sign-in')],
            ];
        }

        return $user;
    }

    /**
     * Fixed return type: User::get() returns User|Error, but this was declared to
     * return only `array` — under strict_types, any reset request for an email that
     * doesn't match a user (a routine, expected case, not an edge case) would throw
     * a TypeError instead of the intended "no matching account" behavior.
     */
    public function resetPassword(array $input): array|Error
    {
        $email = Safe::email($input['email'] ?? '');
        $user  = User::get($email, 'email');

        if ($user instanceof User) {
            $mailIsSent = Mail::send(
                $email,
                t('Instructions for reset password'),
                View::make(EX_DASHBOARD . 'mails/wrapper', [
                    'body_template' => EX_DASHBOARD . 'mails/reset-password',
                ])
            );

            return ['mail-is-sent' => $mailIsSent];
        }

        return $user;
    }
}
