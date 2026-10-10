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
        Asset::render(['toFooter' => true], combine: false, minify: false, inline: false);
    }

    public function dashboardLoaded(string $content): string
    {
        return str_replace(
            '0Q 0.001s 999kb',
            sprintf('%dQ %s %s', count(Db::log()), metrics()->time(), metrics()->memory()),
            $content
        );
    }
}
