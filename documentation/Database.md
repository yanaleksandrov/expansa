# Введение

Работа с базой данных: построитель запросов на массивах (порт Medoo), модели с атрибутами и
мутаторами, мета-поля, схема таблиц. Пакет находится в `Expansa\Database`, запросы — через фасад
`Db`, модели наследуют `Expansa\Database\Model`.

```php
use App\Models\User;
use Expansa\Facades\Db;

Db::select('posts', ['id', 'title'], ['status' => 'publish', 'LIMIT' => 10]);

$user = User::get(1);
$user->fill(['firstname' => 'Ann'])->save();
```

| Класс                           | Назначение                                                                  |
|---------------------------------|-----------------------------------------------------------------------------|
| `Query\Builder`                 | Соединение и запросы на массивах; цель фасада `Db`                          |
| `Query\AbstractBuilder`         | Генерация SQL: экранирование, условия, JOIN                                 |
| `Query\Raw`                     | Сырой фрагмент SQL: `Db::raw('NOW()')`                                      |
| `Model`                         | Базовая модель: атрибуты, защита массового присваивания, методы `Query`     |
| `Query`                         | Запросы одной модели: `get()`, `find()`, `save()`, `delete()`, `restore()`  |
| `Attribute`                     | Мутаторы чтения и записи одного атрибута                                    |
| `FieldEav`                      | Мета-поля ключ/значение модели в таблице `{table}_fields`                   |
| `Traits\Has*`                   | Возможности модели: мягкое удаление, очистка, проверка, скрытые атрибуты…   |
| `Contracts\Fieldable`           | Владелец мета-полей                                                         |
| `Schema`, `Schema\*`            | Создание и изменение таблиц                                                 |
| `Exceptions\InvalidConnection`  | Параметры соединения не позволяют подключиться                              |

## Конфигурация

Соединение и сервисы моделей задаются в фазе `configure` (`bootstrap.php`), без запросов к БД:

```php
Db::configure(...EX_DB);

Expansa\Database\Model::configure(
    cache: fn (string $key, string $group, ?Closure $callback = null) => Cache::get($key, $group, $callback),
    forgetCache: fn (string $key, string $group) => Cache::forget($key, $group),
    sanitizer: fn (array $data, array $rules) => Safe::data($data, $rules)->apply(),
    validator: fn (array $data, array $rules, bool $break) => new Expansa\Security\Validator($data, $rules, $break),
);
```

Пакет не зависит от `Cache` и `Security`: кэш, очистка и проверка приходят колбэками.

| Параметр      | Сигнатура колбэка                                                   | Без него                          |
|---------------|---------------------------------------------------------------------|-----------------------------------|
| `cache`       | `(string $key, string $group, ?Closure $callback): mixed` — при промахе сохраняет и возвращает результат `$callback` | строки и мета-поля не кэшируются |
| `forgetCache` | `(string $key, string $group)`                                       | —                                 |
| `sanitizer`   | `(array $data, array $rules): array` — очищенные значения по ключам правил | модель с `HasSanitizing` бросает `LogicException` |
| `validator`   | `(array $data, array $rules, bool $break): object` с `apply()`, `isValid()`, `getErrors()` | модель с `HasValidation` бросает `LogicException` |

Повторный вызов заменяет все колбэки.

## Модели

Модель объявляет таблицу, разрешённые для массового присваивания поля и трейты возможностей:

```php
use Expansa\Database\Attribute;
use Expansa\Database\Model;
use Expansa\Database\Traits\HasSanitizing;
use Expansa\Database\Traits\HasSoftDeletes;

final class Post extends Model
{
    use HasSanitizing;
    use HasSoftDeletes;

    public protected(set) string $table = 'posts';

    protected array $fillable = ['title', 'password'];

    protected function getSanitizerRules(): array
    {
        return ['title' => 'trim'];
    }

    protected function password(): Attribute
    {
        return new Attribute(set: fn (string $value) => password_hash($value, PASSWORD_DEFAULT));
    }
}
```

- `new Post($data)` и `$post->fill($data)` — сырой ввод: только поля из `$fillable`, правила очистки
  и мутаторы записи применяются. Модель без `$fillable` бросает `LogicException`.
- `Post::hydrate($row)` — доверенные данные, например строка БД: как есть, без проверок. С `id` модель
  считается сохранённой, и `save()` пишет только изменённые атрибуты (`getChanges()`).
- `$post->title` читает атрибут через мутатор чтения, `$post->attributes` — сырые значения.
- Методы `Query` вызываются на модели: `Post::get(1)`, `Post::where([...])->find()`, `$post->save()`,
  `$post->delete()`. Неизвестный метод — `BadMethodCallException`.
- Кэшируется только `get()` по `id`, группа кэша — таблица; `save()`, `delete()`, `restore()` его сбрасывают.

Мутатор — метод модели с именем атрибута в camelCase, возвращающий `Attribute`:
`new Attribute(get: ..., set: ...)`, `Attribute::get(...)`, `Attribute::set(...)`.

| Трейт                   | Что даёт                                                                      |
|-------------------------|-------------------------------------------------------------------------------|
| `HasSoftDeletes`        | `delete()` ставит `deleted_at` (`$deletedAtColumn`), запросы пропускают такие строки; `withTrashed()`, `onlyTrashed()`, `restore()` |
| `HasTimestamps`         | `created_at` и `updated_at` читаются как `DateTime`                           |
| `HasSanitizing`         | правила `getSanitizerRules()` при каждой записи атрибута                      |
| `HasValidation`         | `isValid()`, `getValidatorErrors()` по `validatorRules()`, свои правила — в `validatorExtend()` |
| `HasHiddenAttributes`   | атрибуты из `$hidden` не попадают в `toArray()` и JSON                        |
| `HasReadonlyAttributes` | атрибуты из `$readonly` записываются один раз                                 |
| `HasFieldEav`           | мета-поля `$model->field` в `{table}_fields`; модель реализует `Fieldable`    |
| `HasFieldEavTyped`      | то же в пяти таблицах по типу значения, с индексами и сортировкой             |

## Производительность

`tests/benchmarks/Database.php` сравнивает текущий код с коммитом (`--baseline=<ref>`): гидратация 20
строк, чтение атрибутов, `fill()` с очисткой, `get()` по id из кэша и генерация SQL. После выноса
кэша и очистки в колбэки гидратация, чтение и SQL — на уровне прежнего кода; `fill()` с очисткой и
`get()` из кэша — на ~5% (≈0.05 мкс на вызов) медленнее из-за одного вызова колбэка вместо фасада.
