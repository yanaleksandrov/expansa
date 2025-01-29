<?php

declare(strict_types=1);

namespace app\Listeners;

use Expansa\Asset;
use Expansa\Db;

final class Assets
{
    public function renderDashboardHeader(): void
    {
        Asset::render('*.css');
    }

    public function renderDashboardFooter(): void
    {
        Asset::render('*.js');
    }

    public function dashboardLoaded(string $content): string
    {
        return str_replace(
            '0Q 0.001s 999kb',
            t(':queries\Q :memory :memory_peak', count(Db::log()), metric()->time(), metric()->memory()),
            $content
        );
    }
}
