<?php

declare(strict_types=1);

use Expansa\Builders\Form;
use Expansa\Builders\Table;
use Expansa\Builders\Tree;

// run: php tests/Builders.php
require_once __DIR__ . '/bootstrap.php';

// Tree: items are nested by parent_id and sorted by position, equal positions keep their order
Tree::attach('menu', fn (Tree $tree) => $tree->addItems([
    ['id' => 'c', 'title' => 'C', 'position' => 5],
    ['id' => 'a', 'title' => 'A', 'position' => 1],
    ['id' => 'b', 'title' => 'B', 'position' => 1],
    ['id' => 'b1', 'title' => 'B1', 'parent_id' => 'b'],
    ['id' => 'b2', 'title' => 'B2', 'parent_id' => 'b'],
    ['id' => 'secret', 'title' => 'Secret', 'capabilities' => ['manage_options']],
]));
Tree::attach('menu', fn (Tree $tree) => $tree->addItem(['id' => 'd', 'title' => 'D', 'position' => 1]));

$nested = [];
Tree::render('menu', function (array $items) use (&$nested) {
    $nested = $items;
});

check('tree items are sorted by position, equal ones in the order they were added', array_column($nested, 'id') === ['secret', 'a', 'b', 'd', 'c']);
check('tree children go under `children` with the next depth', array_column($nested[2]['children'], 'id') === ['b1', 'b2'] && $nested[2]['children'][0]['depth'] === 1);
check('attach() of the same name adds to one tree', count(Tree::get('menu')->items) === 7);
check('an item without id throws', throws(fn () => Tree::get('menu')->addItem(['title' => 'No ID']), InvalidArgumentException::class));

Tree::configure(allows: fn (array $capabilities) => ! in_array('manage_options', $capabilities, true));
$ids = [];
Tree::render('menu', function (array $items) use (&$ids) {
    $ids = array_column($items, 'id');
});
check('items failing the capability check are left out', ! in_array('secret', $ids, true));

Tree::attach('pages', fn (Tree $tree) => $tree->addItem(['id' => 'mail', 'url' => 'settings?tab=mail', 'capabilities' => ['manage_options']]));
check('allowsUrl() closes the page of a hidden item', ! Tree::allowsUrl('settings', ['tab' => 'mail']));
check('allowsUrl() allows a page of no item', Tree::allowsUrl('settings', ['tab' => 'other']));
Tree::configure();

$html = Tree::render('menu', fn (array $items, Tree $tree) => print($tree->format('<a href="#%id$s">%title$s</a>', $items[1])));
check('format() puts item values by name', $html === '<a href="#a">A</a>');

$html = Tree::build(['docs' => ['img' => []]], function (int $depth, string $key) {
    echo "<ul data-depth=\"$depth\"><li>$key@nested</li></ul>";
});
check('build() replaces @nested with the children', $html === '<ul data-depth="1"><li>docs<ul data-depth="2"><li>img</li></ul></li></ul>');

// Form: templates are named by type, conditions become u-show and hidden
Form::configure(view: fn (string $template, array $data) => "[$template:" . ($data['name'] ?? '') . ']');

$uid = Form::enqueue('profile', ['class' => 'form'], [
    ['type' => 'email', 'name' => 'email'],
    ['type' => 'group', 'name' => 'extra', 'fields' => [['type' => 'textarea', 'name' => 'bio']]],
]);
check('a form renders its fields in a form tag with the id and POST', Form::render($uid) === "<form id=\"profile\" method=\"POST\" class=\"form\">\n[input:email][layout-group:extra]</form>\n");
check('a taken uid throws', throws(fn () => Form::enqueue('profile'), InvalidArgumentException::class));
check('an unknown form renders empty', Form::render('missing') === '');

$names = fn (Form $form) => array_map(fn (array $field) => $field['name'] ?? '-', $form->fields);

$form = Form::override('profile', fn (Form $form) => $form
    ->after('email', [['type' => 'text', 'name' => 'first'], ['type' => 'text', 'name' => 'last']])
    ->before('email', [['type' => 'hidden', 'name' => 'id']]));
check('after() and before() insert a block of fields in its order', $names($form) === ['id', 'email', 'first', 'last', 'extra']);

$form->replace('first', [['type' => 'text', 'name' => 'given'], ['type' => 'text', 'name' => 'family']]);
check('replace() puts the whole block instead of the field', $names($form) === ['id', 'email', 'given', 'family', 'last', 'extra']);

$form->after('id', [['type' => 'divider']])->before('email', [['type' => 'text', 'name' => 'phone']]);
check('a field is found among fields without a name', $names($form) === ['id', '-', 'phone', 'email', 'given', 'family', 'last', 'extra']);

$form->remove('phone')->remove('missing');
check('remove() takes out the field, a missing one changes nothing', $names($form) === ['id', '-', 'email', 'given', 'family', 'last', 'extra']);

$form->prepend([['type' => 'text', 'name' => 'top']])->append([['type' => 'text', 'name' => 'bottom']]);
check('prepend() and append() add at the start and the end', $names($form)[0] === 'top' && $names($form)[8] === 'bottom');

$form->after('missing', [['type' => 'text', 'name' => 'note']]);
check('fields go to the end when there is no target field', $names($form)[9] === 'note');

Form::forget('profile');
check('forget() removes a form', Form::render('profile') === '');

$data = [];
Form::configure(view: function (string $template, array $field) use (&$data) {
    $data[$field['name']] = $field;
    return '';
});
Form::renderFields([
    ['type' => 'text', 'name' => 'code', 'attributes' => ['value' => '42']],
    ['type' => 'select', 'name' => 'country', 'attributes' => ['required' => true]],
    ['type' => 'text', 'name' => 'digits', 'conditions' => [['field' => 'code', 'operator' => 'pattern', 'value' => '^\d+$']]],
    ['type' => 'text', 'name' => 'letters', 'conditions' => [['field' => 'code', 'operator' => 'pattern', 'value' => ['^[a-z]+$']]]],
    ['type' => 'text', 'name' => 'big', 'conditions' => [['field' => 'code', 'operator' => '>', 'value' => 100]]],
]);
check('a select gets its widget and no type', $data['country']['attributes'] === ['name' => 'country', 'required' => true, 'u-select' => '']);
check('a matching pattern leaves the field shown', $data['digits']['conditions']['hidden'] === false);
check('no matching pattern hides the field', $data['letters']['conditions']['hidden'] === true);
check('a pattern condition is checked in the browser by RegExp', str_contains($data['digits']['conditions']['u-show'], "new RegExp(pattern, 'u').test(code)"));
check('a comparison becomes a JS expression', $data['big']['conditions']['u-show'] === 'code > 100' && $data['big']['conditions']['hidden'] === true);

// Table: cells use the configured view with the kind of the column
Table::configure(cellView: 'components/table/cell');
$table = new class extends Table {
    public function data(): array
    {
        return [];
    }

    public function cells(): array
    {
        return [$this->cell('date')->view('date')];
    }
};
check('a cell view adds its kind to the configured one', $table->cells[0]->view === 'components/table/cell-date');

$table = new class extends Table {
    public int $queries = 0;

    public function data(): array
    {
        $this->queries++;

        return [['title' => 'First']];
    }

    public function cells(): array
    {
        return [];
    }
};
$created = $table->queries;
$rows    = [$table->getData(), $table->getData()];
check('table rows are got on the first read, once', $created === 0 && $table->queries === 1 && $rows[1] === [['title' => 'First']]);

exit($failures ? 1 : 0);
