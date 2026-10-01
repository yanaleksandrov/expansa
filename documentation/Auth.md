# Введение

Авторизация: что субъекту разрешено. Есть два вида проверок: **permission** — глобальное право по имени
(`article.update`), и **ability** — действие над конкретным ресурсом (`update` у `$article`), которое решает
policy класса ресурса. Permission обычно выдаются через **роли**. Пакет находится в `Expansa\Auth`,
доступ — через фасады `Auth` (проверки) и `Role` (роли).

```php
use Expansa\Facades\Auth;
use Expansa\Facades\Role;

Role::add('editor', 'Editor', ['article.update', 'article.publish']);

Auth::allows($user, 'article.update');      // permission
Auth::can($user, 'update', $article);       // ability ресурса по его policy
Auth::authorize($user, 'update', $article); // то же, но AccessDenied вместо false
```

Auth не знает, кто такой субъект и что такое ресурс: субъект — любой объект или `null` для гостя,
правила ресурса пишет приложение в `Contracts\Policy`. Роли — встроенный источник permission; его
можно заменить своим (`Contracts\Permissions`): конфиг, внешний сервис, роли в БД.

| Класс                        | Назначение                                                              |
|------------------------------|-------------------------------------------------------------------------|
| `Manager`                    | Проверки permission и ability, реестр policy; экземпляр фасада `Auth`   |
| `Roles`                      | Роли и их permission, источник permission; экземпляр фасада `Role`      |
| `Contracts\Permissions`      | Источник permission: `Roles` или свой                                   |
| `Contracts\Subject`          | Субъект с ролями: `$roles`, его permission даёт `Roles`                 |
| `Contracts\Policy`           | Правила одного типа ресурса                                             |
| `Exceptions\AccessDenied`    | `authorize()` отказал: решение, а не сбой                               |
| `Exceptions\PolicyNotFound`  | Для класса ресурса нет policy: ошибка конфигурации                      |

## Конфигурация

В фазе `configure` (`bootstrap.php`) один экземпляр `Roles` становится и фасадом `Role`, и источником
permission:

```php
$roles = new Expansa\Auth\Roles();
Role::swap($roles);
Auth::configure(
    permissions: $roles,
    policies: [Article::class => ArticlePolicy::class],
);
```

`configure(?Permissions $permissions = null, array $policies = [])`. Повторный вызов заменяет всё. Без
конфигурации любой permission запрещён, а `can()` бросает `PolicyNotFound`: доступ закрыт, пока его
явно не открыли.

Роли регистрируются в фазе `register` на каждый запрос, плагины добавляют свои там же:

```php
Role::add(role: 'author', name: t('Author'), permissions: ['read', 'types_edit']);
Role::add(role: 'reviewer', name: t('Reviewer'), permissions: 'author'); // копия permission роли author
Role::grant('author', 'files_upload');
Role::revoke('author', ['types_edit']);
```

Policy добавляется и позже, например плагином:

```php
Auth::setPolicy(Seo\Redirect::class, Seo\RedirectPolicy::class);
Auth::setPolicy(Seo\Redirect::class, new Seo\RedirectPolicy($settings)); // с зависимостями
```

Класс policy создаётся при первой проверке ресурса без аргументов конструктора; policy с зависимостями
передают экземпляром.

## Использование

### Роли

| Метод                                                 | Что делает                                    |
|-------------------------------------------------------|-----------------------------------------------|
| `add(string $role, string $name, array\|string $permissions = [])` | добавить роль, если её нет; строка — роль, чьи permission копируются |
| `get(string $role): ?array`                           | `['name' => ..., 'permissions' => [...]]`     |
| `all(): array`                                        | все роли по имени                             |
| `hasRole(string $role)`                               | роль есть                                     |
| `forget(string $role)`                                | убрать роль, её субъекты теряют её permission |
| `grant()` / `revoke()`                                | добавить / забрать permission у роли          |
| `hasPermission(string $role, string $permission)`     | у роли есть permission                        |
| `has(?object $subject, string $permission)`           | у какой-то роли субъекта есть permission      |

Роли субъекта хранит приложение. Субъект реализует `Contracts\Subject` — свойство `$roles` с именами ролей;
остальные объекты и гость permission от `Roles` не получают. `App\Models\User` хранит роли в колонке
`roles`:

```php
$user->assignRole('editor'); // только зарегистрированная роль, сохраняет модель
$user->removeRole('editor');
$user->hasRole('editor');
$user->can('types_edit');    // Auth::allows($user, 'types_edit')
```

### Permission

```php
if (Auth::denies($user, 'types_edit')) {
    return;
}
```

`allows()` и `denies()` спрашивают источник permission. Гость — `null`: `Auth::allows(null, 'comment.create')`.

### Policy ресурса

```php
use Expansa\Auth\Contracts\Policy;

final class ArticlePolicy implements Policy
{
    public function can(?object $subject, string $ability, object $resource): bool
    {
        return match ($ability) {
            'view'   => $resource->status === 'published' || $resource->authorId === $subject?->id,
            'update' => $resource->authorId === $subject?->id,
            default  => false,
        };
    }
}

Auth::can($user, 'update', $article);
Auth::cannot($user, 'update', $article);
```

Неизвестную ability policy запрещает (`default => false`). Policy видит только переданные объекты: не
нужно ни ORM, ни запросов, если данных ресурса хватает для решения.

### Сложное правило

Условия сочетаются внутри policy; глобальное право — через источник permission, переданный в конструктор:

```php
use Expansa\Auth\Contracts\Permissions;
use Expansa\Auth\Contracts\Policy;

final class ArticlePolicy implements Policy
{
    public function __construct(private Permissions $permissions) {}

    public function can(?object $subject, string $ability, object $resource): bool
    {
        if ($subject === null || $subject->banned) {
            return $ability === 'view' && $resource->status === 'published';
        }

        $owner  = $resource->authorId === $subject->id;
        $editor = $this->permissions->has($subject, 'other_types_edit');

        return match ($ability) {
            // автор правит незаблокированную статью, редактор — любую, кроме опубликованной заблокированной
            'update'  => ($owner && ! $resource->locked) || ($editor && ! ($resource->locked && $resource->status === 'published')),
            'publish' => $editor && $resource->status === 'draft',
            default   => false,
        };
    }
}

Auth::setPolicy(Article::class, new ArticlePolicy($roles));
```

### Как находится policy

По классу ресурса: сам класс, затем родители от ближайшего, затем интерфейсы. Policy базового класса или
интерфейса (`Owned`) действует для всех наследников, policy класса её перекрывает. Найденная policy
запоминается для класса до следующего `setPolicy()` или `configure()`.

### Отказ и ошибка

`allows()`, `can()` и их отрицания возвращают `bool`: так проверяют, что показать. `authorize()` бросает
`AccessDenied`, когда без права дальше идти нельзя: контроллер, команда, сервис.

```php
Auth::authorize($user, 'article.publish');  // permission: без ресурса
Auth::authorize($user, 'update', $article); // ability: с ресурсом
```

`AccessDenied` — решение: в нём `ability` и `resource`. Всё остальное — сбой и не превращается в отказ:
`PolicyNotFound` (нет policy), исключения источника permission (БД недоступна) проходят как есть.

`App\Http\Kernel` отвечает на `AccessDenied` статусом 403. Http об Auth не знает: связь — в приложении.

## Расширение

Свой источник permission — класс с `Contracts\Permissions`: права из конфига, внешнего сервиса или
поверх `Roles` (кэш, супер-админ, журнал).

```php
use Expansa\Auth\Contracts\Permissions;
use Expansa\Auth\Contracts\Subject;
use Expansa\Auth\Roles;

final class SuperAdmin implements Permissions
{
    public function __construct(private Roles $roles) {}

    public function has(?object $subject, string $permission): bool
    {
        return ($subject instanceof Subject && in_array('admin', $subject->roles, true))
            || $this->roles->has($subject, $permission);
    }
}

Auth::configure(permissions: new SuperAdmin($roles));
```

Свой субъект — любой класс с `Contracts\Subject`: ключ API, сервисный аккаунт.

```php
final class ApiKey implements Expansa\Auth\Contracts\Subject
{
    public function __construct(public array $roles) {}
}

Auth::allows(new ApiKey(['editor']), 'types_edit');
```

## Границы пакета

| Что                        | Где                     | Почему                                                    |
|----------------------------|-------------------------|-----------------------------------------------------------|
| Аутентификация, вход       | `App\Models\User`, `Webauthn`, `Session` | кто субъект — не вопрос Auth                |
| Subject                    | контракт — Auth, класс — приложение | любой объект; `Contracts\Subject` — только для ролей |
| Роли и их permission       | Auth (`Roles`), набор — приложение | реестр в памяти, регистрируется на каждый запрос |
| Роли пользователя          | приложение (`User::$roles`) | хранение — дело модели                               |
| Permission, ability        | имена — приложение      | строки, без классов                                       |
| Policy                     | контракт — Auth, правила — приложение |                                             |
| Gate, Authorizer           | нет                     | это `Manager`                                             |
| Voter                      | нет                     | голосование заменяет одна policy на тип ресурса           |
| Guard, Identity            | нет                     | это аутентификация                                        |
| Middleware, 403            | приложение (`App\Http`) | Auth не зависит от Http, Http — от Auth                   |

Auth зависит только от базового слоя. От него зависят `App\` и плагины, через фасады или `Manager`.
