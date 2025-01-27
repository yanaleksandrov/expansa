<?php

declare(strict_types=1);

/**
 * Setting up the priority rule for translations.
 *
 * @since 2025.1
 */
Expansa\Hook::configure(EX_PATH . 'app/Listeners');

/**
 * Setting up the priority rule for translations.
 *
 * @since 2025.1
 */
Expansa\I18n::configure(
    [
        EX_CORE      => EX_DASHBOARD,
        EX_DASHBOARD => EX_DASHBOARD,
        EX_PLUGINS   => EX_PLUGINS . ':dirname',
        EX_THEMES    => EX_THEMES . ':dirname',
    ],
    'i18n/%s'
);

/**
 * Add core API endpoints.
 * Important! If current request is request to API, stop code execution after Api::create().
 *
 * @since 2025.1
 */
Expansa\Api::configure('/api', sprintf('%sapp/Api', EX_PATH));

/**
 * Load installed and launch active plugins & themes.
 *
 * @since 2025.1
 */
Expansa\Extensions::enqueue(fn () => [
    ...Expansa\Disk::dir(EX_PLUGINS)->files('*/*.php'),
    ...Expansa\Disk::dir(EX_THEMES)->files('*/*.php'),
]);
