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

        $data = [];
        if ($filetext) {
            $json  = Json::decode($filetext, true);

            foreach ($json as $source => $value) {
                $data[] = compact('source', 'value');
            }
        }

        return $data;
    }

    public function cells(): array
    {
        return [
            $this->cell('source')->title(t('Source text'))->view('raw'),
            $this->cell('value')->title(t('Translations'))->view('text'),
        ];
    }

    public function headData(): array
    {
        return [
            'title'       => t('Translations'),
            'badge'       => t(':stringsCount of :allStringsCount completed', 56, 408) . '<i class="t-green">(25%)</i>',
            'translation' => true,
        ];
    }

    public function notFoundData(): array
    {
        return [
            'title'       => t('No translations found'),
            'description' => t("Click the 'Scan' button to load translatable strings from the source code."),
        ];
    }
}
