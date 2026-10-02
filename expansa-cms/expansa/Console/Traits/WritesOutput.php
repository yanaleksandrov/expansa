<?php

declare(strict_types=1);

namespace Expansa\Console\Traits;

use Stringable;
use Expansa\Console\Enums\Color;
use Expansa\Console\Enums\Style;

/**
 * Writing to STDOUT and STDERR with the `[color,style]#text#` markup: `[green,bold]#Done#`.
 *
 * @package Expansa\Console
 */
trait WritesOutput
{
    /**
     * Write a line to STDOUT.
     *
     * @param string $text Text with the markup.
     * @return void
     */
    protected function info(string $text): void
    {
        fwrite(STDOUT, $this->decorate($text) . PHP_EOL);
    }

    /**
     * Replace the `[color,style]#text#` markup with terminal escape codes.
     *
     * @param string $text
     * @return string
     */
    protected function decorate(string $text): string
    {
        // t() prepares text for HTML; decoded first, since entities like &#039; contain the "#" of the markup
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace_callback('/\[(\w+(?:,\s*\w+)*)\]#([^#]+)#/', function (array $matches): string {
            $color = '';
            $style = '';

            foreach (explode(',', $matches[1]) as $attribute) {
                if ($case = Color::fromName($attribute)) {
                    $color = $case->value;
                } elseif ($case = Style::fromName($attribute)) {
                    $style .= $case->value;
                }
            }

            return $color . $style . $matches[2] . "\033[0m";
        }, $text);
    }

    /**
     * Color comma-separated options yellow: `-g, --greet`.
     *
     * @param string $options
     * @return string
     */
    protected function decorateOptions(string $options): string
    {
        return implode(', ', array_map(
            fn (string $item) => $this->decorate(
                sprintf('[yellow]#%s#', trim($item))
            ),
            explode(',', $options)
        ));
    }

    /**
     * Write empty lines.
     *
     * @param int $lines
     * @return void
     */
    public function newLine(int $lines = 1): void
    {
        fwrite(STDOUT, str_repeat(PHP_EOL, $lines));
    }

    /**
     * Play the terminal bell.
     *
     * @param int $times
     * @param int $usleep Interval in microseconds.
     * @return void
     */
    public function beep(int $times = 1, int $usleep = 0): void
    {
        for ($i = 0; $i < $times; $i++) {
            fwrite(STDOUT, "\x07");
            usleep($usleep);
        }
    }

    /**
     * Write a red message to STDERR and exit with the code.
     *
     * @param string   $message
     * @param int|null $exitCode `null` to continue.
     * @return void
     */
    public function error(string $message, ?int $exitCode = 1): void
    {
        $this->beep();

        fwrite(STDERR, $this->decorate("[red]#$message#") . PHP_EOL);

        if ($exitCode !== null) {
            exit($exitCode);
        }
    }

    /**
     * Erase the terminal screen.
     *
     * @return void
     */
    public function eraseScreen(): void
    {
        fwrite(STDOUT, "\e[H\e[2J");
    }

    /**
     * Rewrite the current line, for progress messages.
     *
     * @param string $text
     * @param bool   $finalize End the line after the text.
     * @return void
     */
    public function liveLine(string $text, bool $finalize = false): void
    {
        // "\33[2K" erases the line where the terminal supports it
        $line = (PHP_OS_FAMILY !== 'Windows' ? "\33[2K" : '') . "\r" . $text;

        fwrite(STDOUT, $finalize ? $line . PHP_EOL : $line);
    }

    /**
     * Write a table with columns padded to the longest cell.
     *
     * @param array<array<Stringable|scalar>> $tbody Rows.
     * @param array<Stringable|scalar>        $thead Header cells.
     * @return void
     */
    public function table(array $tbody, array $thead = []): void
    {
        $rows = $thead !== [] ? [array_values($thead)] : [];
        foreach ($tbody as $row) {
            $rows[] = array_values((array) $row);
        }

        $widths = [];
        foreach ($rows as $row) {
            foreach ($row as $column => $cell) {
                $widths[$column] = max($widths[$column] ?? 0, mb_strlen((string) $cell));
            }
        }

        $line = '+';
        foreach ($widths as $width) {
            $line .= str_repeat('-', $width + 2) . '+';
        }

        $table = $line . PHP_EOL;
        $last  = count($rows) - 1;
        foreach ($rows as $index => $row) {
            $cells = [];
            foreach ($row as $column => $cell) {
                $cells[] = $cell . str_repeat(' ', $widths[$column] - mb_strlen((string) $cell));
            }

            $table .= '| ' . implode(' | ', $cells) . ' |' . PHP_EOL;

            if (($index === 0 && $thead !== []) || $index === $last) {
                $table .= $line . PHP_EOL;
            }
        }

        fwrite(STDOUT, $rows === [] ? '' : $table);
    }
}
