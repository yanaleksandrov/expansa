<?php

declare(strict_types=1);

use Expansa\Support\ClassMap;

// run: php tests/Autoload.php
require_once __DIR__ . '/bootstrap.php';
/**
 * Run PHP code in a fresh process with the autoloader, so every check starts with no class loaded.
 */
function isolated(string $code): string
{
    $file = tempnam(sys_get_temp_dir(), 'autoload');
    file_put_contents($file, "<?php\nconst EX_PATH = " . var_export(EX_PATH, true) . ";\nrequire EX_PATH . 'autoload.php';\n" . $code);
    $output = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file) . ' 2>&1');
    unlink($file);

    return trim($output);
}

$classmap = EX_PATH . 'cache/classmap.php';
$saved    = is_file($classmap) ? file_get_contents($classmap) : null;

try {
    @unlink($classmap);

    // without the class map: the namespace prefixes
    check('framework and app classes load', isolated('echo json_encode([class_exists("Expansa\\\\Support\\\\Arr"), class_exists("App\\\\Support\\\\Requirements")]);') === '[true,true]');
    check('bundled libraries load: Spatie and PHPMailer', isolated('echo json_encode([class_exists("Spatie\\\\Image\\\\Image"), class_exists("PHPMailer\\\\PHPMailer\\\\PHPMailer")]);') === '[true,true]');
    check('interfaces, traits and enums load', isolated('echo json_encode([interface_exists("Expansa\\\\Log\\\\Contracts\\\\Handler"), trait_exists("Expansa\\\\Support\\\\Traits\\\\Macroable"), enum_exists("Expansa\\\\Log\\\\Level")]);') === '[true,true,true]');
    check('a missing class is undefined without warnings, also when asked again', isolated('error_reporting(E_ALL); echo json_encode([class_exists("Expansa\\\\Nope"), class_exists("Expansa\\\\Nope"), class_exists("App\\\\Nope\\\\Deep"), class_exists("Unknown\\\\Vendor"), class_exists("Plain")]);') === '[false,false,false,false,false]');
    check('a class of another vendor is left to other autoloaders', isolated('spl_autoload_register(function ($c) { if ($c === "Plugin\\\\Thing") { eval("namespace Plugin; class Thing {}"); } }); echo json_encode(class_exists("Plugin\\\\Thing"));') === 'true');

    // the class map
    $classes = ClassMap::scan(EX_PATH);
    check('scan finds classes, interfaces, traits and enums with paths relative to EX_PATH', ($classes['Expansa\\Log\\Logger'] ?? '') === 'expansa/Log/Logger.php'
        && ($classes['Expansa\\Log\\Contracts\\Handler'] ?? '') === 'expansa/Log/Contracts/Handler.php'
        && ($classes['Expansa\\Support\\Traits\\Macroable'] ?? '') === 'expansa/Support/Traits/Macroable.php'
        && ($classes['Expansa\\Log\\Level'] ?? '') === 'expansa/Log/Level.php'
        && ($classes['PHPMailer\\PHPMailer\\PHPMailer'] ?? '') === 'expansa/Mail/PHPMailer/PHPMailer.php');
    check('scan skips Foo::class and anonymous classes', ! array_filter(array_keys($classes), fn (string $class) => str_ends_with($class, '\\class') || $class === 'class'));

    ClassMap::dump($classmap, $classes);
    check('dump writes a PHP file returning the map', (require $classmap) === $classes);
    check('classes load from the map', isolated('echo json_encode([class_exists("Expansa\\\\Log\\\\Logger"), class_exists("Spatie\\\\Image\\\\Image"), class_exists("Expansa\\\\Nope")]);') === '[true,true,false]');

    // a class added after the dump loads by its prefix
    ClassMap::dump($classmap, array_diff_key($classes, ['Expansa\\Support\\Arr' => true]));
    check('a class missing from the map loads by its namespace', isolated('echo json_encode(class_exists("Expansa\\\\Support\\\\Arr"));') === 'true');
} finally {
    $saved === null ? @unlink($classmap) : file_put_contents($classmap, $saved);
}

exit($failures > 0 ? 1 : 0);
