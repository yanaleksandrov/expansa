<?php

declare(strict_types=1);

use Expansa\Http\Exceptions\HttpException;
use Expansa\Http\Exceptions\ValidationException;
use Expansa\Http\Request;
use Expansa\Http\Response;

require __DIR__ . '/bootstrap.php';

$get = Request::create('https://Example.com:8443/posts/?page=2&s=+cat+', server: [
    'HTTP_USER_AGENT'      => 'Test',
    'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 10.0.0.2',
]);
check('GET parameters go to the query', $get->query === ['page' => '2', 's' => ' cat '] && $get->post === []);
check('method', $get->method === 'GET');
check('secure', $get->secure);
check('host keeps a non-default port', $get->host === 'example.com:8443');
check('path without the trailing slash', $get->path === '/posts');
check('url without the query', $get->url === 'https://example.com:8443/posts');
check('ip is the first forwarded address', $get->ip === '10.0.0.1');
check('header in any case', $get->getHeader('User-Agent') === 'Test' && $get->getHeader('USER_AGENT') === 'Test');
check('missing header gives the default', $get->getHeader('Accept', '*/*') === '*/*');
check('getInt', $get->getInt('page') === 2 && $get->getInt('s', 7) === 7);
check('getString trims', $get->getString('s') === 'cat' && $get->getString('missing', 'x') === 'x');

$post = Request::create('/api/posts', 'post', ['title' => 'Hello', 'draft' => 'on'], content: '{"id":5,"title":"Json"}');
check('POST parameters go to the body', $post->post === ['title' => 'Hello', 'draft' => 'on'] && $post->query === []);
check('root path', $post->path === '/api/posts' && Request::create('/')->path === '/');
check('not secure over http', ! $post->secure && $post->url === 'http://localhost/api/posts');
check('json is decoded from the body', $post->json === ['id' => 5, 'title' => 'Json']);
check('input merges the sources, json overrides the form', $post->input === ['title' => 'Json', 'draft' => 'on', 'id' => 5]);
check('get and getBool', $post->get('id') === 5 && $post->getBool('draft') && ! $post->getBool('missing'));
check('not a json object gives an empty array', Request::create('/', 'POST', content: 'plain text')->json === []);

$basic = new Request(server: ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'secret']);
check('authorization from basic auth', $basic->getHeader('Authorization') === 'Basic ' . base64_encode('admin:secret'));

$rewritten = new Request(server: ['REDIRECT_HTTP_AUTHORIZATION' => 'Bearer abc']);
check('authorization restored after a rewrite', $rewritten->getHeader('Authorization') === 'Bearer abc');

$response = new Response()->json(['ok' => true], 201, ['X-Id' => '1']);
check('json response', $response->content === '{"ok":true}' && $response->statusCode === 201);
check('json response headers', $response->headers === ['Content-Type' => 'application/json', 'X-Id' => '1']);
check('HEAD response has no body', new Response('body')->prepare(Request::create('/', 'HEAD'))->content === '');
check('cookies are collected', new Response()->setCookie('id=1')->setCookie('a=2')->cookies === ['id=1', 'a=2']);

$error = new ValidationException('Invalid', ['email' => ['Required']]);
check('validation exception is a 422 http exception', $error instanceof HttpException && $error->statusCode === 422);
check('validation errors', $error->errors === ['email' => ['Required']]);

exit($failures ? 1 : 0);
