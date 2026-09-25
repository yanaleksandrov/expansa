<?php

namespace Dashboard;

use App\Support\DashboardAssets;

/**
 * Asset bootstrap for the installer, run by the "install" lifecycle context.
 *
 * @see auth.php for the equivalent bootstrap of the sign-in pages.
 */
DashboardAssets::enqueue(
    ['expansa', 'controls', 'utility', 'phosphor'],
    [
        'youla' => [
            'data' => [
                'apiurl'   => url('/api/'),
                'flagsUrl' => url('/dashboard/assets/sprites/flags.svg'),
            ],
        ],
        'youla-ajax',
        'youla-select',
        'youla-expansa',
    ]
);
