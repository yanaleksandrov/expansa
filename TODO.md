Ты — senior framework architect. Я разрабатываю собственный PHP framework, который является ядром CMS.

Нужно с нуля спроектировать отдельный framework-level package:

Auth

Текущие packages:

Assets, Builders, Cache, Codecs, Console, Cookie, Database, Debug,
Extensions, Facades, Filesystem, Hooks, Http, Images, Lifecycle,
Log, Mail, Patterns, Routing, Scheduler, Security, Session, Support,
Translation, View.

Добавляется:

Auth

## Главная задача

Определи ответственность и границы Auth как независимого framework package.

Важно разделить:

Authentication → кто субъект?
Auth → что субъекту разрешено?
Security → механизмы безопасности
Session → хранение состояния сессии

Не смешивай эти ответственности.

## Authorization

Auth должен поддерживать:

```php
$auth->allows($subject, 'article.update');
$auth->can($subject, 'update', $article);
````

Также нужны соответствующие отрицательные проверки.

Framework не должен знать, что такое `$article`, `User`, Article или CMS-specific permissions.

Пример:

```text
permission:
article.update

policy:
User can update Article only if they are its owner
```

Конкретные модели, permissions и бизнес-правила определяет CMS/application.

## Архитектурные концепции

Исследуй необходимость:

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

Для каждого укажи:

1. Нужен ли он;
2. Зачем;
3. Framework или application/CMS level;
4. Как взаимодействует с остальными;
5. Не дублирует ли другой компонент.

Не копируй Laravel/Symfony без необходимости. Лишние концепции прямо исключай.

## Framework vs CMS

Отдельно определи:

```text
Roles
Permissions
Policies
Abilities
User
```

что относится к framework, а что к CMS/application.

## API

Спроектируй минимальный чистый API. Рассмотри:

```php
$auth->allows($subject, 'article.update');
$auth->denies($subject, 'article.update');

$auth->can($subject, 'update', $article);
$auth->cannot($subject, 'update', $article);
```

При необходимости предложи лучший вариант.

Покажи PHP-примеры:

1. permission check;
2. resource policy;
3. сложное правило с несколькими условиями.

## Policies

Покажи регистрацию:

```php
$auth->policy(Article::class, ArticlePolicy::class);
```

или предложи лучший API.

Объясни, как framework находит policy.

Policy не должна зависеть от ORM.

## Permissions

Auth должен предоставлять permission checking, но не должен требовать конкретного storage.

Возможные реализации:

```text
Static
Config
Database
External provider
Custom
```

Если нужны interfaces — спроектируй их.

## Storage

Auth не должен напрямую зависеть от Database:

```php
$db->query(...);
```

Если persistence нужен — только через abstractions/interfaces.

## Exceptions

Определи:

* нужен ли AuthorizationException;
* нужен ли ForbiddenException;
* когда возвращать bool, а когда бросать exception;
* как отличать deny от системной ошибки;
* как интегрировать deny с HTTP 403.

## Integrations

Объясни интеграцию с:

```text
Security
Session
Http
Routing
Console
Extensions
Facades
```

Особенно:

* должен ли Http зависеть от Auth;
* должен ли Auth зависеть от Security;
* должен ли Security зависеть от Auth;
* нужен ли middleware;
* нужен ли CLI authorization.

Покажи dependency direction.

Все интеграции должны использовать abstractions/adapters.

## Package structure

Предложи конкретную структуру:

```text
Auth/
├── ...
```

Используй только необходимые директории, например:

```text
Contracts
Core
Policies
Exceptions
```

или предложи лучшую структуру.

Не создавай директории ради формальности.

## Dependency graph

Покажи:

```text
Auth
 ├── depends on → ?
 └── used by → ?
```

и общий граф относительно:

```text
Security
Session
Http
Auth
```

## Ограничения

Не делай Laravel-клон.

Не используй:

* global static state;
* God object;
* ORM;
* прямую зависимость от Database;
* прямую зависимость от Session;
* прямую зависимость от HTTP;
* конкретную User model.

Предпочтение:

```text
маленькое ядро
+
минимальные interfaces
+
адаптеры
```

Не добавляй функциональность ради полноты.

## Финальный результат

Дай:

1. Итоговую архитектуру Auth.
2. Обязательные классы.
3. Обязательные interfaces.
4. Framework-level компоненты.
5. CMS/application-level компоненты.
6. Dependency graph.
7. Полный flow permission check.
8. Полный flow policy check.
9. Что НЕ должно входить в Auth.
10. Что можно добавить позже без изменения основной архитектуры.

После архитектуры реализуй минимальную production-ready версию Auth на PHP:

* только обязательные компоненты;
* PHPDoc;
* unit-тесты;
* без optional features до объяснения их необходимости.

Код должен соответствовать предложенной архитектуре и сохранять независимость от ORM, Database, Session, HTTP и конкретной User model.