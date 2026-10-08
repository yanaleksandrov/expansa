<?php

declare(strict_types=1);

use App\Support\Secrets;

// run: php tests/Secrets.php
require_once __DIR__ . '/bootstrap.php';

const EX_KEYS = ['auth' => 'test-key', 'nonce' => '', 'hash' => ''];

$encrypted = Secrets::encrypt('smtp password');
check('a secret round-trips', Secrets::decrypt($encrypted) === 'smtp password');
check('the stored value does not contain the secret', ! str_contains($encrypted, 'smtp password'));
check('every encryption uses a new IV', Secrets::encrypt('smtp password') !== $encrypted);
check('another purpose can not read it', Secrets::decrypt($encrypted, 'two-factor') === '');
check('an empty secret stays empty', Secrets::encrypt('') === '' && Secrets::decrypt('') === '');
$damaged = substr($encrypted, 0, -4) . 'AAAA';
check('a damaged value gives an empty secret', Secrets::decrypt($damaged) === '' && Secrets::decrypt('plain text') === '');

$saved = ['password' => $encrypted, 'dkim' => ['private' => Secrets::encrypt('pem')]];
$form  = ['host' => 'smtp.example.com', 'password' => '', 'dkim' => ['private' => '']];
$kept  = Secrets::keep($form, $saved, ['password', 'dkim.private']);
check('an empty field keeps the saved secret as is', $kept['password'] === $encrypted && $kept['dkim'] === $saved['dkim']);

$changed = Secrets::keep(['password' => ' new password '], $saved, ['password']);
check('a filled field is encrypted, trimmed', Secrets::decrypt($changed['password']) === 'new password');

$fresh = Secrets::keep(['password' => ''], [], ['password', 'dkim.private']);
check('nothing saved and nothing sent stays empty', $fresh['password'] === '' && $fresh['dkim']['private'] === '');

exit($failures > 0 ? 1 : 0);
