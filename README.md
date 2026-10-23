# Expansa

CMS на PHP 8.4 со своим фреймворком: панель управления, типы записей и пользовательские поля, меню, роли,
мультиязычность, плагины и темы, генерация плагинов с помощью ИИ. Производительность в приоритете, ключевые
параметры настраиваются в панели, а не в коде.

## Требования

- PHP 8.4+ с расширениями `pdo`, `fileinfo`, `curl`, `intl`, `iconv`, `openssl`, `zlib`, `zip`, `bcmath`
- MySQL 8.0+
- Для разработки: Composer и Node.js

## Быстрый старт

```bash
php expansa-cms/artisan serve            # http://127.0.0.1:8000, --host и --port меняют адрес
```

При первом открытии сайта запускается установщик: он проверяет окружение, создаёт таблицы и записывает
`expansa-cms/env.php` (образец — `env.example.php`). Переменные окружения `EX_DB_*`, `EX_KEY_*` и `EX_DEBUG`
переопределяют его значения. Пример конфигурации nginx — `expansa-cms/nginx.conf`.

## Структура

| Путь                      | Что лежит                                                                        |
|---------------------------|----------------------------------------------------------------------------------|
| `expansa-cms/expansa/`    | Фреймворк: пакеты `Expansa\<Package>` (`Log`, `Cache`, `Database`, `Builders` и др.) |
| `expansa-cms/app/`        | Приложение: модели, API, страницы панели, слушатели хуков                         |
| `expansa-cms/dashboard/`  | Панель управления: шаблоны, формы, переводы, собранные `assets/`                  |
| `expansa-cms/plugins/`    | Плагины: `<slug>/index.php` возвращает наследник `Expansa\Extensions\Plugin`      |
| `expansa-cms/themes/`     | Темы: `<slug>/index.php` возвращает наследник `Expansa\Extensions\Theme`          |
| `expansa-cms/bootstrap.php` | Фазы запуска и конфигурация пакетов                                             |
| `src/`                    | Исходники CSS и JS панели, собираются в `expansa-cms/dashboard/assets/`           |
| `documentation/`          | Документация пакетов                                                             |
| `tests/`                  | Тесты пакетов и бенчмарки                                                        |

## Разработка

```bash
composer install                         # PHPUnit, phpcs, PHPStan и сторонний код для копий в expansa/
npm install
npm run build                            # src/ → expansa-cms/dashboard/assets/, обычный и .min
php tests/run.php                        # тесты и PHPStan, перед каждым коммитом
vendor/bin/phpcs                         # стиль кода по phpcs.xml
```

CSS и JS правятся только в `src/`: `dashboard/assets/` перезаписывается при сборке. Соглашения по именованию,
структуре пакетов, шаблонам и текстам интерфейса — в [AGENTS.md](AGENTS.md), эталон пакета —
`expansa-cms/expansa/Log` и [documentation/Log.md](documentation/Log.md).

## Документация

| Раздел       | Пакеты                                                                                              |
|--------------|-----------------------------------------------------------------------------------------------------|
| Основа       | [Lifecycle](documentation/Lifecycle.md), [Hooks](documentation/Hooks.md), [Http](documentation/Http.md), [View](documentation/View.md), [Debug](documentation/Debug.md), [Log](documentation/Log.md) |
| Данные       | [Database](documentation/Database.md), [Cache](documentation/Cache.md), [Filesystem](documentation/Filesystem.md), [Session](documentation/Session.md), [Cookie](documentation/Cookie.md) |
| Безопасность | [Auth](documentation/Auth.md), [Access](documentation/Access.md), [Markup](documentation/Markup.md), [Xss](documentation/Xss.md)      |
| Интерфейс    | [Dashboard](documentation/Dashboard.md), [Builders](documentation/Builders.md), [Asset](documentation/Asset.md), [Translation](documentation/Translation.md), [Ldml](documentation/Ldml.md) |
| Расширения   | [Extensions](documentation/Extensions.md), [Ai](documentation/Ai.md), [Scheduler](documentation/Scheduler.md), [Mail](documentation/Mail.md) |
| Форматы      | [Codecs](documentation/Codecs.md), [Json](documentation/Json.md), [Csv](documentation/Csv.md), [Base64](documentation/Base64.md), [Marshal](documentation/Marshal.md) |

Планы — в [TODO.md](TODO.md).

## Лицензия

[Functional Source License 1.1 с переходом в Apache 2.0](LICENSE) (FSL-1.1-ALv2).

- Можно бесплатно пользоваться, изменять и распространять Expansa, делать на ней сайты для себя и клиентов.
- Нельзя выпускать на основе кода конкурирующий продукт или сервис: свою CMS, её замену или аналог.
- Каждая версия через два года после выхода становится доступной и по Apache License 2.0, без этого ограничения.
- Плагины и темы — не конкурирующий продукт: их можно распространять и продавать под любой лицензией.
- Хостеры могут предлагать Expansa, в том числе платно и с установкой в один клик: у каждого клиента своя
  установка, Expansa предлагается под своим именем, клиент может забрать сайт к другому хостеру.
  Подробно — в [LICENSE-PLUGINS.md](LICENSE-PLUGINS.md).

Сторонний код сохраняет свои лицензии, см. [THIRD-PARTY.md](THIRD-PARTY.md).
