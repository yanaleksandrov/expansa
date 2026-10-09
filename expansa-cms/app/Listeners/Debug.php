<?php

declare(strict_types=1);

namespace App\Listeners;

use Expansa\Facades\Db;
use Expansa\Facades\Debug as DebugFacade;
use Expansa\Facades\Lifecycle;
use Expansa\Facades\Panel;
use Expansa\Support\Is;

/**
 * Debug panel at the bottom of the dashboard in debug mode: metrics, the lifecycle timeline, the queries
 * and the request. Plugins add their sections with Panel::add().
 */
final class Debug
{
    public function dashboardLoaded(string $content): string
    {
        $position = strripos($content, '</body>');
        if (! Is::debug() || $position === false) {
            return $content;
        }

        Panel::add('Metrics', static fn () => [
            'time'         => metrics()->time(),
            'memory peak'  => metrics()->memory(),
            'memory limit' => metrics()->memoryPercent() === null ? 'unlimited' : metrics()->memoryPercent() . '%',
        ]);

        Panel::add('Timeline', static fn () => array_map(fn (array $step) => [
            'type'   => $step['type'],
            'name'   => $step['name'],
            'time'   => $step['time'] . 'ms',
            'memory' => round($step['memory'] / 1024, 1) . 'KB',
        ], Lifecycle::timeline()));

        Panel::add('Queries', static fn () => array_map(fn (string $query) => ['query' => $query], Db::log()));
        Panel::add('Request', static fn () => DebugFacade::getContext());

        $html = '<link rel="stylesheet" id="debug-css" href="/dashboard/assets/css/debug.css">' . Panel::render();

        return substr_replace($content, $html, $position, 0);
    }
}
