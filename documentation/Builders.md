# Введение

Построители интерфейса дашборда: формы, таблицы и деревья (меню). Пакет находится в `Expansa\Builders`,
фасадов у него нет: `Form`, `Table` и `Tree` вызываются напрямую. Пакет описывает данные и порядок вывода,
а разметку рисуют шаблоны приложения: пакет не знает ни путей к шаблонам, ни фасадов `View` и `Asset`,
всё это приходит через `configure()`.

```php
use Expansa\Builders\Form;

Form::enqueue('profile', ['class' => 'dg g-7'], [
    ['type' => 'email', 'name' => 'email', 'label' => t('Email'), 'attributes' => ['required' => true]],
    ['type' => 'textarea', 'name' => 'bio', 'label' => t('About')],
]);

echo Form::render('profile');
```

| Класс                     | Назначение                                                                 |
|---------------------------|----------------------------------------------------------------------------|
| `Form`                    | Форма: поля и атрибуты тега; статически — реестр форм по uid и их вывод   |
| `Form\Contracts\Field`    | Тип поля для конструктора полей: настройки, правила проверки, вывод        |
| `Form\Fields\*`           | Встроенные типы полей, база — `AbstractField`                              |
| `Table`                   | База таблицы дашборда: строки и колонки, наследуется в `App\Tables`        |
| `Table\Cell`              | Колонка таблицы, описывается цепочкой методов                              |
| `Tree`                    | Именованное дерево элементов: меню, комментарии, таксономии                |

## Конфигурация

Все три построителя настраиваются в фазе `configure` (`bootstrap.php`):

```php
Form::configure(
    types: ['text' => Fields\Input::class, 'select' => Fields\Select::class /* ... */],
    view: fn (string $template, array $data) => (string) View::create("components/form/$template", $data),
    assets: fn (string $template, string $uid) => Asset::discover(View::create("components/form/$template")->path, $uid, ['type' => $uid]),
);

Table::configure(
    filter: EX_DASHBOARD . 'forms/items-filter.php',
    cellView: 'components/table/cell',
);

Tree::configure(
    allows: fn (array $capabilities) => array_all($capabilities, fn (string $capability) => Access::allows($user, $capability)),
);
```

| Параметр            | Что задаёт                                                                        |
|---------------------|-----------------------------------------------------------------------------------|
| `Form` `types`      | Классы типов полей по имени типа, их читает конструктор полей через `getTypes()`  |
| `Form` `view`       | Вывод шаблона поля по имени (`input`, `select`, `layout-tab`); без него — пустая строка |
| `Form` `assets`     | Подключение CSS и JS шаблона; uid — тип поля, чтобы `date` и `color` с общим шаблоном `input` получили свои скрипты |
| `Table` `filter`    | Файл, который регистрирует форму фильтра, подключается перед каждой таблицей      |
| `Table` `cellView`  | Вид ячеек по умолчанию, `->view('date')` колонки превращает его в `…/cell-date`   |
| `Tree` `allows`     | Проверка `capabilities` элемента; без неё видны все элементы                      |

## Формы

### Регистрация и вывод

Форма описывается в файле, который возвращает её uid, и выводится функцией `form()`: файл подключается
один раз, затем `Form::render()` собирает разметку.

```php
// dashboard/forms/user-sign-in.php
return Form::enqueue('user-sign-in', ['@submit.prevent' => '$ajax.post("user/sign-in")'], [/* поля */]);

// шаблон
echo form('user-sign-in', EX_DASHBOARD . 'forms/user-sign-in.php');
```

`enqueue()` очищает uid и бросает `InvalidArgumentException`, если он пуст или занят. У тега формы
всегда есть `id` (uid) и `method="POST"`. `Form::renderFields($fields)` выводит поля без тега формы.

### Поле

| Ключ          | Что значит                                                                        |
|---------------|-----------------------------------------------------------------------------------|
| `type`        | Тип: имя шаблона; `text`, `email`, `date` и другие HTML5-типы выводит шаблон `input` |
| `name`        | Имя поля, становится атрибутом `name`                                             |
| `label`       | Подпись; у обязательного поля шаблон ставит ей `data-required="*"` для CSS        |
| `attributes`  | HTML-атрибуты поля, `required` делает его обязательным                            |
| `error`       | Ключ ошибки ответа, `youla-ajax.js` показывает её у поля                          |
| `conditions`  | Условия показа по значениям других полей того же уровня                           |
| `fields`      | Вложенные поля у `tab`, `step`, `group`                                           |

Атрибуты дополняются по типу: `type` и `name`, виджет youla.js (`u-select`, `u-textarea`,
`u-datepicker`), у `select` нет `type`, у `date` он `text`.

`tab`, `step` и `group` — обёртки: их поля выводятся внутрь, шаблон — `layout-<type>`. Перед первой
вкладкой уровня выводится общее меню вкладок `layout-tab-menu`, шаги нумеруются по порядку.

### Условия показа

```php
['type' => 'text', 'name' => 'company', 'conditions' => [
    ['field' => 'account', 'operator' => '==', 'value' => 'business'],
    ['field' => 'code', 'operator' => 'pattern', 'value' => '^\d{6}$'],
]],
```

Поле видно, пока выполнены все условия. Каждое проверяется дважды: в браузере — выражением `u-show`,
на сервере — чтобы скрыть поле атрибутом `hidden` ещё до запуска скриптов. Обе проверки описаны рядом
в `Form\Internal\Condition`.

| Оператор                              | `value`                                       |
|---------------------------------------|-----------------------------------------------|
| `>`, `>=`, `<`, `<=`                  | число или строка                              |
| `==`, `===`, `!=`, `!==`              | значение; список — «одно из» / «ни одно из»   |
| `contains`                            | список, значение поля должно в нём быть       |
| `pattern`                             | регулярка без разделителей или список, подходит любая; флаг `u` |

### Изменение чужой формы

Плагин добавляет поля в зарегистрированную форму, не меняя её файл:

```php
Form::override('user-profile', fn (Form $form) => $form
    ->after('email', [['type' => 'tel', 'name' => 'phone', 'label' => t('Phone')]])
    ->remove('bio')
    ->attributes(['class' => 'dg g-8']));
```

Методы — как у `Http\Response`: первым аргументом имя поля-цели, вставляется весь блок полей в его порядке.

| Метод                       | Что делает                                                  |
|-----------------------------|-------------------------------------------------------------|
| `before($target, $fields)`  | вставляет перед полем                                       |
| `after($target, $fields)`   | вставляет после поля                                        |
| `replace($target, $fields)` | заменяет поле                                               |
| `prepend($fields)`          | вставляет в начало формы                                    |
| `append($fields)`           | добавляет в конец формы                                     |
| `remove($target)`           | убирает поле                                                |

Если поля-цели нет, `before()`, `after()` и `replace()` добавляют поля в конец, а `remove()` ничего
не делает. `Form::forget($uid)` убирает форму из реестра.

## Таблицы

Таблица дашборда — наследник `Table` в `App\Tables`: `data()` возвращает строки, `cells()` описывает колонки.

```php
final class Emails extends Table
{
    public function data(): array
    {
        return Email::all();
    }

    public function cells(): array
    {
        return [
            $this->cell('title')->title(t('Title'))->flexibleWidth('15rem')->sortable()->view('title'),
            $this->cell('date')->title(t('Date'))->fixedWidth('9rem')->view('date'),
        ];
    }
}
```

Шаблон читает строки через `$table->getData()`: `data()` вызывается при первом обращении и один раз,
поэтому создание таблицы не выполняет запросов. Ширины колонок собираются в `$table->style` — переменную `--expansa-grid-template-columns`,
одинаковые ширины подряд сворачиваются в `repeat()`.

## Деревья

Элементы добавляются плоским списком с `id` и `parent_id`, `render()` вкладывает их под `children`,
ставит `depth` от 0 и сортирует по `position`; при равных `position` сохраняется порядок добавления.

```php
Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->addItems([
    ['id' => 'posts', 'url' => 'posts', 'title' => t('Posts'), 'position' => 10],
    ['id' => 'tags', 'url' => 'tags', 'title' => t('Tags'), 'parent_id' => 'posts', 'capabilities' => ['manage_options']],
]));

echo tree('dashboard-main-menu', function (array $items, Tree $tree) {
    foreach ($items as $item) {
        echo $tree->format('<a href="%url$s">%title$s</a>', $item);
    }
});
```

- `attach()` с тем же именем дополняет дерево — так плагин добавляет пункты в меню ядра.
- Элементы, у которых не прошла проверка `capabilities`, не выводятся, а `Tree::allowsUrl()` закрывает
  их страницы.
- `format()` подставляет значения элемента по имени: `%title$s`, `%count$d`.
- `Tree::build($items, $callback)` выводит вложенные массивы (например, папки): `@nested` в выводе
  уровня заменяется на его детей.
