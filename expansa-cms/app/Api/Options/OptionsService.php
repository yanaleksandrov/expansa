<?php

declare(strict_types=1);

namespace App\Api\Options;

use App\Models\Option;
use App\Models\User;
use App\Support\Mailer;
use App\Support\RoleSettings;
use App\Support\Secrets;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Facades\Auth;
use Expansa\Facades\Mail;
use Expansa\Http\Request;
use Expansa\Http\Response;
use Expansa\Support\Arr;

final class OptionsService
{
    public function update(Request $request, Response $response): Response
    {
        $options = Arr::exclude($request->post, ['nonce']);

        foreach ($options as $option => $value) {
            // the roles keep the administrator's permissions and take the new role, see RoleSettings
            if ($option === 'roles' && is_array($value)) {
                $value = RoleSettings::normalize($value);
            }

            // the form never shows the saved SMTP password and DKIM key back, see Mailer::normalize()
            if ($option === 'mail' && is_array($value)) {
                $value = Mailer::normalize($value);
            }

            if ($option === 'oauth' && is_array($value)) {
                $value = $this->normalizeProviders($value);
            }

            // clearing the address drops the key, like clearing the SMTP server
            if ($option === 'ai' && is_array($value)) {
                $value = Secrets::keep($value, (array) Option::get('ai', []), ['key']);
                $value['key']     = trim((string) ($value['url'] ?? '')) === '' ? '' : $value['key'];
                $value['schemas'] = (bool) ($value['schemas'] ?? false);
            }

            Option::update($option, $value);
        }

        return $response->notify(t('Options updated successfully.'));
    }

    /**
     * Encrypt the client secrets of the sign-in providers; an empty secret keeps the saved one,
     * an empty client ID drops it.
     *
     * @param array<string, mixed> $providers Form values: `name => [client_id, client_secret, ...]`.
     * @return array<string, mixed>
     */
    private function normalizeProviders(array $providers): array
    {
        $saved = (array) Option::get('oauth', []);
        foreach ($providers as $name => $config) {
            if (! is_array($config)) {
                continue;
            }

            $config = Secrets::keep($config, (array) ($saved[$name] ?? []), ['client_secret']);
            if (trim((string) ($config['client_id'] ?? '')) === '') {
                $config['client_secret'] = '';
            }

            $providers[$name] = $config;
        }

        return $providers;
    }

    /**
     * Send a test email to the current user with the saved Mail settings; five in ten minutes.
     *
     * @param Response $response
     * @return Response Notice with the result or the SMTP error.
     */
    public function mailTest(Response $response): Response
    {
        $user = User::current();

        try {
            Auth::limit("mail-test:$user->id", 5, 600);
        } catch (TooManyAttempts) {
            return $response->notify(t('Too many test emails. Try again in a few minutes.'));
        }

        $message = Mail::to($user->email)
            ->subject(t('Test email from :site', (string) Option::get('site.name', '') ?: 'Expansa'))
            ->message('<p>' . t('The mail settings work.') . '</p>');

        $notice = $message->send()
            ? t('Test email sent to :email.', $user->email)
            : t('The email was not sent: :error', $message->error);

        return $response->notify($notice);
    }
}
