<?php

declare(strict_types=1);

namespace Expansa\Codecs;

use InvalidArgumentException;

/**
 * Encodes text as a QR code (ISO/IEC 18004): byte mode, error correction level M, versions 1–10,
 * up to 213 bytes, e.g. an `otpauth://` link for an authenticator app. render() draws it as SVG.
 *
 * ```php
 * echo new QrCode()->render('otpauth://totp/Expansa:admin?secret=JBSWY3DPEHPK3PXP');
 * ```
 *
 * @package Expansa\Codecs
 */
final class QrCode
{
    /**
     * Error correction blocks of level M by version: codewords of error correction per block,
     * then [number of blocks, data codewords per block] groups.
     */
    private const array BLOCKS = [
        1  => [10, [[1, 16]]],
        2  => [16, [[1, 28]]],
        3  => [26, [[1, 44]]],
        4  => [18, [[2, 32]]],
        5  => [24, [[2, 43]]],
        6  => [16, [[4, 27]]],
        7  => [18, [[4, 31]]],
        8  => [22, [[2, 38], [2, 39]]],
        9  => [22, [[3, 36], [2, 37]]],
        10 => [26, [[4, 43], [1, 44]]],
    ];

    /**
     * Centers of the alignment patterns by version.
     */
    private const array ALIGNMENT = [
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /**
     * Light modules around the code, as the standard asks.
     */
    private const int QUIET_ZONE = 4;

    /**
     * Exponents and logarithms of GF(256) with the polynomial 0x11D.
     *
     * @var int[]
     */
    private array $exp = [];

    /**
     * Logarithms of GF(256), see $exp.
     *
     * @var int[]
     */
    private array $log = [];

    /**
     * Encode text as a matrix of modules.
     *
     * @param string $data
     * @return bool[][] Rows of modules, true for dark.
     * @throws InvalidArgumentException If the text is longer than version 10 holds.
     */
    public function encode(string $data): array
    {
        $version = $this->chooseVersion(strlen($data));
        $size    = 17 + 4 * $version;

        // null marks a module not yet set: what is left after the function patterns carries data
        $modules = array_fill(0, $size, array_fill(0, $size, null));
        $this->drawFunctionPatterns($modules, $version);

        $bits   = $this->interleave($this->dataCodewords($data, $version), $version);
        $best   = null;
        $lowest = PHP_INT_MAX;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = $modules;
            $this->placeData($candidate, $bits, $mask);
            $this->drawFormat($candidate, $mask);

            $penalty = $this->penalty($candidate);
            if ($penalty < $lowest) {
                [$best, $lowest] = [$candidate, $penalty];
            }
        }

        return array_map(fn (array $row) => array_map(fn (?bool $module) => (bool) $module, $row), $best);
    }

    /**
     * Draw the code as an SVG image.
     *
     * @param string $data
     * @param int    $scale Pixels per module.
     * @return string
     */
    public function render(string $data, int $scale = 4): string
    {
        $modules = $this->encode($data);
        $size    = count($modules) + 2 * self::QUIET_ZONE;
        $path    = '';

        foreach ($modules as $y => $row) {
            foreach ($row as $x => $isDark) {
                if ($isDark) {
                    $path .= 'M' . ($x + self::QUIET_ZONE) . ',' . ($y + self::QUIET_ZONE) . 'h1v1h-1z';
                }
            }
        }

        $pixels = $size * $scale;

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $pixels . '" height="' . $pixels . '"'
            . ' viewBox="0 0 ' . $size . ' ' . $size . '" shape-rendering="crispEdges">'
            . '<rect width="100%" height="100%" fill="#fff"/><path d="' . $path . '" fill="#000"/></svg>';
    }

    /**
     * The smallest version that holds the text.
     *
     * @param int $length Bytes.
     * @return int
     * @throws InvalidArgumentException
     */
    private function chooseVersion(int $length): int
    {
        foreach (self::BLOCKS as $version => [, $groups]) {
            $capacity = array_sum(array_map(fn (array $group) => $group[0] * $group[1], $groups));

            // mode (4 bits) and length (8 bits, 16 from version 10)
            if ($length * 8 + 4 + ($version < 10 ? 8 : 16) <= $capacity * 8) {
                return $version;
            }
        }

        throw new InvalidArgumentException('The text is too long for a QR code of version 10.');
    }

    /**
     * Data codewords: mode, length, bytes, terminator and padding.
     *
     * @param string $data
     * @param int    $version
     * @return int[]
     */
    private function dataCodewords(string $data, int $version): array
    {
        $capacity = array_sum(array_map(fn (array $group) => $group[0] * $group[1], self::BLOCKS[$version][1]));
        $bits     = '0100' . str_pad(decbin(strlen($data)), $version < 10 ? 8 : 16, '0', STR_PAD_LEFT);

        foreach (str_split($data) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $bits .= str_repeat('0', min(4, $capacity * 8 - strlen($bits)));
        $bits .= str_repeat('0', (8 - strlen($bits) % 8) % 8);

        $codewords = array_map(bindec(...), str_split($bits, 8));
        $start     = count($codewords);
        for ($i = $start; $i < $capacity; $i++) {
            $codewords[] = ($i - $start) % 2 === 0 ? 0xEC : 0x11;
        }

        return $codewords;
    }

    /**
     * Split the data into blocks, add the error correction and interleave both, as bits.
     *
     * @param int[] $data
     * @param int   $version
     * @return string
     */
    private function interleave(array $data, int $version): string
    {
        [$ecLength, $groups] = self::BLOCKS[$version];

        $blocks = [];
        foreach ($groups as [$count, $length]) {
            for ($i = 0; $i < $count; $i++) {
                $blocks[] = array_splice($data, 0, $length);
            }
        }

        $corrections = array_map(fn (array $block) => $this->errorCorrection($block, $ecLength), $blocks);
        $codewords   = [];

        for ($i = 0, $longest = max(array_map(count(...), $blocks)); $i < $longest; $i++) {
            foreach ($blocks as $block) {
                if (isset($block[$i])) {
                    $codewords[] = $block[$i];
                }
            }
        }

        for ($i = 0; $i < $ecLength; $i++) {
            foreach ($corrections as $correction) {
                $codewords[] = $correction[$i];
            }
        }

        return implode('', array_map(fn (int $codeword) => str_pad(decbin($codeword), 8, '0', STR_PAD_LEFT), $codewords));
    }

    /**
     * Reed–Solomon error correction codewords of a block.
     *
     * @param int[] $block
     * @param int   $length
     * @return int[]
     */
    private function errorCorrection(array $block, int $length): array
    {
        if ($this->exp === []) {
            for ($i = 0, $value = 1; $i < 255; $i++) {
                $this->exp[$i]     = $value;
                $this->log[$value] = $i;
                $value             = $value << 1 ^ ($value & 0x80 ? 0x11D : 0);
            }
        }

        // generator polynomial (x - a^0)(x - a^1)...(x - a^(length - 1))
        $generator = [1];
        for ($i = 0; $i < $length; $i++) {
            $next = array_fill(0, count($generator) + 1, 0);
            foreach ($generator as $j => $coefficient) {
                $next[$j]     ^= $coefficient;
                $next[$j + 1] ^= $this->multiply($coefficient, $this->exp[$i]);
            }
            $generator = $next;
        }

        $remainder = [...$block, ...array_fill(0, $length, 0)];
        foreach (array_keys($block) as $i) {
            $factor = $remainder[$i];
            if ($factor !== 0) {
                foreach ($generator as $j => $coefficient) {
                    $remainder[$i + $j] ^= $this->multiply($coefficient, $factor);
                }
            }
        }

        return array_slice($remainder, count($block));
    }

    /**
     * Product in GF(256).
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    private function multiply(int $a, int $b): int
    {
        return $a === 0 || $b === 0 ? 0 : $this->exp[($this->log[$a] + $this->log[$b]) % 255];
    }

    /**
     * Finder, separator, timing and alignment patterns, the dark module, the reserved format
     * area and, from version 7, the version information.
     *
     * @param array<int, array<int, bool|null>> $modules
     * @param int                               $version
     * @return void
     */
    private function drawFunctionPatterns(array &$modules, int $version): void
    {
        $size = count($modules);

        foreach ([[0, 0], [$size - 7, 0], [0, $size - 7]] as [$x, $y]) {
            for ($dy = -1; $dy <= 7; $dy++) {
                for ($dx = -1; $dx <= 7; $dx++) {
                    // the separator stops at the edge of the code
                    if ($y + $dy >= 0 && $y + $dy < $size && $x + $dx >= 0 && $x + $dx < $size) {
                        $ring = max(abs($dx - 3), abs($dy - 3));
                        $modules[$y + $dy][$x + $dx] = $ring !== 2 && $ring !== 4;
                    }
                }
            }
        }

        for ($i = 8; $i < $size - 8; $i++) {
            $modules[6][$i] = $modules[$i][6] = $i % 2 === 0;
        }

        $centers = self::ALIGNMENT[$version] ?? [];
        foreach ($centers as $cy) {
            foreach ($centers as $cx) {
                // the corners of the finder patterns have no alignment pattern; the timing lines do
                $isFinder = ($cx < 9 && $cy < 9) || ($cx > $size - 10 && $cy < 9) || ($cx < 9 && $cy > $size - 10);
                if ($isFinder) {
                    continue;
                }

                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $modules[$cy + $dy][$cx + $dx] = max(abs($dx), abs($dy)) !== 1;
                    }
                }
            }
        }

        $modules[$size - 8][8] = true;

        // reserve the format area with light modules, drawFormat() writes it per mask
        for ($i = 0; $i < 9; $i++) {
            $modules[8][$i] ??= false;
            $modules[$i][8] ??= false;
        }
        for ($i = 0; $i < 8; $i++) {
            $modules[8][$size - 1 - $i] ??= false;
            $modules[$size - 1 - $i][8] ??= false;
        }

        if ($version >= 7) {
            $bits = $version << 12 | $this->bch($version, 0x1F25, 12);
            for ($i = 0; $i < 18; $i++) {
                $bit                                           = ($bits >> $i & 1) === 1;
                $modules[intdiv($i, 3)][$size - 11 + $i % 3] = $bit;
                $modules[$size - 11 + $i % 3][intdiv($i, 3)] = $bit;
            }
        }
    }

    /**
     * Place the data bits in the free modules, zigzag from the bottom right, with a mask.
     *
     * @param array<int, array<int, bool|null>> $modules
     * @param string                            $bits
     * @param int                               $mask
     * @return void
     */
    private function placeData(array &$modules, string $bits, int $mask): void
    {
        $size   = count($modules);
        $index  = 0;
        $length = strlen($bits);
        $upward = true;

        for ($right = $size - 1; $right > 0; $right -= 2) {
            // the vertical timing pattern takes a whole column
            if ($right === 6) {
                $right = 5;
            }

            for ($step = 0; $step < $size; $step++) {
                $y = $upward ? $size - 1 - $step : $step;

                foreach ([$right, $right - 1] as $x) {
                    if ($modules[$y][$x] !== null) {
                        continue;
                    }

                    $bit = $index < $length && $bits[$index] === '1';
                    $index++;

                    $modules[$y][$x] = $bit !== $this->isMasked($mask, $x, $y);
                }
            }

            $upward = ! $upward;
        }
    }

    /**
     * Whether a mask pattern flips the module.
     *
     * @param int $mask
     * @param int $x
     * @param int $y
     * @return bool
     */
    private function isMasked(int $mask, int $x, int $y): bool
    {
        return match ($mask) {
            0 => ($x + $y) % 2 === 0,
            1 => $y % 2 === 0,
            2 => $x % 3 === 0,
            3 => ($x + $y) % 3 === 0,
            4 => (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0,
            5 => $x * $y % 2 + $x * $y % 3 === 0,
            6 => ($x * $y % 2 + $x * $y % 3) % 2 === 0,
            default => (($x + $y) % 2 + $x * $y % 3) % 2 === 0,
        };
    }

    /**
     * Write the format information: level M and the mask, twice.
     *
     * @param array<int, array<int, bool|null>> $modules
     * @param int                               $mask
     * @return void
     */
    private function drawFormat(array &$modules, int $mask): void
    {
        $size = count($modules);
        $data = 0b00 << 3 | $mask;
        $bits = ($data << 10 | $this->bch($data, 0x537, 10)) ^ 0x5412;

        for ($i = 0; $i < 15; $i++) {
            $bit = ($bits >> $i & 1) === 1;

            // around the top left finder
            if ($i < 6) {
                $modules[$i][8] = $bit;
            } elseif ($i < 8) {
                $modules[$i + 1][8] = $bit;
            } else {
                $modules[8][$i === 8 ? 7 : 14 - $i] = $bit;
            }

            // split between the bottom left and the top right finders
            if ($i < 8) {
                $modules[8][$size - 1 - $i] = $bit;
            } else {
                $modules[$size - 15 + $i][8] = $bit;
            }
        }
    }

    /**
     * BCH remainder of the format or version bits.
     *
     * @param int $value
     * @param int $generator
     * @param int $degree
     * @return int
     */
    private function bch(int $value, int $generator, int $degree): int
    {
        $remainder = $value << $degree;
        for ($bit = 31; $bit >= $degree; $bit--) {
            if ($remainder >> $bit & 1) {
                $remainder ^= $generator << ($bit - $degree);
            }
        }

        return $remainder;
    }

    /**
     * Penalty of a masked matrix by the four rules of the standard; the mask with the lowest one is used.
     *
     * @param array<int, array<int, bool|null>> $modules
     * @return int
     */
    private function penalty(array $modules): int
    {
        $size    = count($modules);
        $penalty = 0;
        $dark    = 0;
        $columns = array_map(null, ...$modules);

        foreach ([$modules, $columns] as $lines) {
            foreach ($lines as $line) {
                $line = array_map(fn (?bool $module) => (bool) $module, $line);

                // runs of five or more of the same color
                $run = 1;
                for ($i = 1; $i <= $size; $i++) {
                    if ($i < $size && $line[$i] === $line[$i - 1]) {
                        $run++;
                        continue;
                    }
                    $penalty += $run >= 5 ? $run - 2 : 0;
                    $run      = 1;
                }

                // a finder-like 1:1:3:1:1 pattern with four light modules on a side
                $text     = implode('', array_map(fn (bool $module) => $module ? '1' : '0', $line));
                $penalty += 40 * (substr_count($text, '10111010000') + substr_count($text, '00001011101'));
            }
        }

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $dark += $modules[$y][$x] ? 1 : 0;

                // 2x2 blocks of one color
                if ($y < $size - 1 && $x < $size - 1) {
                    $isSame   = (bool) $modules[$y][$x] === (bool) $modules[$y][$x + 1]
                        && (bool) $modules[$y][$x] === (bool) $modules[$y + 1][$x]
                        && (bool) $modules[$y][$x] === (bool) $modules[$y + 1][$x + 1];
                    $penalty += $isSame ? 3 : 0;
                }
            }
        }

        // the share of dark modules away from 50%
        return $penalty + 10 * intdiv(abs(intdiv($dark * 100, $size * $size) - 50), 5);
    }
}
