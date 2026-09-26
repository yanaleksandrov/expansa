# Введение

HTTP-cookie: проверка атрибутов и значение заголовка `Set-Cookie`. Пакет находится в `Expansa\Cookie`
и зависит только от PHP.

```php
use Expansa\Cookie\Cookie;
use Expansa\Cookie\SameSite;

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
| `SameSite`                   | Атрибут `SameSite`: `None`, `Lax`, `Strict`             |
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
