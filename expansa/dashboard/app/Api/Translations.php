<?php

namespace dashboard\app\Api;

use Expansa\Dir;
use Expansa\File;
use Expansa\Json;
use Expansa\Safe;

class Translations extends \Expansa\Api\Handler
{
    /**
     * Endpoint name.
     */
    public string $endpoint = 'translations';

    /**
     * Get media files.
     *
     * @since 2025.1
     */
    public static function get(): array
    {
        $dirpath = Safe::path(EX_PATH . ( $_POST['project'] ?? '' ));
        $paths   = ( new Dir($dirpath) )->getFiles('*.php', 10);

        $result = [];
        foreach ($paths as $path) {
            $content = ( new File($path) )->read();

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
            /ux';                 # Enable extended mode and Unicode support

            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

            // extracting the found strings into a separate array
            $i18nStrings = array_map(fn($match) => $match[1] ?? '', $matches);
            if ($i18nStrings) {
                $result = [ ...$result, ...$i18nStrings ];
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
            $i18nStrings = array_filter([ $matches[2] ?? null, $matches[4] ?? null ]);
            if ($i18nStrings) {
                $result = [ ...$result, ...$i18nStrings ];
            }

            usort($result, fn($a, $b) => strcasecmp($a, $b));
        }

        $result = array_values(array_unique($result));

        $result = array_map(function ($item) {
            return [
                'source' => $item,
                'value'  => '',
            ];
        }, $result);

        return [
            'items' => $result,
        ];
    }

    /**
     * Update item by ID.
     *
     * @url    PUT api/posts/$id
     */
    public static function update(): array
    {
        [ $project, $translations ] = Safe::data(
            $_REQUEST,
            [
                'project'      => 'trim',
                'translations' => 'array',
            ]
        )->values();

        if ($project && $translations) {
            $content  = Json::encode($translations);
            $filepath = sprintf('%s/%s.json', EX_I18N . $project, 'ru');

            $file = ( new File($filepath) )->write($content, false);
            //$file = Disk::file( $filepath )->write( $content, false );
        }
        return [];
    }
}
