# Введение

HTTP-cookie: проверка атрибутов и значение заголовка `Set-Cookie`. Пакет находится в `Expansa\Cookie`
и зависит только от PHP.

```php
use Expansa\Cookie\Cookie;
use Expansa\Cookie\Enums\SameSite;

Cookie::send(new Cookie(
    name: 'expansa_auth',
    value: $token,
    expires: time() + 86400,
    secure: Cookie::isSecureRequest(),
    httpOnly: true,
    sameSite: SameSite::Lax,
));

$token = Cookie::get('expansa_auth', '');
```

| Класс                        | Назначение                                              |
|------------------------------|---------------------------------------------------------|
| `Cookie`                     | Одна cookie; `__toString()` — значение `Set-Cookie`     |
| `CookieJar`                  | Умолчания и очередь cookie для ответа; цель фасада `Cookie` |
| `Contracts\Factory`, `Queue` | Создание cookie с умолчаниями; очередь                  |
| `Enums\SameSite`             | Атрибут `SameSite`: `None`, `Lax`, `Strict`             |
| `Exceptions\InvalidName`     | Недопустимое имя                                        |

## Использование

Атрибуты — публичные свойства, заданные в конструкторе: `name`, `value`, `expires`, `path`, `domain`,
`secure`, `httpOnly`, `sameSite`; `maxAge` — секунды до истечения.

- Имя — буквы, цифры и `._-`, иначе `InvalidName`.
- Пустое значение удаляет cookie: заголовок с датой в прошлом и `Max-Age=0`.
- `expires` — Unix-время, `0` — cookie до закрытия браузера, отрицательное становится `0`.
- Пустой `path` становится `/`.

`Cookie::send()` сразу отправляет заголовок. Чтобы отправить cookie вместе с ответом, передайте её в
`Expansa\Http\Response::setCookie()`. `Cookie::get()` читает cookie текущего запроса из `$_COOKIE`,
`Cookie::isSecureRequest()` — пришёл ли запрос по HTTPS, в том числе через прокси.

## Очередь cookie

`CookieJar` — цель фасада `Expansa\Facades\Cookie`: создаёт cookie с настроенными умолчаниями и
собирает их в очередь, которую `App\Http\Kernel` добавляет в ответ API (`Response::setCookie()`).
Экземпляр один на запрос, поэтому умолчания и очередь живут между вызовами фасада.

```php
use Expansa\Facades\Cookie;

Cookie::configure(domain: 'example.com', secure: true, sameSite: SameSite::Strict);

Cookie::queue('theme', 'dark', 60 * 24);         // имя, значение, минуты
Cookie::queue(Cookie::createForever('lang', 'ru'));
Cookie::expire('promo');                         // удалить в браузере
Cookie::forget('theme');                         // убрать из очереди
```

| Метод                                        | Что делает                                                   |
|----------------------------------------------|--------------------------------------------------------------|
| `configure($path, $domain, $secure, $httpOnly, $sameSite)` | умолчания, повторный вызов заменяет все; по умолчанию `/`, `''`, `false`, `true`, `Lax` |
| `create($name, $value, $minutes, ...)`       | cookie на `$minutes` минут, `0` — до закрытия браузера; `null`-атрибуты — из умолчаний |
| `createForever(...)`                         | cookie примерно на год                                       |
| `createExpired($name, $path, $domain)`       | cookie, удаляющая существующую                               |
| `queue($cookie или $name, ...)`              | в очередь; та же пара имя и путь заменяется                  |
| `hasQueued($name, $path)`, `getQueued($name, $path)` | есть ли / cookie из очереди; без пути — последняя с этим именем |
| `getQueue()`                                 | все cookie очереди                                           |
| `forget($name, $path)`                       | убрать из очереди, без пути — со всех путей                  |
| `expire($name, $path, $domain)`              | поставить в очередь удаляющую cookie                         |
| `flush()`                                    | очистить очередь                                             |

Умолчания читаются свойствами объекта: `$jar->path`, `domain`, `secure`, `httpOnly`, `sameSite`.
Контракты — `Contracts\Factory` (создание и умолчания) и `Contracts\Queue` (очередь).
