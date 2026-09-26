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
| `Redirect`                       | Заголовок `Location` с фильтрами из `configure()`                  |
| `Exceptions\HttpError`           | Ошибка со статусом и заголовками, `Kernel` превращает её в ответ    |
| `Exceptions\ValidationFailed`    | Ошибка 422 со списком ошибок полей                                 |

## Конфигурация

Настраивается только `Redirect`: `bootstrap.php` связывает его фильтры с хуками `redirectLocation`,
`redirectStatus` и `redirectBy`.

```php
Expansa\Http\Redirect::configure(
    location: fn (string $to, int $status) => Hook::call('redirectLocation', $to, $status),
    status: fn (int $status, string $to) => Hook::call('redirectStatus', $status, $to),
    redirectBy: fn (string $redirectBy, int $status, string $to) => Hook::call('redirectBy', $redirectBy, $status, $to),
);
```

Повторный вызов заменяет все фильтры. Без `configure()` значения не меняются.

## Запрос

`Request::createFromGlobals()` собирает запрос из `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`, `$_SERVER`;
`Request::create($uri, $method, $parameters, ..., content: $body)` — для тестов и внутренних вызовов.

Исходные значения — массивы только для чтения: `query`, `post`, `cookies`, `files` (в форме `$_FILES`),
`server`. Остальное вычисляется при обращении:

| Свойство  | Значение                                                                     |
|-----------|------------------------------------------------------------------------------|
| `headers` | заголовки с ключами как в `$_SERVER`: `CONTENT_TYPE`, `USER_AGENT`           |
| `content` | тело запроса; `php://input` читается при первом обращении                    |
| `json`    | тело, разобранное как JSON-объект, иначе `[]`                                |
| `input`   | `query`, `post`, `json` и `files` вместе, следующие перекрывают предыдущие   |
| `method`  | `GET`, `POST`, ...                                                           |
| `secure`  | HTTPS напрямую или через прокси (`X-Forwarded-Proto`, `X-Forwarded-SSL`)     |
| `host`    | хост в нижнем регистре, с портом, если он не стандартный                     |
| `path`    | путь без строки запроса и завершающего `/`                                   |
| `url`     | адрес без строки запроса                                                     |
| `ip`      | первый адрес `X-Forwarded-For`, `CF-Connecting-IP` или `REMOTE_ADDR`         |

`headers`, `content` и `json` вычисляются один раз, поэтому запрос, читающий только `post`, не трогает тело.

```php
$request->post['title'] ?? '';        // значение формы как есть
$request->get('id');                  // из input, null если нет
$request->getHeader('X-Csrf-Token');  // имя в любом регистре
```

Типизированное чтение из `input` возвращает значение по умолчанию, если значение не того типа:

| Метод                          | Результат                                                   |
|--------------------------------|-------------------------------------------------------------|
| `getString($key, $default)`    | строка без пробелов по краям; массив и bool — `$default`    |
| `getInt($key, $default)`       | целое, `'12'` → `12`, `'1.5'` → `$default`                  |
| `getFloat($key, $default)`     | число                                                       |
| `getBool($key, $default)`      | `1`, `true`, `on`, `yes` — `true`; `0`, `false`, `off`, `no`, `''` — `false` |

## Ответ

```php
new Response()->json(['data' => $items], 201, ['X-Total' => '42'])->send();

$response = new Response($html, 200, ['Content-Type' => 'text/html']);
$response->statusCode = 404;
$response->setCookie(new Cookie('id', '1', httpOnly: true));
$response->prepare($request)->send();
```

`content`, `statusCode`, `headers` — публичные свойства. `json()` кодирует данные, ставит статус и
`Content-Type: application/json`. К `Content-Type` при отправке добавляется `charset=utf-8`.
`setCookie()` принимает строку заголовка или объект с `__toString()`, например `Expansa\Cookie\Cookie`.
`prepare()` убирает тело у ответа на `HEAD`. `send()` отправляет статус, заголовки и тело и завершает
запрос для клиента (`fastcgi_finish_request()`), скрипт может продолжить работу.

## Редирект

```php
redirect('dashboard');                             // url('dashboard'), 302
Expansa\Http\Redirect::send('https://example.com', 301);
```

`send()` прогоняет адрес, статус и `X-Redirect-By` через фильтры, отправляет заголовки и завершает
скрипт. Если фильтр адреса вернул пустую строку, редирект отменяется и `send()` возвращает управление.
Статус не из `3xx` — `InvalidArgumentException`.

## Ошибки

`HttpError` и наследники превращаются `Kernel` в JSON-ответ `{ "message": ... }` со своим статусом;
`ValidationFailed` добавляет `errors`.

```php
throw new HttpError(409, t('Expansa is already installed.'));
throw new ValidationFailed(t('Check the form'), ['email' => [t('Required')]]);
```
