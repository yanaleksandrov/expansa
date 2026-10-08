<?php

declare(strict_types=1);

use Expansa\Codecs\QrCode;

// run: php tests/Codecs.php
require_once __DIR__ . '/bootstrap.php';

// QR codes: a real decoder (jsQR) read every version 1–10 when the encoder was written; these checks keep the structure
$qr = new QrCode();

$finder = function (array $modules, int $x, int $y): bool {
    for ($dy = 0; $dy < 7; $dy++) {
        for ($dx = 0; $dx < 7; $dx++) {
            $ring = max(abs($dx - 3), abs($dy - 3));
            if ($modules[$y + $dy][$x + $dx] !== ($ring !== 2)) {
                return false;
            }
        }
    }

    return true;
};

foreach ([1 => 'A', 2 => str_repeat('a', 20), 7 => str_repeat('a', 120), 10 => str_repeat('a', 213)] as $version => $text) {
    $modules = $qr->encode($text);
    $size    = 17 + 4 * $version;
    check("version $version: the smallest version that holds the text", count($modules) === $size && count($modules[0]) === $size);
    check("version $version: three finder patterns", $finder($modules, 0, 0) && $finder($modules, $size - 7, 0) && $finder($modules, 0, $size - 7));
    check("version $version: the timing pattern and the dark module", $modules[6][8] && ! $modules[6][9] && $modules[8][6] && ! $modules[9][6] && $modules[$size - 8][8]);
}

check('the same text gives the same code', $qr->encode('otpauth://totp/x?secret=ABC') === $qr->encode('otpauth://totp/x?secret=ABC'));
check('a text longer than version 10 holds throws', throws(fn () => $qr->encode(str_repeat('a', 214)), InvalidArgumentException::class));

$svg = $qr->render('A', 3);
check('render() draws an SVG with the quiet zone', str_starts_with($svg, '<svg') && str_contains($svg, 'viewBox="0 0 29 29"') && str_contains($svg, 'width="87"'));

exit($failures > 0 ? 1 : 0);
