<?php

declare(strict_types=1);

namespace App\Tables;

use App\Api\Translations\TranslationsService;
use Expansa\Builders\Table;

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
     * Rows of a string: `source` without its context, `context`, and `value` empty for an untranslated string;
     * a translation with plural forms shows its first one and is marked by `forms`, it is edited in the file.
     *
     * @return array<int, array{source: string, context: string, value: string, forms: bool}>
     */
    public function data(): array
    {
        $service = new TranslationsService();
        $project = $this->project ?: 'core';
        $values  = $service->translations($project);
        $keys    = $this->project !== '' ? $service->scan($project) : TranslationsService::sort(array_keys($values));

        return array_map(
            function (int|string $key) use ($values): array {
                $value = $values[$key] ?? '';

                return [
                    'source'  => TranslationsService::text((string) $key),
                    'context' => TranslationsService::context((string) $key),
                    'value'   => (string) (is_array($value) ? reset($value) : $value),
                    'forms'   => is_array($value),
                ];
            },
            $keys,
        );
    }

    public function cells(): array
    {
        return [
            $this->cell('source')
                ->title(t('Source Text'))
                ->view('raw'),
            $this->cell('value')
                ->title(t('Translations'))
                ->view('text'),
        ];
    }

    /**
     * All strings on one page: the form saves the strings it shows.
     *
     * @return int
     */
    public function perPage(): int
    {
        return 0;
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
