<?php

declare(strict_types=1);

namespace app\Tables;

use Expansa\Builders\Table\Cell;
use Expansa\Builders\Table\Row;
use Expansa\Disk;
use Expansa\Hook;
use Expansa\I18n;
use Expansa\Json;

final class Translations
{
    public function data(): array
    {
        Hook::add('expansa_dashboard_data', function ($data) {
            $filepath = EX_DASHBOARD . sprintf('i18n/%s.json', I18n::locale());
            $filetext = Disk::file($filepath)->read();

            $json  = Json::decode($filetext, true);

            foreach ($json as $source => $value) {
                $data['items'][] = [ 'source' => $source, 'value' => $value ];
            }

            return $data;
        });

        return [ 435 ];
    }

    public function dataBefore(): string
    {
        return '<form class="translation" method="POST" @input.debounce.500ms="$ajax(\'translations/update\',{project})">';
    }

    public function dataAfter(): string
    {
        return '</form>';
    }

    public function rows(): array
    {
        return [
            Row::add()->attribute('class', 'translation__grid'),
        ];
    }

    public function columns(): array
    {
        return [
            Cell::add('source')
                ->title(t(':icon Source text - English', '<i class="ph ph-text-aa"></i>'))
                ->attributes([ 'class' => 'translation__source' ])
                ->view('raw'),
            Cell::add('value')
                ->title(t(':icon Translations - Russian', '<i class="ph ph-globe-hemisphere-east"></i>'))
                ->attributes([ 'class' => 'translation__value' ])
                ->view('translation'),
        ];
    }

    public function attributes(): array
    {
        return [
            'class' => 'table',
        ];
    }

    public function headerContent(): array
    {
        return [
            'title'       => t('Translations'),
            'badge'       => t('completed :stringsCount from :allStringsCount <i class="t-green">(:percent%)</i>', 56, 408, 25),
            'translation' => true,
        ];
    }

    public function notFoundContent(): array
    {
        return [
            'title'       => t('Translates not found'),
            'description' => t("Click the 'Scan' button to get started and load the strings to be translated from the source code."),
        ];
    }
}
