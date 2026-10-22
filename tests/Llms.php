<?php

declare(strict_types=1);

use App\Models\Option;
use App\Post\Type;
use App\Support\Llms;
use Expansa\Facades\Db;

// run: php tests/Llms.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

const EX_DB = ['prefix' => 'expansa_'];

// the database: rows of the post tables and their slugs, the queries are kept to check them
$db = new class {
    public array $rows = [];
    public array $slugs = [];
    public array $queries = [];

    public function select(string $table, array $columns, array $where): array
    {
        $this->queries[] = [$table, $where];
        if ($table === 'slugs') {
            return array_values(array_filter($this->slugs, fn (array $slug) => $slug['entity_table'] === $where['entity_table'] && in_array($slug['entity_id'], $where['entity_id'], true)));
        }

        $rows = $this->rows[$table] ?? [];
        if (isset($where['id'])) {
            $rows = array_values(array_filter($rows, fn (array $row) => in_array($row['id'], $where['id'], true)));
        }

        return $rows;
    }

    public function hasTable(string $table): bool
    {
        return true;
    }

    // the post tables have the column of the trash, Type does not add it
    public function schema(string $table): array
    {
        return ['deleted_at' => []];
    }
};
Db::swap($db);

$options = new ReflectionProperty(Option::class, 'options');
$seed    = fn (array $values) => $options->setValue(null, $values);

$type = fn (string $key, string $plural, bool $searchable = true) => Type::register(
    key: $key,
    labelName: $plural,
    labelNamePlural: $plural,
    labelAllItems: $plural,
    labelAdd: $plural,
    labelEdit: $plural,
    labelUpdate: $plural,
    labelView: $plural,
    labelSearch: $plural,
    labelSave: $plural,
    searchable: $searchable,
    showInMenu: false,
);
$type('pages', 'Pages');
$type('news', 'News');
$type('files', 'Storage', false);

$db->rows = [
    'pages' => [
        ['id' => 1, 'title' => 'About [us]', 'content' => '<p>We are   a <b>foundation</b> &amp; more.</p>'],
        ['id' => 2, 'title' => 'Contact', 'content' => ''],
        ['id' => 3, 'title' => 'Draft without slug', 'content' => ''],
    ],
    'news' => [
        ['id' => 7, 'title' => '', 'content' => str_repeat('word ', 60)],
    ],
];
$db->slugs = [
    ['entity_table' => 'pages', 'entity_id' => 1, 'slug' => 'about'],
    ['entity_table' => 'pages', 'entity_id' => 2, 'slug' => 'contact'],
    ['entity_table' => 'news', 'entity_id' => 7, 'slug' => 'first-news'],
];

$seed([
    'site' => ['name' => 'amfAR', 'tagline' => "Making\nAIDS History"],
    'seo'  => ['llms' => ['enabled' => 'on', 'selection' => 'automatic']],
]);
$text = Llms::render();
$url  = url();

check('the file starts with the site name and the tagline as a quote', str_starts_with($text, "# amfAR\n\n> Making AIDS History\n"));
check('a page is a link with the summary of its text', str_contains($text, "## Pages\n\n- [About \\[us\\]]({$url}about): We are a foundation & more."));
check('an item without a slug is left out', ! str_contains($text, 'Draft without slug'));
check('each public post type has its section, the storage has none', str_contains($text, "## News\n\n- [first-news]({$url}first-news): word") && ! str_contains($text, 'Storage'));
check('a long summary is cut by a word', str_contains($text, 'word word…') && ! str_contains($text, 'word …'));
check('automatic selection takes published pages by position', $db->queries[0] === ['pages', ['status' => 'publish', 'ORDER' => ['position' => 'ASC', 'title' => 'ASC'], 'LIMIT' => 50]]);

$seed(['site' => ['name' => 'amfAR'], 'seo' => ['llms' => ['enabled' => 'on', 'selection' => 'manual', 'pages' => ['about' => '2', 'contact' => '1', 'terms' => '', 'privacy' => '2']]]]);
$text = Llms::render();
check('manual selection lists the chosen pages in the order of the settings', str_contains($text, "## Pages\n\n- [Contact]({$url}contact)\n- [About"));
check('a site without a tagline has no quote', str_starts_with($text, "# amfAR\n\n## Pages"));

$seed(['site' => ['name' => 'amfAR'], 'seo' => ['llms' => ['enabled' => 'on', 'selection' => 'manual', 'pages' => []]]]);
check('manual selection without pages has no pages section', ! str_contains(Llms::render(), '## Pages'));

$existed = is_file(Llms::PATH);
$backup  = $existed ? file_get_contents(Llms::PATH) : null;

check('generate() writes the enabled file', Llms::generate() && file_get_contents(Llms::PATH) === Llms::render());

$seed(['seo' => ['llms' => ['enabled' => '0']]]);
check('generate() deletes the disabled file', Llms::generate() && ! is_file(Llms::PATH));
check('generate() of a disabled missing file succeeds', Llms::generate());

if ($existed) {
    file_put_contents(Llms::PATH, $backup);
}

exit($failures ? 1 : 0);
