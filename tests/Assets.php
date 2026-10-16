<?php

declare(strict_types=1);

use Expansa\Assets\Manager;

// run: php tests/Assets.php
require_once __DIR__ . '/bootstrap.php';

$assets = new Manager();
$script = 'https://cdn.example.com/picker.js';

$assets->script('date', $script);
$assets->script('time', $script);
check('one file under several uids is enqueued once', array_keys($assets->get()) === ['date-js']);

ob_start();
$assets->render();
check('the file renders once', substr_count((string) ob_get_clean(), $script) === 1);

$assets->style('time', 'https://cdn.example.com/picker.css');
check('another provider of the uid is enqueued', isset($assets->get()['time-css']));

$assets->dequeue('date');
$assets->script('time', $script);
check('a dequeued file can be enqueued again under another uid', isset($assets->get()['time-js']));

$assets->override('time', 'https://cdn.example.com/picker-2.js', 'js');
$assets->script('month', $script);
check('an overridden source frees the old one', isset($assets->get()['month-js']));

$render = function (array $filter) use ($assets): string {
    ob_start();
    $assets->render($filter);

    return (string) ob_get_clean();
};

$assets->style('head', 'https://cdn.example.com/head.css');
$head = $render(['toFooter' => false]);
$assets->style('late', 'https://cdn.example.com/late.css');
$late = $render(['toFooter' => false]);
check('a style is rendered once', str_contains($head, 'head.css') && ! str_contains($late, 'head.css'));
check('a style enqueued after the head renders on the next call', str_contains($late, 'late.css'));

exit($failures ? 1 : 0);
