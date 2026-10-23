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
| `Tree\Item`               | Пункт вложенного дерева: поля как свойства, `depth`, `children`            |

## Конфигурация

Все три построителя настраиваются в фазе `configure` (`bootstrap.php`):

```php
Form::configure(
    types: ['text' => Fields\Input::class, 'select' => Fields\Select::class /* ... */],
    view: fn (string $template, array $data) => (string) View::create("parts/form/$template", $data),
    assets: fn (string $template, string $uid) => Asset::discover(View::create("parts/form/$template")->path, $uid, ['type' => $uid]),
    directory: EX_DASHBOARD . 'forms',
);

Table::configure(
    filter: EX_DASHBOARD . 'forms/items-filter.php',
    cellView: 'parts/table/cell',
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
| `Form` `directory`  | Папка файлов форм: `render('settings')` сам подключает `<directory>/settings.php`  |
| `Table` `filter`    | Файл, который регистрирует форму фильтра, подключается перед каждой таблицей      |
| `Table` `cellView`  | Вид ячеек по умолчанию, `->view('date')` колонки превращает его в `…/cell-date`   |
| `Tree` `allows`     | Проверка `capabilities` элемента; без неё видны все элементы                      |

## Формы

### Регистрация и вывод

Форма описывается в файле, который возвращает её uid, и выводится функцией `form()`: файл формы из папки
`directory` подключается один раз, затем `Form::render()` собирает разметку. Плагин со своими формами
передаёт путь вторым аргументом.

```php
// dashboard/forms/user-sign-in.php
return Form::enqueue('user-sign-in', ['@submit.prevent' => '$ajax.post("user/sign-in")'], [/* поля */]);

// шаблон
echo form('user-sign-in');

// плагин
echo form('order-editor', EX_PLUGINS . 'ecommerce/core/order.php');
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

Атрибуты дополняются по типу: `type` и `name`, виджет youla.js (`u-select`,
`u-pickadate` с опциями по типу), у `select` и `textarea` нет `type`, у `date`, `datetime-local`, `time` и `month` он `text`; `week` остаётся нативным.

`minlength` и `maxlength` у `textarea` и текстовых типов (`text`, `email`, `search`, `tel`, `url`) — положительные
числа, ноль и пустое значение убираются. Поле с ними получает счётчик под собой: `12 / 160` или `12` без максимума,
его обновляет директива `u-counter` из youla-expansa.js. У счётчика класс `is-short`, пока введённый текст короче
минимума, и `is-full` на максимуме. Правила проверки `validate()` поля — `lengthMin` и `lengthMax`; пустое
необязательное поле, как и в браузере, проверяется только по максимуму:

```php
['type' => 'textarea', 'name' => 'excerpt', 'label' => t('Excerpt'), 'attributes' => ['maxlength' => 160]],
```

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

### Ячейки

`->view('date')` выводит значение колонки шаблоном `parts/table/cell-date`. Шаблон только выводит значение:
скаляр или готовый объект из `Table\Values`, который собирает `data()`. Объект приводится к строке, по ней
строки сортируются и фильтруются в PHP; числовая строка сравнивается как число.

| Шаблон                           | Значение                                          | Что выводит                                           |
|----------------------------------|---------------------------------------------------|-------------------------------------------------------|
| `raw`                            | скаляр                                            | значение как есть, вид по умолчанию                   |
| `cb`, `toggle`, `text`, `image`  | `id`, `bool`, `string`, URL                       | чекбокс строки, переключатель, поле, аватар           |
| `title`                          | `Title`                                           | ссылку на элемент и действия при наведении (`Link[]`) |
| `badge`, `badges`                | `Badge`, `Badge[]`                                | бейдж, бейджи подряд                                  |
| `author`, `user`                 | `Person`                                          | имя с аватаром или логином и email                    |
| `date`                           | `Date`                                            | `<time>` с заметкой сверху, текст без даты — «Никогда» |
| `file`                           | `File`                                            | миниатюру или иконку, имя, размер и разрешение        |
| `progress`                       | `Progress`                                        | подпись, процент и полосу                             |
| `code`                           | `Code`                                            | моноширинный текст с кнопкой копирования              |
| `version`                        | `Version`                                         | версию, со стрелкой к новой и бейджем вида обновления |
| `device`                         | `Device`                                          | браузер и систему, IP, бейдж «Это устройство»         |
| `money`                          | `Money`                                           | сумму в валюте; сортируется по `amount`               |
| `rating`                         | `Rating`                                          | звёзды и число отзывов                                |
| `links`, `menu`                  | `Link[]`                                          | ссылки через запятую, меню «⋯» действий строки        |
| `count`                          | `Link`                                            | число со ссылкой на список, который оно считает       |
| `post`                           | `Post` или `null`                                 | пост комментария, ничего без поста                    |
| `plugin`, `extension`, `theme`   | `Extension`                                       | плагин или тему                                       |

```php
'date'   => new Date(Dates::format($row['created_at']), $row['created_at']),
'visit'  => new Date(t('Never')),
'slug'   => new Code($row['slug']),
'amount' => new Money(550.45, '$550.45', 't-green'),
```

### Фильтры, сортировка и страницы

Состояние таблицы — параметры адреса: фильтр — параметр своего поля со значениями через запятую, `!` после имени
значит «не равно» (`?author=1,3&role!=editor`), сортировка — `orderby` (ключ колонки) и `order` (`asc` или `desc`),
страница — `page`. Неизвестные поля, значения и колонки отбрасываются. Параметры, которые занимает страница
(`status`, `s`, `orderby` и другие), перечислены в `reserved` у `Table::configure()`: поле фильтра с таким
параметром бросает `LogicException`.

| Метод                     | Что описывает                                                                                         |
|---------------------------|-------------------------------------------------------------------------------------------------------|
| `filters()`               | Поля фильтра по ключу: `label`, `icon`, `options` (значение => подпись), `param` — параметр адреса, `column` — колонка запроса; без полей фильтра нет |
| `->sortable($by)`         | Колонку можно сортировать; `$by` — ключ строки или колонка запроса, по которой сравнивать, по умолчанию ключ колонки |
| `headData()` `parts`      | Части списка для select фильтра: подпись и параметры адреса каждой, `part` — текущая                  |
| `Table::action()`         | Атрибуты ссылки действия строки: API-маршрут, данные, подтверждение, обновление строк после ответа    |

```php
public function filters(): array
{
    return [
        'author_id' => ['label' => t('Author'), 'icon' => 'ph ph-user', 'param' => 'author', 'options' => User::options()],
    ];
}

public function cells(): array
{
    return [
        // в строке — отформатированная дата, сравнивается сырая
        $this->cell('date')->title(t('Date'))->sortable('created_at')->view('date'),
    ];
}

public function headData(): array
{
    return [
        'parts' => [
            ''      => ['label' => t('All'), 'query' => []],
            'trash' => ['label' => t('Trash'), 'query' => ['view' => 'trash']],
        ],
        'part'  => $this->isTrash ? 'trash' : '',
    ];
}
```

Таблица работает одним из двух способов:

- **В PHP** (по умолчанию). `data()` отдаёт все строки, `Table` сам отбирает их по фильтрам, сортирует и режет
  на страницы. Подходит для небольших списков: письма, плагины, темы.
- **В базе** — таблица реализует `Table\Queried`. `data()` читает только текущую страницу через `paged()`,
  `count()` считает строки по фильтрам через `filtered()`. Фильтры и сортировка работают по всей таблице:
  посты, комментарии, пользователи.

```php
final class Comments extends Table implements Queried
{
    public function data(): array
    {
        $rows = Db::select('comments', '*', $this->paged($this->where(), ['created_at' => 'DESC', 'id' => 'DESC'])) ?? [];

        return array_map(fn (array $row) => [/* значения ячеек */], $rows);
    }

    public function count(): int
    {
        return (int) Db::count('comments', $this->filtered($this->where()));
    }
}
```

`filtered($where)` добавляет к условиям таблицы условия фильтров, каждый фильтр — своей группой в скобках.
`paged($where, $order)` добавляет ещё порядок и `LIMIT` страницы; `$order` — порядок по умолчанию, он же
досортировывает строки, равные по выбранной колонке.

### Расширение

Поле фильтра или колонка, которые нельзя сравнить «как есть», описываются переопределением метода таблицы.
Свой метод обрабатывает только своё поле, остальные отдаёт родителю.

| Способ таблицы | Фильтр                                         | Сортировка                                  |
|----------------|------------------------------------------------|---------------------------------------------|
| В PHP          | `matchesFilter($row, $field, $operator, $values)` — подходит ли строка | `sortValue($row, $cell)` — значение для сравнения |
| В базе         | `filterWhere($field, $operator, $values)` — условие запроса | `orderBy($cell, $direction, $order)` — порядок запроса |

`$operator` — `is` или `not`, `$values` — уже проверенные значения из `options`. Без переопределения
фильтр сравнивает значение строки или колонку (`column`, по умолчанию ключ поля) со списком значений,
а сортировка — значение `Cell::$sortBy`.

Значение внутри JSON-списка — роль пользователя в колонке `roles`:

```php
protected function filterWhere(string $field, string $operator, array $values): array
{
    if ($field !== 'role') {
        return parent::filterWhere($field, $operator, $values);
    }

    // проценты ставятся явно: с `_` в имени LIKE не добавит их сам
    $roles = array_map(fn (string $role) => '%"' . $role . '"%', $values);

    return $operator === 'not' ? ['roles[!~]' => ['AND' => $roles]] : ['roles[~]' => $roles];
}
```

Диапазон дат — месяц создания поста, значения вида `2026-10`:

```php
protected function filterWhere(string $field, string $operator, array $values): array
{
    if ($field !== 'months') {
        return parent::filterWhere($field, $operator, $values);
    }

    $ranges = [];
    foreach ($values as $index => $month) {
        $start                       = $month . '-01 00:00:00';
        $ranges["AND #month $index"] = ['created_at[' . ($operator === 'not' ? '><' : '<>') . ']' => [$start, date('Y-m-t 23:59:59', strtotime($start))]];
    }

    return $operator === 'not' ? $ranges : ['OR' => $ranges];
}
```

Сортировка по выражению — имя или, без него, логин. Ключ массива `ORDER` всегда колонка, поэтому выражение
возвращается целиком через `Db::raw()`, вместе с порядком по умолчанию:

```php
protected function orderBy(Cell $cell, string $direction, array $order): array|Raw
{
    if ($cell->key !== 'name') {
        return parent::orderBy($cell, $direction, $order);
    }

    return Db::raw("COALESCE(NULLIF(<showname>, ''), <login>) $direction, <id> ASC");
}
```

То же в таблице в PHP:

```php
protected function sortValue(array $row, Cell $cell): int|float|string
{
    return $cell->key === 'name' ? mb_strtolower($row['showname'] ?: $row['login']) : parent::sortValue($row, $cell);
}
```

### В браузере

Разметка таблицы (`parts/table/table` и `parts/table/table-header`) связана с компонентом `table` из
`youla-expansa.js` через `u-bind`, нужные обработчикам значения лежат в `data-*`. Компонент получает поля
фильтра, активные фильтры и части списка параметрами: `u-data="table({fields, rows, parts})"`. Любое изменение
фильтра, поиска, части списка, сортировки или страницы меняет адрес и запрашивает `tables/filter`: API
отдаёт строки, панель шапки с пагинацией и заголовки колонок, фильтр остаётся на странице.

Новый параметр списка не требует JS. Поле с `name` и `u-bind="queryField"` при изменении ставит в адрес
параметр своего имени (пустое значение его убирает) и обновляет таблицу, а таблица читает параметр
на сервере, например в `data()`. Параметр добавляется в `reserved`, чтобы его не заняло поле фильтра.

```blade
<select name="language" u-bind="queryField">
    <option value="">{{ t('All languages') }}</option>
    <option value="ru">Русский</option>
</select>
```

Из своего обработчика компонента то же делает `setQuery({language: 'ru'})`.

## Деревья

Элементы добавляются плоским списком с `id` и `parent_id`. `Tree::get($name)` отдаёт
только видимые — объекты `Tree\Item`, вложенные в `children` на любую глубину: у листа это пустой список,
`depth` считается от 0. Поля, с которыми пункт добавлен, читаются как свойства: `$item->title`,
`$item->url`; отсутствующее поле — `null`. Элементы одного уровня отсортированы по `position`, при равных
сохраняется порядок добавления.

```php
Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->append([
    ['id' => 'posts', 'url' => 'posts', 'title' => t('Posts'), 'position' => 10],
    ['id' => 'tags', 'url' => 'tags', 'title' => t('Tags'), 'parent_id' => 'posts', 'capabilities' => ['manage_options']],
]));
```

Шаблон выводит дерево функцией `tree()`. Её функция получает один уровень — список пунктов и его
глубину — и сама пишет обёртку и цикл, а `tree()` без функции внутри неё выводит детей пункта тем же телом, поэтому глубина
не ограничена. Пустой уровень не выводится, так что проверять наличие пунктов и детей не нужно. Разметка
печатается сразу, без буферизации:

```blade
<?php tree('post-comments', function (array $comments, int $depth) { ?>
    <ul class="comments comments--{{ $depth }}">
        @foreach($comments as $comment)
            <li>
                <p>{{ $comment->text }}</p>
                <?php tree($comment->children); ?>
            </li>
        @endforeach
    </ul>
<?php }); ?>
```

Первым аргументом `tree()` принимает имя дерева или пункты; пустой список, `null` и не массив ничего
не выводят. `tree()` без функции берёт ту, внутри которой вызвана, поэтому внутри дерева можно вывести
другое дерево со своей функцией. Глубина считается по вызовам `tree()`: 0 у корня, у каждого вложенного
уровня на один больше, поэтому она есть и у обычных массивов. В классах
вместо функции — `Tree::walk()`. Тело — функция, переменные шаблона передаются в него через `use`:
`function (array $comments, int $depth) use ($user)`.

Так выводятся меню дашборда: `parts/menu`, `parts/user-account`.

- `attach()` с тем же именем дополняет дерево — так плагин добавляет пункты в меню ядра.
- Элементы, у которых не прошла проверка `capabilities`, не выводятся вместе с их детьми, а
  `Tree::canOpen()` закрывает их страницы.
- `append()` добавляет пункты списком; в `attach()` у дерева есть `$tree->items` — пункты как добавлены,
  без вложенности и проверки прав. `get()` только читает: дерево создаётся первым `attach()`.

## Производительность

`Tree::get()` раскладывает пункты по `parent_id` один раз, поэтому время растёт линейно с их числом.
Бенчмарк — `php tests/benchmarks/Builders.php --baseline=<ref>`:

| Дерево            | До индекса | С индексом |
|-------------------|------------|------------|
| меню, 50 пунктов  | 75 мкс     | 21 мкс     |
| 1 000 комментариев | 21 мс     | 0,4 мс     |
| 5 000 комментариев | 572 мс    | 2,1 мс     |

Памяти уходит около полутора размеров самих пунктов: строки не копируются, создаются только массивы
пунктов с `depth` и `children`.
