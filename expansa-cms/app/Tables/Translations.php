<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;
use Expansa\Facades\Disk;
use Expansa\Facades\I18n;
use Expansa\Facades\Json;

final class Translations extends Table
{
    public function data(): array
    {
        $filepath = EX_DASHBOARD . sprintf('i18n/%s.json', I18n::locale());
        $filetext = Disk::file($filepath)->read();

        $json  = Json::decode($filetext, true);

        $data = [];
        foreach ($json as $source => $value) {
            $data['items'][] = [ 'source' => $source, 'value' => $value ];
        }

        return $data;
    }

    public function columns(): array
    {
        return [
            $this->cell('source')
                ->title(t(':icon Source text - English', '<i class="ph ph-text-aa"></i>'))
                ->attributes([ 'class' => 'translation__source' ])
                ->view('raw'),
            $this->cell('value')
                ->title(t(':icon Translations - Russian', '<i class="ph ph-globe-hemisphere-east"></i>'))
                ->attributes([ 'class' => 'translation__value' ])
                ->view('translation'),
        ];
    }
}
