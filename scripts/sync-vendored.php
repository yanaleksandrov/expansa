<?php

declare(strict_types=1);

// Copies the third-party libraries the framework ships with from vendor/ into expansa-cms/expansa/,
// run by Composer after install and update. Usage: php scripts/sync-vendored.php [target root]
$root   = dirname(__DIR__);
$target = rtrim($argv[1] ?? $root, '/\\');

$map = [
    'vendor/spatie/image/src'                => 'expansa-cms/expansa/Images/Spatie/Image',
    'vendor/spatie/image-optimizer/src'      => 'expansa-cms/expansa/Images/Spatie/ImageOptimizer',
    'vendor/dragonmantank/cron-expression/src' => 'expansa-cms/expansa/Scheduler',
];

$copy = static function (string $from, string $to) use (&$copy): int {
    if (! is_dir($to) && ! mkdir($to, 0755, true) && ! is_dir($to)) {
        throw new RuntimeException("Unable to create $to");
    }

    $count = 0;
    foreach (new FilesystemIterator($from, FilesystemIterator::SKIP_DOTS) as $item) {
        $destination = $to . '/' . $item->getFilename();

        if ($item->isDir()) {
            $count += $copy($item->getPathname(), $destination);
        } elseif (copy($item->getPathname(), $destination)) {
            $count++;
        } else {
            throw new RuntimeException("Unable to copy {$item->getPathname()}");
        }
    }

    return $count;
};

foreach ($map as $from => $to) {
    if (! is_dir("$root/$from")) {
        fwrite(STDERR, "skipped $from: not installed" . PHP_EOL);
        continue;
    }

    echo $copy("$root/$from", "$target/$to") . " files: $from -> $to" . PHP_EOL;
}
