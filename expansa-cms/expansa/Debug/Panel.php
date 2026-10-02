<?php

declare(strict_types=1);

namespace Expansa\Debug;

use Closure;
use Throwable;

/**
 * Debug panel at the bottom of a page: sections like the timeline, queries and the request,
 * each a table of rows. A section collects its rows only when the panel is rendered, so it
 * sees the whole request. One instance per request behind the Panel facade; styles are in `css/debug.css`.
 *
 * ```php
 * Panel::add('Queries', fn () => array_map(fn (string $sql) => ['query' => $sql], Db::log()));
 * ```
 *
 * @package Expansa\Debug
 */
final class Panel
{
    /**
     * Collectors of the sections by title.
     *
     * @var array<string, Closure>
     */
    private array $sections = [];

    /**
     * Add a section, unless one with this title is there: plugins do not replace the core ones.
     *
     * @param string  $title
     * @param Closure $rows  fn (): array, a list of rows, each `column => value`, or `name => value` pairs.
     * @return static
     */
    public function add(string $title, Closure $rows): static
    {
        $this->sections[$title] ??= $rows;

        return $this;
    }

    /**
     * Forget a section, e.g. to add another one with its title.
     *
     * @param string $title
     * @return static
     */
    public function forget(string $title): static
    {
        unset($this->sections[$title]);

        return $this;
    }

    /**
     * Get the sections with their rows collected: title => rows; a failing collector gives its error as the only row.
     *
     * @return array<string, list<array<string, string>>>
     */
    public function getSections(): array
    {
        $sections = [];
        foreach ($this->sections as $title => $collect) {
            try {
                $rows = $collect();
            } catch (Throwable $e) {
                $rows = [['error' => $e->getMessage()]];
            }

            $sections[$title] = $this->rows($rows);
        }

        return $sections;
    }

    /**
     * HTML of the panel, empty without sections.
     *
     * @return string
     */
    public function render(): string
    {
        $sections = $this->getSections();
        if ($sections === []) {
            return '';
        }

        $h    = fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $html = '<div class="debug-panel">';

        foreach ($sections as $title => $rows) {
            $html .= '<details class="debug-panel-section" name="debug-panel">';
            $html .= '<summary>' . $h($title) . ' <span>' . count($rows) . '</span></summary>';
            $html .= '<div class="debug-panel-content"><table><thead><tr>';

            foreach (array_keys($rows[0] ?? []) as $column) {
                $html .= '<th>' . $h((string) $column) . '</th>';
            }

            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . $h($value) . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div></details>';
        }

        return $html . '</div>';
    }

    /**
     * Normalize rows: a list of arrays stays, `name => value` pairs become rows; values become strings.
     *
     * @param array<array-key, mixed> $rows
     * @return list<array<string, string>>
     */
    private function rows(array $rows): array
    {
        if (! array_is_list($rows) || ($rows !== [] && ! is_array($rows[0]))) {
            $pairs = [];
            foreach ($rows as $name => $value) {
                $pairs[] = ['name' => $name, 'value' => $value];
            }
            $rows = $pairs;
        }

        $dumper = new Dumper();

        return array_map(
            fn (mixed $row) => array_map(fn (mixed $value) => is_string($value) ? $value : $dumper->render($value), (array) $row),
            $rows
        );
    }
}
