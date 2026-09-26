<?php

declare(strict_types=1);

use App\Support\Installation;
use Expansa\Facades\Extensions;

// run: php tests/Installation.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

// installation steps in a scratch copy, so the real env.php is never touched
$root = sys_get_temp_dir() . '/expansa-install-' . getmypid() . '/';
@mkdir($root);
copy(EX_PATH . 'env.example.php', $root . 'env.example.php');

check('not installed without env.php', ! Installation::isComplete($root));

$draft = Installation::draft(['db.name' => 'first_db'], $root);
Installation::discard($draft);
check('a failed attempt leaves neither env.php nor the draft', ! Installation::isComplete($root) && ! is_file($draft));

file_put_contents($root . 'env.install.php', '<?php // stale_db');
$draft = Installation::draft(['db.name' => 'second_db', 'auth.key' => str_repeat('a', 64)], $root);
check('a stale draft is replaced', ! str_contains(file_get_contents($draft), 'stale_db'));
check('the draft is not an installation yet', ! Installation::isComplete($root));

Installation::complete($draft, $root);
$env = file_get_contents($root . 'env.php');
check('complete() publishes env.php and removes the draft', Installation::isComplete($root) && ! is_file($draft));
check('env.php holds the new values', str_contains($env, "'second_db'") && str_contains($env, str_repeat('a', 64)));

array_map('unlink', glob($root . '*.php'));
rmdir($root);

// extensions load by id from the configured root
Extensions::configure(root: EX_PATH);
Extensions::load(ids: ['plugins/query-monitor', '../etc', 'plugins/../../etc', 42]);
$plugins = array_map(fn ($extension) => $extension->id, Extensions::get('plugin'));
check('only valid extension ids are loaded', $plugins === ['plugins/query-monitor']);

exit($failures > 0 ? 1 : 0);
