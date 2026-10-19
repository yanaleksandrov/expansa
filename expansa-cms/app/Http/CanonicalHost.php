<?php

declare(strict_types=1);

namespace App\Http;

use App\Models\Option;

/**
 * Serves the site only at the host of its address in the settings. The Host header comes from the
 * client: a page built for a foreign host could put that host into links, e.g. of a password reset
 * email, and hand the token to whoever owns it. Reading and safe requests are moved to the site
 * address, the others are refused with 421 Misdirected Request.
 */
final class CanonicalHost
{
    /**
     * Redirect or stop a request to a host other than the one of the site address; does nothing in
     * the console and while the address is not set.
     *
     * @return void
     */
    public static function handle(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $site  = (string) Option::get('site.url', '');
        $parts = parse_url($site);
        if (! isset($parts['host'])) {
            return;
        }

        $host = strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
        if (strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')) === $host) {
            return;
        }

        if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
            // the request URI already has the path of the site address
            header('Location: ' . ($parts['scheme'] ?? 'https') . '://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        } else {
            http_response_code(421);
        }

        exit;
    }
}
