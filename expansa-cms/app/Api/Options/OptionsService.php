<?php

declare(strict_types=1);

namespace App\Api\Options;

use App\Models\Menu;
use App\Models\Option;
use App\Models\User;
use App\Support\Llms;
use App\Support\Mailer;
use App\Support\Permalinks;
use App\Support\RoleSettings;
use App\Support\Secrets;
use App\Support\Transliteration;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Facades\Auth;
use Expansa\Facades\Mail;
use Expansa\Http\Exceptions\ValidationFailed;
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

            // the SEO form sends only the name and the tagline of the site
            if ($option === 'site' && is_array($value)) {
                $value = [...(array) Option::get('site', []), ...$value];
                $value['url'] = $this->normalizeSiteUrl((string) ($value['url'] ?? ''));
            }

            if ($option === 'seo' && is_array($value)) {
                $value = $this->normalizeSeo($value);
            }

            if ($option === 'permalinks' && is_array($value)) {
                $value = $this->normalizePermalinks($value);
            }

            if ($option === 'comments' && is_array($value)) {
                $value = $this->normalizeComments($value);
            }

            if ($option === 'multilingual' && is_array($value)) {
                $value = $this->normalizeMultilingual($value);
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

        // the URLs of the post items of the menus follow the permalinks and the site address
        if (isset($options['permalinks']) || isset($options['site'])) {
            Menu::forgetLinks();
        }

        // llms.txt follows the SEO settings at once, not at the next weekly rebuild
        if (isset($options['seo']) && ! Llms::generate()) {
            return $response->notify(t('Options updated, but the llms.txt file could not be written to :path', Llms::PATH));
        }

        return $response->notify(t('Options updated successfully'));
    }

    /**
     * Check the site address: an http(s) URL with a host and no query, without the trailing slash.
     * The installation is complete while it is set, and requests to other hosts are moved to it.
     *
     * @param string $url Form value.
     * @return string
     * @throws ValidationFailed When it is not such an URL.
     */
    private function normalizeSiteUrl(string $url): string
    {
        $url   = rtrim(trim($url), '/');
        $parts = parse_url($url);

        $isValid = is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && ($parts['host'] ?? '') !== ''
            && ! isset($parts['user']) && ! isset($parts['query']) && ! isset($parts['fragment'])
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
        if (! $isValid) {
            throw ValidationFailed::field('site.url', t('Enter the full address of the site, e.g. https://example.com'));
        }

        return $url;
    }

    /**
     * Merge the parts of the permalinks the form sent into the saved ones: the structures, the language
     * in the URLs (shown only with translations) and the transliteration, each checked on its own.
     *
     * @param array<string, mixed> $permalinks Form values.
     * @return array<string, mixed>
     * @throws ValidationFailed When a structure is wrong, see Permalinks::normalize().
     */
    private function normalizePermalinks(array $permalinks): array
    {
        $value = (array) Option::get('permalinks', []);
        if (isset($permalinks['pages'])) {
            $value['pages'] = Permalinks::normalize((array) $permalinks['pages']);
        }

        if (isset($permalinks['language'])) {
            $value['language'] = $permalinks['language'] === 'subdomain' ? 'subdomain' : 'directory';
        }

        // the mode is a radio, it is always sent with the transliteration
        if (isset($permalinks['mode'])) {
            $value = [...$value, ...Transliteration::normalize($permalinks)];
        }

        return $value;
    }

    /**
     * Bring the multilingual settings to their types: the languages as `locale => code`, the keys
     * of the services encrypted.
     *
     * @param array<string, mixed> $multilingual Form values, the languages as `locale[]` and `code[]`.
     * @return array<string, mixed>
     * @throws ValidationFailed When two languages have one code.
     */
    private function normalizeMultilingual(array $multilingual): array
    {
        $locales   = (array) ($multilingual['languages']['locale'] ?? []);
        $codes     = (array) ($multilingual['languages']['code'] ?? []);
        $languages = [];
        foreach ($locales as $index => $locale) {
            $locale = trim((string) $locale);
            $code   = (string) preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($codes[$index] ?? '')));
            if ($locale !== '' && $code !== '') {
                $languages[$locale] = $code;
            }
        }

        if (count(array_unique($languages)) !== count($languages)) {
            throw ValidationFailed::field('multilingual.languages', t('Each language needs its own code.'));
        }

        $values = [
            'languages' => $languages,
            'deepl'     => ['key' => (string) ($multilingual['deepl']['key'] ?? '')],
            'google'    => ['key' => (string) ($multilingual['google']['key'] ?? '')],
        ];

        return Secrets::keep($values, (array) Option::get('multilingual', []), ['deepl.key', 'google.key']);
    }

    /**
     * Bring the SEO settings to their types: switches to booleans, the post types of the sitemap
     * to a list of keys, the profiles without empty ones, the robots.txt rules with `\n` line breaks.
     *
     * @param array<string, mixed> $seo Form values.
     * @return array<string, mixed>
     */
    private function normalizeSeo(array $seo): array
    {
        $seo['organization']['profiles'] = array_values(array_filter(
            array_map(trim(...), (array) ($seo['organization']['profiles'] ?? [])),
            static fn (string $profile) => $profile !== ''
        ));

        foreach ([['llms', 'enabled'], ['robots', 'discourage'], ['robots', 'ai'], ['sitemap', 'enabled']] as [$group, $key]) {
            $seo[$group][$key] = (bool) ($seo[$group][$key] ?? false);
        }

        // the hidden `_` sends the field when no type is checked
        $types = (array) ($seo['sitemap']['types'] ?? []);
        $seo['sitemap']['types'] = array_keys(array_filter($types, static fn ($checked, $type) => $type !== '_' && $checked, ARRAY_FILTER_USE_BOTH));

        $rules = preg_split('/\R/', (string) ($seo['robots']['rules'] ?? '')) ?: [];
        $seo['robots']['rules'] = trim(implode("\n", array_map(rtrim(...), $rules)));

        return $seo;
    }

    /**
     * Bring the Discussions settings to their types: the switches to booleans, false when unchecked
     * as the form does not send them, the days before closing and the levels of threads to numbers.
     *
     * @param array<string, mixed> $comments Form values.
     * @return array<string, mixed>
     */
    private function normalizeComments(array $comments): array
    {
        $switches = [
            'default_status',
            'require_name_email',
            'registration',
            'close_comments_for_old_posts',
            'thread_comments',
            'notify_posts',
            'notify_moderation',
            'moderation',
            'previously_approved',
        ];
        foreach ($switches as $key) {
            $comments[$key] = isset($comments[$key]) && $comments[$key] !== '' && $comments[$key] !== '0';
        }

        $comments['close_comments_days_old'] = max(1, (int) ($comments['close_comments_days_old'] ?? 14));
        $comments['thread_comments_depth']   = max(2, min(10, (int) ($comments['thread_comments_depth'] ?? 5)));
        $comments['flood_interval']          = max(0, (int) ($comments['flood_interval'] ?? 15));
        $comments['fill_time']               = max(0, (int) ($comments['fill_time'] ?? 3));

        return $comments;
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
            ? t('Test email sent to :email', $user->email)
            : t('The email was not sent: :error', $message->error);

        return $response->notify($notice);
    }
}
