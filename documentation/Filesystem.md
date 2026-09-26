# Введение

Работа с локальной файловой системой: файлы, каталоги и загрузки. Пакет находится в `Expansa\Filesystem`,
доступ к нему — через фасад `Disk`. Неудачные операции бросают `FilesystemException`.

```php
use Expansa\Facades\Disk;

Disk::file(EX_STORAGE . 'notes/today.txt')->write('Hello');
$paths = Disk::dir(EX_PATH . 'plugins')->files('*.php', depth: 2);
$image = Disk::upload($_FILES['image'], EX_STORAGE . 'i/original');
```

| Класс                                  | Назначение                                                      |
|----------------------------------------|-----------------------------------------------------------------|
| `Disk`                                 | Экземпляр фасада: создаёт `File` и `Directory`, загружает файлы  |
| `File`                                 | Файл: чтение, запись, копирование, перемещение, отдача в браузер |
| `Directory`                            | Каталог: списки файлов и подкаталогов, копирование, удаление     |
| `AbstractEntry`                        | Общее для файла и каталога: части пути и метаданные             |
| `MimeType`                             | Разрешённые для загрузки расширения и их MIME-типы               |
| `Contracts\Entry`, `File`, `Directory` | Контракты файла и каталога                                      |
| `Exceptions\FilesystemException`       | Операция или загрузка не удалась                                |

## Использование

### Метаданные

Объект создаётся без обращения к диску; свойства читаются с диска при обращении, поэтому после
записи они актуальны. Для отсутствующего файла строки пустые, числа — `0`.

```php
$file = Disk::file(EX_STORAGE . 'i/original/photo.jpg');

$file->exists;    // true
$file->basename;  // photo.jpg
$file->filename;  // photo
$file->extension; // jpg
$file->dirpath;   // .../storage/i/original
$file->bytes;     // 254310
$file->size;      // 248.35 Kb
$file->mime;      // image/jpeg
$file->url;       // https://example.com/storage/i/original/photo.jpg
```

Также есть `dirname`, `type`, `modified`, `permission`, `sizeKb`, `sizeMb`, `sizeGb`, у файла — `hash`
(MD5 содержимого). У каталога `bytes` — сумма размеров файлов внутри.

### Файлы

```php
$file = Disk::file(EX_STORAGE . 'notes/today.txt');

$file->write('first line');                 // создаёт файл и каталог, дописывает в конец
$file->write('new content', append: false); // перезаписывает
$file->replace(['{name}' => 'Shop']);       // заменяет подстроки
$text = $file->read();                      // пустая строка для отсутствующего файла

$copy = $file->copy('yesterday');           // notes/yesterday.txt, возвращает копию
$file->rename('Today Note');                // имя очищается: today-note.txt
$file->move(EX_STORAGE . 'archive');        // занятое имя получает суффикс: today-note-2.txt
$file->touch();
$file->clean();                             // очищает содержимое
$file->delete();                            // false, если файла нет
$file->download();                          // отдаёт файл и завершает запрос
```

`rename()` и `move()` меняют путь объекта, `copy()` возвращает новый объект.

### Каталоги

```php
$dir = Disk::dir(EX_STORAGE . 'exports')->create();

$dir->files('*.{csv,json}', depth: 1); // пути по glob-шаблону, каталоги — со слешем в конце
$dir->directories(depth: 2);           // пути подкаталогов
$dir->tree();                          // ['exports' => ['2025' => ['01' => []]]]
$dir->chmod(0755, recursive: true);    // файлы внутри получают 0644
$dir->copy('exports-backup');
$dir->clean();                         // удаляет содержимое, каталог остаётся
$dir->delete();
$dir->download();                      // zip-архив, нужен ZipArchive
```

### Загрузки

`upload()` проверяет код ошибки PHP, лимит `upload_max_filesize` и расширение по `MimeType`, очищает
имя и сохраняет файл под свободным именем. `grab()` скачивает файл по HTTP(S) с теми же правилами
для имени. Остальные правила (MIME-тип, размеры изображения, квоты) проверяет вызывающий код до вызова.

```php
use Expansa\Filesystem\Exceptions\FilesystemException;

try {
    $file = Disk::upload($_FILES['file'], EX_STORAGE . 'i/');
    $logo = Disk::grab('https://example.com/logo.png', EX_STORAGE . 'i/');
} catch (FilesystemException $e) {
    return ['error' => t($e->getMessage())];
}

Disk::getMaxUploadSize(); // лимит в байтах
```

Сообщения исключений — английские строки, их можно перевести через `t()`.
