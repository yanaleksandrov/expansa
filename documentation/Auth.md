# Введение

Аутентификация: кто вошёл. Ядро хранит пользователя в подписанном токене (в cookie) и отдаёт его через
`Auth::user()`. Способы входа только доказывают личность и заканчиваются вызовом `Auth::login()`:
пароль (`App\Models\User`), passkey (`Passkey`) и внешние провайдеры OAuth 2.0 / OpenID Connect
(`Auth::provider()`). Пакет находится в `Expansa\Auth`, доступ — через фасад `Auth`.

```php
use Expansa\Facades\Auth;

Auth::user();                          // ?Identity, один раз за запрос
Auth::isLoggedIn();
Auth::login($user, remember: true);    // после пароля, passkey или провайдера
Auth::logout();
```

На сервере ничего не хранится: анонимные запросы (боты) ничего не пишут. Что разрешено вошедшему — вопрос
пакета `Access`: `Access::allows(Auth::user(), 'types_edit')`.

| Класс                         | Назначение                                                              |
|-------------------------------|-------------------------------------------------------------------------|
| `Manager`                     | Текущий пользователь, login/logout, реестр провайдеров; экземпляр фасада `Auth` |
| `Passkey`                     | WebAuthn relying party: опции для браузера и проверка ответов           |
| `Passkey\Attestation`         | Разобранный ответ регистрации `navigator.credentials.create()`          |
| `Passkey\Assertion`           | Разобранный ответ входа `navigator.credentials.get()`                   |
| `Passkey\Credential`          | Сохранённый ключ: всё для проверки следующих входов                     |
| `OAuth\State`                 | Секреты одной попытки входа через провайдера: state, PKCE, nonce        |
| `OAuth\Profile`               | Пользователь, как его подтверждает провайдер                            |
| `Providers\AbstractProvider`  | Поток authorization code с PKCE, база провайдеров                       |
| `Providers\OpenId`            | Любой OpenID Connect по issuer; Google — он же                          |
| `Providers\GitHub`            | GitHub OAuth app                                                        |
| `Contracts\Identity`          | Пользователь, который может войти: `$identifier`, `$stamp`              |
| `Contracts\Provider`          | Провайдер: `redirect()`, `profile()`                                    |
| `Contracts\Sessions`          | Записи входов на сервере: выход с отдельного устройства                 |
| `Exceptions\InvalidCredential`| Ответ passkey не прошёл проверку                                        |
| `Exceptions\InvalidState`     | Callback не совпал с попыткой: чужой, истёкший, без code                |
| `Exceptions\InvalidToken`     | Токен личности провайдера не прошёл проверку                            |
| `Exceptions\Denied`           | Пользователь или провайдер отказал во входе                             |
| `Exceptions\RequestFailed`    | Провайдер недоступен или ответил ошибкой                                |
| `Exceptions\ProviderNotFound` | Провайдер не настроен: ошибка конфигурации                              |
| `Exceptions\TooManyAttempts`  | Попытки входа по ключу исчерпаны: `$retryAfter` секунд до следующей     |

## Конфигурация

В фазе `configure` (`bootstrap.php`): как найти пользователя, ключ подписи, как читать и отправлять токен,
провайдеры.

```php
Auth::configure(
    find: fn (string $login) => ($user = User::find($login, 'login')) instanceof User ? $user : null,
    key: EX_KEYS['auth'],
    read: fn (string $name) => (string) Cookie::get("expansa_$name", ''),     // auth, device
    write: fn (string $name, string $value, int $expires) => Cookie::send(new Cookie(
        name: "expansa_$name",
        value: $value,          // пустая строка удаляет cookie
        expires: $expires,      // 0 — до закрытия браузера
        path: '/',
        httpOnly: true,
        sameSite: SameSite::Lax,
    )),
    providers: fn () => [
        'google' => ['client_id' => '...', 'client_secret' => '...', 'redirect' => url('oauth/google/callback')],
        'github' => ['client_id' => '...', 'client_secret' => '...', 'redirect' => url('oauth/github/callback')],
        'gitlab' => ['driver' => 'openid', 'issuer' => 'https://gitlab.com', 'client_id' => '...', 'client_secret' => '...',
                     'redirect' => url('oauth/gitlab/callback')],
    ],
);
```

| Параметр           | По умолчанию | Что это                                                               |
|--------------------|--------------|-----------------------------------------------------------------------|
| `find`             | `null`       | `fn (string $identifier): ?Identity`                                  |
| `key`              | `''`         | секрет подписи; без него все — гости                                  |
| `read`             | `null`       | `fn (string $name): string` — cookie запроса: `auth`, `device`, `accounts` |
| `write`            | `null`       | `fn (string $name, string $value, int $expires): void`               |
| `lifetime`         | `172800`     | срок токена, секунд (2 дня)                                           |
| `rememberLifetime` | `1209600`    | срок с «запомнить меня» (14 дней), cookie переживает перезапуск браузера |
| `providers`        | `[]`         | имя => конфиг, или колбэк, который вернёт их при первом обращении     |
| `transport`        | curl         | `fn (string $method, string $url, array $headers, string $body): array{status: int, body: string}` |
| `maxAttempts`      | `0`          | неудачных паролей на устройство или на логин с неизвестных; 0 — без ограничения |
| `maxIpAttempts`    | `0`          | неудачных паролей на IP с неизвестных устройств; 0 — без лимита IP     |
| `lockout`          | `900`        | первая блокировка, секунд; каждая следующая вдвое дольше, до суток    |
| `readAttempts`     | `null`       | `fn (string $key): ?array` — сохранённые попытки                      |
| `writeAttempts`    | `null`       | `fn (string $key, ?array $attempts, int $ttl): void` — `null` удаляет |
| `sessions`         | `null`       | `Contracts\Sessions` — записи входов; без них токены без состояния     |

Конфиг провайдера: `driver` (по умолчанию — имя: `google`, `github`, `openid`), `client_id`, `client_secret`,
`redirect` — callback URL, зарегистрированный у провайдера, `scopes` (свои по умолчанию у каждого),
`issuer` — для `openid`. Колбэк в `providers` нужен, когда значения известны позже: в Expansa URL сайта
читается из БД, а `configure` не делает запросов.

Без `configure()` все — гости, а `login()` бросает `LogicException`.

В Expansa ограничение попыток и провайдеры задаются в `env.php`, кнопки на странице входа появляются для каждого
провайдера:

```php
define('EX_AUTH', ['attempts' => 5, 'ip_attempts' => 50, 'lockout' => 900]); // 0 — без ограничения

define('EX_OAUTH', [
    'google' => ['client_id' => '...', 'client_secret' => '...'],
]);
```

Попытки хранятся в таблице `cache` (`Cache\Providers\Database`), а не в хранилище кэша по умолчанию: оно может
быть памятью запроса.

## Использование

### Текущий пользователь

`user()` читает токен один раз за запрос. Неверный, истёкший или выданный удалённому пользователю токен
удаляется (`write('', 0)`).

```php
$user = Auth::user();               // ?Identity
$user = App\Models\User::current(); // то же, типизировано как ?User
```

### Вход и выход

```php
if (password_verify($password, $user->password)) {
    Auth::login($user, remember: true);
}

Auth::logout();
```

Токен — `identifier|expires|session|hmac(identifier|expires|session|stamp, key)`. Смена `stamp` (у `User` это хэш пароля) или
ключа завершает все сессии. После смены пароля текущее устройство остаётся в системе:

```php
$wasCurrent = Auth::user()?->identifier === $user->login; // до смены: токен подписан старым хэшем
$user->update(['password' => $new]);
if ($wasCurrent) {
    Auth::refresh($user); // перевыпустить токен с тем же сроком; для другого пользователя ничего не делает
}
```

### Несколько аккаунтов в браузере

Вход в другой аккаунт не выходит из текущего: прежний уходит в cookie `accounts` (до 5 аккаунтов на браузер),
между ними можно переключаться без пароля.

```php
Auth::login($editor);              // $admin остаётся в браузере
Auth::getAccounts();               // [$admin] — другие аккаунты, недавние первыми
Auth::switchAccount('admin');      // $admin текущий, $editor — в списке
Auth::logout();                    // выйти из текущего, следующий становится текущим
Auth::logout(all: true);           // выйти из всех
```

- В cookie лежат те же подписанные токены, что и в `auth`: смена пароля или удаление сессии аккаунта убирает
  его из списка.
- Cookie `accounts` переживает закрытие браузера, только если все токены в ней были с «запомнить меня»:
  токен без него не должен остаться на чужом компьютере.
- В Expansa: пункт «Add another account» меню пользователя ведёт на `sign-in?add=1`, другие аккаунты
  перечислены в том же меню (`user/switch-account`), `sign-out?all=1` — выход из всех.

### Сессии и выход с устройства

С `configure(sessions:)` каждый вход записывается на сервере (`Contracts\Sessions`), токен несёт ID сессии:
`identifier|expires|session|hmac`. Удалите запись — устройство вышло, даже если cookie у него осталась.
Без `sessions` токены работают без состояния, как прежде.

```php
final class Sessions implements Expansa\Auth\Contracts\Sessions
{
    public function create(Identity $user, int $expires): string { /* запись, вернуть ID из букв и цифр */ }
    public function isActive(string $id, Identity $user): bool { /* запись есть и принадлежит пользователю */ }
    public function delete(string $id): void { /* удалить запись */ }
}

Auth::getSessionId(); // ID сессии текущего устройства, например чтобы отметить его в списке
```

- `isActive()` вызывается на каждом запросе вошедшего пользователя: держите поиск по индексу, отметку
  последнего использования пишите не чаще раза в несколько минут.
- `logout()` удаляет сессию, `refresh()` сохраняет её. Анонимные запросы по-прежнему ничего не пишут.
- В Expansa — `App\Api\User\Sessions`, таблица `user_sessions` (хранится SHA-256 ID). В профиле блок
  «Devices»: устройства, «This device», выход с любого (`user/session-delete`) и со всех остальных
  (`user/sessions-delete-others`). Смена пароля удаляет записи остальных устройств.

### Ограничение попыток

`attempt()` проверяет пароль и считает неудачи. Схема — device cookies (OWASP): злоумышленник не может
заблокировать владельца аккаунта.

```php
use Expansa\Auth\Exceptions\TooManyAttempts;

try {
    $isValid = Auth::attempt($user?->identifier ?? $login, $ip, fn () => password_verify($password, $hash));
} catch (TooManyAttempts $e) {
    return error('user-login', t('Try again in :minutes min.', (int) ceil($e->retryAfter / 60)));
}
```

| Откуда попытка                                   | Счётчики                         | Лимит           |
|--------------------------------------------------|----------------------------------|-----------------|
| Доверенное устройство: браузер уже входил как этот пользователь | свой, на устройство | `maxAttempts`   |
| Неизвестное устройство                           | общий на логин и общий на IP     | `maxAttempts`, `maxIpAttempts` |

- `login()` выдаёт cookie `device` на год. Её подпись отличается от подписи токена входа, поэтому одну нельзя
  выдать за другую. `logout()` и смена пароля её не трогают.
- Злоумышленник со сменой IP упирается в общий счётчик логина, но блокирует только неизвестные устройства:
  владелец входит со своего браузера, с нового — через passkey или сброс пароля.
- Счётчик IP закрывает перебор одного пароля по многим логинам. Он мягкий (50 по умолчанию в Expansa), чтобы
  не задеть офис за NAT, и не действует на доверенные устройства.
- Блокировка — `lockout` секунд, каждая следующая вдвое дольше (15 мин, 30 мин, 1 ч… с `lockout: 900`), не
  больше суток. Неудачи дальше друг от друга, чем `lockout`, не складываются; сутки без неудач забывают прошлые
  блокировки.
- Успех сбрасывает счётчик устройства или логина. Счётчик IP остаётся: вход в свой аккаунт не должен обнулять
  перебор чужих.
- Передавайте идентификатор найденного пользователя, а не введённый email: иначе вход по email и по логину
  считались бы раздельно и не узнали бы доверенное устройство.
- Пользователя, которого нет, проверяйте против фиктивного хэша, как `App\Models\User::login()`: быстрый ответ
  выдал бы, что логин не зарегистрирован.
- В хранилище попадают SHA-256 ключей, а не логины и IP. `isTrustedDevice($identifier)` отвечает, доверенный ли
  браузер.

Без `maxAttempts` или хранилища `attempt()` просто возвращает результат проверки.
### Passkey

Только протокол: challenge и ключи хранит вызывающий код (в Expansa — `App\Api\User\Passkey`, сессия и
таблица `passkeys`).

```php
use Expansa\Auth\Passkey;
use Expansa\Auth\Passkey\Assertion;
use Expansa\Auth\Passkey\Attestation;

$passkey = new Passkey(id: 'example.com', origin: 'https://example.com', name: 'Example');

// регистрация вошедшего пользователя
$options    = $passkey->creationOptions($challenge, $user->uuid, $user->login, $user->showname, $registered);
$credential = $passkey->register(Attestation::parse($json), $challenge, $user->uuid); // сохранить

// вход: ключ находится по $assertion->id
$assertion = Assertion::parse($json);
$stored    = $passkey->authenticate($assertion, $challenge, $credential); // сохранить новый счётчик
Auth::login($owner);
```

Challenge — 32 случайных байта, хранится на сервере до ответа и принимается один раз. Счётчик подписи
только растёт: клон аутентификатора отклоняется.

### Внешние провайдеры

Два шага: редирект на страницу согласия и callback. Между ними `State` лежит в сессии.

```php
use Expansa\Auth\OAuth\State;

// GET /oauth/google
$state = State::create('google');
Session::set('oauth', $state->toArray());
Redirect::send(Auth::provider('google')->redirect($state));

// GET /oauth/google/callback
$state = State::fromArray(Session::get('oauth'));
Session::forget('oauth');                                 // до проверки: callback нельзя повторить
$profile = Auth::provider('google')->profile($_GET, $state);
// $profile->provider, ->id, ->email, ->emailVerified, ->name, ->avatar, ->raw
```

Что проверяется:

- `state` совпадает с попыткой, попытка не старше `State::TTL` (10 минут) и того же провайдера;
- code обменивается с PKCE verifier (S256): перехваченный code бесполезен;
- OpenID: discovery по `issuer`, подпись `id_token` ключом из JWKS (RS256, ES256; `none` и HMAC
  отклоняются), `iss`, `aud` (и `azp` при нескольких), `exp`, `nonce`;
- GitHub: email берётся только из `/user/emails` с `verified: true`, публичный email профиля не используется.

Токены провайдера не сохраняются. Пара `provider` + `id` — это аккаунт; email надёжен только при
`emailVerified`, и даже тогда аккаунт с тем же email привязывает его владелец после входа, а не автоматически
(так делает `App\Api\User\Identities`).

| Исключение      | Когда                                       | Что делать                          |
|-----------------|---------------------------------------------|-------------------------------------|
| `Denied`        | пользователь нажал «Отмена», `error=` в callback | вернуть на страницу входа        |
| `InvalidState`  | нет попытки, чужая, истекла, нет code       | начать вход заново                  |
| `InvalidToken`  | подпись, issuer, audience, срок, nonce      | записать в лог, вернуть на вход     |
| `RequestFailed` | сеть, HTTP-ошибка, не JSON                  | записать в лог, вернуть на вход     |

`getProviders()` возвращает имена настроенных провайдеров — например, для кнопок входа.

## Расширение

Свой драйвер провайдера:

```php
Auth::extend('gitea', fn (array $config, string $name, ?Closure $transport) => new Gitea(
    name: $name,
    clientId: $config['client_id'],
    clientSecret: $config['client_secret'],
    redirect: $config['redirect'],
    transport: $transport,
));
```

Провайдер OAuth 2.0 наследует `Providers\AbstractProvider` (PKCE, state и обмен code уже есть):

```php
use Expansa\Auth\OAuth\Profile;
use Expansa\Auth\OAuth\State;
use Expansa\Auth\Providers\AbstractProvider;

final class Gitea extends AbstractProvider
{
    protected const array SCOPES = ['read:user'];

    protected function getAuthorizeUrl(): string
    {
        return 'https://gitea.com/login/oauth/authorize';
    }

    protected function getTokenUrl(): string
    {
        return 'https://gitea.com/login/oauth/access_token';
    }

    protected function createProfile(array $token, State $state): Profile
    {
        $user = $this->request('GET', 'https://gitea.com/api/v1/user', ['Authorization' => 'Bearer ' . $token['access_token']]);

        return new Profile(provider: $this->name, id: (string) $user['id'], name: $user['login']);
    }
}
```

Провайдер OpenID Connect не требует класса: `'driver' => 'openid', 'issuer' => '...'`. Совсем другой протокол —
свой класс с `Contracts\Provider`.

Тесты без сети: `configure(transport: ...)` подменяет HTTP (см. `tests/Auth.php`).

## Границы пакета

| Что                          | Где                                | Почему                                       |
|------------------------------|------------------------------------|----------------------------------------------|
| Права, роли, policy          | `Access`                           | авторизация не зависит от способа входа      |
| Cookie, сессия               | приложение, колбэки `configure()`  | пакет не зависит от `Cookie` и `Session`     |
| Пароль                       | `App\Models\User`                  | `password_verify`, затем `Auth::login()`     |
| Хранение passkey и аккаунтов провайдеров | приложение (`passkeys`, `user_identities`) | схема — дело приложения     |
| Правила привязки аккаунтов   | `App\Api\User\Identities`          | политика сайта: регистрация, email           |
| HTTP к провайдерам           | `Internal\Http` (curl)             | без зависимости от пакета `Http`             |
| Токены провайдера, его API   | нет                                | для входа не нужны                           |

Auth зависит только от базового слоя (`Codecs`). От него зависят `App\`, `bootstrap.php` и плагины.
