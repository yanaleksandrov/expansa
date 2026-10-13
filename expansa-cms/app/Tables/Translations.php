<?php

declare(strict_types=1);

namespace App\Tables;

use App\Api\Translations\TranslationsService;
use Expansa\Builders\Table;
use Expansa\Facades\Disk;
use Expansa\Facades\I18n;
use Expansa\Facades\Json;

/**
 * Strings of the dashboard with their translations to the current language; with a project,
 * the strings found in its code.
 *
 * @package App\Tables
 */
final class Translations extends Table
{
    public function __construct(

        /**
         * Project scanned for the strings: `core`, a plugin or theme id; empty for the saved strings.
         */
        public readonly string $project = '',
    ) {
        parent::__construct();
    }

    /**
     * Rows of `source` and `value`, the value is empty for an untranslated string.
     *
     * @return array<int, array{source: string, value: string}>
     */
    public function data(): array
    {
        $text   = Disk::file(EX_DASHBOARD . sprintf('i18n/%s.json', I18n::locale()))->read();
        $values = $text ? (array) Json::decode($text, true) : [];

        $sources = $this->project !== '' ? new TranslationsService()->scan($this->project) : array_keys($values);

        return array_map(
            fn (int|string $source) => ['source' => (string) $source, 'value' => (string) ($values[$source] ?? '')],
            $sources,
        );
    }

    public function cells(): array
    {
        return [
            $this->cell('source')->title(t('Source Text'))->view('raw'),
            $this->cell('value')->title(t('Translations'))->view('text'),
        ];
    }

    public function headData(): array
    {
        return [
            'title'       => t('Translations'),
            'badge'       => t(':stringsCount of :allStringsCount completed', 56, 408) . '<i class="t-green">(25%)</i>',
            'translation' => true,
            'project'     => $this->project,
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
