# Плагины и темы

Пакет `Expansa\Extensions` загружает активные плагины и темы и вызывает их методы жизненного цикла. Плагин — `plugins/<slug>/index.php`, который возвращает анонимный наследник `Expansa\Extensions\Plugin`; тема — то же в `themes/<slug>/`.

```php
use Expansa\Facades\Extensions;

Extensions::load(['plugins/seo', 'plugins/file-manager']);
Extensions::register('plugin');
Extensions::boot('plugin');
```

| Класс | Назначение |
| --- | --- |
| `Manager` | Загружает расширения по id, вызывает `register()`, `boot()` и другие методы, изолирует сбои. |
| `Plugin`, `Theme` | Базовые классы плагина и темы. |
| `Contracts\Extension` | Методы жизненного цикла. |
| `Exceptions\MissingProperty` | У расширения не заданы `name`, `description` или `version`. |
| `Facades\Extensions` | Статический доступ к `Manager`. |

## Настройка

```php
Extensions::configure(
    root: EX_PATH,
    quarantine: EX_STORAGE . 'quarantine.json',
    failed: fn (string $id, Throwable $error) => Log::error('Extension {id} failed: {message}', [
        'id'      => $id,
        'message' => $error->getMessage(),
    ]),
);
```

`root` — каталог с папками `plugins` и `themes`; id вида `plugins/<slug>` отсчитываются от него, другие id игнорируются. `quarantine` — JSON-файл карантина, без него сбои изолируются только в текущем запросе. `failed` получает id и ошибку каждого сбоя.

## Области меню темы

Тема объявляет области, в которых выводятся меню сайта, в конструкторе вместе с метаданными:

```php
return new class extends Expansa\Extensions\Theme {
    public function __construct()
    {
        $this->setName('Aurora')
            ->setDescription('Clean theme for blogs')
            ->setVersion('1.0')
            ->setMenus(['primary' => t('Header'), 'footer' => t('Footer')]);
    }

    // boot(), activate() и другие методы Contracts\Extension
};
```

Ключ — строчные латинские буквы, цифры, `-` и `_`, иначе `setMenus()` бросает `InvalidArgumentException`. Пакет только хранит области в `$theme->menus`. Меню к ним привязывает приложение: `bootstrap.php` передаёт области активной темы в `App\Models\Menu::setThemeLocations()`, а меню области выводится деревом `menu-<ключ>`: `tree('menu-primary', ...)`. Без активной темы есть области `primary` и `footer`. Плагин добавляет свои фильтром `menuLocations`.

Пункт дерева несёт `type`: `link`, `post`, `heading` (заголовок без ссылки), `separator`, `auth` (вход или выход по посетителю) и `profile`. Атрибуты ссылки — в `$item->attributes`: тема выводит их, и клики по пункту считаются для статистики редактора меню.

```php
tree('menu-primary', function (array $items) {
    foreach ($items as $item) {
        echo match ($item->type) {
            'separator' => '<hr>',
            'heading'   => '<span>' . htmlspecialchars($item->title) . '</span>',
            default     => '<a href="' . htmlspecialchars($item->url) . '"' . Expansa\Support\Arr::toHtmlAttributes($item->attributes) . '>' . htmlspecialchars($item->title) . '</a>',
        };
    }
});
```

Для headless-сайта меню области отдаёт `GET /api/menus/<ключ>`: `{"location": "primary", "items": [{"title", "url", "type", ..., "active", "children": []}]}`. Ответ публичный и учитывает посетителя запроса: его вход, язык страницы и даты показа пунктов.

## Установленные и активные

Пакет не хранит, какие расширения активны: список id передаёт приложение. В Expansa это опция `extensions.active`, её читает `bootstrap.php` и меняет `App\Api\Extensions\ExtensionsService` (эндпоинты `extensions/activate` и `extensions/deactivate` с `id` в теле, право `plugins_activate`).

```php
Extensions::getInstalled('plugin'); // ['plugins/seo' => Plugin, ...] — все из папки plugins, кроме карантина
Extensions::activate('plugins/seo');   // вызвать activate(); false — нет такого расширения или метод бросил
Extensions::deactivate('plugins/seo'); // то же для deactivate(); также install() и uninstall()
```

`getInstalled()` только создаёт объекты неактивных расширений ради метаданных: `register()` и `boot()` не вызываются, поэтому конструктор плагина не должен иметь побочных эффектов. Каждый файл подключается один раз за запрос, `load()` и `getInstalled()` делят один объект.

## Изоляция сбоев

Сломанный плагин не должен ронять сайт — особенно сгенерированный пользователем без программиста (см. [Ai.md](Ai.md)). Поэтому код каждого расширения выполняется под защитой:

| Что случилось | Текущий запрос | Следующие запросы |
| --- | --- | --- |
| Исключение (`Exception`) при загрузке или в методе жизненного цикла | расширение отключается, сайт работает | расширение снова загружается: сбой мог быть временным (БД, сеть) |
| `Error` (`TypeError`, вызов неизвестной функции, `ParseError`) там же | расширение отключается, сайт работает | расширение в карантине, не загружается |
| Фатальная ошибка (повторное объявление, память, таймаут) в коде расширения | запрос падает — фатальную ошибку PHP не перехватить | расширение в карантине, не загружается |
| Неперехваченный `Error` из кода расширения позже, например в хуке | запрос падает на странице ошибки | расширение в карантине (вызов `Extensions::quarantine($e)` в `Lifecycle::run()`) |

Фатальную ошибку ловит shutdown-функция: виновник определяется по файлу ошибки, а если ошибка случилась в ядре — по расширению, чей код выполнялся в этот момент. Неперехваченный `Error` приписывается расширению, в чьём файле он брошен или через чей код прошёл.

Хуки, которые расширение успело зарегистрировать до сбоя, остаются в текущем запросе; со следующего запроса расширение в карантине не загружается вовсе.

```php
Extensions::getQuarantined();          // ['plugins/seo' => ['error' => ..., 'file' => ..., 'line' => ..., 'time' => ...]]
Extensions::forget('plugins/seo');      // вернуть из карантина после исправления или переустановки
```

## Проверки

Тесты пакета: `php tests/Extensions.php`.
