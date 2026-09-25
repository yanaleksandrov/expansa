<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\VerifyCsrfToken;
use Expansa\Facades\Asset;
use Expansa\Support\Is;

/**
 * Enqueues dashboard CSS/JS by name from dashboard/assets, shared by the install, auth and dashboard contexts.
 */
final class DashboardAssets
{
    /**
     * Enqueue styles and scripts in the given order, minified outside debug mode. Also seeds the CSRF
     * cookie that youla-ajax.js sends back with every request.
     *
     * @param string[]                 $styles  Style names, e.g. ['expansa', 'controls'].
     * @param array<int|string, mixed> $scripts Script names, or name => extra Asset::script() data.
     */
    public static function enqueue(array $styles, array $scripts): void
    {
        VerifyCsrfToken::seed();

        $suffix = self::suffix();

        foreach ($styles as $style) {
            Asset::style($style, url("/dashboard/assets/css/$style$suffix.css"));
        }

        foreach ($scripts as $key => $value) {
            [$script, $data] = is_int($key) ? [$value, []] : [$key, $value];

            Asset::script($script, url("/dashboard/assets/js/$script$suffix.js"), $data);
        }
    }

    public static function suffix(): string
    {
        return Is::debug() ? '' : '.min';
    }
}
