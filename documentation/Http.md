# Введение

Входящий запрос, исходящий ответ и редирект. Пакет находится в `Expansa\Http` и зависит только от
базового слоя. API-контроллеры получают `Request` от `App\Http\Kernel` и возвращают массив или `Response`.

```php
use Expansa\Http\Request;
use Expansa\Http\Response;

public function get(Request $request): array
{
    return $this->service->list([
        'page' => max(1, $request->getInt('page', 1)),
        's'    => $request->getString('s'),
    ]);
}

public function export(Request $request): Response
{
    return new Response($csv, headers: ['Content-Type' => 'text/csv']);
}
```

| Класс                            | Назначение                                                         |
|----------------------------------|--------------------------------------------------------------------|
| `Request`                        | Значения суперглобальных массивов и то, что из них следует         |
| `Response`                       | Статус, заголовки, cookie и тело; `send()` отправляет              |
| `Redirect`                       | Заголовок `Location` с фильтрами из `configure()`, страница с отсчётом |
| `Status`                         | Тексты кодов статуса: `Status::getText(404)`                       |
| `Contracts\Request`, `Response`  | Контракты запроса и ответа                                         |
| `Contracts\Error`                | Исключение, которое становится ответом с ошибкой                   |
| `Contracts\Session`, `Route`     | Что Http ждёт от сессии и маршрутизатора; реализуют другие пакеты  |
| `Exceptions\HttpError`           | Ошибка со статусом и заголовками, `Kernel` превращает её в ответ    |
| `Exceptions\NotFound`            | Ошибка 404                                                         |
| `Exceptions\ValidationFailed`    | Ошибка 422 со списком ошибок полей                                 |
| `Exceptions\ResponseReady`       | Прервать обработку и отправить готовый ответ                       |

## Конфигурация

Настраивается только `Redirect`: `bootstrap.php` связывает его фильтры с хуками `redirectLocation`,
`redirectStatus` и `redirectBy`.

```php
Expansa\Http\Redirect::configure(
    location: fn (string $to, int $status) => Hook::call('redirectLocation', $to, $status),
    status: fn (int $status, string $to) => Hook::call('redirectStatus', $status, $to),
    redirectBy: fn (string $redirectBy, int $status, string $to) => Hook::call('redirectBy', $redirectBy, $status, $to),
    flash: fn (string $key, array $values) => $_SESSION['flash'][$key] = $values,
);
```

`flash` — куда сохранить значения `Redirect::flash()` для следующего запроса. Повторный вызов заменяет
все колбэки. Без `configure()` значения не меняются, `flash()` ничего не сохраняет.

## Запрос

`Request::createFromGlobals()` собирает запрос из `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`, `$_SERVER`;
`Request::create($uri, $method, $parameters, ..., content: $body)` — для тестов и внутренних вызовов.

Исходные значения — массивы только для чтения: `query`, `post`, `cookies`, `files` (в форме `$_FILES`),
`server`. Остальное вычисляется при обращении:

| Свойство          | Значение                                                                  |
|-------------------|---------------------------------------------------------------------------|
| `headers`         | заголовки с ключами как в `$_SERVER`: `CONTENT_TYPE`, `USER_AGENT`        |
| `content`         | тело запроса; `php://input` читается при первом обращении                 |
| `json`            | тело, разобранное как JSON-объект, иначе `[]`                             |
| `input`           | `query`, `post`, `json` и `files` вместе, следующие перекрывают предыдущие |
| `acceptableTypes` | типы из `Accept` в порядке клиента                                        |
| `languages`       | языки из `Accept-Language` по убыванию качества: `en_us`                  |
| `method`          | `GET`, `POST`, ...                                                        |
| `secure`, `scheme`| HTTPS напрямую или через прокси; `https` или `http`                       |
| `host`, `port`    | хост в нижнем регистре, с портом, если он не стандартный; порт            |
| `uri`             | `REQUEST_URI` как есть                                                    |
| `path`            | путь без строки запроса и завершающего `/`                                |
| `queryString`     | строка запроса без `?`                                                    |
| `root`, `url`     | `https://example.com`; адрес без строки запроса                           |
| `ip`              | первый адрес `X-Forwarded-For`, `CF-Connecting-IP` или `REMOTE_ADDR`      |
| `userAgent`       | заголовок `User-Agent`                                                    |
| `bearerToken`     | токен `Authorization: Bearer`                                             |
| `authUser`, `authPassword` | HTTP Basic                                                       |
| `isAjax`, `isPjax`, `isPrefetch` | XMLHttpRequest, PJAX, предзагрузка браузером               |

`headers`, `content`, `json`, `input`, `acceptableTypes` и `languages` вычисляются один раз, поэтому
запрос, читающий только `post`, не трогает тело.

```php
$request->post['title'] ?? '';        // значение формы как есть
$request->get('id');                  // из input, null если нет
$request['id'];                       // то же; запись бросает LogicException
$request->getHeader('X-Csrf-Token');  // имя в любом регистре
$request->getFullUrl(['page' => 2]);  // url с текущей строкой запроса и новыми значениями
```

Методы ввода работают с ключами верхнего уровня `input`:

| Метод                                   | Результат                                                   |
|-----------------------------------------|-------------------------------------------------------------|
| `getString($key, $default)`             | строка без пробелов по краям; массив и bool — `$default`    |
| `getInt($key, $default)`                | целое, `'12'` → `12`, `'1.5'` → `$default`                  |
| `getFloat($key, $default)`              | число                                                       |
| `getBool($key, $default)`               | `1`, `true`, `on`, `yes` — `true`; `0`, `false`, `off`, `no`, `''` — `false` |
| `has(...$keys)`, `hasAny(...$keys)`     | есть все / хотя бы один ключ                                |
| `only(...$keys)`, `except(...$keys)`    | ввод только с ключами / без них                             |
| `isFilled(...)`, `isAnyFilled(...)`, `isEmpty(...)` | заполнены все / хотя бы одно / ни одно: не `null`, не пустая строка, не `[]` |
| `whenHas`, `whenFilled`, `whenMissing`  | колбэк по условию, иначе колбэк `$default`                  |
| `isMethod(...$methods)`, `hasHeader(...$names)` | метод из списка; все заголовки есть                 |

Согласование: `accepts(...$types)` (без `Accept` — всё), `acceptsAny()`, `acceptsJson()`,
`acceptsHtml()`, `getFormat($default)` — первый принятый формат (`html`, `json`, `xml`, ...),
`wantsJson()` — JSON первый в `Accept`, `expectsJson()` — ещё и AJAX-запрос, принимающий всё;
`getLanguages(...$supported)` — принятые языки, только из поддерживаемых; `acceptsLanguage('en-US')`.

Сессия, маршрут и пользователь приходят снаружи через свойства, Http не зависит от этих пакетов:

```php
$request->session      = $session;   // Contracts\Session: getOldInput(), setOldInput()
$request->route        = $route;     // Contracts\Route: pattern, parameters
$request->userResolver = fn (?string $guard) => User::current();

$request->getUser();                 // результат резолвера или null
$request->getOld('email');           // ввод прошлого запроса, без сессии — default
$request->flash();                   // весь ввод; flashOnly(...), flashExcept(...), flushOld()
```

Без сессии методы `flash*()` и `flushOld()` бросают `LogicException`. Класс подключает `Macroable`.

## Ответ

```php
new Response()->json(['data' => $items], 201, ['X-Total' => '42'])->send();

$response = new Response($html, 200, ['Content-Type' => 'text/html']);
$response->statusCode = 404;
$response->setHeader('Cache-Control', 'no-store')->setCookie(new Cookie('id', '1', httpOnly: true));
$response->prepare($request)->send();
```

`content`, `statusCode`, `headers` — публичные свойства, `setHeader()` — то же для цепочки. `json()`
кодирует данные, ставит статус и `Content-Type: application/json`. К `Content-Type` при отправке
добавляется `charset=utf-8`. `setCookie()` принимает строку заголовка или объект с `__toString()`,
например `Expansa\Cookie\Cookie`; `cookies` — добавленные, `flushCookies()` — забыть их. `prepare()`
убирает тело у ответа на `HEAD`. `send()` отправляет статус, заголовки и тело и завершает запрос для
клиента (`fastcgi_finish_request()`), скрипт может продолжить работу. `isRedirect($location)` — статус
редиректа, на адрес, если указан. `Response::closeOutputBuffers($level, flush: true)` закрывает буферы
вывода.

## Редирект

```php
redirect('dashboard');                             // url('dashboard'), 302
Expansa\Http\Redirect::send('https://example.com', 301);

Expansa\Http\Redirect::flash('errors', $errors);   // сохранится колбэком flash при send()
Expansa\Http\Redirect::back(fallback: url('dashboard'));

echo Expansa\Http\Redirect::render($url, 5, t('Redirecting'), t('Redirecting to <a href=":url">:url</a> in <strong>:seconds</strong>'));
```

`send()` прогоняет адрес, статус и `X-Redirect-By` через фильтры, передаёт значения `flash()` колбэку,
отправляет заголовки и завершает скрипт. Если фильтр адреса вернул пустую строку, редирект отменяется и
`send()` возвращает управление. Статус не из `3xx` — `InvalidArgumentException`. `back()` ведёт на
`Referer`, без него — на `$fallback`. `render()` возвращает страницу с отсчётом и `meta refresh`; в
текстах `:url` и `:seconds` заменяются, отсчёт обновляет `<strong>`.

## Ошибки

`HttpError` и наследники превращаются `Kernel` в JSON-ответ `{ "message": ... }` со своим статусом;
`ValidationFailed` добавляет `errors`. `ResponseReady` отправляет свой ответ как есть.

```php
throw new HttpError(409, t('Expansa is already installed.'));
throw new NotFound(t('Post not found'));
throw new ValidationFailed(t('Check the form'), ['email' => [t('Required')]]);
throw new ResponseReady(new Response($csv, headers: ['Content-Type' => 'text/csv']));
```

`Status::getText(404)` — `Not Found`, неизвестный код — `InvalidArgumentException`; `Status::isValid()`.
