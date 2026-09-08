<?php

declare(strict_types=1);

namespace App\Http;

use Expansa\Http\Response;
use Expansa\Security\Csrf\Csrf;
use Expansa\Security\Csrf\Providers\NativeCookieProvider;
use Expansa\Security\Exception\InvalidCsrfTokenException;

/**
 * Verifies the CSRF token on state-changing API requests — double-submit cookie pattern.
 *
 * The token lives in a plain (JS-readable) cookie. The client reads it itself with
 * `document.cookie` on every request and echoes it back via the X-CSRF-Token header —
 * see youla-ajax.js. Nothing is embedded into rendered HTML: a page can be cached,
 * reused across tabs, whatever — the client always reads the live cookie value, never
 * a value baked into a page at render time, so there's nothing to go stale.
 *
 * A cross-origin attacker's page can't replicate this: the browser still attaches the
 * cookie to a forged request automatically, but Same-Origin Policy stops that page's
 * script from reading the cookie's value to also set it as a header — which is the
 * whole point, and why "submitted value" and "reference value" being the same cookie
 * is fine here, unlike the old code that compared it against itself server-side with
 * no client-supplied value in the loop at all.
 *
 * check() runs with the default (non-`multiple`) mode, which re-issues the cookie with
 * a fresh Max-Age on every successful check — i.e. the token keeps sliding forward on
 * its own as long as the user stays active, instead of expiring on a fixed page-load
 * clock. Reading the cookie live (not a cached JS value) is what makes that safe: the
 * very next request already sees whatever the previous response just refreshed it to.
 */
final class VerifyCsrfToken
{
    private const string KEY = 'token';

    /**
     * Route "before" middleware. Ends the request with a 403 JSON response on failure —
     * unlike the previous version, a failed check no longer lets the request continue.
     */
    public function handle(): void
    {
        // A raw $_SERVER read, not a full Request::createFromGlobals() — this middleware
        // needs exactly one header, and Kernel::dispatch() builds a real Request for the
        // controller moments later anyway; building one here too would just be doing the
        // (non-trivial: parses every superglobal, reads and JSON-decodes the request body)
        // work twice on every single mutating request.
        $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        try {
            $this->csrf()->check(self::KEY, $token, 3600);
        } catch (InvalidCsrfTokenException) {
            $response = new Response()->json(['message' => t('Invalid or missing CSRF token.')], 403);

            // HEAD must never carry a body — Response::prepare(Request) would normally
            // enforce this, kept here as a plain check instead of pulling in a Request.
            if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
                $response->setContent(null);
            }

            $response->send();
            exit;
        }
    }

    /**
     * Seeds the cookie if this is the first time this browser is seen (sign-in, install,
     * a fresh dashboard load). A no-op in spirit once one already exists — check() itself
     * keeps refreshing it from then on — but harmless to call again either way.
     */
    public static function seed(): void
    {
        new self()->csrf()->generate(self::KEY);
    }

    private function csrf(): Csrf
    {
        return new Csrf(new NativeCookieProvider());
    }
}
