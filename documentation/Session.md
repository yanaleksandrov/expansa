# Введение

Сессия и flash-сообщения. Пакет находится в `Expansa\Session`, доступ — через фасад `Session`
или функцию `session()`, которая возвращает драйвер из конфигурации.

```php
use Expansa\Facades\Session;

Session::start();
Session::set('user_id', 7);

$session = session();

$session->set('cart', [12, 15]);
$session->get('cart');              // [12, 15]

$session->flash->add('notice', 'Сохранено');
$session->flash->pull('notice');    // ['Сохранено'] — на следующем запросе
```

| Класс                         | Назначение                                                       |
|-------------------------------|------------------------------------------------------------------|
| `Manager`                     | Драйвер из конфигурации, создаётся при первом обращении; экземпляр фасада |
| `Providers\Native`            | Сессия PHP: данные в `$_SESSION` после `start()`                 |
| `Providers\Memory`            | Сессия в массиве до конца запроса: тесты, консоль                |
| `StartSession`                | PSR-15 middleware: запускает сессию до обработчика, сохраняет после |
| `Contracts\Session`           | Данные: значения по ключу и `$flash`                             |
| `Contracts\Lifecycle`         | Жизненный цикл: `start()`, `regenerateId()`, `save()`, `delete()` |
| `Contracts\Flash`             | Flash-сообщения: хранятся в сессии, пока их не прочитают         |
| `Exceptions\*`                | `AlreadyStarted`, `NotStarted`, `HeadersSent`; сбой функций сессии PHP — `RuntimeException` |

## Конфигурация

`bootstrap.php` выбирает драйвер в фазе `configure`: сессию PHP в браузере, массив в консоли.
Сессия не запускается сама — её запускает код, которому она нужна (`Session::start()`, `StartSession`,
flash-значения `Redirect`). Повторный `configure()` сбрасывает созданный драйвер.

```php
Session::configure(
    driver: PHP_SAPI === 'cli' ? 'memory' : 'native',
    options: ['name' => 'expansa', 'secure' => Cookie::isSecureRequest()],
);

Session::extend('redis', fn (array $options) => new RedisSession($options)); // свой драйвер
```

Настройки `native` — ключи сессии и cookie, остальные уходят в `ini_set('session.<ключ>')`:

```php
Session::configure(driver: 'native', options: [
    'name'          => 'expansa',  // имя cookie, по умолчанию app
    'lifetime'      => 7200,       // время жизни cookie, секунды
    'path'          => null,       // null — из настроек PHP
    'domain'        => null,
    'secure'        => false,
    'httponly'      => true,
    'cache_limiter' => 'nocache',  // public, private_no_expire, private, nocache или '' — без заголовков
    'id'            => null,       // свой id сессии
    'gc_maxlifetime' => 7200,      // session.gc_maxlifetime
]);
```

У `memory` есть только `name`. Драйверы можно создать и напрямую: `new Native([...])`, `new Memory('test')`.

## Использование

Используйте методы сессии вместо `session_start()`, `session_regenerate_id()` и `session_destroy()`.
Фасад не видит свойств: вместо `$started` и `$flash` у него `Session::isStarted()` и `Session::getFlash()`.

| Метод или свойство              | Что делает                                                  |
|---------------------------------|-------------------------------------------------------------|
| `start()`                       | Запускает сессию; повторный запуск — `AlreadyStarted`, вывод уже начат — `HeadersSent` |
| `$started`, `$id`, `$name`      | Состояние, id и имя сессии, только чтение                   |
| `regenerateId()`                | Переносит данные на новый id, старая сессия удаляется; до `start()` — `NotStarted` |
| `save()`                        | Сохраняет и закрывает сессию; PHP делает это и сам в конце запроса |
| `delete()`                      | Забывает данные, удаляет сессию и её cookie                 |
| `get($key, $default)`, `all()`  | Значение по ключу, все значения                             |
| `set($key, $value)`, `setValues($values)` | Записывает одно или несколько значений            |
| `has($key)`                     | Есть ли ключ, в том числе со значением `null`               |
| `forget($key)`, `flush()`       | Забывает одно значение или все, flash-сообщения тоже        |

После входа пользователя меняйте id, чтобы старый id нельзя было использовать:

```php
$session->regenerateId();
$session->set('user_id', $user->id);
```

До `start()` данные `Native` хранятся в памяти и в `$_SESSION` не попадают.

### Flash-сообщения

Сообщение живёт в сессии, пока его не прочитают, — обычно на следующем запросе после редиректа.
Сообщения хранятся по ключу, под одним ключом их может быть несколько.

```php
$flash = session()->flash;

$flash->add('error', 'Неверный пароль');
$flash->has('error');     // true
$flash->pull('error');    // ['Неверный пароль'], сообщения забыты
$flash->pullAll();        // все сообщения по ключам, сообщения забыты
$flash->set('error', ['Первое', 'Второе']);
$flash->flush();
```

### Middleware

`StartSession` работает с PSR-15 контрактами из `Expansa\Session\Contracts`: запускает сессию, если
она не запущена, передаёт запрос дальше и сохраняет сессию.

```php
use Expansa\Session\StartSession;

$middleware = new StartSession(session());
```

### Flash-значения редиректа

`bootstrap.php` передаёт в `Redirect::configure(flash: ...)` запись в flash-сообщения сессии и запускает
её при необходимости: `Redirect::flash('errors', $errors)` перед `Redirect::send()` доживает до
следующего запроса, где его читает `Session::getFlash()->pull('errors')`.

## Расширение

Своё хранилище реализует `Contracts\Session` и `Contracts\Lifecycle` и подключается через
`Session::extend()`. Свойства контрактов объявлены
только для чтения — `public string $id { get; }`, реализация задаёт их обычным свойством или hook:

```php
final class Redis implements Session, Lifecycle
{
    public private(set) string $id = '';

    public bool $started {
        get => $this->id !== '';
    }

    // ...
}
```
