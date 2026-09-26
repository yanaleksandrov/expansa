# Введение

Сессия и flash-сообщения. Пакет находится в `Expansa\Session`, сессию PHP возвращает функция `session()`.

```php
$session = session();
$session->start();

$session->set('cart', [12, 15]);
$session->get('cart');              // [12, 15]

$session->flash->add('notice', 'Сохранено');
$session->flash->pull('notice');    // ['Сохранено'] — на следующем запросе
```

| Класс                         | Назначение                                                       |
|-------------------------------|------------------------------------------------------------------|
| `Providers\Native`            | Сессия PHP: данные в `$_SESSION` после `start()`                 |
| `Providers\Memory`            | Сессия в массиве до конца запроса: тесты, консоль                |
| `StartSession`                | PSR-15 middleware: запускает сессию до обработчика, сохраняет после |
| `Contracts\Session`           | Данные: значения по ключу и `$flash`                             |
| `Contracts\Manager`           | Жизненный цикл: `start()`, `regenerateId()`, `save()`, `delete()` |
| `Contracts\Flash`             | Flash-сообщения: хранятся в сессии, пока их не прочитают         |
| `Exceptions\*`                | `AlreadyStarted`, `NotStarted`, `HeadersSent`; сбой функций сессии PHP — `RuntimeException` |

## Конфигурация

`Native` принимает массив настроек. Известные ключи — настройки сессии и cookie, остальные уходят в
`ini_set('session.<ключ>')`:

```php
use Expansa\Session\Providers\Native;

$session = new Native([
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

`session(array $config = ['name' => 'expansa'])` создаёт `Native` при первом вызове и дальше
возвращает его же. `Memory` принимает только имя: `new Memory('test')`.

## Использование

Используйте методы сессии вместо `session_start()`, `session_regenerate_id()` и `session_destroy()`.

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

## Расширение

Своё хранилище реализует `Contracts\Session` и `Contracts\Manager`. Свойства контрактов объявлены
только для чтения — `public string $id { get; }`, реализация задаёт их обычным свойством или hook:

```php
final class Redis implements Session, Manager
{
    public private(set) string $id = '';

    public bool $started {
        get => $this->id !== '';
    }

    // ...
}
```
