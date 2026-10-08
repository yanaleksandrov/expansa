# Auth: вход паролем, passkey и через внешних провайдеров

Аутентификация собрана в один пакет `Auth`: ядро знает текущего пользователя и выдаёт токен, способы входа
(passkey, OAuth/OIDC) только доказывают личность и делят криптографию (`Internal\PublicKey`, `Internal\Jwt`).
Авторизация — отдельный пакет `Access` (бывший `Auth`): ей нужен только субъект, без криптографии и cookie.
`App\` хранит данные (cookie, сессия, таблицы) и связывает способ входа с `Auth::login()`.

| Пакет    | Отвечает за                                                        | Фасады          |
|----------|--------------------------------------------------------------------|-----------------|
| `Auth`   | кто вошёл: токен, login/logout; passkey; OAuth 2.0 / OIDC          | `Auth`          |
| `Access` | что субъекту разрешено: permission, policy, роли                   | `Access`, `Role` |

```
Support · Patterns · Codecs     ← базовый слой
      ↑                 ↑
     Auth             Access    (друг о друге не знают)
      ↑                 ↑
      └─ App\ + bootstrap.php ─┘  ← Cookie, Session, Db
```

```php
Auth::user();                          // ?Identity, лениво, один раз за запрос
Auth::check();                         // вошёл ли
Auth::login($user, remember: true);    // после пароля, passkey или OAuth
Auth::logout();
Auth::refresh($user);                  // stamp сменился (пароль): перевыпустить токен текущего

$state   = State::create('google');                         // в сессию
$url     = Auth::provider('google')->redirect($state);      // редирект
$profile = Auth::provider('google')->profile($_GET, $state); // callback → Profile

Access::authorize(Auth::user(), 'update', $article);
```

## Решения

- [x] `Auth` → `Access`, `Authentication` + `Webauthn` + `OAuth` → `Auth`.
- [x] Провайдеры первой версии: Google, GitHub + общий `OpenId`.
- [x] Автопривязка по email выключена: аккаунт с тем же email привязывают после входа паролем или passkey.
- [x] HTTP-запросы OAuth — curl внутри пакета (`Internal\Http`), `configure(transport:)` подменяет для тестов.

## 1. Пакет `Access` (переименование `Auth`)

- [x] `Expansa\Auth` → `Expansa\Access`, фасад `Facades\Auth` → `Facades\Access`, `Role` → `Access\Roles`.
- [x] Использования: `User`, `Kernel`, `AiService`, `bootstrap.php`, `tests/Access.php`, `documentation/Access.md`.

## 2. Пакет `Auth`

```
Auth/
├── Manager.php            user(), check(), login(), logout(), refresh(), provider(), extend(); цель фасада
├── Passkey.php            бывший Webauthn\RelyingParty
├── Passkey/               Attestation, Assertion, Credential
├── OAuth/                 State (state, PKCE verifier, nonce), Profile
├── Providers/             AbstractProvider, OpenId, Google, GitHub
├── Contracts/             Identity ($identifier, $stamp), Provider
├── Internal/              Token, Http, Jwt, PublicKey, Cbor, AuthenticatorData, ClientData, Payload
└── Exceptions/            InvalidCredential, InvalidState, InvalidToken, Denied, RequestFailed, ProviderNotFound
```

Ядро (перенос из `App\Models\User`):

- [x] `Contracts\Identity`: `$identifier` (login), `$stamp` (хэш пароля — его смена убивает все токены).
- [x] `Internal\Token`: `identifier|expires|hmac(identifier|expires|stamp, key)` — формат текущего cookie,
      после переноса никто не разлогинится; `hash_equals`, проверка срока.
- [x] `Manager::configure(find, key, read, write, lifetime, remember_lifetime, providers, transport)`:
      cookie и поиск пользователя — колбэками. Без `configure()` — гость.

Passkey (перенос `Webauthn`):

- [x] `RelyingParty` → `Passkey`, `Attestation`/`Assertion`/`Credential` → `Passkey\`, внутренние — в `Internal\`.
- [x] `tests/Webauthn.php` → часть `tests/Auth.php`.

OAuth:

- [x] `State::create(provider)`: случайные state, PKCE verifier, nonce, время; `toArray()`/`fromArray()` для сессии.
- [x] `Profile`: provider, id, email, emailVerified, name, avatar, raw.
- [x] `Providers\AbstractProvider`: redirect с PKCE (S256), проверка state и срока, обмен code, `error=` → `Denied`.
- [x] `OpenId`: discovery, `id_token` по JWKS (RS256/ES256), claims `iss`, `aud`, `exp`, `nonce`.
- [x] `Google` — `OpenId` с issuer `https://accounts.google.com`; `GitHub` — `/user` + `/user/emails` (`verified`).
- [x] email подтверждён только при `email_verified = true` (OIDC) или `verified: true` (GitHub).
- [x] Токены провайдера не сохраняются.
- [x] `tests/Auth.php` без сети: фиктивный `transport`, свои RSA/EC ключи для `id_token`.
- [x] `documentation/Auth.md`; бенчмарк разбора токена: regex и ручной разбор равны (~0,38 мкс), оставлен regex.

## 3. Приложение (`App\`)

- [x] `User` реализует `Identity`; из модели убраны `isLogged()`, `authenticate()`, `logout()`, работа с cookie;
      `current()` — типизированная обёртка над `Auth::user()`.
- [x] `bootstrap.php`: `Auth::configure()` с cookie, `EX_KEYS['auth']` и `EX_OAUTH`; `Access::configure()`.
- [x] `env.php`: `EX_OAUTH` — учётные данные провайдеров; пример в `env.example.php`.
- [x] Миграция `user_identities`: `user_id`, `provider`, `subject` (уникальная пара), `created_at`.
- [x] `App\Api\User\OAuth`: `GET /oauth/{provider}` — `State` в сессию, редирект; `GET /oauth/{provider}/callback` —
      `profile()`, поиск аккаунта, `Auth::login()`, `Session::regenerateId()`, редирект в дашборд.
- [x] Правила привязки:
      1. пара `(provider, subject)` найдена — вход её владельцем;
      2. пользователь уже вошёл — привязать провайдера к нему;
      3. есть `User` с тем же email — не привязывать: войти паролем или passkey и привязать;
      4. иначе — регистрация, если она открыта: роль по умолчанию + привязка.
- [x] Ошибки callback — на страницу входа с сообщением, без деталей провайдера.
- [x] UI: кнопки настроенных провайдеров на странице входа.
- [x] Ограничение попыток входа паролем: `Auth::attempt()` с device cookies (OWASP), лимит на IP, нарастающая
      блокировка; опция `EX_AUTH`. Проверка неизвестного логина против фиктивного хэша.
- [x] Закрыты дыры: отключённые аккаунты (`status`) не входят и теряют токены; подтверждение email после
      регистрации; регистрация только при `users.membership`; `Auth::limit()` для сброса пароля, регистрации,
      passkey и OAuth; `user/update` меняет только поля профиля.
- [x] Возврат на запрошенную страницу админки после входа (`redirect_to`, как в WordPress), только пути сайта.
- [x] Повторное подтверждение паролем или passkey (`App\Api\User\Confirmation`, 15 минут) для смены email,
      добавления и удаления способов входа, выхода с устройств.
- [x] Письмо о входе с нового устройства со ссылкой «это не я»: выход везде и сброс пароля.
- [x] Журнал безопасности (`user_events`) и блок «Recent activity» в профиле.
- [x] Проверка паролей: длина, не логин и не email, база утечек HIBP (`EX_AUTH['breached']`).
- [x] UI профиля: подключённые аккаунты, подключение — с текущим паролем (как passkey), отключение.
- [x] Несколько аккаунтов в браузере: `Auth::switchAccount()`, меню пользователя, `sign-in?add=1`.
- [x] Сессии на сервере (`Contracts\Sessions`, таблица `user_sessions`): устройства в профиле, выход с любого.
- [ ] Проверить вход Google и GitHub на живом сайте с настоящими ключами.
- [ ] Существующие установки: создать таблицы `media`, `passkeys`, `user_identities`, `user_sessions`, `user_events`, добавить
      внешние ключи таблиц полей и заполнить пустые `uuid` (миграции есть только в установщике).

## Не входит

- Пароль — `password_verify` в `User`.
- Хранение access/refresh токенов провайдера и вызовы его API.
- Expansa как OAuth-сервер.

## Позже, без смены архитектуры

- Bearer-токены API (api-keys) — второй способ чтения в `Auth\Manager`.
- Scopes токенов — через `Access\Contracts\Subject` и свой `Permissions`.
- TOTP / 2FA — способ входа в `Auth`.
- Новые провайдеры — класс в `Auth/Providers/` или `Auth::extend()`.
- Кэш discovery и JWKS.
