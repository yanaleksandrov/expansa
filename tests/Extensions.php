<?php

declare(strict_types=1);

use Expansa\Extensions\Manager;

require_once __DIR__ . '/bootstrap.php';

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'expansa-extensions-' . getmypid() . DIRECTORY_SEPARATOR;
$quarantine = $root . 'storage/quarantine.json';

/**
 * Writes plugins/<slug>/index.php that returns a plugin with the given method bodies.
 *
 * @param array<string, string> $methods Lifecycle method name mapped to its body
 */
$plugin = function (string $slug, array $methods = [], string $source = '') use ($root): void {
    $bodies = array_map(
        fn (string $name): string => "public function {$name}(): void { " . ($methods[$name] ?? '') . ' }',
        ['register', 'boot', 'activate', 'deactivate', 'install', 'uninstall'],
    );
    @mkdir("{$root}plugins/{$slug}", 0777, true);
    file_put_contents("{$root}plugins/{$slug}/index.php", $source ?: "<?php\nreturn new class extends Expansa\\Extensions\\Plugin {\n"
        . "public function __construct() { \$this->setName('{$slug}')->setDescription('Test')->setVersion('1.0'); }\n"
        . implode("\n", $bodies) . "\n};\n");
};

$plugin('works', ['boot' => '$GLOBALS["booted"][] = "works";']);
$plugin('throws', ['boot' => 'throw new RuntimeException("Service is down");']);
$plugin('buggy', ['register' => 'strlen([]);']);
$plugin('broken', source: "<?php\nreturn new class {");
$plugin('unnamed', source: "<?php\nreturn new class extends Expansa\\Extensions\\Plugin {\n"
    . "public function boot(): void {}\npublic function activate(): void {}\npublic function deactivate(): void {}\n"
    . "public function install(): void {}\npublic function uninstall(): void {}\n};\n");

$reported = [];
$manager = new Manager();
$manager->configure($root, $quarantine, function (string $id, Throwable $error) use (&$reported): void {
    $reported[$id] = $error;
});
$manager->load(['plugins/works', 'plugins/throws', 'plugins/buggy', 'plugins/broken', 'plugins/unnamed', '../etc', 'plugins/missing']);
$manager->register('plugin');
$manager->boot('plugin');
$loaded = array_map(fn ($extension): string => $extension->id, $manager->get('plugin'));

check('Extensions keep working plugins running next to failing ones', $loaded === ['plugins/works'] && $GLOBALS['booted'] === ['works']);
check(
    'Extensions report every failure with its plugin id',
    array_keys($reported) === ['plugins/broken', 'plugins/unnamed', 'plugins/buggy', 'plugins/throws']
        && $reported['plugins/throws']->getMessage() === 'Service is down',
);
check(
    'Extensions quarantine bugs but not environment failures',
    array_keys($manager->getQuarantined()) === ['plugins/broken', 'plugins/buggy']
        && array_keys(json_decode(file_get_contents($quarantine), true)) === ['plugins/broken', 'plugins/buggy'],
);

$reported = [];
$next = new Manager();
$next->configure($root, $quarantine, function (string $id, Throwable $error) use (&$reported): void {
    $reported[$id] = $error;
});
$next->load(['plugins/buggy']);
check('Extensions skip quarantined plugins on the next request', $reported === [] && count($next->get('plugin')) === 1);
check(
    'Extensions release a plugin from quarantine',
    $next->forget('plugins/buggy') && ! $next->forget('plugins/buggy') && ! isset($next->getQuarantined()['plugins/buggy']),
);

$plugin('hooked', source: "<?php\nfunction hooked_callback() { return strlen([]); }\n");
require "{$root}plugins/hooked/index.php";
$error = null;
try {
    hooked_callback();
} catch (Error $error) {
}
check('Extensions quarantine a plugin behind an uncaught runtime error', $next->quarantine($error) === 'plugins/hooked');
check('Extensions ignore uncaught exceptions', $next->quarantine(new RuntimeException('Database is down')) === null);
check('Extensions ignore errors outside plugins', $next->quarantine(new Error('core')) === null);

// a fatal error can not be caught, a separate process shows that the shutdown function quarantines the plugin
$plugin('fatal', source: "<?php\nfunction fatal_twice() {}\nfunction fatal_twice() {}\n");
$script = $root . 'fatal.php';
file_put_contents($script, '<?php require ' . var_export(__DIR__ . '/bootstrap.php', true) . ";\n"
    . '$manager = new Expansa\Extensions\Manager();' . "\n"
    . '$manager->configure(' . var_export($root, true) . ', ' . var_export($quarantine, true) . ");\n"
    . '$manager->load(["plugins/fatal"]);' . "\n");
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1', $output, $code);
$quarantined = json_decode(file_get_contents($quarantine), true);
check(
    'Extensions quarantine a plugin that causes a fatal error',
    $code !== 0 && str_contains($quarantined['plugins/fatal']['error'] ?? '', 'fatal_twice'),
);

$remove = function (string $path) use (&$remove): void {
    foreach (glob($path . '/{,.}[!.,!..]*', GLOB_BRACE) ?: [] as $file) {
        is_dir($file) ? $remove($file) : unlink($file);
    }
    rmdir($path);
};
$remove(rtrim($root, '/\\'));
