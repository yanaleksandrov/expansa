<?php

declare(strict_types=1);

namespace App\Api\Options;

use App\Models\Option;
use App\Models\User;
use App\Support\Mailer;
use App\Support\RoleSettings;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Facades\Auth;
use Expansa\Facades\Mail;
use Expansa\Support\Arr;

final class OptionsService
{
    public function update(array $input): array
    {
        $options = Arr::exclude($input, ['nonce']);

        foreach ($options as $option => $value) {
            // the roles keep the administrator's permissions and take the new role, see RoleSettings
            if ($option === 'roles' && is_array($value)) {
                $value = RoleSettings::normalize($value);
            }

            // the form never shows the saved SMTP password and DKIM key back, see Mailer::normalize()
            if ($option === 'mail' && is_array($value)) {
                $value = Mailer::normalize($value);
            }

            Option::update($option, $value);
        }

        return [
            ['target' => 'body', 'notify' => t('Options updated successfully.')],
        ];
    }

    /**
     * Send a test email to the current user with the saved Mail settings; five in ten minutes.
     *
     * @return array<int, array<string, mixed>> Notice fragment with the result or the SMTP error.
     */
    public function mailTest(): array
    {
        $user = User::current();

        try {
            Auth::limit("mail-test:$user->id", 5, 600);
        } catch (TooManyAttempts) {
            return [['target' => 'body', 'notify' => t('Too many test emails. Try again in a few minutes.')]];
        }

        $message = Mail::to($user->email)
            ->subject(t('Test email from :site', (string) Option::get('site.name', '') ?: 'Expansa'))
            ->message('<p>' . t('The mail settings work.') . '</p>');

        $notice = $message->send()
            ? t('Test email sent to :email.', $user->email)
            : t('The email was not sent: :error', $message->error);

        return [['target' => 'body', 'notify' => $notice]];
    }
}
