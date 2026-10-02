<?php

declare(strict_types=1);

use Expansa\Support\Error;

// run: php tests/Error.php
require_once __DIR__ . '/bootstrap.php';

$first  = new Error('media_upload', 'first.jpg is too big');
$second = new Error('media_upload', 'second.jpg has a wrong type');
check('errors with the same code keep their own messages', $first->messages === ['first.jpg is too big'] && $second->messages === ['second.jpg has a wrong type']);
check('json has the code and the messages of the instance', json_encode($second) === '{"code":"media_upload","message":["second.jpg has a wrong type"]}');

$validation = new Error('user-add', ['login' => ['Required'], 'email' => ['Invalid']]);
check('validator errors keep their field keys', $validation->messages === ['login' => ['Required'], 'email' => ['Invalid']]);

check('add() appends a message', new Error('slug-find')->add('Not found')->add(['Try again'])->messages === ['Not found', 'Try again']);
check('an empty message adds nothing', new Error('slug-find')->messages === []);

exit($failures > 0 ? 1 : 0);
