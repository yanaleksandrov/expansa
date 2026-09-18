<?php

namespace Dashboard;

use App\Http\VerifyCsrfToken;
use App\Support\DashboardFavicons;
use Expansa\Facades\Asset;
use Expansa\Support\Is;

/**
 * Lightweight asset bootstrap for the unauthenticated auth pages (sign-in,
 * sign-up, reset-password). These render through the same `welcome.blade.php`
 * shell as the installer, but a visitor here is by definition not logged in,
 * so none of the actual admin panel (menus, dialogs, the users/posts tables,
 * the API-key lookup baked into the dashboard's `youla` data dump, per-field
 * vendor JS such as youla-select/pickadate/filler, ...) is reachable - only
 * the CSS the login/registration forms are styled with and the JS runtime
 * that submits them (`$ajax.post(...)`, see dashboard/forms/user-sign-in.php)
 * and renders notifications/the password-strength widget.
 *
 * @see index.php for the equivalent bootstrap once a user is actually logged in.
 */
new class
{
    public function __construct()
    {
        VerifyCsrfToken::seed();

        DashboardFavicons::enqueue();

        $suffix = ! Is::debug() ? '.min' : '';

        $styles = ['phosphor', 'expansa', 'controls', 'utility', 'notifications'];
        foreach ($styles as $style) {
            Asset::style($style, url("/dashboard/assets/css/$style$suffix.css"));
        }

        $scripts = ['youla', 'youla-ajax', 'youla-expansa'];
        foreach ($scripts as $script) {
            // Only `apiurl` - youla-ajax.js reads it to build the $ajax.post() base URL.
            // Everything else in the dashboard's own `youla` data dump (API-key table,
            // dialog titles, datepicker locale, ...) belongs to admin-only widgets that
            // never render here.
            $data = $script === 'youla' ? ['data' => ['apiurl' => url('/api/')]] : [];

            Asset::script($script, url("/dashboard/assets/js/$script$suffix.js"), $data);
        }
    }
};
