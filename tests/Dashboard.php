<?php

declare(strict_types=1);

use App\Dashboard\Manager;
use Expansa\Builders\Table;
use Expansa\Http\Request;

// run: php tests/Dashboard.php
require_once __DIR__ . '/bootstrap.php';

$manager = new Manager();
$pages   = new ReflectionProperty(Manager::class, 'pages');
$collect = new ReflectionMethod(Manager::class, 'collect');
$request = Request::create('https://example.com/dashboard/orders?id=7');

$manager->page('orders', view: 'list', can: 'orders_read');
$manager->page('orders', title: 'Orders');
check('page() keeps the options left out', $pages->getValue($manager)['orders'] === ['title' => 'Orders', 'view' => 'list', 'can' => 'orders_read']);

$manager->page('orders', view: 'grid');
check('page() replaces the options given', $pages->getValue($manager)['orders']['view'] === 'grid');

$order = [];
$manager->data('orders', function (Request $request, array $data) use (&$order) {
    $order[] = 'page';

    return ['id' => $request->getInt('id'), 'seen' => array_keys($data)];
});
$manager->data('*', function () use (&$order) {
    $order[] = 'every';

    return ['user' => 'admin'];
});
$manager->data('orders', function () use (&$order) {
    $order[] = 'early';

    return ['user' => 'guest'];
}, 5);
$manager->data('invoices', fn () => ['invoices' => []]);

$data = $collect->invoke($manager, 'orders', $request, []);
check('providers run by priority, then in the order of registration, `*` first', $order === ['early', 'every', 'page']);
check('a provider gets the request and the data collected before it', $data['id'] === 7 && $data['seen'] === ['user']);
check('a later provider overrides the keys of an earlier one', $data['user'] === 'admin');
check('providers of other pages do not run', ! isset($data['invoices']));
check('`*` runs on a page without providers of its own', $collect->invoke($manager, 'reports', $request, []) === ['user' => 'admin']);

$table = new class extends Table {
    public function data(): array
    {
        return [];
    }

    public function cells(): array
    {
        return [
            $this->cell('cb')->fixedWidth('2rem'),
            $this->cell('title')->flexibleWidth('16rem'),
            $this->cell('author'),
            $this->cell('status'),
            $this->cell('date'),
            $this->cell('views')->fixedWidth('6rem'),
        ];
    }
};
check('a table style lists the column widths, a run of the same width as repeat()', $table->style === '--expansa-grid-template-columns: 2rem minmax(16rem, 1fr) repeat(3, 1fr) 6rem');

check('Timezones::all() has 418 zones by identifier', count(App\Support\Timezones::all()) === 418 && isset(App\Support\Timezones::all()['Europe/Moscow']));
check('Countries::all() has 250 countries', count(App\Support\Countries::all()) === 250);

exit($failures ? 1 : 0);
