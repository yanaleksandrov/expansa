# OAuth: вход через внешних провайдеров

Вход через Google, GitHub и любой OpenID Connect провайдер. Слой аутентификации делится на ядро и способы
входа: способы (`OAuth`, `Webauthn`) только доказывают личность, ядро (`Authentication`) выдаёт токен и знает
текущего субъекта, `App\` связывает их. Пакеты друг от друга не зависят (CONVENTIONS, «Независимость пакетов»).

| Пакет            | Отвечает за                                                   | Статус   |
|------------------|---------------------------------------------------------------|----------|
| `Authentication` | текущий субъект: подписанный токен, login, logout             | новый    |
| `OAuth`          | протокол OAuth 2.0 / OIDC: редирект, обмен code, профиль      | новый    |
| `Webauthn`       | протокол passkey                                              | есть     |
| `Auth`           | что субъекту разрешено                                        | без изменений |

```
Support · Patterns · Codecs            ← базовый слой
    ↑            ↑         ↑        ↑
Authentication  OAuth   Webauthn   Auth    (друг о друге не знают)
    ↑            ↑         ↑        ↑
    └────── App\ + bootstrap.php ───┘      ← Cookie, Session, Cache, Db
```

## Решения перед стартом

- [ ] Название: переименовать `Auth` → `Access`, чтобы не путать с `Authentication` (дёшево, пакет новый).
- [ ] Провайдеры первой версии: Google, GitHub + общий `OpenId`.
- [ ] Автопривязка по email: выключена по умолчанию или опция для доверенных OIDC-провайдеров.

## 1. Пакет `Authentication` (перенос из `App\Models\User`)

Сначала ядро: OAuth завершается вызовом `Authentication::login()`.

```
Authentication/
├── Manager.php            текущий субъект, login/logout; цель фасада
├── Token.php              id|expires|hmac(id|expires|stamp, key)
├── Contracts/Identity.php $identifier, $stamp
└── Contracts/Users.php    find(string $identifier): ?Identity
```

- [ ] `Contracts\Identity`: `public string $identifier { get; }` (login/UUID), `public string $stamp { get; }`
      (хэш пароля или security stamp — его смена убивает все токены).
- [ ] `Contracts\Users::find()`.
- [ ] `Token`: подпись и проверка, формат как у текущего cookie — после переноса никто не разлогинится;
      `hash_equals`, проверка срока.
- [ ] `Manager`: `configure(users, key, read, write)` — cookie приходит колбэками, без зависимости от `Cookie`;
      `user(): ?Identity` (лениво, один раз за запрос), `isAuthenticated()`, `login(Identity, bool $remember)`,
      `logout()`. Без `configure()` — гость.
- [ ] Фасад `Facades\Authentication` с `@method`.
- [ ] `User` реализует `Identity`; из модели убрать `current()`, `authenticate()`, `signAuthCookie()`,
      `verifyAuthCookie()`, `setAuthCookie()`, `clearAuthCookie()`, `$cookieName`; `changePassword()` перевыпускает токен
      через `Authentication::login()`.
- [ ] `App\Support\Users` реализует `Contracts\Users` (поиск по login).
- [ ] Обновить использования: `User::current()`, `User::isLogged()`, `RequireAuth`, `Passkey`, `UserService`,
      шаблоны, `bootstrap.php` (контекст `sign-out`).
- [ ] `tests/Authentication.php`, `documentation/Authentication.md`; бенчмарк `user()` — горячий путь.

## 2. Пакет `OAuth`

Как `Webauthn`: только протокол, без состояния; хранение state и сеть — у вызывающего.

```
OAuth/
├── Manager.php                     провайдеры из конфигурации, extend(); цель фасада
├── State.php                       state, PKCE verifier, nonce, провайдер, время создания
├── Profile.php                     provider, id, email, emailVerified, name, avatar, raw
├── Contracts/Provider.php          redirect(State): string, profile(array $query, State): Profile
├── Providers/AbstractProvider.php  общий обмен code → token
├── Providers/OpenId.php            любой OIDC: discovery, id_token по JWKS
├── Providers/Google.php            OpenId, issuer accounts.google.com
├── Providers/GitHub.php            OAuth 2.0: /user + /user/emails
├── Internal/Jwt.php                RS256/ES256 через openssl_verify
├── Internal/Jwks.php               JWK → PEM
└── Exceptions/                     InvalidState, InvalidToken, Denied, RequestFailed
```

- [ ] `Manager::configure(providers, redirect, transport, cache)`:
      `providers` — `['google' => ['driver' => 'google', 'client_id' => ..., 'client_secret' => ...]]`;
      `redirect` — `fn (string $provider): string`; `transport` — HTTP-запрос колбэком (HTTP-клиента во фреймворке нет);
      `cache` — discovery и JWKS, чтобы не ходить к провайдеру на каждый вход. `createGoogleDriver()` и т. д., `extend()`.
- [ ] `State::create(string $provider)`: 32 случайных байта state, PKCE verifier, nonce; срок 10 минут.
- [ ] `Profile` — неизменяемый, `raw` — ответ провайдера как есть.
- [ ] `OpenId`: discovery `/.well-known/openid-configuration`, обмен code, проверка `id_token`.
- [ ] `GitHub`: email только из `/user/emails` с `verified: true`.
- [ ] Фасад `Facades\OAuth` с `@method`.
- [ ] Исключения: `InvalidState` (нет, чужой, истёк), `InvalidToken` (подпись, claims), `Denied` (`error=` в callback,
      пользователь отказался), `RequestFailed` (сеть, HTTP-ошибка провайдера).

Безопасность — обязательный минимум:

- [ ] state одноразовый, сравнение `hash_equals` — защита callback от CSRF.
- [ ] PKCE S256 всегда, даже для confidential-клиента.
- [ ] nonce проверяется в `id_token`.
- [ ] `id_token`: подпись по JWKS, `iss`, `aud`, `azp`, `exp`/`iat` с допуском 60 с; алгоритмы из белого списка,
      `none` и HS* отклоняются; неизвестный `kid` — одно обновление JWKS, не больше.
- [ ] email подтверждён только при `email_verified = true` (OIDC) или `verified: true` (GitHub).
- [ ] Токены провайдера не сохраняются: для входа они не нужны.
- [ ] `tests/OAuth.php` без сети: фиктивный `transport`, свои ключи RSA/EC для подписи `id_token`;
      `documentation/OAuth.md`.

## 3. Приложение (`App\`)

- [ ] `env.php`: `EX_OAUTH` — учётные данные провайдеров (не в БД); пример в `env.example.php`.
- [ ] `bootstrap.php`, фаза `configure`: `Authentication::configure()`, `OAuth::configure()`.
- [ ] `App\Support\Http`: curl-транспорт для `OAuth`.
- [ ] Миграция `user_identities`: `user_id`, `provider`, `subject` (уникальная пара), `created_at`.
- [ ] `App\Api\User\OAuth` (как `Passkey`):
      `GET /sign-in/{provider}` — `State` в `Session` под `oauth.<state>`, редирект на `redirect()`;
      `GET /oauth/{provider}/callback` — `pull` state, `profile()`, поиск аккаунта, `Authentication::login()`,
      `Session::regenerateId()`, редирект в дашборд.
- [ ] Правила привязки:
      1. пара `(provider, subject)` найдена — вход её владельцем;
      2. пользователь уже вошёл — привязать провайдера к нему;
      3. есть `User` с тем же email — **не** привязывать автоматически: войти паролем или passkey и привязать
         (автопривязка — только опцией, для доверенных OIDC и при `emailVerified`);
      4. иначе — регистрация, если она открыта: роль по умолчанию + привязка.
- [ ] Ошибки callback — на страницу входа с сообщением, без деталей провайдера.
- [ ] UI: кнопки провайдеров на странице входа (только настроенные); в профиле — привязанные провайдеры,
      привязать/отвязать (нельзя отвязать последний способ входа без пароля и passkey).
- [ ] `RequireAuth::PUBLIC_ROUTES`: маршруты входа и callback.

## Не входит

- Пароль — остаётся `password_verify` в `User`.
- Роли и права — `Auth`.
- Хранение access/refresh токенов провайдера и вызовы его API.
- Expansa как OAuth-сервер.

## Позже, без смены архитектуры

- Bearer-токены API (api-keys) — второй способ чтения в `Authentication`.
- Scopes токенов — через `Auth\Contracts\Subject` и свой `Permissions`.
- TOTP / 2FA — отдельный пакет-способ.
- Новые провайдеры — класс в `OAuth/Providers/` или `OAuth::extend()`.
- Ограничение частоты попыток входа.
