<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use Expansa\Facades\I18n;

/**
 * Settings of the site that templates show, shared with every view as `$site`:
 * name, language, charset, addresses and version, read only when a template asks for them.
 *
 * @package App\Support
 */
final class Site
{
    /**
     * Name of the site, "Expansa" until it is set.
     */
    public string $name {
        get => (string) Option::get('site.name', '') ?: 'Expansa';
    }

    /**
     * Language of the pages, e.g. `ru-RU`.
     */
    public string $locale {
        get => I18n::locale();
    }

    /**
     * Charset of the pages, UTF-8 by default.
     */
    public string $charset {
        get => (string) Option::get('charset', 'UTF-8');
    }

    /**
     * Address of the site, e.g. `https://example.com/`.
     */
    public string $url {
        get => url();
    }

    /**
     * Address of the dashboard, e.g. `https://example.com/dashboard`.
     */
    public string $dashboard {
        get => url('dashboard');
    }

    /**
     * Version of Expansa, e.g. `2027.6`.
     */
    public string $version {
        get => EX_VERSION;
    }
}
