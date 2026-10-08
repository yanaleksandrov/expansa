<?php

declare(strict_types=1);

namespace Expansa\Ai\Contexts;

use Expansa\Ai\Contracts\Context;

/**
 * Reference material from text files of a directory, such as the CMS documentation.
 * Files are ranked by how often the words of the request occur in them; `always` files come first.
 * A simple keyword search: enough to test generation, a search index suits large documentation better.
 */
final class Files implements Context
{
    /**
     * Stores the directory and the files every request receives.
     */
    public function __construct(

        /**
         * Directory of the files, with a trailing slash.
         */
        public string $directory {
            set => rtrim($value, '/\\') . '/';
        },

        /**
         * File names sent with every request, e.g. the plugin API.
         *
         * @var string[]
         */
        public readonly array $always = [],

        /**
         * Glob pattern of the files inside the directory.
         */
        public readonly string $pattern = '*.md',
    ) {}

    /**
     * Returns the best matching files until the limit is filled; the manager truncates the rest.
     *
     * @param string $input User requirements and prior clarifications
     * @param int $maxTokens Maximum returned context token count
     * @return string Files as Markdown sections
     */
    public function get(string $input, int $maxTokens): string
    {
        preg_match_all('/\p{L}[\p{L}\p{N}_]{3,}/u', mb_strtolower($input), $matches);
        $words = array_unique($matches[0]);

        $scores = [];
        $texts = [];
        foreach (glob($this->directory . $this->pattern) ?: [] as $path) {
            $name = basename($path);
            $texts[$name] = (string) file_get_contents($path);
            $text = mb_strtolower($name . ' ' . $texts[$name]);
            $score = array_sum(array_map(fn (string $word): int => substr_count($text, $word), $words));
            $scores[$name] = in_array($name, $this->always, true) ? PHP_INT_MAX : $score;
        }

        arsort($scores);

        // about four characters per token, so the manager's truncation decides the exact size
        $limit = $maxTokens * 4;
        $context = '';
        foreach ($scores as $name => $score) {
            if ($score === 0 || mb_strlen($context) >= $limit) {
                break;
            }

            $context .= "## {$name}\n\n{$texts[$name]}\n\n";
        }

        return $context;
    }
}
