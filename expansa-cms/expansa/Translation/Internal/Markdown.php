<?php

declare(strict_types=1);

namespace Expansa\Translation\Internal;

/**
 * Regex-based subset of Markdown for translations: headers, quotes, bold, italic, images and links.
 * Links allow only URL schemes without scripts.
 *
 * @internal
 * @package Expansa\Translation\Internal
 */
final class Markdown
{
    /**
     * Render Markdown into HTML, the result is trimmed.
     * A quote may start with `&gt;`: translations escape the string before rendering.
     *
     * @param string $text
     * @return string
     */
    public static function render(string $text): string
    {
        // most translations are plain text: skip the regular expressions
        if (strpbrk($text, '#>*_[') === false && ! str_contains($text, '&gt;')) {
            return trim($text);
        }

        // each expression runs only when its marker is in the text
        $text = "\n" . $text . "\n";

        if (str_contains($text, '#')) {
            $text = self::headers($text);
        }

        if (str_contains($text, '&gt;') || str_contains($text, '>')) {
            $text = preg_replace('#^(?:>|&gt;) \s*(.*)$#mx', '<blockquote>$1</blockquote>', $text);
        }

        if (str_contains($text, '*')) {
            $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
            $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
        }

        if (str_contains($text, '_')) {
            // as in CommonMark, underscores inside a word stay: upload_max_filesize, EX_AI_KEY; invalid UTF-8 keeps the text as is
            $text = preg_replace('/(?<![\p{L}\p{N}_])__(?=\S)(.+?)(?<=\S)__(?![\p{L}\p{N}_])/su', '<strong>$1</strong>', $text) ?? $text;
            $text = preg_replace('/(?<![\p{L}\p{N}_])_(?=\S)(.+?)(?<=\S)_(?![\p{L}\p{N}_])/su', '<em>$1</em>', $text) ?? $text;
        }

        if (str_contains($text, '](')) {
            $text = self::images($text);
            $text = preg_replace(
                '/(?<!\!)\[([^\[]+)\]\((https?|mailto|tel|file|ws|ftp|sftp|git|svn):\/\/([^\)]+)\)/',
                '<a href="$2://$3">$1</a>',
                $text
            );
        }

        return trim($text);
    }

    /**
     * Convert atx headers: `## Header` → `<h2>Header</h2>`.
     *
     * @param string $text
     * @return string
     */
    private static function headers(string $text): string
    {
        return preg_replace_callback(
            '{
                ^(\#{1,6})   # (1) level
                [ ]*
                (.+?)        # (2) text
                [ ]*
                \#*          # optional closing #
                \n+
            }xm',
            fn (array $matches): string => '<h' . strlen($matches[1]) . '>' . $matches[2] . '</h' . strlen($matches[1]) . '>',
            $text
        );
    }

    /**
     * Convert images: `![Alt](url)` → `<img src="url" alt="Alt"/>`.
     *
     * @param string $text
     * @return string
     */
    private static function images(string $text): string
    {
        return preg_replace_callback(
            '#!\[([^\]]*)\]\(([^)]+)\)#',
            function (array $matches): string {
                $url = filter_var($matches[2], FILTER_SANITIZE_URL);
                $alt = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');

                return $alt !== '' ? sprintf('<img src="%s" alt="%s"/>', $url, $alt) : sprintf('<img src="%s"/>', $url);
            },
            $text
        );
    }
}
