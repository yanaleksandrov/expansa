<?php

declare(strict_types=1);

use Expansa\Http\Contracts\Error;
use Expansa\Http\Contracts\Session;
use Expansa\Http\Enums\Notice;
use Expansa\Http\Exceptions\HttpError;
use Expansa\Http\Exceptions\NotFound;
use Expansa\Http\Exceptions\ResponseReady;
use Expansa\Http\Exceptions\ValidationFailed;
use Expansa\Http\Redirect;
use Expansa\Http\Request;
use Expansa\Http\Response;
use Expansa\Http\Status;
use Expansa\Support\Error as ModelError;

require __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

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

$error = new ValidationFailed('Invalid', ['email' => ['Required']]);
check('validation exception is a 422 http exception', $error instanceof HttpError && $error->statusCode === 422);
check('validation errors', $error->errors === ['email' => ['Required']]);
check('not found is a 404 http error', new NotFound('No post')->statusCode === 404 && new NotFound() instanceof Error);
check('ready response is carried', new ResponseReady($response)->response === $response);

$page = Request::create('https://example.com/blog?tag=php', server: [
    'HTTP_ACCEPT'           => 'application/ld+json;q=0.9, text/html',
    'HTTP_ACCEPT_LANGUAGE'  => 'ru;q=0.8, en-US, en;q=0.5',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
    'HTTP_AUTHORIZATION'    => 'Bearer tok123, other',
]);
check('url parts', $page->scheme === 'https' && $page->port === 443 && $page->root === 'https://example.com');
check('uri and query string', $page->uri === '/blog?tag=php' && $page->queryString === 'tag=php');
check('full url merges the query', $page->getFullUrl(['page' => 2]) === 'https://example.com/blog?tag=php&page=2');
check('isMethod in any case', $page->isMethod('post', 'get') && ! $page->isMethod('POST'));
check('hasHeader', $page->hasHeader('Accept', 'X-Requested-With') && ! $page->hasHeader('Accept', 'X-Pjax'));
check('bearer token', $page->bearerToken === 'tok123');
check('ajax flags', $page->isAjax && ! $page->isPjax && ! $page->isPrefetch);
check('acceptable types', $page->acceptableTypes === ['application/ld+json', 'text/html']);
check('accepts', $page->accepts('text/html') && $page->acceptsHtml() && ! $page->accepts('image/png'));
check('json accepted through +json', $page->wantsJson() && $page->getFormat() === 'html');
check('expectsJson', $page->expectsJson());
check('languages by quality', $page->languages === ['en_us', 'ru', 'en'] && $page->getLanguages('en', 'ru') === ['ru', 'en']);
check('acceptsLanguage', $page->acceptsLanguage('en-US') && ! $page->acceptsLanguage('de'));
check('no Accept header accepts anything', Request::create('/')->accepts('image/png') && Request::create('/')->acceptsAny());

$basicAuth = new Request(server: ['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('ann:p:w')]);
check('basic credentials', $basicAuth->authUser === 'ann' && $basicAuth->authPassword === 'p:w');

$form = Request::create('/', 'POST', ['name' => 'Ann', 'blank' => '  ', 'list' => [], 'zero' => '0']);
check('has and hasAny', $form->has('name', 'blank') && ! $form->has('name', 'x') && $form->hasAny('x', 'zero'));
check('only and except', $form->only('name', 'x') === ['name' => 'Ann'] && array_keys($form->except('blank', 'list')) === ['name', 'zero']);
check('isFilled', $form->isFilled('name', 'zero') && ! $form->isFilled('name', 'blank'));
check('isAnyFilled and isEmpty', $form->isAnyFilled('blank', 'name') && $form->isEmpty('blank', 'list', 'missing'));

$calls = [];
$form->whenHas('name', function ($v) use (&$calls) { $calls[] = "has $v"; })
    ->whenFilled('blank', fn () => null, function () use (&$calls) { $calls[] = 'blank'; })
    ->whenMissing('missing', function () use (&$calls) { $calls[] = 'missing'; });
check('when helpers', $calls === ['has Ann', 'blank', 'missing']);

check('array access and magic read input', $form['name'] === 'Ann' && isset($form['zero']) && $form->name === 'Ann' && $form->nothing === null);
check('input is read-only', throws(function () use ($form) { $form['name'] = 'x'; }, LogicException::class));

$form->userResolver = fn (?string $guard) => "user:$guard";
check('user resolver', $form->getUser('api') === 'user:api' && Request::create('/')->getUser() === null);

$session = new class implements Session {
    public array $old = ['name' => 'Old'];

    public function getOldInput(string $key, mixed $default = null): mixed
    {
        return $this->old[$key] ?? $default;
    }

    public function setOldInput(array $input): void
    {
        $this->old = $input;
    }
};
check('old input without a session is the default', $form->getOld('name', 'd') === 'd');
check('flash needs a session', throws(fn () => $form->flash(), LogicException::class));
$form->session = $session;
check('old input', $form->getOld('name') === 'Old');
$form->flashOnly('name');
check('flashOnly', $session->old === ['name' => 'Ann']);
$form->flashExcept('blank', 'list', 'zero');
check('flashExcept', $session->old === ['name' => 'Ann']);
$form->flushOld();
check('flushOld', $session->old === []);

$redirect = new Response('', 302, ['Location' => '/login']);
check('isRedirect', $redirect->isRedirect() && $redirect->isRedirect('/login') && ! $redirect->isRedirect('/') && ! new Response()->isRedirect());
check('setHeader and flushCookies', new Response()->setHeader('X-A', '1')->headers === ['X-A' => '1']);
$withCookies = new Response()->setCookie('a=1');
$withCookies->flushCookies();
check('flushCookies', $withCookies->cookies === []);

check('status text', Status::getText(404) === 'Not Found' && Status::isValid(418) && ! Status::isValid(299));
check('unknown status', throws(fn () => Status::getText(299), InvalidArgumentException::class));

$html = Redirect::render('https://example.com/?a=1&b="2"', 3);
check('delayed redirect page', str_contains($html, 'content="3;url=https://example.com/?a=1&amp;b=&quot;2&quot;"') && str_contains($html, '<strong>3</strong>'));

// fragments: actions of $ajax on the page
check('page-wide actions have no target', json_encode(new Response()->notify('Saved.')->redirect('/a')->reload()->changeUrl('/b')->fragments)
    === '[{"notify":"Saved."},{"redirect":"\/a"},{"reload":true},{"changeURL":"\/b"}]');

check('a notice type and duration go as a list', json_encode(new Response()->notify('Oops', Notice::Error)->notify('Wait', Notice::Loading, 0)->fragments)
    === '[{"notify":["Oops","error"]},{"notify":["Wait","loading",0]}]');

check('a delay is the suffix of the action', json_encode(new Response()->redirect('/a', 1500)->remove('#x', 300)->fragments)
    === '[{"redirect:1500":"\/a"},{"target":"#x","remove:300":true}]');

$html = new Response()
    ->update('#a', '<b>1</b>')
    ->replace('#b', '<i></i>')
    ->before('#c', 'x')
    ->prepend('#c', 'y')
    ->append('#c', 'z')
    ->after('#c', 'w')
    ->value('[name="q"]', '');
check('element actions keep their target and order', array_map(fn (array $f) => $f['target'] . '|' . array_key_last($f), $html->fragments)
    === ['#a|update', '#b|replace', '#c|before', '#c|prepend', '#c|append', '#c|after', '[name="q"]|value']);

$dom = new Response()
    ->addClass('#a', 'on')
    ->removeClass('#a', 'off')
    ->setAttribute('#a', 'aria-busy', 'true')
    ->removeAttribute('#a', 'hidden')
    ->scrollTo('#a')
    ->scrollIntoView('#a', ['block' => 'center']);
check('class, attribute and scroll actions use the names of youla-ajax.js', $dom->fragments === [
    ['target' => '#a', 'classList.add' => 'on'],
    ['target' => '#a', 'classList.remove' => 'off'],
    ['target' => '#a', 'setAttribute' => ['aria-busy', 'true']],
    ['target' => '#a', 'removeAttribute' => 'hidden'],
    ['target' => '#a', 'scrollTo' => true],
    ['target' => '#a', 'scrollIntoView' => ['block' => 'center']],
]);

check('repeated actions are all kept', count(new Response()->remove('#a')->remove('#a')->fragments) === 2);
check('no actions is an empty list', json_encode(new Response()->fragments) === '[]');

$field = ValidationFailed::field('email', 'Taken.');
check('a field error keeps its message and field', $field->getMessage() === 'Taken.' && $field->errors === ['email' => ['Taken.']]);

$model = ValidationFailed::from(new ModelError('user-add', ['email' => ['Taken.']]), 'Check the fields.');
check('validator errors keep their fields with the summary', $model->errors === ['email' => ['Taken.']] && $model->getMessage() === 'Check the fields.');
check('a plain model error becomes the message', ValidationFailed::from(new ModelError('user-add', 'Not saved.'), 'Summary')->getMessage() === 'Not saved.');

check('fragments are the JSON body of the response', new Response()->notify('Hi')->remove('#x')->content === '{"data":[{"notify":"Hi"},{"target":"#x","remove":true}]}');
check('a response with fragments is JSON', (new Response()->notify('Hi')->headers['Content-Type'] ?? '') === 'application/json');

check('response() makes a new response every time', response() !== response() && response()->notify('Hi')->fragments === [['notify' => 'Hi']] && response()->fragments === []);

exit($failures ? 1 : 0);
