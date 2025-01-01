# Введение

Хуки Grafema позволяют создавать и отслеживать различные события, происходящие в приложении.
Такое поведение в программировании иногда называется перехват. Но в рамках экосистемы Grafema 
их принято называть хуками. Другими совами, хуки, — это участки кода которые позволяют 
переопределить или расширить стандартное поведение приложения.

Например, вот распространённый сценарий. Необходимо отправлять уведомление в Telegram 
пользователю каждый раз, когда создан новый заказ в магазине. Вместо того чтобы нагромождать код 
обработки заказа с кодом уведомления, вы можете создать хук, в котором прикреплённый слушатель 
добавляет код отправки уведомлений.

## Создание новых хуков

Используя фасад Hooks, вы можете вручную регистрировать события в любом доступном вам месте 
приложения. В качестве значений возможно передать неограниченное число переменных, которые 
будут доступны слушателю.

```php
<?php
use Grafema\Hook;

Hook::call('testHook', $var1, $var2, ...);
```

## Создание новых слушателей

Для создания новых слушателей доступны несколько подходов.

### #1. Добавление через обычную callback функцию

```php
use Grafema\Hook;

function applyTestHook($var) {
    $var = 'foo';
    return $var;
}

Hook::add('testHook', 'applyTestHook');
```

### #2. Добавление через анонимную функцию

```php
use Grafema\Hook;
use Grafema\Hooks\HookListenerAlias;

Hook::add('testHook', fn(&$var) => $var = 'foo');
// или
Hook::add('testHook', #[HookListenerAlias('applyTestHook')] fn(&$var) => $var = 'foo');
```

Использование псевдонима позволяет удалять слушателя, обращаясь к нему по ключу. Псевдоним для 
анонимной функции устанавливается через аттрибут `HookListenerAlias`. Поэтому, при разработке 
рекомендуется использовать второй подход.

### #3. Добавление через сканирование каталога с PHP классами

По умолчанию Grafema автоматически ищет и регистрирует ваши слушатели событий, просканировав 
каталог Listeners. Когда Grafema находит (!внимание) **публичный метод класса** слушателя, Grafema 
регистрирует эти методы как слушатели для хука. При этом, название метода должно соответствовать
названию хука.

```php
Grafema\Hook::configure(GRFM_CORE . 'Listeners');

// file app/Listeners/Test.php
<?php
class Test
{
	public function testHook($var) {
		$var = 'foo';
		return $var;
	}
}
```

Не беспокойтесь, если вы не видите этот каталог в своей теме или плагине. Вы можете создать их
вручную. В будущем, мы предложим способ генерировать события и слушателей с помощью команд консоли.

## Общая информация

TODO: вероятно этот механизм следует исправить
Хуки не только добавляют функционал, но и позволяют переопределять значение переменных.
Разработчику нужно быть внимательным, чтобы при создании нового слушателя для таких хуков, функция 
всегда возвращала значение. В противном случае это может нарушить логику работы последующих 
слушателей в очереди.

## Изменение приоритета слушателей

Один хук может иметь несколько слушателей, которые не зависят друг от друга. Если мы хотим 
контролировать последовательность выполнения для таких случаев, используется параметр приоритет. 
Приоритет - это просто числовое значение: чем оно выше, тем раньше оно выполняется.

Если слушатель подключается через метод `add`, приоритет указывается в 3 параметре метода. В 
данном примере, функция `commerceProductCreate` сработает раньше `commerceProductUpdate`, несмотря
на то что она зарегистрирована позже.

```php
<?php
use Grafema\Hook;
use Grafema\Hooks\Priority;

Hook::add('testHook', 'commerceProductUpdate', Priority::BASE);
Hook::add('testHook', 'commerceProductCreate', Priority::HIGH + 1);
```

Если слушатель подключается через каталог `Listeners`, номер приоритета указывается через PHP 
аттрибут `HookListenerPriority`, где 400 - число приоритета:

```php
use Grafema\Hooks\HookListenerPriority;

class Test
{
	#[HookListenerPriority(400)]
	public function testHook($var) {
		$var = 'foo';
		return $var;
	}
}
```

## Соглашение об именовании хуков

В рамках разработки плагинов и тем, рекомендуется придерживаться соглашения об именовании хуков: 
использовать `префикс` + `название метода` в camelCase формате. Например: `commerceBeforeCreateOrder`.
Именование без префикса допускается только в рамках использования ядра Grafema. Не используйте 
однословные названия хуков. Пусть название отражает его целевое назначение.

Такой подход предотвращает конфликты имен в крупных проектах, упрощает работу для инструментов
анализа кода и поиска по подстроке, а также инструментах генерации документации.

В названии динамических хуков используйте фигурные скобки для переменных:

```php
// правильно:
Grafema\Hook::call("commerceUpdate{$status}{$post}");

// неправильно:
Grafema\Hook::call('commerceUpdate' . $status . $post);
```

Также, в PHP комментариях к динамическому хуку рекомендуется писать возможные варианты 
конкретного имени хука. Формат такой записи выглядит так:

```php
/**
 * Possible hook names include:
 *
 *  - `commerceUpdateNewProduct`
 *  - `commerceUpdateOldOrder`
 *  - `commerceUpdateDraftPage`
 */
Grafema\Hook::call("commerceUpdate{$status}{$post}");
```