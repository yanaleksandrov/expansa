<?php

namespace Dashboard;

use App\Support\DashboardAssets;
use App\Support\DashboardFavicons;

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
DashboardFavicons::enqueue();

DashboardAssets::enqueue(
    ['phosphor', 'expansa', 'controls', 'utility', 'notifications'],
    [
        // only `apiurl`: youla-ajax.js builds the $ajax.post() base URL from it
        'youla' => ['data' => ['apiurl' => url('/api/')]],
        'youla-ajax',
        'youla-expansa',
    ]
);
