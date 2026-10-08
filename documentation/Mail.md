# Введение

Почта отправляется через PHPMailer, который поставляется с ядром (`Mail/PHPMailer`, не правится).
Пакет `Expansa\Mail` даёт короткий вызов и fluent-интерфейс поверх него:

```php
use Expansa\Facades\Mail;

Mail::send('user@example.com', 'Добро пожаловать', $html);

Mail::to('user@example.com')
    ->from('noreply@example.com')
    ->replyTo('support@example.com')
    ->subject('Отчёт')
    ->message($html)
    ->headers("X-Report: daily")
    ->attach([EX_STORAGE . 'report.csv'])
    ->send();
```

| Класс     | Назначение                                                        |
|-----------|-------------------------------------------------------------------|
| `Manager` | Цель фасада `Mail`: конфигурация, `to()` и `send()` в один вызов   |
| `Message` | Одно письмо: получатели, тема, тело, заголовки, вложения, отправка |

## Конфигурация

Без конфигурации PHPMailer отправляет письма функцией `mail()`. Настройку SMTP и подмену в тестах
даёт колбэк `setup`: он получает PHPMailer каждого письма перед отправкой и возвращает тот, которым
отправлять. `bootstrap.php` связывает его с фильтром `mailer`:

```php
Mail::configure(
    setup: fn (PHPMailer\PHPMailer\PHPMailer $mailer) => Hook::call('mailer', $mailer),
);
```

```php
Hook::add('mailer', function (PHPMailer\PHPMailer\PHPMailer $mailer) {
    $mailer->isSMTP();
    $mailer->Host = 'smtp.example.com';

    return $mailer;
});
```

Проверка сертификата сервера по умолчанию выключена (самоподписанные сертификаты); колбэк может
включить её через `$mailer->SMTPOptions`.

В Expansa SMTP, отправитель и DKIM задаются на вкладке «Mail» настроек, опция `mail`; `App\Support\Mailer`
применяет их к каждому письму до фильтра `mailer`:
- без сервера письма уходят через `mail()`;
- пароль SMTP и ключ DKIM хранятся зашифрованными (`App\Support\Secrets`), форма их не показывает:
  пустое поле оставляет сохранённое значение, очистка сервера или селектора удаляет их;
- ключ DKIM вставляется текстом PEM;
- кнопка «Send a test email» отправляет письмо текущему пользователю с сохранёнными настройками.

## Использование

- `send(string $to, string $subject, string $body, array $attachments = []): bool` — письмо одним вызовом.
- `to()` начинает новое письмо: каждое письмо — отдельный `Message`, состояние между ними не делится.
- `attach()` пропускает несуществующие файлы, `headers()` принимает строки `Имя: значение`.
- `send()` письма возвращает `false`, причина — в свойстве `$mailer->error`.
