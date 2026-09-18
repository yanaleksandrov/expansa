<?php

declare(strict_types=1);

namespace App\Support;

use Expansa\Facades\Asset;

/**
 * Favicon/manifest links shared by every dashboard-family bootstrap - both the
 * authenticated dashboard shell ({@see EX_PATH}/dashboard/index.php) and the
 * unauthenticated sign-in/sign-up/reset-password pages ({@see EX_PATH}/dashboard/auth.php)
 * show the same tab icon, so it's kept in one place instead of two copies
 * drifting apart.
 */
final class DashboardFavicons
{
    public static function enqueue(): void
    {
        foreach (
            [
                'favicon'   => [
                    'href'  => url('/dashboard/assets/favicon/favicon-96x96.png'),
                    'rel'   => 'icon',
                    'type'  => 'image/png',
                    'sizes' => '96x96',
                ],
                'favicon-svg'  => [
                    'href' => url('/dashboard/assets/favicon/favicon.svg'),
                    'rel'  => 'icon',
                    'type' => 'image/svg+xml',
                ],
                'favicon-ico'  => [
                    'href' => url('/dashboard/assets/favicon/favicon.ico'),
                    'rel'  => 'shortcut icon',
                    'type' => '',
                ],
                'favicon-apple' => [
                    'href'  => url('/dashboard/assets/favicon/apple-touch-icon.png'),
                    'rel'   => 'apple-touch-icon',
                    'sizes' => '180x180',
                    'type'  => '',
                ],
                'manifest'  => [
                    'href' => url('/dashboard/assets/favicon/site.webmanifest'),
                    'rel'  => 'manifest',
                    'type' => '',
                ],
            ] as $uid => $asset
        ) {
            Asset::style($uid, $asset['href'], $asset);
        }
    }
}
