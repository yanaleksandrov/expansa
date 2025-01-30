<?php

declare(strict_types=1);

namespace Expansa\Codecs;

/**
 * Provides functionality for encoding and decoding CSV data.
 *
 * @package Expansa\Codec
 */
class Csv
{
    /**
     * The raw CSV content.
     *
     * @var string
     */
    private string $csv = '';

    /**
     * Csv constructor.
     *
     * @param string $delimiter The field delimiter (default: 'auto').
     * @param string $enclosure The field enclosure character (default: 'auto').
     * @param string $linebreak The linebreak sequence (default: 'auto').
     */
    public function __construct(
        private string $delimiter = 'auto',
        private string $enclosure = 'auto',
        private string $linebreak = 'auto'
    ) {} // phpcs:ignore

    /**
     * Decodes a CSV file or string into an array.
     *
     * @param string $filepathOrData The file path or raw CSV data to decode.
     * @param string $delimiter The field delimiter (default: 'auto').
     * @param string $enclosure The field enclosure character (default: 'auto').
     * @param string $linebreak The linebreak sequence (default: 'auto').
     * @return array The decoded data as an array.
     */
    public function decode(
        string $filepathOrData,
        string $delimiter = 'auto',
        string $enclosure = 'auto',
        string $linebreak = 'auto'
    ): array
    {
        return (new self($delimiter, $enclosure, $linebreak))->toArray($filepathOrData);
    }

    /**
     * Encodes an array into a CSV string.
     *
     * @param array $items The data to encode.
     * @param string $delimiter The field delimiter (default: ',').
     * @param string $enclosure The field enclosure character (default: '"').
     * @param string $linebreak The linebreak sequence (default: "\r\n").
     * @return string The encoded CSV string.
     */
    public function encode(
        array $items,
        string $delimiter = ',',
        string $enclosure = '"',
        string $linebreak = "\r\n"
    ): string
    {
        return (new self($delimiter, $enclosure, $linebreak))->fromArray($items);
    }

    /**
     * Determines the field delimiter based on the CSV content.
     *
     * @return string The detected or set field delimiter.
     */
    public function delimiter(): string
    {
        if ($this->delimiter === 'auto') {
            $delimiters = [ ',', "\t", ';' ];
            foreach ($delimiters as $delimiter) {
                if (str_contains($this->csv, $this->enclosure . $delimiter)) {
                    return $this->delimiter = $delimiter;
                }
            }
            $this->delimiter = ',';
        }

        return $this->delimiter;
    }

    /**
     * Determines the field enclosure character based on the CSV content.
     *
     * @return string The detected or set field enclosure character.
     */
    public function enclosure(): string
    {
        if ($this->enclosure === 'auto') {
//          if ( str_contains( $this->csv, '"' ) ) {
//              $this->enclosure = '"';
//          } elseif ( str_contains( $this->csv, "'" ) ) {
//              $this->enclosure = "'";
//          } else {
//              $this->enclosure = '"';
//          }
            $this->enclosure = match (true) {
                str_contains($this->csv, '"') => '"',
                str_contains($this->csv, "'") => "'",
                default                              => '"',
            };
        }
        return $this->enclosure;
    }

    /**
     * Determines the linebreak sequence based on the CSV content.
     *
     * @return string The detected or set linebreak sequence.
     */
    public function linebreak(): string
    {
        if ($this->linebreak === 'auto') {
//          if ( str_contains( $this->csv, "\r\n" ) ) {
//              $this->linebreak = "\r\n";
//          } elseif ( str_contains( $this->csv, "\n" ) ) {
//              $this->linebreak = "\n";
//          } elseif ( str_contains( $this->csv, "\r" ) ) {
//              $this->linebreak = "\r";
//          } else {
//              $this->linebreak = "\r\n";
//          }
            $this->linebreak = match (true) {
                str_contains($this->csv, "\r\n") => "\r\n",
                str_contains($this->csv, "\n")   => "\n",
                str_contains($this->csv, "\r")   => "\r",
                default                                 => "\r\n",
            };
        }
        return $this->linebreak;
    }

    /**
     * Converts CSV content into an array.
     *
     * @param string $filename The file path of the CSV content.
     * @return array The parsed CSV data.
     */
    private function toArray(string $filename): array
    {
        if (! is_file($filename)) {
            return [];
        }

        $this->csv = file_get_contents($filename);

        $linebreak = $this->linebreak();
        $enclosure = $this->enclosure();
        $delimiter = $this->delimiter();

        //$this->csv = mb_convert_encoding( $this->csv, 'UTF-8', mb_detect_encoding( $this->csv, $file_encodings ) );
        $this->csv = iconv(mb_detect_encoding($this->csv, mb_detect_order(), true), 'UTF-8', $this->csv);

        $lines = explode($linebreak, trim($this->csv));
        $lines = array_filter($lines);
        $lines = array_map('trim', $lines);

        unset($this->csv);

        $data = [];
        foreach ($lines as $key => $line) {
            $data[] = str_getcsv($line, $delimiter, $enclosure);
            unset($lines[ $key ]);
        }

        return $data;
    }

    /**
     * Converts an array into a CSV string.
     *
     * @param array $items The data to convert.
     * @return string The generated CSV string.
     */
    private function fromArray(array $items): string
    {
        $delimiter = $this->delimiter();
        $enclosure = $this->enclosure();
        $linebreak = $this->linebreak();

        $result = '';
        foreach ($items as $i) {
            $line = '';

            foreach ($i as $v) {
                if (str_contains($v, $enclosure)) {
                    $v = str_replace($enclosure, $enclosure . $enclosure, $v);
                }

                if (str_contains($v, $delimiter) || str_contains($v, $enclosure) || str_contains($v, $linebreak)) {
                    $v = $enclosure . $v . $enclosure;
                }
                $line .= $line ? $delimiter . $v : $v;
            }
            $result .= $result ? $linebreak . $line : $line;
        }

        return $result;
    }
}
