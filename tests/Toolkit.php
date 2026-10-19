<?php

declare(strict_types=1);

use Plugins\Toolkit\Panel;

// run: php tests/Toolkit.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'plugins/toolkit/src/Panel.php';

$panel = new Panel()
    ->add('Queries', fn () => [['query' => 'SELECT <1>']])
    ->add('Metrics', fn () => ['time' => '1ms', 'count' => 3])
    ->add('Broken', fn () => throw new RuntimeException('no data'));
$sections = $panel->getSections();
check('a list of rows stays as it is', $sections['Queries'] === [['query' => 'SELECT <1>']]);
check('pairs become name and value rows', $sections['Metrics'] === [['name' => 'time', 'value' => '1ms'], ['name' => 'count', 'value' => '3']]);
check('a failing section shows its error', $sections['Broken'] === [['error' => 'no data']]);
check('the panel escapes values', str_contains($panel->render(), 'SELECT &lt;1&gt;'));
check('an empty panel renders nothing', new Panel()->render() === '');

$panel->add('Queries', fn () => [['query' => 'replaced']]);
check('add() keeps a section with the same title', $panel->getSections()['Queries'] === [['query' => 'SELECT <1>']]);
$panel->forget('Queries')->add('Queries', fn () => [['query' => 'replaced']]);
check('forget() lets another section take the title', $panel->getSections()['Queries'] === [['query' => 'replaced']]);

exit($failures > 0 ? 1 : 0);
