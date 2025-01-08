<?php

declare(strict_types=1);

namespace Expansa\Translation;

/**
 * A simple regex-based Markdown parser.
 *
 * @package Expansa
 */
class Markdown
{
    /**
     * Constructor for the Markdown class.
     *
     * Initializes the Markdown parser with default rules for converting Markdown elements to HTML.
     * The constructor is private to prevent external instantiation of the class.
     *
     * @param array $rules An optional array of rules to extend the default Markdown parsing capabilities.
     */
    private function __construct(
        private readonly array $rules = [
            'headers',
            'blockquote',
            'bold',
            'italic',
            'image',
            'link',
            //'code',
        ]
    ) {} // phpcs:ignore

    /**
     * Render some Markdown into HTML.
     *
     * This method processes a Markdown string and converts it into HTML based on predefined rules.
     *
     * @param string $text The Markdown text to be converted.
     * @return string      The converted HTML.
     */
    public static function render(string $text): string
    {
        $markdown = new self();

        $text = "\n" . $text . "\n";
        foreach ($markdown->rules as $rule) {
            if (! method_exists($markdown, $rule)) {
                throw new \InvalidArgumentException("Unknown markdown rule: $rule");
            }

            $text = $markdown->$rule($text);
        }
        return trim($text);
    }

    /**
     * Convert Markdown headers into HTML headers.
     *
     * This method processes Markdown atx-style headers (e.g. `## Header 2`)
     * and converts them into HTML `<h2>Header 2</h2>` tags.
     *
     * @param string $text The Markdown text containing headers.
     * @return string      The text with converted HTML headers.
     */
    private function headers(string $text): string
    {
        return preg_replace_callback(
            '{
                ^(\#{1,6})    # $1 = string of #\'s
                [ ]*
                (.+?)        # $2 = Header text
                [ ]*
                \#*            # optional closing #\'s (not counted)
                \n+
            }xm',           # 'm' modifier allows ^ and $ to match the start and end of each line (multiline)
            function ($matches) {
                $level = strlen($matches[1]);  // Длина символов '#' для определения уровня заголовка
                return "<h{$level}>{$matches[2]}</h{$level}>";  // Возвращаем HTML для заголовка
            },
            $text
        );
    }

    /**
     * Convert Markdown blockquotes into HTML blockquotes.
     *
     * This method processes Markdown blockquotes (e.g. `> Blockquote`) and
     * converts them into HTML `<blockquote>Blockquote</blockquote>` tags.
     *
     * @param string $text The Markdown text containing blockquotes.
     * @return string      The text with converted HTML blockquotes.
     */
    private function blockquote(string $text): string
    {
        return preg_replace(
            '#^> \s*(.*)$#mx',
            '<blockquote>$1</blockquote>',
            $text
        );
    }

    /**
     * Convert Markdown bold text into HTML bold tags.
     *
     * This method processes bold Markdown text (e.g. `**bold**` or `__bold__`)
     * and converts it into HTML `<strong>bold</strong>` tags.
     *
     * @param string $text The Markdown text containing bold elements.
     * @return string      The text with converted HTML bold tags.
     */
    private function bold(string $text): string
    {
        return preg_replace(
            '/
                \*\*(.*?)\*\*   # Match text surrounded by double asterisks (e.g. &#42;&#42;bold&#42;&#42;)
                |               # OR (alternation operator)
                \_\_(.*?)\_\_   # Match text surrounded by double underscores (e.g. __bold__)
            /sx',
            '<strong>${1}${2}</strong>',
            $text
        );
    }

    /**
     * Convert Markdown italic text into HTML italic tags.
     *
     * This method processes italic Markdown text (e.g. `*italic*` or `_italic_`)
     * and converts it into HTML `<em>italic</em>` tags.
     *
     * @param string $text The Markdown text containing italic elements.
     * @return string      The text with converted HTML italic tags.
     */
    private function italic(string $text): string
    {
        return preg_replace(
            '/
                \*(.*?)\*    # Match text surrounded by single asterisks (e.g. *italic*)
                |            # OR (alternation operator)
                \_(.*?)\_    # Match text surrounded by single underscores (e.g. _italic_)
            /sx',
            '<em>${1}${2}</em>',
            $text
        );
    }

    /**
     * Convert Markdown links into HTML anchor tags.
     *
     * This method processes Markdown links (e.g. `[Link](http://example.com)`) and
     * converts them into HTML `<a href="http://example.com">Example</a>` tags.
     * Links without possibility to add javascript, XSS topic.
     *
     * @param string $text The Markdown text containing links.
     * @return string      The text with converted HTML anchor tags.
     */
    private function link(string $text): string
    {
        return preg_replace(
            '/(?<!\!)\[([^\[]+)\]\((https?|mailto|tel|file|ws|ftp|sftp|git|svn):\/\/([^\)]+)\)/',
            '<a href="$2://$3">$1</a>',
            $text
        );
    }

    /**
     * Convert Markdown images into HTML image tags.
     *
     * This method processes Markdown images (e.g. `![Alt Text](http://example.com/image.jpg)`) and
     * converts them into HTML `<img src="http://example.com/image.jpg" alt="Alt Text" />` tags.
     *
     * @param string $text The Markdown text containing images.
     * @return string      The text with converted HTML image tags.
     */
    private function image(string $text): string
    {
        return preg_replace_callback(
            '#!\[([^\]]*)\]\(([^)]+)\)#',
            function (array $matches) {
                $url = filter_var($matches[2], FILTER_SANITIZE_URL);
                $alt = htmlspecialchars($matches[1] ?? '', ENT_QUOTES, 'UTF-8');
                if (! empty($alt)) {
                    return sprintf('<img src="%s" alt="%s"/>', $url, $alt);
                }
                return sprintf('<img src="%s"/>', $url);
            },
            $text
        );
    }

    /**
     * Convert Markdown inline code into HTML code tags.
     *
     * This method processes inline code (e.g. `` `code` ``) and converts it into HTML `<code>code</code>` tags.
     *
     * @param string $text The Markdown text containing inline code.
     * @return string      The text with converted HTML code tags.
     */
    private function code(string $text): string
    {
        return preg_replace(
            '#(?<!\\\)`([^\n`]+?)`#',
            '<code>$1</code>',
            $text
        );
    }
}
