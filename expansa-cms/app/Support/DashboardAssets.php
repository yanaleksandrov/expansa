<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\VerifyCsrfToken;
use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Support\Is;

/**
 * Enqueues dashboard CSS/JS by name from dashboard/assets, shared by the install, auth and dashboard contexts.
 */
final class DashboardAssets
{
    /**
     * Whether the notifications markup is already hooked into the page footer.
     */
    private static bool $notifications = false;

    /**
     * Enqueue styles and scripts in the given order, minified outside debug mode. Also seeds the CSRF
     * cookie that youla-ajax.js sends back with every request and adds the notifications of `$notice`
     * and the `notify` API fragments: every page with the dashboard runtime shows them the same way.
     * The `youla` data also gets `ajax`, the settings of youla-ajax.js; youla-expansa.js puts them into `Youla.ajax`.
     *
     * @param string[]                 $styles  Style names, e.g. ['expansa', 'controls'].
     * @param array<int|string, mixed> $scripts Script names, or name => extra Asset::script() data.
     */
    public static function enqueue(array $styles, array $scripts): void
    {
        VerifyCsrfToken::seed();

        $suffix = self::suffix();

        if (isset($scripts['youla'])) {
            $scripts['youla']['data']['ajax'] ??= self::ajax();
        }

        foreach (array_unique([...$styles, 'notifications']) as $style) {
            Asset::style($style, url("/dashboard/assets/css/$style$suffix.css"));
        }

        foreach ($scripts as $key => $value) {
            [$script, $data] = is_int($key) ? [$value, []] : [$key, $value];

            Asset::script($script, url("/dashboard/assets/js/$script$suffix.js"), $data);
        }

        if (! self::$notifications) {
            self::$notifications = true;

            Hook::add('renderDashboardFooter', function () {
                echo view('parts/notifications');
            });
        }
    }

    /**
     * Settings of youla-ajax.js: the API address, the CSRF cookie and header, where the errors of
     * a field go in the form builder markup and the texts of a request that failed without a message.
     *
     * @return array<string, mixed>
     */
    private static function ajax(): array
    {
        return [
            'baseURL'  => url('/api/'),
            'csrf'     => ['cookie' => VerifyCsrfToken::COOKIE, 'header' => VerifyCsrfToken::HEADER],
            'errors'   => [
                'field'        => '[data-error="{name}"]',
                'wrapper'      => '.field',
                'anchor'       => '.field-item',
                'messageClass' => 'field-error',
                'invalidClass' => 'is-invalid',
            ],
            'messages' => [
                'failed'  => t('Something went wrong. Please try again later.'),
                'network' => t('No connection. Check the internet and try again.'),
            ],
        ];
    }

    public static function suffix(): string
    {
        return Is::debug() ? '' : '.min';
    }
}
