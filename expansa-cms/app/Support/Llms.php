<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use App\Post\Type;
use Expansa\Facades\Db;
use Expansa\Facades\Hook;

/**
 * The llms.txt file of the site (https://llmstxt.org): a Markdown list of its main pages and latest content
 * for AI tools. It is a static file in the root of the site, so the web server gives it without PHP;
 * the scheduler rebuilds it weekly, saving the SEO settings at once.
 *
 * @package App\Support
 */
final class Llms
{
    /**
     * Path of the file, next to index.php.
     */
    public const string PATH = EX_PATH . 'llms.txt';

    /**
     * Number of the latest items of each post type in the file.
     */
    private const int LATEST = 10;

    /**
     * Number of the pages chosen by the automatic selection.
     */
    private const int PAGES = 50;

    /**
     * Length of an item description, in characters.
     */
    private const int SUMMARY = 160;

    /**
     * Write the file when it is enabled in the SEO settings, otherwise delete it.
     *
     * @return bool Whether the file is in the state of the settings.
     */
    public static function generate(): bool
    {
        if (! Option::get('seo.llms.enabled', false)) {
            return ! is_file(self::PATH) || unlink(self::PATH);
        }

        // a temporary file and rename(): the web server never gives a half-written file
        $temporary = self::PATH . '.' . getmypid();
        if (file_put_contents($temporary, self::render(), LOCK_EX) === false) {
            return false;
        }

        if (! rename($temporary, self::PATH)) {
            unlink($temporary);

            return false;
        }

        return true;
    }

    /**
     * Get the content of the file: the site name, its tagline, the pages and the latest content of each post type.
     *
     * @return string
     */
    public static function render(): string
    {
        $site    = new Site();
        $tagline = self::line((string) Option::get('site.tagline', ''));
        $text    = '# ' . self::line($site->name) . "\n" . ($tagline === '' ? '' : "\n> $tagline\n");

        $sections = [t('Pages') => self::pages()];
        foreach (Type::fetch(['public' => true, 'searchable' => true]) as $type) {
            if ($type->key !== 'pages') {
                $sections[$type->labelNamePlural] = self::latest($type->table);
            }
        }

        /**
         * Sections of llms.txt: Markdown lines of the links by the section title.
         *
         * @param array<string, list<string>> $sections
         */
        foreach (Hook::call('llmsSections', $sections) as $title => $links) {
            if ($links !== []) {
                $text .= "\n## " . self::line((string) $title) . "\n\n" . implode("\n", $links) . "\n";
            }
        }

        return $text;
    }

    /**
     * Get the links of the pages: the ones chosen in the SEO settings for the manual selection,
     * otherwise the published pages by their position.
     *
     * @return list<string>
     */
    private static function pages(): array
    {
        if (Option::get('seo.llms.selection', 'automatic') !== 'manual') {
            return self::links('pages', ['status' => 'publish', 'ORDER' => ['position' => 'ASC', 'title' => 'ASC'], 'LIMIT' => self::PAGES]);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) Option::get('seo.llms.pages', [])))));
        if ($ids === []) {
            return [];
        }

        // the order of the settings: about, contact, terms, privacy
        $links = self::links('pages', ['id' => $ids, 'status' => 'publish']);
        $order = array_flip($ids);
        uksort($links, fn (int $a, int $b) => $order[$a] <=> $order[$b]);

        return array_values($links);
    }

    /**
     * Get the links of the latest published items of a post type.
     *
     * @param string $table Table of the post type.
     * @return list<string>
     */
    private static function latest(string $table): array
    {
        return array_values(self::links($table, ['status' => 'publish', 'ORDER' => ['updated_at' => 'DESC'], 'LIMIT' => self::LATEST]));
    }

    /**
     * Get the Markdown links of the items with a slug: `- [Title](url): summary`.
     *
     * @param string               $table Table of the post type.
     * @param array<string, mixed> $where Conditions of the query.
     * @return array<int, string> Links by the item ID, in the order of the query.
     */
    private static function links(string $table, array $where): array
    {
        $rows = Db::select($table, ['id [Int]', 'title', 'content'], $where) ?? [];
        if ($rows === []) {
            return [];
        }

        $slugs = array_column(
            Db::select('slugs', ['entity_id [Int]', 'slug'], ['entity_table' => $table, 'entity_id' => array_column($rows, 'id')]) ?? [],
            'slug',
            'entity_id'
        );

        $links = [];
        foreach ($rows as $row) {
            if (! isset($slugs[$row['id']])) {
                continue;
            }

            $title   = str_replace(['[', ']'], ['\[', '\]'], self::line((string) $row['title'])) ?: $slugs[$row['id']];
            $summary = self::summary((string) $row['content']);

            $links[$row['id']] = "- [$title](" . url($slugs[$row['id']]) . ')' . ($summary === '' ? '' : ": $summary");
        }

        return $links;
    }

    /**
     * Get the beginning of the text of an item without markup, cut by a word.
     *
     * @param string $content
     * @return string
     */
    private static function summary(string $content): string
    {
        $text = self::line(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (mb_strlen($text) <= self::SUMMARY) {
            return $text;
        }

        $text = mb_substr($text, 0, self::SUMMARY);
        $last = mb_strrpos($text, ' ');

        return rtrim($last === false ? $text : mb_substr($text, 0, $last), " ,.;:-") . '…';
    }

    /**
     * Get a text as one line: Markdown of the file is line-based.
     *
     * @param string $text
     * @return string
     */
    private static function line(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
