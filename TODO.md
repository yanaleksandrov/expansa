Ты — senior framework architect. Я разрабатываю собственный PHP framework, который является ядром CMS.

Мне нужно спроектировать отдельный core package под названием:

`Auth`

Важно: это именно **framework-level infrastructure**, а не CMS-domain. Не нужно проектировать User, Article, Post, AdminPanel и другие сущности конкретной CMS.

Моя текущая структура framework:

* Assets
* Builders
* Cache
* Codecs
* Console
* Cookie
* Database
* Debug
* Extensions
* Facades
* Filesystem
* Hooks
* Http
* Images
* Lifecycle
* Log
* Mail
* Patterns
* Routing
* Scheduler
* Security
* Session
* Support
* Translation
* View

Я хочу добавить:

* Auth

## Главная задача

Спроектируй `Auth` с нуля как независимый framework package.

В первую очередь определи его ответственность и границы.

Мне важно разделить:

```text
Authentication → кто субъект?
Auth           → что этому субъекту разрешено?
Security       → механизмы безопасности
Session        → хранение состояния сессии
```

Не смешивай эти ответственности.

## Authorization

`Auth` должен поддерживать как минимум два типа проверок.

Простая проверка permission:

```php
$auth->allows($subject, 'article.update');
```

И проверка действия над конкретным ресурсом:

```php
$auth->can($subject, 'update', $article);
```

При этом framework не должен знать, что такое `$article`.

Например:

```text
permission:
article.update

policy:
User can update Article only if they are its owner
```

Конкретные `Article`, `User` и CMS permissions должны определяться приложением/CMS, а не самим framework.

## Исследуй архитектуру

Определи, нужны ли в `Auth` следующие понятия:

```text
Subject
Identity
Role
Permission
Ability
Policy
Gate
Authorizer
Voter
Guard
```

Для каждого:

1. Нужен ли он?
2. Если нужен — зачем?
3. На каком уровне он должен находиться?
4. Как он взаимодействует с остальными?
5. Не дублирует ли он другой компонент?

Не добавляй сущности просто потому, что они существуют в Laravel/Symfony.

Если какая-то концепция лишняя — прямо исключи её.

## Framework vs CMS

Особенно чётко раздели:

### Framework-level

То, что должно находиться внутри `Auth`.

### CMS-level

То, что должно реализовываться самой CMS поверх framework.

Например, ответь отдельно:

```text
Roles        → framework или CMS?
Permissions  → framework или CMS?
Policies     → framework или CMS?
Abilities    → framework или CMS?
User         → framework или CMS?
```

## API

Спроектируй минимальный и чистый API.

Например:

```php
$auth->allows($subject, 'article.update');

$auth->denies($subject, 'article.update');

$auth->can($subject, 'update', $article);

$auth->cannot($subject, 'update', $article);
```

Но не ограничивайся этим API — предложи лучший вариант, если считаешь нужным.

Покажи реальные PHP-примеры:

### 1. Permission

```php
$auth->allows($subject, 'article.update');
```

### 2. Resource policy

```php
$auth->can($subject, 'update', $article);
```

### 3. Сложное правило

Правило должно зависеть от нескольких условий.

## Exceptions

Определи:

* нужен ли `AuthorizationException`;
* нужен ли `ForbiddenException`;
* должен ли `Auth` возвращать `bool` или бросать exceptions;
* как framework должен отличать `deny` от ошибки;
* как это должно интегрироваться с HTTP 403.

## Policies

Покажи, как приложение регистрирует policy:

```php
$auth->policy(Article::class, ArticlePolicy::class);
```

или предложи другой API.

Покажи, как framework находит нужную policy.

Policy не должна зависеть от ORM.

## Permissions

Покажи, как framework предоставляет permission checking, но не заставляй framework хранить permissions в конкретной базе данных.

Например, реализация может быть:

```text
Static
Database
Config
External provider
Custom
```

Если для этого нужны interfaces — спроектируй их.

## Storage

`Auth` не должен напрямую зависеть от Database.

Не делай внутри него:

```php
$db->query(...);
```

Вместо этого используй abstractions/interfaces, если persistence вообще нужен.

## Интеграции

Объясни, как `Auth` интегрируется с:

```text
Security
Session
Http
Routing
Console
Extensions
Facades
```

Особенно важно:

* должен ли `Http` зависеть от `Auth`;
* должен ли `Auth` зависеть от `Security`;
* должен ли `Security` зависеть от `Auth`;
* нужен ли middleware;
* нужен ли CLI authorization.

Покажи dependency direction.

## Структура пакета

Предложи конкретную структуру:

```text
Auth/
├── ...
```

Раздели:

```text
Contracts
Core
Policies
Exceptions
```

или предложи другую структуру, если она архитектурно лучше.

Не создавай лишние директории.

## Dependency graph

Покажи:

```text
Auth
 ├── depends on → ?
 └── used by → ?
```

И общий граф относительно моих существующих пакетов.

Особенно хочу увидеть:

```text
Security
Session
Http
Auth
```

## Архитектурные ограничения

Не делай Laravel-клон.

Не используй глобальное статическое состояние.

Не делай God object.

Не привязывай `Auth` напрямую к:

* ORM;
* Database;
* Session;
* HTTP;
* конкретной модели User.

Все интеграции должны быть через abstractions/adapters.

Не добавляй функциональность только ради полноты.

Предпочтение:

```text
маленькое ядро
+
расширяемые interfaces
+
адаптеры
```

вместо огромного универсального Auth subsystem.

## Финальный результат

В конце дай:

1. Итоговую архитектуру `Auth`.
2. Список обязательных классов.
3. Список обязательных interfaces.
4. Что является CMS-level.
5. Что является framework-level.
6. Dependency graph.
7. Пример полного flow проверки permission.
8. Пример полного flow проверки policy.
9. Что НЕ нужно включать в `Auth`.
10. Что можно добавить позже без изменения основной архитектуры.

После архитектуры реализуй **минимальную production-ready версию `Auth` на PHP**: только обязательные компоненты, с PHPDoc и unit-тестами. Не реализуй опциональные функции, пока не объяснишь, зачем они нужны.
