<?php

declare(strict_types=1);

namespace App\Api\Translations;

use Expansa\Facades\Disk;
use Expansa\Facades\Json;
use Expansa\Facades\Safe;
use Expansa\Http\Request;

final class TranslationsService
{
    /**
     * Get the strings of a project to translate, see scan().
     *
     * @param Request $request
     * @return array{items: list<array{source: string, value: string}>}
     */
    public function get(Request $request): array
    {
        $items = array_map(
            fn (string $source) => ['source' => $source, 'value' => ''],
            $this->scan((string) ($request->post['project'] ?? '')),
        );

        return ['items' => $items];
    }

    /**
     * Find the translatable strings in the PHP files of a project: arguments of I18n::translate()
     * and I18n::translateAttribute(), unique, sorted case-insensitively.
     *
     * @param string $project Folder under the root: `core`, a plugin or theme id.
     * @return list<string>
     */
    public function scan(string $project): array
    {
        $pattern = '/I18n::   # Match the literal "I18n::"
            translate(?:Attribute)?  # translate() or translateAttribute()
            \s*               # Match any whitespace characters (optional)
            \(                # Match the opening parenthesis
            \s*               # Match any whitespace characters (optional)
            [\'"]             # Match either single or double quote
            (.*?)             # Capture the content inside the quotes (non-greedy match)
            [\'"]             # Match either single or double quote
            \s*               # Match any whitespace characters (optional)
            (?:,\s*[^)]+)?    # Optionally match additional parameters before closing parenthesis
            \)                # Match the closing parenthesis
        /ux';                 // Enable extended mode and Unicode support

        $strings = [];
        foreach (Disk::dir(Safe::path(EX_PATH . $project))->files('*.php', 10) as $path) {
            preg_match_all($pattern, (string) Disk::file($path)->read(), $matches);

            $strings = [...$strings, ...$matches[1]];
        }

        $strings = array_values(array_unique($strings));
        usort($strings, strcasecmp(...));

        return $strings;
    }

    public function update(Request $request): array
    {
        [$project, $translations] = Safe::data($request->post, [
            'project'      => 'trim',
            'translations' => 'array',
        ])->values();

        if ($project && $translations) {
            $filepath = sprintf('%s/%s.json', EX_I18N . $project, 'ru');
            Disk::file($filepath)->write(Json::encode($translations), append: false);
        }

        return [];
    }
}
