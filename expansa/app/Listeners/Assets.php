<?php

declare(strict_types=1);

namespace app\Listeners;

use Expansa\Asset;
use Expansa\Database\Db;
use Expansa\Debug;
use Expansa\I18n;

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
            I18n::_t(':queries\Q :memory :memory_peak', count(Db::log()), Debug::timer('getall'), Debug::memory_peak()),
            $content
        );
    }
}
