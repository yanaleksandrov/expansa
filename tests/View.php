<?php

declare(strict_types=1);

use Expansa\View\Contracts\Engine;
use Expansa\View\Exceptions\ViewNotFound;
use Expansa\View\Manager;

// run: php tests/View.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

$tmp = sys_get_temp_dir() . '/expansa-view-test-' . getmypid();

$views = [
    'views/page.php'               => 'Hello, <?php echo $name; ?>',
    'views/row.blade.php'          => '<li>{{ $item }}</li>',
    'views/list.blade.php'         => '<ul>@foreach ($items as $item)@include(\'row\', [\'item\' => $item])@endforeach</ul>',
    'views/layout.blade.php'       => '<main>@yield(\'content\')</main>',
    'views/child.blade.php'        => '@extends(\'layout\')@section(\'content\')Child @endsection',
    'views/form/field.php'         => 'field',
    'views/style.css'              => 'a{}',
    'views/both.blade.php'         => 'blade',
    'views/both.php'               => 'php',
    'plugin/panel.php'             => 'panel',
    'mails/wrapper.php'            => 'mail <?php echo $to; ?>',
    'views/note.txt'               => 'note',
];
foreach ($views as $file => $source) {
    @mkdir(dirname("$tmp/$file"), 0777, true);
    file_put_contents("$tmp/$file", $source);
}

$manager = new Manager();
$manager->configure("$tmp/views", "$tmp/cache");

check('php view gets its data', $manager->create('page', ['name' => 'Ann'])->render() === 'Hello, Ann');
check('view name and path are properties', ($view = $manager->create('form/field'))->name === 'form/field' && str_ends_with(strtr($view->path, '\\', '/'), 'views/form/field.php'));
check('blade escapes echoes', $manager->create('row', ['item' => '<b>'])->render() === '<li>&lt;b&gt;</li>');
check('blade includes other views', $manager->create('list', ['items' => ['a', 'b']])->render() === '<ul><li>a</li><li>b</li></ul>');
check('blade extends a layout with sections', $manager->create('child')->render() === '<main>Child </main>');
check('a file view is returned as is', $manager->create('style')->render() === 'a{}');
check('blade.php wins over php', $manager->create('both')->render() === 'blade');
check('an absolute path without extension works', $manager->create("$tmp/mails/wrapper", ['to' => 'x'])->render() === 'mail x');
check('a missing view throws ViewNotFound', throws(fn () => $manager->create('missing'), ViewNotFound::class) && ! $manager->exists('missing'));

$manager->addNamespace('seo', "$tmp/plugin");
check('namespaced views are found', $manager->exists('seo::panel') && $manager->create('seo::panel')->render() === 'panel');

$manager->share('name', 'Shared');
check('shared data is merged under the view data', $manager->create('page')->render() === 'Hello, Shared' && $manager->create('page', ['name' => 'Own'])->render() === 'Hello, Own');
check('getShared() reads a shared variable', $manager->getShared('name') === 'Shared' && $manager->getShared('none', 1) === 1);
check('with() and withName() add data', $manager->create('page')->withName('Magic')->render() === 'Hello, Magic');

$manager->extend('txt', fn () => new class implements Engine {
    public private(set) string $lastRendered = '';

    public function render(string $path, array $data = []): string
    {
        $this->lastRendered = $path;

        return strtoupper(file_get_contents($path));
    }
});
check('extend() adds an engine for an extension', $manager->create('note')->render() === 'NOTE');
check('an engine remembers the last rendered file', str_ends_with($manager->getEngine('txt')->lastRendered, 'note.txt'));

$remove = function (string $dir) use (&$remove) {
    foreach (array_diff(scandir($dir), ['.', '..']) as $entry) {
        is_dir("$dir/$entry") ? $remove("$dir/$entry") : unlink("$dir/$entry");
    }
    rmdir($dir);
};
$remove($tmp);

exit($failures > 0 ? 1 : 0);
