# Введение

Жизненный цикл описывает, **что, когда и где** загружается при каждом запросе. Весь порядок
записан в одном файле, `expansa-cms/bootstrap.php`, и читается сверху вниз: фазы, затем контексты,
затем запуск роутинга. Сам механизм находится в `Expansa\Lifecycle\Manager`, доступ к нему
через фасад `Lifecycle`.

Принцип тот же, что у WordPress (`plugins_loaded` → `init` → `wp_loaded` → …): фиксированный
порядок и хук на каждом шаге. Отличия:

- загружается только то, что нужно запросу: API не подключает меню и ассеты дашборда;
- расширения сначала объявляют себя (`register()`), и только потом запускаются (`boot()`);
- после отправки ответа выполняется хук `terminate`.

Консоль (`php artisan`) проходит через тот же цикл, в контексте `cli`.

## Порядок загрузки

```
index.php / artisan
└─ bootstrap.php
   ├─ константы, env.php, autoload.php
   ├─ functions.php                metrics() считает время от начала запроса
   ├─ Requirements::check()        старая версия PHP → страница ошибки (500) или текст в консоли
   ├─ $isInstalled = Installation::isComplete() вычисляется один раз
   ├─ фазы
   │  ├─ boot          вывод ошибок в debug, maintenance.php, ленивые справочники
   │  ├─ configure     configs.php: слушатели хуков, поля форм, переводы
   │  ├─ register      register.php: роли, типы постов                   только если установлено
   │  ├─ extensions    загрузка активных расширений, их register()       только если установлено
   │  └─ booted        boot() всех расширений                            только если установлено
   ├─ контекст (первый подходящий)
   │  ├─ cli           консоль: Expansa\Console\Terminal
   │  ├─ api           /api/...: API-маршруты в bootstrap.php
   │  ├─ install       система не установлена: установщик
   │  ├─ auth          sign-in, sign-up, reset-password
   │  ├─ dashboard     /dashboard/...: проверка входа, ассеты, меню
   │  └─ web           всё остальное: главная и /installed, иначе 404
   ├─ маршрутизация    Route::run() внутри Lifecycle::run() (кроме cli)
   ├─ run(catch:)      исключение из любого шага → Debug::render(), страница отладки
   └─ terminate        хук после отправки ответа
```

## Фазы

Фаза — это шаг, который выполняется при каждом запросе, в порядке объявления.

```php
use Expansa\Facades\Lifecycle;

Lifecycle::phase('register', $isInstalled, function () {
    require_once EX_PATH . 'register.php';
});
```

Второй аргумент — условие, как у контекста: фаза выполняется, только если оно равно `true`. Так
фазы, которым нужны `env.php` и база данных, пропускаются до установки. Пропущенная фаза не вызывает хуки и не
попадает в таймлайн.

Условие фазы или контекста — это готовый `bool` или callable. Callable нужен, только если значение
неизвестно при объявлении: контекст проверяет URI или хук, который регистрируют расширения.

| Фаза         | Что делает                                                     | Условие             |
|--------------|----------------------------------------------------------------|---------------------|
| `boot`       | Режим обслуживания, `Registry::lazy()` для справочников        | всегда              |
| `configure`  | `configure()` пакетов: `Url`, `View`, `Hook`, `Form`, `I18n`…  | всегда              |
| `register`   | Роли, типы постов (создают таблицы)                            | система установлена |
| `extensions` | Загрузка расширений из опции `extensions.active`, `register()` | система установлена |
| `booted`     | `boot()` всех расширений                                       | система установлена |

Фазы `boot` и `configure` не должны обращаться к базе данных и константам из `env.php`: на
свежей копии их ещё нет. Пути к папкам (`EX_CORE`, `EX_DASHBOARD`, `EX_PLUGINS`, `EX_THEMES`,
`EX_STORAGE`, `EX_I18N`) объявлены в `bootstrap.php`, поэтому они доступны всегда.

## Контексты

Контекст — это часть приложения, к которой относится запрос. Выполняется **только первый**
контекст, чьё условие вернуло `true`. Условие получает URI запроса без базового пути, query-строки
и конечного слэша, например `/dashboard/chat`.

```php
Lifecycle::context('dashboard', fn (string $uri) => str_starts_with(trim($uri, '/'), 'dashboard'), function () {
    if (! User::isLogged()) {
        redirect('sign-in');
    }

    require_once EX_PATH . 'dashboard/index.php';

    Route::get('/(.*)', [App\Controllers\Web::class, 'index']);
});
```

Замыкание контекста выполняется после всех фаз, но **определить** контекст можно раньше:

```php
Lifecycle::current();          // 'dashboard'
Lifecycle::is('dashboard');    // true
Is::dashboard();               // то же самое
```

Контекст определяется при первом таком вызове, в том числе изнутри фазы (например, в `boot()`
плагина), и запоминается до конца запроса. До `Lifecycle::run()` оба метода возвращают `null` и
`false`.

> Если плагин меняет корень дашборда через хук `dashboardRootSlug`, а другой плагин до этого уже
вызвал `Is::dashboard()`, контекст будет определён по старому корню. Меняйте `dashboardRootSlug`
в слушателе из `app/Listeners`: они подключаются в фазе `configure`, раньше всех расширений.

| Контекст    | Условие                                   | Что подключает                                                    |
|-------------|-------------------------------------------|-------------------------------------------------------------------|
| `cli`       | `PHP_SAPI === 'cli'`                      | Консольные команды                                                |
| `api`       | URI начинается с `/api/`                  | API-маршруты прямо в `bootstrap.php`                              |
| `install`   | система не установлена                    | `dashboard/install.php`, `Web::install()`                         |
| `auth`      | `sign-in`, `sign-up`, `reset-password`    | `dashboard/auth.php`; вошедшего пользователя ведёт в дашборд       |
| `dashboard` | URI начинается с корня дашборда           | `dashboard/index.php`; гостя ведёт на `sign-in`                    |
| `web`       | всё остальное                             | Главная `/` и `/installed` (для вошедшего), остальное — 404       |

## Хуки жизненного цикла

Хуки вызываются автоматически, отдельно объявлять их не нужно. Имена в camelCase, чтобы их можно
было слушать методами классов из `app/Listeners`.

| Хук                          | Когда                                                  |
|------------------------------|--------------------------------------------------------|
| `before{Phase}`              | перед фазой: `beforeRegister`, `beforeBooted`          |
| `after{Phase}`               | после фазы: `afterRegister`, `afterExtensions`         |
| `enter{Context}`             | после замыкания контекста: `enterDashboard`, `enterApi`|
| `terminate`                  | после отправки ответа, в том числе после `exit`        |

Слушатели из `app/Listeners` подключаются в фазе `configure`, поэтому `beforeBoot`, `afterBoot`
и `beforeConfigure` им недоступны.

```php
// app/Listeners/Audit.php
final class Audit
{
    public function enterDashboard(): void
    {
        Asset::style('audit', url('/dashboard/assets/css/audit.css'));
    }

    public function terminate(): void
    {
        // запись в лог, отправка уведомлений: клиент этого уже не ждёт
    }
}
```

`terminate` работает через `Hook::defer()`: под PHP-FPM ответ сначала отправляется клиенту
(`fastcgi_finish_request()`), и только потом выполняются слушатели. Их возвращаемое значение не
используется.

## Расширения

Активные плагины и темы перечислены в опции `extensions.active` по id вида `plugins/{папка}` или
`themes/{папка}`. Остальные id игнорируются.

```php
Options::update('extensions', ['active' => ['plugins/seo', 'plugins/file-manager']]);
```

У расширения два метода жизненного цикла:

```php
return new class extends Plugin
{
    // фаза extensions: объявить, что даёт плагин (типы постов, роли, хуки)
    public function register(): void
    {
        Type::register(key: 'products', ...);
    }

    // фаза booted: все расширения объявлены, контекст известен
    public function boot(): void
    {
        if (! Is::dashboard()) {
            return;
        }

        Asset::style('shop', '/plugins/shop/assets/css/main.css');
    }
};
```

`register()` необязателен: по умолчанию он пустой.

## Ошибки

Аргумент `catch` у `Lifecycle::run()` получает исключение, выброшенное любым шагом. После него
оставшиеся шаги не выполняются. Без обработчика исключение уходит дальше.

```php
Lifecycle::run(catch: fn (Throwable $e) => Debug::render($e, EX_PATH . 'dashboard/debug.php'));
```

## Таймлайн

Каждый выполненный шаг записывается в таймлайн: имя, тип (`phase`, `context`, `route`), время
в миллисекундах и прирост памяти в байтах. Шаг записывается, даже если он завершился
редиректом, `exit` или исключением.

```php
Lifecycle::timeline();
// [['name' => 'boot', 'type' => 'phase', 'time' => 0.136, 'memory' => 2048], ...]
```

Плагин **Query Monitor** отдаёт таймлайн страниц дашборда в заголовке `Server-Timing`. Его видно
в инструментах разработчика браузера: Network → запрос → Timing.

```
Server-Timing: phase-boot;dur=0.136, phase-configure;dur=0.605, phase-register;dur=0.724,
               phase-extensions;dur=2.421, phase-booted;dur=0.074, context-dashboard;dur=3.171
```

## Правила

- **В `bootstrap.php` нет синтаксиса PHP 8.4.** Проверка версии PHP стоит в этом же файле, и на
  старом PHP он должен хотя бы разобраться, чтобы показать страницу требований. То же относится к
  `env.php`, `autoload.php`, `expansa/functions.php`, `App\Support\Requirements` и `dashboard/error.php`.
  Правило проверяет `tests/Syntax.php`: он падает на синтаксисе новее PHP 8.0 в этих файлах.
- **Фреймворк не знает про `app` и константы `EX_`.** Всё, что ему нужно от приложения (пути,
  адрес сайта, соединение с базой, версия), приложение передаёт через `configure()` пакета с
  именованными аргументами: `Db`, `Url`, `View`, `Extensions`, `Terminal`, `Table`, `I18n`, `Hook`,
  `Form` — в фазе `configure`; `Is` — до фаз, потому что фаза `boot` читает `Is::debug()`.
- **Установлено, если есть `env.php`.** Установщик пишет черновик `env.install.php` и переименовывает
  его в `env.php` только после всех шагов, поэтому прерванная установка не оставляет `env.php`.
  Проверка `Installation::isComplete()` не обращается к базе и вычисляется один раз, в `$isInstalled`:
  запрос установки меняет результат.
- **Объявления после старта запрещены.** Фазу или контекст нельзя объявить после
  `Lifecycle::run()`, повторный запуск и дубли имён выбрасывают `LifecycleException`.
- **Ассеты дашборда подключаются через `DashboardAssets::enqueue()`**: он сам выбирает `.min` и
  выставляет CSRF-cookie, который читает `youla-ajax.js`.

## Разработка

- `php tests/run.php` запускает все тесты, каждый в отдельном процессе, и возвращает 1, если хоть один упал.
- `php artisan serve [--host=127.0.0.1] [--port=8000]` запускает сайт на встроенном сервере PHP через
  `server.php`. Роутер выставляет `SCRIPT_NAME` как Apache, иначе `/dashboard` путается с папкой `dashboard/`.
