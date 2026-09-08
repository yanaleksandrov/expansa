<?php

declare(strict_types=1);

namespace App\Api\Translations;

use Expansa\Facades\Disk;
use Expansa\Facades\Json;
use Expansa\Facades\Safe;

final class TranslationsService
{
    /**
     * Scans a project's PHP files for translatable strings (t()/t_attr()/_c() calls).
     *
     * Fixed from the legacy code: it referenced `Dir` and `File` classes that don't
     * exist anywhere in the framework (would fatal the moment this ran) — the real,
     * equivalent API is Disk::dir()->files() / Disk::file()->read().
     */
    public function get(string $project): array
    {
        $dirpath = Safe::path(EX_PATH . $project);
        $paths   = Disk::dir($dirpath)->files('*.php', 10);

        $result = [];
        foreach ($paths as $path) {
            $content = Disk::file($path)->read();

            $pattern = '/I18n::   # Match the literal "I18n::"
                (?:_?t|_?t_attr)  # Non-capturing group, matches "_t(f)" or "_t(f)_attr" functions
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

            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

            // extracting the found strings into a separate array
            $i18nStrings = array_map(fn($match) => $match[1] ?? '', $matches);
            if ($i18nStrings) {
                $result = [...$result, ...$i18nStrings];
            }

            // regular expression pattern with comments
            $pattern = '/
				I18n::                   # Match the literal "I18n::"
				_?с(?:_attr)?            # Function names matches: с, _с, с_attr, _с_attr
				\s*\(                    # Opening parenthesis with optional spaces
				[^,]+,                   # First parameter (anything up to the first comma)
				\s*([\'"])(.*?)\1        # Second parameter: string in single or double quotes
				\s*,\s*                  # Comma with optional spaces
				([\'"])(.*?)\3           # Third parameter: string in single or double quotes
			/x';

            preg_match_all($pattern, $content, $matches);

            // extracting the found strings
            $i18nStrings = array_filter([$matches[2] ?? null, $matches[4] ?? null]);
            if ($i18nStrings) {
                $result = [...$result, ...$i18nStrings];
            }

            usort($result, fn($a, $b) => strcasecmp($a, $b));
        }

        $result = array_values(array_unique($result));

        $result = array_map(fn($item) => [
            'source' => $item,
            'value'  => '',
        ], $result);

        return [
            'items' => $result,
        ];
    }

    public function update(array $input): array
    {
        [$project, $translations] = Safe::data($input, [
            'project'      => 'trim',
            'translations' => 'array',
        ])->values();

        if ($project && $translations) {
            $filepath = sprintf('%s/%s.json', EX_I18N . $project, 'ru');
            Disk::file($filepath)->write(Json::encode($translations), false);
        }

        return [];
    }
}
