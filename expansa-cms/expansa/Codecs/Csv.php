<?php

declare(strict_types=1);

namespace Expansa\Codecs;

use Generator;
use InvalidArgumentException;
use Stringable;
use ValueError;

/**
 * Encodes and decodes CSV (RFC 4180): quoted fields may contain delimiters, quotes and line breaks.
 *
 * @package Expansa\Codecs
 */
class Csv
{
    private const array DELIMITERS = [',', ';', "\t", '|'];

    /**
     * Windows-1251 accepts almost any byte sequence, so it must stay after UTF-8.
     */
    private const array ENCODINGS = ['UTF-8', 'Windows-1251'];

    private const array BOMS = [
        "\xEF\xBB\xBF" => 'UTF-8',
        "\xFF\xFE"     => 'UTF-16LE',
        "\xFE\xFF"     => 'UTF-16BE',
    ];

    private const int DETECT_ROWS = 10;

    /**
     * Encoding, line break and delimiter are detected from this many leading bytes only.
     */
    private const int SAMPLE_BYTES = 65536;

    private const int CHUNK_BYTES = 65536;

    /**
     * Decodes a CSV file or a raw CSV string into a list of rows.
     *
     * @param string $input     Path to a file or CSV content.
     * @param string $delimiter Field delimiter, 'auto' detects it from the first rows.
     * @param string $enclosure Enclosure character, 'auto' means '"'.
     * @param string $linebreak Line break, 'auto' detects it from the content.
     * @param string $encoding  Encoding of a file without BOM, 'auto' chooses between UTF-8 and Windows-1251.
     * @return array<int, array<int, string>>
     * @throws InvalidArgumentException When the encoding is not supported.
     */
    public function decode(
        string $input,
        string $delimiter = 'auto',
        string $enclosure = 'auto',
        string $linebreak = 'auto',
        string $encoding = 'auto'
    ): array
    {
        return iterator_to_array($this->iterate($input, $delimiter, $enclosure, $linebreak, $encoding), false);
    }

    /**
     * Yields rows of a CSV file or a raw CSV string one by one: memory does not grow with the file size.
     * The file is closed when the loop ends or breaks.
     *
     * @param string $input     Path to a file or CSV content.
     * @param string $delimiter Field delimiter, 'auto' detects it from the first rows.
     * @param string $enclosure Enclosure character, 'auto' means '"'.
     * @param string $linebreak Line break, 'auto' detects it from the content.
     * @param string $encoding  Encoding of a file without BOM, 'auto' chooses between UTF-8 and Windows-1251.
     * @return Generator<int, array<int, string>>
     * @throws InvalidArgumentException When the encoding is not supported, on the first iteration.
     */
    public function iterate(
        string $input,
        string $delimiter = 'auto',
        string $enclosure = 'auto',
        string $linebreak = 'auto',
        string $encoding = 'auto'
    ): Generator
    {
        $encoding = $encoding === 'auto' ? $encoding : $this->resolveEncoding($encoding);
        $stream   = $this->open($input);

        if ($stream === null) {
            return;
        }

        try {
            $sample   = (string) fread($stream, self::SAMPLE_BYTES);
            $complete = feof($stream);
            $bom      = $this->detectBom($sample);
            // a BOM is unambiguous, so it overrides the encoding passed in
            $encoding = self::BOMS[$bom] ?? ($encoding === 'auto' ? $this->detectEncoding($sample, $complete) : $encoding);
            $sample   = $this->toUtf8(substr($sample, strlen($bom)), $encoding, $complete);

            $linebreak = $linebreak === 'auto' ? $this->detectLinebreak($sample) : $linebreak;
            $enclosure = $enclosure === 'auto' ? '"' : $enclosure;
            $delimiter = $delimiter === 'auto' ? $this->detectDelimiter($sample, $enclosure, $complete) : $delimiter;

            if ($linebreak === "\n" || $linebreak === "\r\n") {
                fseek($stream, strlen($bom));

                if ($encoding !== 'UTF-8') {
                    stream_filter_append($stream, "convert.iconv.$encoding/UTF-8", STREAM_FILTER_READ);
                }
            } else {
                // fgetcsv() splits rows on "\n" only, so other line breaks need the whole content rewritten
                $content = $this->toUtf8((string) stream_get_contents($stream, offset: strlen($bom)), $encoding, true);

                fclose($stream);
                $stream = $this->memory(str_replace($linebreak, "\n", $content));
            }

            yield from $this->rows($stream, $delimiter, $enclosure);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Encodes a list of rows into a CSV string without a trailing line break.
     * Null becomes '', booleans become '1'/'0', arrays and objects are stored as JSON.
     *
     * @param array[] $items     Rows of fields; field keys are ignored.
     * @param string  $delimiter
     * @param string  $enclosure
     * @param string  $linebreak
     * @return string
     */
    public function encode(
        array $items,
        string $delimiter = ',',
        string $enclosure = '"',
        string $linebreak = "\r\n"
    ): string
    {
        if (! $items) {
            return '';
        }

        $csv    = '';
        $stream = fopen('php://memory', 'w+');

        foreach ($items as $item) {
            // flushing in chunks keeps a single copy of the result instead of buffer + string
            if (ftell($stream) >= self::CHUNK_BYTES) {
                $csv .= $this->flush($stream);
            }

            $fields = (array) $item;

            // writing by key leaves all-string rows uncopied, unlike array_map() or a by-reference loop
            foreach ($fields as $key => $field) {
                if (! is_string($field)) {
                    $fields[$key] = $this->stringify($field);
                }
            }

            fputcsv($stream, $fields, $delimiter, $enclosure, '', $linebreak);
        }

        ftruncate($stream, ftell($stream) - strlen($linebreak));
        $csv .= $this->flush($stream);
        fclose($stream);

        return $csv;
    }

    /**
     * Yields parsed rows from the stream, skipping blank lines.
     *
     * @param resource $stream
     * @param string   $delimiter
     * @param string   $enclosure
     * @return Generator<int, array<int, string>>
     */
    private function rows($stream, string $delimiter, string $enclosure): Generator
    {
        while (($row = fgetcsv($stream, null, $delimiter, $enclosure, '')) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            yield $row;
        }
    }

    /**
     * Opens a file for reading or wraps CSV content in a memory stream.
     *
     * @param string $input Path to a file or CSV content.
     * @return resource|null Null for empty input or an unreadable file.
     */
    private function open(string $input)
    {
        $isPath = ! str_contains($input, "\n") && ! str_contains($input, "\0") && is_file($input);

        return match (true) {
            $isPath       => fopen($input, 'rb') ?: null,
            $input === '' => null,
            default       => $this->memory($input),
        };
    }

    /**
     * Returns a memory stream holding the content, positioned at the start.
     *
     * @param string $content
     * @return resource
     */
    private function memory(string $content)
    {
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    /**
     * Returns the stream content and empties the stream.
     *
     * @param resource $stream
     * @return string
     */
    private function flush($stream): string
    {
        $content = (string) stream_get_contents($stream, offset: 0);
        ftruncate($stream, 0);
        rewind($stream);

        return $content;
    }

    /**
     * Returns the byte order mark the sample starts with, or '' if there is none.
     *
     * @param string $sample
     * @return string
     */
    private function detectBom(string $sample): string
    {
        foreach (self::BOMS as $bom => $encoding) {
            if (str_starts_with($sample, $bom)) {
                return $bom;
            }
        }

        return '';
    }

    /**
     * Detects the encoding of a sample without BOM, falling back to UTF-8.
     *
     * @param string $sample
     * @param bool   $complete Whether the sample holds the whole content.
     * @return string
     */
    private function detectEncoding(string $sample, bool $complete): string
    {
        // a cut sample may end in the middle of a multibyte character and fail UTF-8 validation
        if (! $complete) {
            $sample = substr($sample, 0, (int) strrpos($sample, "\n")) ?: mb_strcut($sample, 0, strlen($sample), 'UTF-8');
        }

        return mb_detect_encoding($sample, self::ENCODINGS, true) ?: 'UTF-8';
    }

    /**
     * Checks that both mbstring (sample) and iconv (stream filter) support the encoding.
     * The name is kept as is: normalizing would turn CP932 into Shift_JIS and lose its extensions.
     *
     * @param string $encoding
     * @return string 'UTF-8' for any of its aliases, otherwise the encoding unchanged.
     * @throws InvalidArgumentException
     */
    private function resolveEncoding(string $encoding): string
    {
        try {
            mb_convert_encoding('', 'UTF-8', $encoding);
            // iconv has no list of encodings: an unsupported one is reported only by a warning
            $supported = @iconv($encoding, 'UTF-8', '') !== false; // phpcs:ignore Generic.PHP.NoSilencedErrors
        } catch (ValueError) {
            $supported = false;
        }

        if (! $supported) {
            throw new InvalidArgumentException(sprintf('Unsupported CSV encoding "%s".', $encoding));
        }

        return strcasecmp(str_replace('-', '', $encoding), 'UTF8') === 0 ? 'UTF-8' : $encoding;
    }

    /**
     * Converts content from the given encoding to UTF-8.
     *
     * @param string $content
     * @param string $encoding
     * @param bool   $complete Whether the content is whole rather than a cut sample.
     * @return string
     */
    private function toUtf8(string $content, string $encoding, bool $complete): string
    {
        if ($encoding === 'UTF-8') {
            return $content;
        }

        // an odd byte count would leave half a UTF-16 character at the end of a cut sample
        if (! $complete && stripos($encoding, 'UTF-16') === 0) {
            $content = substr($content, 0, strlen($content) & ~1);
        }

        return mb_convert_encoding($content, 'UTF-8', $encoding);
    }

    /**
     * Picks the most frequent line break, so a "\n" inside a quoted field does not win over "\r" rows.
     *
     * @param string $sample
     * @return string
     */
    private function detectLinebreak(string $sample): string
    {
        $crlf   = substr_count($sample, "\r\n");
        $counts = [
            "\r\n" => $crlf,
            "\n"   => substr_count($sample, "\n") - $crlf,
            "\r"   => substr_count($sample, "\r") - $crlf,
        ];

        return max($counts) > 0 ? (string) array_search(max($counts), $counts, true) : "\n";
    }

    /**
     * Picks the delimiter that gives the most columns consistently across the first rows.
     *
     * @param string $sample
     * @param string $enclosure
     * @param bool   $complete Whether the sample holds the whole content.
     * @return string
     */
    private function detectDelimiter(string $sample, string $enclosure, bool $complete): string
    {
        $best    = self::DELIMITERS[0];
        $columns = 1;

        foreach (self::DELIMITERS as $delimiter) {
            $counts = [];
            $stream = $this->memory($sample);

            foreach ($this->rows($stream, $delimiter, $enclosure) as $row) {
                $counts[] = count($row);

                if (count($counts) === self::DETECT_ROWS) {
                    break;
                }
            }
            fclose($stream);

            // the last row of a cut sample may be incomplete
            if (! $complete && count($counts) > 1 && count($counts) < self::DETECT_ROWS) {
                array_pop($counts);
            }

            $min = $counts ? min($counts) : 1;
            if ($min > $columns) {
                [$best, $columns] = [$delimiter, $min];
            }
        }

        return $best;
    }

    /**
     * Converts a non-string field to its CSV representation, see encode().
     *
     * @param mixed $value
     * @return string
     */
    private function stringify(mixed $value): string
    {
        return match (true) {
            $value === null                                 => '',
            is_bool($value)                                 => $value ? '1' : '0',
            is_scalar($value), $value instanceof Stringable => (string) $value,
            default                                         => new Json()->encode($value),
        };
    }
}
