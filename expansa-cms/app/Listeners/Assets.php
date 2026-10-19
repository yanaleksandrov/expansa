<?php

declare(strict_types=1);

namespace App\Listeners;

use Expansa\Facades\Asset;
use Expansa\Facades\Db;

final class Assets
{
    public function renderDashboardHeader(): void
    {
        Asset::render(['toFooter' => false], combine: false, minify: false, inline: false);
    }

    public function renderDashboardFooter(): void
    {
        // the styles field templates discover while the page renders, after the head is out
        Asset::render(['toFooter' => false], combine: false, minify: false, inline: false);
        Asset::render(['toFooter' => true], combine: false, minify: false, inline: false);
    }

    public function dashboardLoaded(string $content): string
    {
        return str_replace(
            '0Q 0.001s 999kb',
            // always in seconds, like the placeholder
            sprintf('%dQ %.3fs %s', count(Db::log()), metrics()->time(true), metrics()->memory()),
            $content
        );
    }
}
