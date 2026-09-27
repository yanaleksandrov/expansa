# Соглашения Expansa

Именование и структура для нового кода и рефакторинга: все пакеты устроены одинаково.
Эталон — `expansa-cms/expansa/Log` и [documentation/Log.md](documentation/Log.md); неясно — делай как там.

## Принципы

1. **Имя короткое, по возможности одно слово**, роль задаёт namespace: `Assets\Providers\Link`, не
   `LinkProvider`. Конфликт имён в файле решается алиасом: `use Expansa\Cache\Providers\File as FileCache`.
2. **Одно понятие — одно слово.** Есть термин (`configure`, `forget`, `Manager`) — используй его.
3. **Без сокращений**, кроме `Db`, `Url`, `Csv`, `Json`, `Html`, `I18n`, `Id`. Аббревиатура — как слово: `HtmlDom`.
4. **Пакеты самостоятельны** (см. «Независимость пакетов»).
5. **Структура плоская:** подпапка — от 2 однородных классов; `Internal/`, `Exceptions/`, `Contracts/`, `Enums/` — с одного.
6. **Корень пакета — публичный API**, внутренняя кухня — в `Internal/`.
7. **Бета:** нарушения исправляются переименованием без алиасов и совместимости, все использования — в том же коммите.

## Структура пакета

`expansa-cms/expansa/<Package>/`, namespace `Expansa\<Package>`, имя — предметная область одним словом.

```
Log/
├── Manager.php        точка входа, цель фасада
├── Logger.php         основные классы — в корне
├── LogRecord.php
├── Contracts/Handler.php
├── Enums/Level.php
├── Handlers/AbstractHandler.php, File.php, Telegram.php
├── Formatters/AbstractFormatter.php, Line.php
├── Traits/
└── Exceptions/InvalidLevel.php, UnwritableFile.php
```

| Папка        | Что лежит                                          | Имя                                     |
|--------------|----------------------------------------------------|-----------------------------------------|
| `Contracts/` | интерфейсы                                         | роль: `Handler`                         |
| `<Role>s/`   | реализации контракта: `Handlers/`, `Providers/`, `Fields/`, `Commands/` | вариант: `Handlers\File`, `Providers\Redis` |
| `Traits/`    | трейты                                             | способность: `HasTimestamps`, `Macroable`, `Locks` |
| `Enums/`     | перечисления                                       | набор значений: `Level`, `SameSite`     |
| `Exceptions/` | исключения                                        | что произошло: `InvalidLevel`           |
| `<Class>/`   | части большого класса (`Database/Schema/` для `Schema.php`) | по роли                        |
| `Internal/`  | внутренние классы пакета, даже один                | по роли: `Scheduler\Internal\FailedJob` |

**Корень пакета — публичный API:** в нём только классы, которые остальное приложение (`App\`,
`bootstrap.php`, шаблоны, плагины, драйверы) упоминает по имени — в `use`, `new`, типе, `extends`,
статическом вызове. Класс, который пакет создаёт и использует сам, — в `Internal/` с тегом `@internal`
в описании, даже если его объект возвращается наружу. `Contracts/`, `Exceptions/`, `Traits/`, `Enums/`,
`<Role>s/` публичны: их реализуют, ловят, подключают и передают в параметрах снаружи.
`Expansa\<Package>\Internal\*` вне пакета не импортируется.

Абстрактный класс по тому же правилу: точку расширения, которую наследуют снаружи (`Handlers\AbstractHandler`,
`Commands\AbstractCommand`), кладут рядом с реализациями; общую базу публичных классов, которую снаружи
не наследуют и не указывают типом (`AbstractExtension` для `Plugin` и `Theme`), — в `Internal/`.

Нельзя: папки `Abstracts/`, `Interfaces/`, `Concerns/`, `Helpers/`, `Utils/`, `Exception/`; `Abstract<Role>`
в корне пакета; `Base` в имени (`BaseHandler`, `HandlerAbstract`, `TableBase`).

### Классы

| Что              | Правило                                                              | Пример                          |
|------------------|----------------------------------------------------------------------|---------------------------------|
| Файл             | один класс, имя файла = имя класса                                   | `LogRecord.php`                 |
| Класс            | существительное, единственное число, PascalCase                      | `Logger`, `LogRecord`           |
| Точка входа      | `Manager`, если есть конфигурация или реестр каналов/драйверов; иначе по роли | `Log\Manager`; `Router`, `Validator` |
| Реализация       | вариант без суффикса роли                                            | `Handlers\RotatingFile`, `Fields\Checkbox` |
| Абстрактный      | `Abstract<Role>`: точка расширения — рядом с реализациями, общая база — в `Internal/` | `Handlers/AbstractHandler.php`, `Extensions/Internal/AbstractExtension.php` |
| База для чужого кода | реализаций в пакете нет, наследуют только снаружи — в корне, имя по роли без `Abstract` | `Database\Model`, `Extensions\Plugin`, `Patterns\Facade` |
| Интерфейс        | без суффикса; `Interface` — только для имён PSR; конфликт с реализацией — алиасом | `Handler`, `File`; `LoggerInterface` |
| Трейт            | способность; `Has<Noun>` — данные и методы вокруг них                | `HasSoftDeletes`                |
| Enum             | в `Enums/`, единственное число, кейсы PascalCase                     | `Enums\Level::Debug`            |
| Исключение       | что произошло, без суффикса `Exception`; см. «Исключения»            | `InvalidLevel`, `ValidationFailed` |
| Модификаторы     | `final`, если не наследуются; `abstract` для базовых                 | `final class Manager`           |

### Исключения

- Имя — что произошло, без суффикса `Exception` (роль задаёт `Exceptions\`): `Log\Exceptions\InvalidLevel`,
  `ChannelNotConfigured`, `UnwritableFile`, `Session\Exceptions\AlreadyStarted`.
- Класс — на причину, которую вызывающий может обработать по-своему, а не на каждое сообщение: детали —
  в тексте.
- Наследует подходящее SPL-исключение: неверный аргумент или конфигурация — `InvalidArgumentException`,
  сбой окружения (файл, сеть, БД) — `RuntimeException`, неверный порядок вызовов — `LogicException`.
- Ошибка программиста, которую никто не ловит отдельно, — SPL-исключение напрямую, без своего класса.
- Нельзя: `<Package>Exception`, имена SPL (`Console\Exceptions\RuntimeException`), общий базовый класс пакета,
  пока никто не ловит «все ошибки пакета».
- `final`, если не наследуется; описание класса в `/** */` — когда бросается:

  ```php
  /**
   * Thrown when a level name or number is not one of the PSR-3 levels.
   */
  final class InvalidLevel extends InvalidArgumentException {}
  ```

### Независимость пакетов

Пакет работает и тестируется только с базовым слоем.

- **Базовый слой** — `Support`, `Patterns`, `Codecs`: доступен всем, сам зависит только от себя.
- Прочие пакеты друг от друга не зависят. Чужое поведение приходит снаружи — через `configure()`,
  конструктор или свой контракт; реализацию передают `bootstrap.php` или `App\`:
  `Scheduler::configure(mailer: fn (string $to, string $subject, string $body) => Mail::send(...))`.
- Внутри пакета нет фасадов других пакетов (`Hook::`, `Db::`, `Safe::`, `Lifecycle::`) — фасады для
  `bootstrap.php`, `App\`, шаблонов, плагинов.
- Точка расширения — колбэк в `configure()` или `extend()`; с хуком её связывает `bootstrap.php`.
- Значение, известное позже в запросе, приходит ленивым колбэком: `Is::configure(dashboard: fn () => ...)`.
- Колбэк, которому нужен чужой пакет, хранится в `Internal/` (`Database\Internal\Cache`), а не в чужом контракте.
- Драйвер через чужой пакет допустим (`Cache\Providers\Database`): зависимость только у него, грузится
  только при выборе в конфигурации.
- Консольная команда — в своём пакете (`Scheduler/Commands/Run.php`), регистрируется в `bootstrap.php`;
  чужое поведение получает колбэком в конструкторе и регистрируется экземпляром. Имя команды — зарезервированное
  слово (`list`) → класс `Index`.
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
- Свойство, которое затеняет магический атрибут модели, получает суффикс: `$deletedAtColumn`.
- У статических свойств нет асимметричной видимости (PHP 8.4): публичное чтение — через геттер.
- `mixed` — только если значение действительно любое (`Cache::get()`); иначе точный тип (`add(): bool`).

### PHP 8.4

- Ленивые объекты (`ReflectionClass::newLazyGhost()`/`newLazyProxy()`) для БД, Mail, Image вместо
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
5. Исключения в `Exceptions/` по разделу «Исключения».
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
- Неиспользуемый публичный API (классы, методы, фасады, контракты, исключения) не удаляй: это задел на
  будущее (`Cookie\CookieJar`), приводи его к соглашениям. Синонимы одного метода сводятся к одному
  имени («Одно понятие — одно слово»).
- Удаляй только мёртвый код реализации: недостижимые ветки, закомментированный код, неиспользуемые
  приватные методы, свойства и переменные.
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

**phpcs** 4.x по `phpcs.xml` (PSR-12 + правила), свои sniff'ы — в `phpcs/Expansa/Sniffs/`: пустое тело `{}`
остаётся на строке объявления (`) {}`, `class X extends Y {}`). `phpcs:ignore` не используется: исключение —
в `phpcs.xml` с комментарием. Сторонний код и `cache/` не проверяются. phpcs 4.x не понимает многострочный
hook `set { }` в сигнатуре конструктора (`Generic.WhiteSpace.ScopeIndent`): phpcbf на таком файле ломает
отступы — не запускай его там. Если это не исправится — PHP-CS-Fixer с теми же правилами; два форматтера
не держим.

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
| `Facades/Db.php`, `Facades/Terminal.php`         | логика в фасаде            | цель — `Database\Manager`, `Console\Manager` |
| `Http/Status`, `Database/FieldEav`, `Database/Schema/*` | длинные PHPDoc      | 2–4 строки                               |

### Зависимости между пакетами

| Пакет         | Зависит от                      | Как развязать                                        |
|---------------|---------------------------------|------------------------------------------------------|
| `Builders`    | `Assets`, `View`, `Security`    | допустимо (UI-слой): колбэки `Form::configure()`, статические помощники (`Sanitizer`) напрямую |
| `Cache`       | `Database` (`Providers\Database`) | допустимо: только драйвер, `Query\Builder` в конструкторе |
| `*\Commands`  | `Console`                       | допустимо: команда пакета наследует `Console\Commands\AbstractCommand` |
