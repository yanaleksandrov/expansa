# Соглашения Expansa

Именование и структура для нового кода и рефакторинга: все пакеты устроены одинаково.
Эталон — `expansa-cms/expansa/Log` и [documentation/Log.md](documentation/Log.md); неясно — делай как там.

## Принципы

1. **Имя короткое, по возможности одно слово**, роль задаёт namespace: `Assets\Providers\Link`, не
   `LinkProvider`. Конфликт имён в файле решается алиасом: `use Expansa\Cache\Providers\File as FileCache`.
2. **Одно понятие — одно слово.** Есть термин (`configure`, `forget`, `Manager`) — используй его.
3. **Без сокращений**, кроме `Db`, `Url`, `Csv`, `Json`, `Html`, `I18n`, `Id`. Аббревиатура — как слово: `HtmlDom`.
4. **Пакеты самостоятельны** (см. «Независимость пакетов»).
5. **Структура плоская:** подпапка — от 2 однородных классов.
6. **Бета:** нарушения исправляются переименованием без алиасов и совместимости, все использования — в том же коммите.

## Структура пакета

`expansa-cms/expansa/<Package>/`, namespace `Expansa\<Package>`, имя — предметная область одним словом.

```
Log/
├── Manager.php        точка входа, цель фасада
├── Logger.php         основные классы — в корне
├── LogRecord.php
├── Contracts/Handler.php
├── Handlers/AbstractHandler.php, File.php, Telegram.php
├── Formatters/AbstractFormatter.php, Line.php
├── Traits/
└── Exceptions/LogException.php
```

| Папка        | Что лежит                                          | Имя                                     |
|--------------|----------------------------------------------------|-----------------------------------------|
| `Contracts/` | интерфейсы                                         | роль: `Handler`                         |
| `<Role>s/`   | реализации контракта: `Handlers/`, `Providers/`, `Fields/`, `Commands/` | вариант: `Handlers\File`, `Providers\Redis` |
| `Traits/`    | трейты                                             | способность: `HasTimestamps`, `Macroable`, `Locks` |
| `Exceptions/` | исключения                                        | `<Package>Exception`                    |
| `<Class>/`   | части большого класса (`Database/Schema/` для `Schema.php`) | по роли                        |

Нельзя: папки `Abstracts/`, `Interfaces/`, `Concerns/`, `Helpers/`, `Utils/`, `Exception/`; абстрактный
класс вне папки реализаций; `Base` в имени (`BaseHandler`, `HandlerAbstract`, `TableBase`).

### Классы

| Что              | Правило                                                              | Пример                          |
|------------------|----------------------------------------------------------------------|---------------------------------|
| Файл             | один класс, имя файла = имя класса                                   | `LogRecord.php`                 |
| Класс            | существительное, единственное число, PascalCase                      | `Logger`, `CookieJar`           |
| Точка входа      | `Manager`, если есть конфигурация или реестр каналов/драйверов; иначе по роли | `Log\Manager`; `Router`, `Validator` |
| Реализация       | вариант без суффикса роли                                            | `Handlers\RotatingFile`, `Fields\Checkbox` |
| Абстрактный      | `Abstract<Role>`, рядом с реализациями                               | `Handlers/AbstractHandler.php`  |
| Интерфейс        | без суффикса; `Interface` — только для имён PSR; конфликт с реализацией — алиасом | `Handler`, `File`; `LoggerInterface` |
| Трейт            | способность; `Has<Noun>` — данные и методы вокруг них                | `HasSoftDeletes`                |
| Enum             | единственное число, кейсы PascalCase                                 | `Level::Debug`                  |
| Исключение       | одно `<Package>Exception`; отдельное — только если его ловят отдельно | `NotFoundHttpException`        |
| Модификаторы     | `final`, если не наследуются; `abstract` для базовых                 | `final class Manager`           |

### Независимость пакетов

Пакет работает и тестируется только с базовым слоем.

- **Базовый слой** — `Support`, `Patterns`, `Codecs`: доступен всем, сам зависит только от себя.
- Прочие пакеты друг от друга не зависят. Чужое поведение приходит снаружи — через `configure()`,
  конструктор или свой контракт; реализацию передают `bootstrap.php` или `App\`:
  `Scheduler::configure(mailer: fn (string $to, string $subject, string $body) => Mail::send(...))`.
- Внутри пакета нет фасадов других пакетов (`Hook::`, `Db::`, `Safe::`, `Lifecycle::`) — фасады для
  `bootstrap.php`, `App\`, шаблонов, плагинов.
- Точка расширения — колбэк в `configure()` или `extend()`; с хуком её связывает `bootstrap.php`.
- Драйвер через чужой пакет допустим (`Cache\Providers\Database`): зависимость только у него, грузится
  только при выборе в конфигурации.
- Консольная команда — в своём пакете (`Scheduler/Commands/Run.php`), регистрируется в `bootstrap.php`.
- Исключение — `Builders` (UI-слой): зависит от других пакетов, но через конструктор, не фасады.
- Циклов нет, даже через драйвер.
- Проверка: `tests/<Package>.php` проходит с одним пакетом и базовым слоем; в `use` нет других `Expansa\*`,
  кроме базового слоя и своих драйверов.

### Фасады

- `Facades/<Name>.php`, один на точку входа; имя — как читается вызов, единственное число:
  `Log::info()`, `Hook::call()`, `Route::get()`.
- `@method static` на **каждый** публичный метод цели с теми же типами и default; сигнатура изменилась — обнови.
- Только `getStaticClassAccessor()`, без логики.

### Конфигурация

- `configure(...)` с именованными аргументами, в фазе `configure` в `bootstrap.php`, без запросов к БД.
- Повторный вызов заменяет конфигурацию целиком и сбрасывает созданные объекты.
- Без `configure()` — безопасные умолчания (`Log` пишет в `error_log()`).
- Встроенные драйверы — `create<Driver>Driver(array $config)`, свои — `extend(string $driver, Closure $factory)`.
- Ключи конфигурации — `snake_case`: `chat_id`.

## Методы и свойства

| Глагол                 | Смысл                                          | Возвращает          |
|------------------------|------------------------------------------------|---------------------|
| `get<X>()`             | чтение без побочных эффектов                   | значение            |
| `set<X>()`             | запись с перезаписью                           | `static` в fluent-API (`Plugin::setVersion()`), иначе `void`; одинаково в пакете |
| `add()`                | добавить, если нет                             | `bool`/`static`     |
| `has<X>()` / `is<X>()` | наличие / состояние                            | `bool`              |
| `can<X>()`             | наличие возможности или права на действие      | `bool`              |
| `forget()` / `flush()` | убрать один / все из памяти или кэша           | `bool`/`static`/`void` |
| `pull()`               | `get()` + `forget()`                           | значение            |
| `delete()`             | удалить сохранённое: строку БД, файл           | `bool`              |
| `with<X>()`            | изменённая копия, исходный не меняется         | `static`            |
| `create<X>()`          | фабрика                                        | объект              |
| `resolve()`            | создать при первом обращении и закэшировать, обычно `protected` | объект |
| `configure()` / `extend()` | настройка / свой драйвер                   | `void` / `static`   |
| `render()`             | HTML или текст                                 | `string`            |
| `handle()`             | обработать входящее: запись, запрос, команду   | результат           |
| `encode()` / `decode()`| в формат и обратно                             | значение            |
| `validate()` / `sanitize()` | проверить / очистить                      | `bool` / значение   |

Нет подходящего — выбери одно слово на весь пакет. Не используй: `fetch`, `retrieve`, `remove`, `drop`,
`clear`, `destroy`, `make`, `build` (кроме `Builders/`), `do`, `process`, `execute`.

- camelCase для методов и свойств; булевы — прилагательное или `is`/`has`: `$secure`, `$isInstalled`.
- Флаг публичного метода передаётся именованным аргументом: `encode($value, pretty: true)`.
- Константы класса — `UPPER_SNAKE_CASE` с типом: `private const int ENCODE_FLAGS`; глобальные — `EX_*`.
- Глобальные функции (`functions.php`) — короткие `snake_case` для шаблонов и частых вызовов (`t()`,
  `t_attr()`, `view()`); новые — только если фасада мало.
- Геттер/сеттер без логики → свойство (короче и быстрее), вызовы `->getX()` → `->x`. Модификатор:
  задаётся один раз в конструкторе — `readonly`; меняется внутри класса — `private(set)`, в наследниках —
  `protected(set)`; при чтении или записи есть логика — hook. Hook ради одного доступа не нужен.
  Контракт объявляет свойство, а не геттер: `public Level $level { get; }`. Геттер остаётся для
  ленивого значения (`getFormatter()`), фасада и чужого контракта (PSR, `Throwable::getMessage()`).

  ```php
  // было: protected Response $response + getResponse()
  public function __construct(

      /**
       * Ready response to send instead of the regular handler result.
       */
      public readonly Response $response,
  ) {}
  ```
- `mixed` — только если значение действительно любое (`Cache::get()`); иначе точный тип (`add(): bool`).

### PHP 8.4

- Ленивые объекты (`ReflectionClass::newLazyGhost()`/`newLazyProxy()`) для БД, Mailer, Image вместо
  ручного `resolve()`; внедрять после бенчмарка запроса без сервиса и с ним.
- `array_find()`, `array_any()`, `array_all()` вместо `foreach` с `break`, если бенчмарк не хуже.
- `new Foo()->bar()` без скобок; `strlen(...)` вместо `fn ($s) => strlen($s)`.

## Хуки

Хуки — единственная система событий: без EventDispatcher, Observer и аналогов. camelCase без префикса `expansa`. Событие — `<объект><прошедшее время>` (`dashboardLoaded`), фильтр — имя
значения (`redirectStatus`), вывод разметки — `render<Место>`, хуки плагина — с его slug (`seoMetaTags`).

## App и плагины

| `App\`            | Что                    | Имя                                         |
|-------------------|------------------------|---------------------------------------------|
| `Api/<Resource>/` | API ресурса            | `<Resource>Controller`, `<Resource>Service` |
| `Models/`         | модели БД              | единственное: `Post`                        |
| `Tables/`         | таблицы дашборда       | множественное: `Posts`                      |
| `Listeners/`      | классы методов-хуков   | область: `Assets`                           |
| `Http/`           | middleware             | действие: `RequireAuth`                     |
| `Console/`        | команды                | как в фреймворке                            |

Логика — в `Service`, контроллер разбирает запрос и отдаёт ответ.

Плагин — `plugins/<slug>/index.php` (тема — `themes/<slug>/`) возвращает анонимный наследник
`Expansa\Extensions\Plugin`. slug — kebab-case, namespace — PascalCase slug (`FileManager\`), правила — как у пакетов.

## Добавление пакета

1. Новый драйвер существующего пакета — класс в его `<Role>s/`, а не пакет.
2. Структура по схеме, `declare(strict_types=1)`, описание класса в `/** */`.
3. Точка входа и фасад с `@method`.
4. `configure()` в `bootstrap.php`, безопасные умолчания без него.
5. `Exceptions/<Package>Exception.php` от подходящего SPL-исключения.
6. `tests/<Package>.php` подключает `tests/bootstrap.php` (`EX_PATH`, `autoload.php`, `check()`, `throws()`)
   и содержит только проверки; `php tests/run.php` — тесты и PHPStan без новых ошибок сверх baseline.
7. Горячий путь (каждый запрос или цикл) — бенчмарк по разделу «Бенчмарки».
8. `documentation/<Package>.md` на русском: вступление с примером и таблицей `Класс — Назначение`,
   конфигурация, использование, расширение; примеры рабочие.
9. `php artisan autoload:dump` при изменении классов в продакшн-сборке (иначе PSR-4).

### Сторонний код

- Не подключай библиотеку ради одной функции: она поставляется с ядром и её надо поддерживать. Сначала
  PHP и `expansa/`, небольшое — пиши сам. Оправдано, когда объём несопоставим с задачей (изображения, почта).
- `require-dev` в `composer.json` → копия в `expansa/` через `$map` в `scripts/sync-vendored.php`; свой
  namespace — в `$prefixes` в `autoload.php`; папка — в `excludePaths` в `phpstan.neon`.
- Не правится и не переименовывается; переписанный под Expansa (`Scheduler/Cron`) — часть пакета по всем правилам.

## Обновление кода

- Тронул пакет — приведи к соглашениям его и его использования, не весь проект.
- Переименование — сразу везде: классы, методы, хуки, `bootstrap.php`, фасады, тесты, документация.
- Удаляй мёртвый код.
- Без защиты от невозможного: не нужны `is_string()` после `string $x`, `null`-проверки не-nullable,
  `try` вокруг небросающего. Проверяй только внешние данные: запрос, файлы, конфигурацию, ответы сервисов.
- Перед коммитом: `php tests/run.php`; горячий путь — бенчмарк против предыдущего коммита.
- Документация и комментарии — вместе с кодом.
- У тронутого пакета нет записей в `phpstan-baseline.neon`: ошибки исправляются, а не переносятся.
- Коммит на английском, повелительно, с пакетом: `Update Log package`, `Fix Cache: expired keys on add()`.

## Инструменты

**CI** — GitHub Actions на каждый push и PR: `php tests/run.php` (тесты и PHPStan) и phpcs. Красная сборка
не мержится.

**PHPStan** — поэтапно до level 6. Когда baseline текущего уровня почти пуст, подними `level` на один и
пересобери: `vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G --generate-baseline phpstan-baseline.neon`.
Уровень не понижается, baseline вручную не пополняется. Пиши типы сразу: `Handler[]`,
`array<string, Logger>`, `array{driver: string, level?: string}`, `class-string<T>`.

**phpcs** по `phpcs.xml` (PSR-12 + правила). Переход на `squizlabs/php_codesniffer` `4.*` (3.x ложно ругается
на hooks): проверь sniff `phpcs/Expansa/Sniffs/Formatting/EmptyConstructorSniff.php` и исключения, убери
устаревшее. До перехода ложные ошибки не исправляй кодом и не глуши `phpcs:ignore`. Если 4.x не тянет
PHP 8.4 — PHP-CS-Fixer с теми же правилами; два форматтера не держим.

**Бенчмарки** — `tests/benchmarks/<Package>.php`, текущий код против `--baseline` на одних данных. Общий код —
в `tests/benchmarks/bootstrap.php` (пока дублируется): опции `--baseline=<ref или файл>` (несколько) и
`--iterations=N`; загрузка базы из git — переименованием класса (`Kses` → `KsesBaseline`) или namespace
(`Expansa\Log` → `LogBaseline`) во временную папку с `mtime` в прошлом для opcache;
`measure(callable $callback, int $iterations, int $rounds = 5): float` — лучший раунд после прогрева;
таблица: вариант, время на операцию, разница в %. Бенчмарк пакета — ~20 строк данных и вызовов; особые
сценарии (процесс на прогон в `Autoload.php`) остаются в нём.

## Известные отклонения

Исправляются при следующей работе с пакетом.

| Где                                              | Проблема                   | Должно быть                              |
|--------------------------------------------------|----------------------------|------------------------------------------|
| `Http/Request/ParameterBug.php`                  | опечатка                   | `ParameterBag`                           |
| `Assets/Abstracts/Provider.php`                  | `Abstracts/`               | `Assets/Providers/AbstractProvider`      |
| `Log/Handlers/*Handler`                          | суффикс роли               | `File`, `RotatingFile`, `ErrorLog`, `Telegram` |
| `Log/Formatters/*Formatter`                      | суффикс роли               | `Line`, `Telegram`                       |
| `Security/Csrf/Providers/Native*Provider`        | суффикс роли               | `Cookie`, `HttpOnlyCookie`, `Session`    |
| `View/Engines/*Engine`                           | суффикс роли               | `Blade`, `File`, `Js`, `Php`             |
| `View/Engines/Engine.php`                        | база названа как роль      | `AbstractEngine` или `Contracts\Engine`  |
| `View/Compilers/BladeCompiler`                   | суффикс роли               | `Blade`                                  |
| `Session/Middleware/SessionStartMiddleware`      | суффикс, повтор пакета     | `StartSession`                           |
| `Cache/Concerns/`                                | `Concerns/`                | `Cache/Traits/`                          |
| `Builders/Table/Abstracts/TableBase`             | `Abstracts/`, `Base`       | `AbstractTable`                          |
| `Database/Query/BuilderAbstract`                 | суффикс `Abstract`         | `AbstractBuilder`                        |
| `Database/Model/Has*`                            | трейты вне `Traits/`       | `Database/Traits/Has*`                   |
| `Filesystem/Contracts/*Interface`                | суффикс не из PSR          | `File`, `Directory`; `CommonInterface` → по роли, например `Entry` |
| `Session/Contracts/{Flash,Session,SessionManager}Interface` | суффикс не из PSR | `Flash`, `Session`, `Manager` (PSR-7/15 имена остаются) |
| `Cache/Contracts/Provider`                       | `add()`, `set()` и др. возвращают `mixed` | точные типы: `bool`         |
| `tests/*.php`                                    | `check()`, `throws()`, `EX_PATH` в каждом из 16 тестов | `tests/bootstrap.php` |
| CI                                               | нет `.github/`             | workflow с `tests/run.php` и phpcs       |
| `Extensions/Traits/ExtensionTraits`, `ExtensionHelpers` | имя без способности | по способности                           |
| `Models/Options`                                 | множественное              | `Option`                                 |
| хук `expansa_view_part`                          | snake_case, префикс        | `viewPart`                               |
| хуки `expansaRedirectBy/Status/Location`, `expansaConfigureMailer` | префикс `expansa` | `redirectBy`, ...          |
| `Facades/Json.php`                               | табы                       | 4 пробела                                |

### Зависимости между пакетами

| Пакет         | Зависит от                      | Как развязать                                        |
|---------------|---------------------------------|------------------------------------------------------|
| `Support`     | `Lifecycle` (`Is::dashboard()`) | значение через `Is::configure()`                     |
| `Scheduler`   | `Mail` (`Job` создаёт `Mailer`) | колбэк отправки в `configure()`                      |
| `Console`     | `Assets`, `Scheduler`, `Hooks`  | `AssetClean`, `ScheduleRun`, `HooksList` — в свои пакеты |
| `Database`    | `Cache`, `Security` (`Safe`)    | кэш и очистка снаружи                                |
| `Filesystem`  | `Debug`, `Security` (`Validator`) | ошибки — исключениями, проверка снаружи            |
| `Http`        | `Cookie`, `Hooks`               | хуки `Redirect` — колбэками в `configure()`          |
| `Mail`        | `Hooks`                         | настройка мейлера — колбэком в `configure()`         |
| `Translation` | `Hooks`, `Security` (`Safe`)    | колбэки в `configure()`                              |
| `Lifecycle`   | `Hooks`, `Routing`              | колбэки фаз и маршрутизации из `bootstrap.php`       |
| `Builders`    | `Assets`, `View`, `Security`    | допустимо (UI-слой), но через конструктор            |
| `Cache`       | `Database` (`Providers\Database`) | допустимо: только драйвер                          |
