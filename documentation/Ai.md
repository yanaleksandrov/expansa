# Генерация расширений с помощью AI

Пакет `Expansa\Ai` превращает текстовый запрос пользователя в спецификацию и набор файлов расширения CMS. Менеджер получает релевантный контекст, при необходимости вызывает ограниченное число инструментов, просит модель сгенерировать PHP-файлы и тесты, проверяет результат и передаёт ошибки вместе с предыдущими файлами генератору для исправления.

Результат — черновик `Draft`: пакет не записывает файлы на диск и не исполняет их, приложение показывает черновик пользователю и устанавливает как плагин после подтверждения. `Manager` не хранит состояния: всё, что нужно для следующего раунда уточнений, лежит в `Draft::$session`, поэтому один менеджер обслуживает любое число пользователей и HTTP-запросов.

## Классы

| Класс | Назначение |
| --- | --- |
| `Manager` | Проводит раунд: контекст, спецификация, инструменты, генерация, проверка. |
| `Draft` | Черновик плагина: файлы, спецификация, ошибки, вопросы, недостающие расширения, сессия и метрики раунда. |
| `Queue` | Фоновая генерация: ставит задачи, запускает воркер, выполняет раунды. |
| `Task` | Фоновый процесс генерации: запрос, статус, шаги, ожидающий ответ, последний черновик. |
| `Step` | Шаг раунда, о котором менеджер сообщает при старте и завершении, с промежуточным результатом. |
| `Enums\Stage` | Этап раунда: context, analysis, tool, refinement, generation, validation. |
| `Enums\Status` | Статус задачи: queued, running, questions, ready, invalid, missing_extensions, failed, cancelled. |
| `Session` | Запрос, ответы, ожидающие вопросы и итоги раундов; сериализуется. |
| `Prompt` | Системные инструкции, данные запроса и JSON Schema ответа. |
| `Brief` | Всё для одной попытки генерации: запрос, контекст, спецификация, прошлые файлы и ошибки. |
| `Completion` | Ответ модели, usage токенов и метаданные провайдера. |
| `Generation` | Файлы и usage этапа генерации кода. |
| `Platform` | Версия PHP и доступные расширения для генерируемого кода. |
| `Limits` | Ограничения ввода, контекста, вызовов, исправлений и бюджет сессии. |
| `Contracts\Provider` | Контракт AI-провайдера. |
| `Providers\OpenAi` | Любой сервис с OpenAI Chat Completions API: Gemini, OpenRouter, Groq, Ollama, OpenAI. |
| `Contexts\Files` | Контекст из текстовых файлов каталога (документация CMS), ранжированных по словам запроса. |
| `Contracts\Context` | Ищет релевантный контекст по запросу пользователя. |
| `Contracts\Tool` | Инструмент, который модель может запросить. |
| `Contracts\ToolRegistry` | Список доступных инструментов и их запуск. |
| `Contracts\Generator` | Генератор файлов. |
| `Contracts\Validator` | Проверка результата. |
| `Contracts\Store` | Хранилище задач с атомарным захватом для воркеров. |
| `Stores\File` | Задачи в файлах каталога, без БД. |
| `Commands\Work` | Команда `ai:work`: выполняет задачи из очереди. |
| `Contracts\TokenCounter` | Подсчёт и обрезка токенов. |
| `Generators\Ai` | Запрашивает у AI список файлов в JSON. |
| `Tools\Registry` | Реестр инструментов по именам. |
| `Validators\Paths` | Безопасные пути, разрешённые типы файлов, наличие тестов. |
| `Validators\Php` | Синтаксис PHP без исполнения кода. |
| `Validators\Policy` | Запрет `eval`, shell-вызовов и `include` переменного пути. |
| `Validators\Extensions` | Вызовы функций и классов недоступных расширений PHP. |
| `Validators\Chain` | Запускает несколько валидаторов. |
| `TokenCounters\Characters` | Приблизительный счётчик: один символ Unicode — один токен. |
| `Facades\Ai` | Статический доступ к настроенному менеджеру. |
| `Exceptions\*` | Ошибки запроса, лимитов, бюджета, уточнения, ответа и инструментов. |

## Настройка

В CMS пакет уже подключён: `App\Support\Ai` собирает очередь из вкладки «AI» настроек (адрес, модель, ключ — опция `ai`, ключ зашифрован, см. `App\Support\Secrets`) и серверных путей `EX_AI` в env.php (`context`, `php`, `options`), `App\Api\Ai\AiController` отдаёт задачи чату дашборда (`POST /api/ai/{index,get,create,clarify,cancel,rename,archive,delete}`, нужно право `plugins_install`), `ai:work` и его запуск раз в минуту зарегистрированы в bootstrap.php. Для проверки с бесплатной моделью достаточно ключа:

- Google Gemini: ключ на aistudio.google.com; адрес и модель `gemini-flash-latest` стоят по умолчанию. Бесплатный tier Flash ограничен примерно 10 запросами в минуту, а раунд — это 2–5 запросов.
- OpenRouter: адрес `https://openrouter.ai/api/v1/`, модель — id с суффиксом `:free`; для моделей без `json_schema` снимите «Structured output».
- Ollama локально: адрес `http://localhost:11434/v1/`, ключ не нужен.

Пустой адрес выключает генерацию.

`Providers\OpenAi` передаёт схему ответа в `response_format` (`json_schema`, а при `schemas: false` — `json_object` и схему в инструкциях), берёт usage из ответа и бросает `RequestFailed` при недоступности сервиса или статусе не 2xx — очередь повторяет такую задачу. `Contexts\Files` отдаёт файлы `always` и файлы каталога, где чаще встречаются слова запроса.

Своё приложение собирает менеджер так:

Менеджеру достаточно провайдера и источника контекста. Генератор по умолчанию — `Generators\Ai` с тем же провайдером; валидатор — `Chain` из `Paths`, `Php`, `Policy` и `Extensions`; платформа — версия PHP и расширения текущего процесса; реестр инструментов пустой; счётчик токенов — `TokenCounters\Characters`. Всё можно заменить именованными аргументами. Провайдер ниже — тестовая заглушка; в приложении её заменяет адаптер AI SDK.

```php
use Expansa\Ai\Completion;
use Expansa\Ai\Contracts\Context;
use Expansa\Ai\Contracts\Provider;
use Expansa\Ai\Limits;
use Expansa\Ai\Manager;
use Expansa\Ai\Prompt;

$provider = new class implements Provider {
    public function complete(Prompt $prompt, int $maxOutputTokens): Completion
    {
        $response = ($prompt->schema['required'] ?? []) === ['files']
            ? ['files' => [
                ['path' => 'welcome.php', 'content' => '<?php declare(strict_types=1);'],
                ['path' => 'tests/WelcomeTest.php', 'content' => '<?php declare(strict_types=1);'],
            ]]
            : ['specification' => 'Добавить расширение приветствия.', 'questions' => [], 'tool_calls' => []];

        return new Completion(json_encode($response, JSON_THROW_ON_ERROR), 80, 24, ['model' => 'example-model']);
    }
};

$context = new class implements Context {
    public function get(string $input, int $maxTokens): string
    {
        return str_contains(mb_strtolower($input), 'приветствие') ? 'Доступный hook: welcome' : '';
    }
};

$manager = new Manager(
    provider: $provider,
    context: $context,
    limits: new Limits(outputTokens: 4000, toolCalls: 2, repairs: 1, totalTokens: 40000),
);

Expansa\Facades\Ai::swap($manager);
$extension = Expansa\Facades\Ai::create('Добавить приветствие на главную страницу');
```

Спецификацию и код можно отдавать разным моделям: `new Manager($cheapProvider, $context, new Generators\Ai($strongProvider))`.

## Фоновая генерация

Раунд генерации — это несколько вызовов модели и исправлений, он длится десятки секунд и не должен держать HTTP-запрос. `Queue` отделяет запрос пользователя от работы: `dispatch()` сохраняет задачу и сразу возвращает её, отдельный процесс `ai:work` генерирует черновик, интерфейс опрашивает `get()`.

```
dispatch($input) ─► Task queued ─► ai:work --id ─► running ─┬─► questions ─► clarify($id, $answer) ─► queued …
                         ▲                                  ├─► ready / invalid / missing_extensions
                         └── временный сбой (сеть, API) ◄───┴─► failed (Error, лимит, бюджет, попытки исчерпаны)
```

```php
use Expansa\Ai\Commands\Work;
use Expansa\Ai\Queue;
use Expansa\Ai\Stores\File as FileStore;

$queue = new Queue(
    store: new FileStore(EX_STORAGE . 'ai'),
    manager: fn () => new Manager(provider: new MyProvider(), context: new MyContext()),
    start: Work::createLauncher(EX_PATH . 'artisan', php: '/usr/bin/php'),
);

// веб-запрос
$task = $queue->dispatch('Добавить форму обратной связи', owner: (string) $userId);
// опрос из интерфейса
$task = $queue->get($id);          // $task->status, $task->steps, $task->draft, $task->error
// ответ на вопросы
$queue->clarify($id, 'Да, с капчей');
// отмена
$queue->cancel($id);
```

Регистрация команды и страховочного запуска раз в минуту в `bootstrap.php`:

```php
Terminal::addCommand(new Expansa\Ai\Commands\Work(queue: fn () => $queue));

Hook::add('schedule', function (Expansa\Scheduler\Scheduler $scheduler) {
    $scheduler->raw(PHP_BINARY, [EX_PATH . 'artisan', 'ai:work'])->everyMinute()->onlyOne();
});
```

- `start` запускает `php artisan ai:work --id=<id>` отдельным процессом (`start /B` в Windows, `&` в Unix) и сразу возвращается. Под PHP-FPM `PHP_BINARY` указывает на FPM, поэтому путь к PHP CLI передаётся явно. Если запуск невозможен (`exec` отключён), задача остаётся в очереди, и её подберёт планировщик.
- `ai:work` без `--id` выполняет задачи, пока очередь не опустеет; с `--id` — одну задачу.
- `Store::claim()` отдаёт задачу одному воркеру под блокировкой. Задача в статусе `running` дольше `timeout` секунд (по умолчанию 900) считается брошенной — воркер упал или превысил лимит времени — и забирается снова.
- Временные сбои (исключения провайдера, сети) повторяются до `attempts` раз (по умолчанию 3). `Error`, `EmptyRequest`, `InputTooLong`, `BudgetExceeded` и `ClarificationUnavailable` сразу переводят задачу в `failed`.
- `Stores\File` хранит задачи сериализованными файлами (`<id>.task`) и подходит для одного сервера; для нескольких серверов или большого числа задач реализуйте `Contracts\Store` поверх БД.

## Ход выполнения

Раунд идёт как сессия Claude в терминале: пользователь видит, чем модель занята сейчас, и что уже получилось. Менеджер сообщает о каждом шаге дважды — при старте (`Step::$duration` равен `null`, в интерфейсе это спиннер) и при завершении, тогда `Step::$data` содержит результат шага:

| Этап | При старте | При завершении |
| --- | --- | --- |
| `context` | — | `tokens`, `truncated` |
| `analysis`, `refinement` | — | `message`, `specification`, `questions`, `missing_extensions`, `tokens` |
| `tool` | `name`, `arguments` | `failed`, `error` при сбое, `tokens`, `truncated` |
| `generation` | `repair` (0 — первая попытка) | `message`, `files` (путь => байты), `tokens` или `error` |
| `validation` | — | `errors` |

`message` — одна-две фразы модели для пользователя на языке запроса: что она поняла, что изменили результаты инструментов, что делают файлы или что исправлено. Модель пишет их в каждом ответе, поле входит в схемы ответа.

Без очереди шаги получает колбэк `progress`:

```php
use Expansa\Ai\Step;

$extension = $manager->create('Добавить приветствие на главную страницу', function (Step $step): void {
    echo $step->isDone
        ? sprintf("✓ %s %s (%d мс)\n", $step->stage->value, $step->data['message'] ?? '', $step->duration)
        : "… {$step->stage->value}\n";
});
```

Исключение из колбэка прерывает раунд и выходит из `create()` или `clarify()`.

В очереди воркер добавляет шаги в `Task::$steps` и сохраняет задачу после каждого: опрос `get()` показывает завершённые шаги и текущий. Сохранение обновляет `updatedAt`, поэтому долгий раунд не считается брошенным. Шаги копятся за всю беседу; `Step::$round` — номер раунда (0 — запрос), а между раундами `r` и `r + 1` стоит ответ `$task->draft->session->answers[r]`. Если воркер упал посреди раунда, при повторе незавершённые шаги этого раунда заменяются новыми. Последний шаг упавшей задачи может остаться незавершённым — интерфейс показывает его прерванным.

`rename($id, $title)`, `archive($id)` и `delete($id)` меняют задачу и во время работы воркера: при сохранении шага он берёт название и флаг архива из хранилища, а удалённую задачу не восстанавливает и останавливается.

`cancel($id)` отменяет задачу в статусах `queued`, `running` и `questions`. Работающий воркер замечает отмену на ближайшей границе шага и останавливается без сохранения; текущий вызов провайдера не прерывается. Отменённую задачу воркеры не берут, и ответ на её вопросы не принимается.

## Уточнение запроса

Если модели не хватает сведений, `create()` возвращает вопросы в `$extension->questions` и не генерирует файлы. Приложение сохраняет `$extension->session` (в сессии пользователя, БД или кэше — объект сериализуется `serialize()`), показывает вопросы и передаёт ответ вместе с сессией в `clarify()`:

```php
$extension = $manager->create('Добавить приветствие на главную страницу');

if ($extension->questions !== []) {
    $_SESSION['ai'] = serialize($extension->session);
}

// следующий HTTP-запрос
$session = unserialize($_SESSION['ai'], ['allowed_classes' => [Expansa\Ai\Session::class]]);
$extension = $manager->clarify($session, 'Использовать hook welcome');
```

Каждый раунд возвращает новую сессию, переданная не меняется: если `clarify()` бросил исключение (сбой провайдера, лимит), ту же сессию можно отправить повторно. Число раундов ограничено `Limits::clarifications`; модель получает `clarifications_left` и на последнем раунде должна выбрать разумные значения сама. Если она всё же вернула вопросы, они отбрасываются (`metadata['questions_ignored']`) и генерация продолжается.

## Расширения PHP

`Platform` описывает, на чём будет работать сгенерированный код: версию PHP и список расширений. По умолчанию это `PHP_VERSION` и `get_loaded_extensions()` процесса, где работает менеджер. Если код генерируется на другой машине, передайте значения целевого сервера:

```php
use Expansa\Ai\Platform;

$manager = new Manager(
    provider: $provider,
    context: $context,
    platform: new Platform('8.4.2', ['core', 'json', 'mbstring', 'pdo', 'curl']),
);
```

Платформа уходит модели в каждом промпте (`platform`), и модель должна пользоваться только этими расширениями. Если задачу нельзя решить без недоступного расширения, модель перечисляет его в `missing_extensions`. Тогда менеджер не генерирует файлы, а возвращает `Draft` с `missingExtensions` и текстом в `errors`. Расширения, которые на самом деле есть в `Platform`, из этого списка отбрасываются.

```php
$extension = $manager->create('Уменьшать загруженные изображения до 800 px');

if ($extension->missingExtensions !== []) {
    // например: «Для задачи нужны расширения PHP: gd. Установите их или измените задачу.»
    $message = 'Для задачи нужны расширения PHP: ' . implode(', ', $extension->missingExtensions);
}
```

`Validators\Extensions` дополнительно проверяет сгенерированный код: вызовы функций (`imagecreatetruecolor()`, `curl_init()`, `mb_strlen()`) и классы (`new ZipArchive`, `Redis::`, `extends`, `instanceof`, `use`) распространённых расширений, которых нет в платформе, становятся ошибками проверки и уходят модели на исправление. Функции и классы, объявленные в самих сгенерированных файлах, не учитываются. Карта символов охватывает основные расширения (gd, intl, curl, zip, mbstring, bcmath, sodium, redis, imagick и др.); вызовы через переменную не отслеживаются.

## Лимиты и метрики

`Limits` задаёт максимум токенов пользовательского ввода, контекста, ответа каждого инструмента и ответа модели, число tool-вызовов, уточнений и исправлений, а также `totalTokens` — бюджет всей сессии, включая все раунды и исправления. Бюджет проверяется перед каждым вызовом провайдера: исправления останавливаются (`metadata['budget_exceeded']`), остальные вызовы бросают `BudgetExceeded`.

Провайдер возвращает `Completion` с числом входных и выходных токенов, текстом и метаданными сервиса; генератор — `Generation` с файлами и usage. Отрицательные значения usage отклоняются с `InvalidResponse`. `Draft::$metadata` содержит итоги раунда (токены, контекст, вызовы, исправления, флаги лимитов, время), шаги выполнения `steps`, применённые `limits`, суммы по сессии `session_totals` и итоги всех раундов `history`.

## Контракты и форматы

`Provider::complete(Prompt $prompt, int $maxOutputTokens)` получает `system` — неизменные инструкции этапа (их удобно кешировать у провайдера), `user` — данные запроса в JSON и `schema` — JSON Schema ответа. Передайте схему в structured output / JSON mode своего SDK. Ответ, обёрнутый в Markdown-блок ```` ```json ````, тоже принимается.

На этапе анализа модель возвращает объект со строками `message` и `specification` и массивами `questions`, `missing_extensions` и `tool_calls` (`name`, `arguments`). Выполняются только зарегистрированные инструменты и не больше лимита; ошибка инструмента или неизвестное имя не прерывают работу, а уходят модели как `{"name": ..., "error": ...}`. После инструментов модель уточняет спецификацию по их ответам.

`Tool` объявляет свойства `name`, `description` и `parameters` (JSON Schema аргументов) и метод `handle(array $arguments): string`:

```php
use Expansa\Ai\Contracts\Tool;

final class Hooks implements Tool
{
    public string $name = 'hooks';
    public string $description = 'Lists CMS hooks of an area';
    public array $parameters = ['type' => 'object', 'properties' => ['area' => ['type' => 'string']]];

    public function handle(array $arguments): string
    {
        return implode("\n", ['welcome', 'dashboardLoaded']);
    }
}
```

`Generator::generate(Brief $brief, int $maxOutputTokens)` возвращает `Generation`. `Brief::$platform` передаёт версию PHP и расширения; при исправлении `Brief` содержит ошибки проверки и файлы прошлой попытки. `Generators\Ai` ожидает ответ `{"message": ..., "files": [{"path": ..., "content": ...}]}` и передаёт `message` в `Generation::$message`; некорректный ответ генератора (`InvalidResponse`) считается неудачной попыткой и тоже уходит на исправление.

`Validators\Paths` разрешает относительные пути из букв, цифр, `.`, `_`, `-` и `/` без `..`, пустых сегментов, завершающих точек и имён устройств Windows; отклоняет пути, различающиеся только регистром, типы файлов вне `extensions` и расширение без PHP-тестов в `tests/` (`requireTests: false` отключает). `Validators\Php` проверяет синтаксис через `token_get_all(TOKEN_PARSE)`. `Validators\Policy` находит `eval`, обратные кавычки, вызовы `exec`, `shell_exec`, `system` и других функций из `functions`, `include`/`require` переменного пути — это защита от ошибок модели, а не песочница. Код не запускается.

Передайте менеджеру `Contracts\TokenCounter` выбранной модели: `TokenCounters\Characters` считает символы Unicode и служит приблизительным запасным вариантом. Невалидный UTF-8 он считает по байтам, поэтому лимиты не обходятся.

Исключения пакета в `Expansa\Ai\Exceptions`: `EmptyRequest`, `InputTooLong`, `BudgetExceeded`, `InvalidConfiguration`, `InvalidResponse`, `UnknownTool`, `ClarificationUnavailable`, `RequestFailed`, `Cancelled` (останавливает раунд отменённой задачи, его ловит `Queue`).

## Проверки

Тесты пакета запускаются командой `php tests/Ai.php`; PHPUnit-набор — `composer test:unit`.
