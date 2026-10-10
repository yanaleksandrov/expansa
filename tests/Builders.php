<?php

declare(strict_types=1);

use Expansa\Builders\Form;
use Expansa\Builders\Table;
use Expansa\Builders\Tree;
use Expansa\Builders\Tree\Item;

// run: php tests/Builders.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

// Tree: items are nested by parent_id and sorted by position, equal positions keep their order
Tree::attach('menu', fn (Tree $tree) => $tree->append([
    ['id' => 'c', 'title' => 'C', 'position' => 5],
    ['id' => 'a', 'title' => 'A', 'position' => 1],
    ['id' => 'b', 'title' => 'B', 'position' => 1],
    ['id' => 'b1', 'title' => 'B1', 'parent_id' => 'b'],
    ['id' => 'b2', 'title' => 'B2', 'parent_id' => 'b'],
    ['id' => 'secret', 'title' => 'Secret', 'capabilities' => ['manage_options']],
]));
Tree::attach('menu', fn (Tree $tree) => $tree->append([['id' => 'd', 'title' => 'D', 'position' => 1]]));

$nested = Tree::get('menu');

$ids = fn (array $items) => array_map(fn (Item $item) => $item->id, $items);

check('tree items are sorted by position, equal ones in the order they were added', $ids($nested) === ['secret', 'a', 'b', 'd', 'c']);
check('tree children go under `children` with the next depth', $ids($nested[2]->children) === ['b1', 'b2'] && $nested[2]->children[0]->depth === 1);
check('a leaf has an empty list of children', $nested[1]->children === [] && $nested[2]->children[0]->children === []);
check('an item reads its keys as properties, a missing one is null', $nested[1]->title === 'A' && $nested[1]->url === null && ! isset($nested[1]->url));
Tree::attach('menu', function (Tree $tree) use (&$count) {
    $count = count($tree->items);
});
check('attach() of the same name adds to one tree', $count === 7);
check('an item without id throws', throws(fn () => Tree::attach('menu', fn (Tree $tree) => $tree->append([['title' => 'No ID']])), InvalidArgumentException::class));

Tree::configure(allows: fn (array $capabilities) => ! in_array('manage_options', $capabilities, true));
check('items failing the capability check are left out', ! in_array('secret', $ids(Tree::get('menu')), true));

Tree::attach('pages', fn (Tree $tree) => $tree->append([['id' => 'mail', 'url' => 'settings?tab=mail', 'capabilities' => ['manage_options']]]));
check('canOpen() closes the page of a hidden item', ! Tree::canOpen('settings', ['tab' => 'mail']));
check('canOpen() allows a page of no item', Tree::canOpen('settings', ['tab' => 'other']));
Tree::configure();
check('an unknown tree has no items', Tree::get('missing') === []);

$html = '';
Tree::walk('menu', function (array $items, int $depth) use (&$html) {
    $html .= "<ul $depth>";
    foreach ($items as $item) {
        $html .= '<' . $item->id . '>';
        Tree::walk($item->children);
        $html .= '</' . $item->id . '>';
    }
    $html .= '</ul>';
});
check('walk() wraps every level, without a callback it outputs the children with the one it runs in', $html === '<ul 0><secret></secret><a></a><b><ul 1><b1></b1><b2></b2></ul></b><d></d><c></c></ul>');

$calls = 0;
Tree::walk([], function () use (&$calls) {
    $calls++;
});
Tree::walk(null, fn () => $calls++);
Tree::walk('missing', fn () => $calls++);
check('walk() skips an empty list, not a list and an unknown tree', $calls === 0);

$html = '';
Tree::walk([['id' => 'p', 'children' => [['id' => 'q', 'children' => []]]]], function (array $items) use (&$html) {
    foreach ($items as $item) {
        $html .= $item['id'] . '(';
        Tree::walk([['id' => 'x', 'children' => [['id' => 'y', 'children' => []]]]], function (array $tags) use (&$html) {
            foreach ($tags as $tag) {
                $html .= $tag['id'];
                Tree::walk($tag['children']);
            }
        });
        Tree::walk($item['children']);
        $html .= ')';
    }
});
check('another tree inside walk() has its own callback, the outer one comes back after it', $html === 'p(xyq(xy))');

$html = '';
tree('menu', function (array $items) use (&$html) {
    $html .= '[';
    foreach ($items as $item) {
        $html .= $item->id;
        tree($item->children);
    }
    $html .= ']';
});

$depths = [];
tree([['id' => 'x', 'children' => [['id' => 'y', 'children' => [['id' => 'z', 'children' => []]]]]]], function (array $items, int $depth) use (&$depths) {
    $depths[] = $depth;
    tree($items[0]['children']);
});
check('the depth of a level is counted by tree() calls, for any items', $depths === [0, 1, 2]);
check('tree() outputs a tree by name level by level', $html === '[secretab[b1b2]dc]');
check('walk() without a callback outside another walk() throws', throws(fn () => Tree::walk([['id' => 'x']]), LogicException::class));

try {
    Tree::walk([['id' => 'e', 'children' => []]], fn () => throw new RuntimeException());
} catch (RuntimeException) {
}
check('a callback that throws is not left for the next walk()', throws(fn () => Tree::walk([['id' => 'x']]), LogicException::class));

// Form: templates are named by type, conditions become u-show and hidden
Form::configure(view: fn (string $template, array $data) => "[$template:" . ($data['name'] ?? '') . ']');

$uid = Form::enqueue('profile', ['class' => 'form'], [
    ['type' => 'email', 'name' => 'email'],
    ['type' => 'group', 'name' => 'extra', 'fields' => [['type' => 'textarea', 'name' => 'bio']]],
]);
check('a form renders its fields in a form tag with the id and POST', Form::render($uid) === "<form id=\"profile\" method=\"POST\" class=\"form\">\n[input:email][layout-group:extra]</form>\n");
check('a taken uid throws', throws(fn () => Form::enqueue('profile'), InvalidArgumentException::class));
check('an unknown form renders empty', Form::render('missing') === '');

$forms = sys_get_temp_dir() . '/expansa-forms-' . getmypid();
@mkdir($forms);
file_put_contents("$forms/newsletter.php", '<?php return Expansa\Builders\Form::enqueue("newsletter", [], [["type" => "email", "name" => "email"]]);');
Form::configure(view: fn (string $template, array $data) => "[$template:" . ($data['name'] ?? '') . ']', directory: $forms);
check('render() loads a form not registered yet from <uid>.php of the directory', Form::render('newsletter') === "<form id=\"newsletter\" method=\"POST\">\n[input:email]</form>\n");
check('the form file is loaded once', Form::render('newsletter') === Form::render('newsletter'));
unlink("$forms/newsletter.php");
rmdir($forms);

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
