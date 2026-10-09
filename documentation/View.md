# Введение

Шаблоны: PHP, Blade, HTML, CSS и JS. Пакет находится в `Expansa\View`, доступ — через фасад `View`
и функцию `view()`.

```php
use Expansa\Facades\View;

echo view('components/state', ['title' => t('Nothing found')]);

$view = View::create('components/form/checkbox', $field);
Asset::discover($view->path);
echo $view->render();
```

| Класс                     | Назначение                                                                  |
|---------------------------|-----------------------------------------------------------------------------|
| `Manager`                 | Создаёт виды из каталогов конфигурации, движки, общие данные, секции Blade; экземпляр фасада |
| `View`                    | Шаблон с переменными: `$name`, `$path`, `$data`, `render()`, `with()`       |
| `Contracts\Engine`        | Движок: `render($path, $data)` и `$lastRendered`                            |
| `Engines\*`               | `Php`, `Blade`, `File` (HTML, CSS как есть), `Js` (в теге `<script>`)       |
| `Exceptions\ViewNotFound` | Имя вида не нашлось ни в одном каталоге                                     |

## Конфигурация

Каталоги видов задаются в фазе `configure` (`bootstrap.php`):

```php
View::configure(
    paths: EX_PATH . 'dashboard/views',
    cachePath: EX_PATH . 'cache/views',
);
```

`configure(string|array $paths, string $cachePath = '')`. В `$cachePath` хранятся скомпилированные
Blade-шаблоны: файл пересобирается, когда он старше шаблона или компилятора. Без `$cachePath` шаблоны
компилируются во временный каталог при первом рендере в каждом запросе. Повторный вызов сбрасывает
найденные виды и созданные движки.

## Использование

Имя вида — путь от каталога видов без расширения: `components/form/checkbox`. Ещё два вида имён:

- `seo::panel` — вид из каталогов пространства имён, их добавляет `View::addNamespace('seo', $dir)`;
- абсолютный путь без расширения — файл как есть: `View::create(EX_DASHBOARD . 'forms/posts-import-fields')`.

Если есть файлы с несколькими расширениями, берётся первое по порядку: `blade.php`, `php`, `html`,
`css`, `js`. Каталог читается один раз, найденный и ненайденный вид запоминаются. Вида нет —
`ViewNotFound`.

```php
View::share('user', $user);            // переменная всех видов
View::getShared('user');               // её значение
View::exists('components/dialogs/media-editor');  // есть ли вид

view('page')->with('title', 'Главная')->withSlug('home')->beautify()->render();
```

Переменные вида перекрывают общие. В шаблоне доступны `$__data` — все переменные массивом — и
`$__env` — менеджер. `beautify()` расставляет отступы в HTML, `minify()` сжимает его в одну строку:
вызывайте их один раз на всю страницу, а не на каждую часть.

### Blade

`{{ $x }}` экранирует значение через `escape()`, `{!! $x !!}` выводит как есть, `@{{ }}` оставляет
скобки. Директивы: `@if`, `@elseif`, `@else`, `@unless`, `@isset`, `@empty`, `@foreach`, `@for`,
`@while`, `@switch`, `@php`, `@verbatim`, `@selected`, `@checked`, `@include('name', [...])`,
`@extends('layout')` с `@section` / `@endsection` / `@overwrite` / `@show` и `@yield('name')`.

## Расширение

Свой движок регистрируется для расширения файла и создаётся один раз:

```php
View::extend('twig.php', fn () => new TwigEngine());
```

Движок реализует `Contracts\Engine`. Новое расширение проверяется раньше встроенных, так что
`twig.php` выигрывает у `php`. Встроенный движок заменяется так же: `View::extend('php', ...)`.
