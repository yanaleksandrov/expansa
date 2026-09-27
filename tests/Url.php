<?php

declare(strict_types=1);

use Expansa\Support\Url;

// run: php tests/Url.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

$_SERVER['HTTP_HOST'] = 'request.test';

Url::configure(root: 'C:\site\\', site: fn () => 'https://site.test');

check('toPath() joins the root with forward slashes', Url::toPath('cache/views') === 'C:/site/cache/views');
check('toPath() drops a leading slash, e.g. of a URL path', Url::toPath('/dashboard/app.css') === 'C:/site/dashboard/app.css');
check('toUrl() maps a file under the root to the site URL', Url::toUrl('C:/site/dashboard/app.css') === 'https://site.test/dashboard/app.css');
check('toUrl() accepts backslashes', Url::toUrl('C:\site\dashboard\app.css') === 'https://site.test/dashboard/app.css');
check('toUrl() treats a path outside the root as relative', Url::toUrl('/uploads/a.png') === 'https://site.test/uploads/a.png');
check('url() appends the path to the configured site URL', url('sign-in') === 'https://site.test/sign-in');

Url::configure(root: 'C:/site', site: fn () => throw new RuntimeException('no database yet'));
check('a failing site source falls back to the request host', url() === 'http://request.test/');

Url::configure(root: 'C:/site');
check('without a site source the URL comes from the request', url('a') === 'http://request.test/a');

exit($failures > 0 ? 1 : 0);
