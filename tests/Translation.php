<?php

declare(strict_types=1);

use Expansa\Translation\Internal\Forms;
use Expansa\Translation\Internal\PluralRule;
use Expansa\Translation\Languages;
use Expansa\Translation\Manager;

// run: php tests/Translation.php
require_once __DIR__ . '/bootstrap.php';

$translator = new Manager();
$render     = fn (string $text): string => $translator->translate($text);

// Markdown
check('headings of every level', $render('# Заголовок') === '<h1>Заголовок</h1>' && $render('###### Заголовок') === '<h6>Заголовок</h6>');
check('bold and italic inside a heading', $render('## Текст с **жирным** и *курсивом*') === '<h2>Текст с <strong>жирным</strong> и <em>курсивом</em></h2>');
check('quote', $render('> Это цитата.') === '<blockquote>Это цитата.</blockquote>');
check('bold and italic in a sentence', $render('Это **важно** и *курсив*') === 'Это <strong>важно</strong> и <em>курсив</em>');
check('link', $render('Ссылка на [Google](https://www.google.com).') === 'Ссылка на <a href="https://www.google.com">Google</a>.');
check('image', $render('![Логотип](https://example.com/logo.png)') === '<img src="https://example.com/logo.png" alt="Логотип"/>');
check('plain text stays as is', $render('Обычный текст') === 'Обычный текст');
check('underscores inside words stay', $render('Set upload_max_filesize and EX_AI_KEY') === 'Set upload_max_filesize and EX_AI_KEY');
check('underscores at word edges emphasize', $render('_курсив_ и __жирный__, «_важно_»') === '<em>курсив</em> и <strong>жирный</strong>, «<em>важно</em>»');
check('a lone underscore stays', $render('a _ b _ c') === 'a _ b _ c');
check('a script link is not rendered', ! str_contains($render('[x](javascript://alert(1))'), '<a'));

// placeholders
check('placeholders take the values in order, with case and suffix', $translator->translate('Hi, ::Firstname, you have :count\st none closed "::TASKNAME" task.', 'john', 1, 'test')
    === 'Hi, John, you have 1st none closed &quot;TEST&quot; task.');
check('values and the string are escaped', $translator->translate('<b>:name</b>', '<i>') === '&lt;b&gt;&lt;i&gt;&lt;/b&gt;');
check('%s is inserted as is for markup', $translator->translate('Read %sthe docs%s', '<a href="/docs">', '</a>') === 'Read <a href="/docs">the docs</a>');
check('placeholders without a value stay', $translator->translate('From :from to :to', 'A') === 'From A to :to');
check('an attribute is plain text escaped once', $translator->translateAttribute('Search "posts" & **pages**') === 'Search &quot;posts&quot; &amp; &lt;strong&gt;pages&lt;/strong&gt;');
check('translateIf picks by the condition', $translator->translateIf(false, 'Yes', 'No') === 'No');

// plural forms in braces, the English rule of the bundled languages
$en = [0 => '0 files', 1 => '1 file', 2 => '2 files', 21 => '21 files'];
check('a block of forms picks by the count, the first value', array_all($en, fn ($expected, $n) => $translator->translate(':count {file|files}', $n) === $expected));
check('a negative count uses its absolute value', $translator->translate(':count {file|files}', -1) === '-1 file');
check('only the word changes, other placeholders keep their order', $translator->translate(':count {file|files} in :folder', 3, 'Docs') === '3 files in Docs');
check('# is the number inside a block', $translator->translate('{One file|# files} in :folder', 1, 'Docs') === 'One file in Docs'
    && $translator->translate('{One file|# files} in :folder', 4, 'Docs') === '4 files in Docs');
check('=N gives the text for an exact count', $translator->translate('{=0 No files|# file|# files}', 0) === 'No files'
    && $translator->translate('{=0 No files|# file|# files}', 1) === '1 file');
check('several blocks follow one count', $translator->translate(':count {new|new} {file|files}', 2) === '2 new files');
check('escaped braces are literal', $translator->translate('{# file|# files} \\{draft\\}', 2) === '2 files {draft}');
check('braces without a numeric first value stay as they are', $translator->translate('Use {name}', 'x') === 'Use {name}');
check('translatePlural() is translate() with the count first', $translator->translatePlural(':count {item|items}', 5) === '5 items');
check('an attribute with forms', $translator->translateAttribute('{"one"|"many"}', 2) === '&quot;many&quot;'
    && $translator->translateAttributePlural('{"one"|"many"}', 1) === '&quot;one&quot;');
$big = $translator->translate(':count {file|files}', 12345);
check('the count is formatted for the locale', $big === (class_exists(NumberFormatter::class) ? '12,345 files' : '12345 files'));

// the rule of the request locale, en-US here, taken from the configured languages
$russian = new Manager();
$russian->configure(routes: [], pattern: '%s', languages: fn () => [[
    'name' => 'Test', 'native' => 'Test', 'locale' => 'en-US', 'iso_639_1' => 'en', 'country' => 'us',
    'plural' => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
]]);
$ru = [1 => '1 файл', 2 => '2 файла', 5 => '5 файлов', 11 => '11 файлов', 21 => '21 файл', 22 => '22 файла', 111 => '111 файлов'];
check('the plural rule comes from the language list', array_all($ru, fn ($expected, $n) => $russian->translate(':count {файл|файла|файлов}', $n) === $expected));
check('a missing form falls back to the last one', $russian->translate(':count {файл|файлов}', 3) === '3 файлов');

$broken = new Manager();
$broken->configure(routes: [], pattern: '%s', languages: fn () => [['locale' => 'en-US', 'plural' => 'system("ls")']]);
check('a broken plural rule falls back to English', $broken->translate('{one|many}', 1) === 'one' && $broken->translate('{one|many}', 2) === 'many');

// every rule of the bundled languages compiles and returns a form within nplurals
$languages = Expansa\Translation\Languages::all();
check('rules of all bundled languages compile', array_all($languages, function (array $language): bool {
    $rule = PluralRule::compile($language['plural']);

    return array_all(range(0, 200), fn (int $n) => ($index = $rule($n)) >= 0 && $index < $language['nplurals']);
}));
check('unsupported tokens are rejected', throws(fn () => PluralRule::compile('n; exit'), InvalidArgumentException::class)
    && throws(fn () => PluralRule::compile('(n == 1'), InvalidArgumentException::class));

// languages come from the configured source, once
$calls   = 0;
$listing = new Manager();
$listing->configure(routes: [], pattern: '%s', languages: function () use (&$calls, $languages) {
    $calls++;

    return $languages;
});
check('language() finds by a field', $listing->language('ru', 'iso_639_1')['native'] === 'Русский');
check('languageOptions() gives flag and name', $listing->languageOptions()['ru-RU']['flag'] === 'ru');
check('the language source is called once', $calls === 1);
check('no configured languages give the bundled list', count($translator->languageOptions()) === 119 && $translator->language('ru-RU')['native'] === 'Русский');

// the chosen locale picks the translation file; without it the Accept-Language header decides
$dir = sys_get_temp_dir() . '/expansa-i18n-' . getmypid();
@mkdir("$dir/i18n", 0777, true);
file_put_contents("$dir/i18n/ru-RU.json", json_encode([
    'Users'                      => 'Пользователи',
    ':count {file|files}'        => ':count {файл|файла|файлов}',
    'Deleted {# file|# files}.'  => ['Удалён # файл.', 'Удалено # файла.', 'Удалено # файлов.'],
]));

$russian = new Manager();
$russian->configure(routes: [__DIR__ => $dir], pattern: 'i18n/%s', languages: fn () => $languages, locale: fn () => 'ru-RU');
check('the chosen locale is used', $russian->locale() === 'ru-RU');
check('a string is translated from the file of the chosen locale', $russian->translate('Users') === 'Пользователи');
check('the plural rule of the chosen locale picks the form', array_map(fn ($n) => $russian->translate(':count {file|files}', $n), [1, 3, 5, 21, '22'])
    === ['1 файл', '3 файла', '5 файлов', '21 файл', '22 файла']);
check('a translation given as a list holds a whole sentence per form', $russian->translate('Deleted {# file|# files}.', 1) === 'Удалён 1 файл.'
    && $russian->translate('Deleted {# file|# files}.', 7) === 'Удалено 7 файлов.');
check('a missing string stays English', $russian->translate('Pages') === 'Pages');
check('translateAttribute() picks a plural form too', $russian->translateAttribute(':count {file|files}', 11) === '11 файлов');
check('`|` outside a block is plain text', $russian->translate('a|b') === 'a|b' && $russian->translate('a|b', 2) === 'a|b');

$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru';
$header = new Manager();
$header->configure(routes: [], pattern: '%s', languages: fn () => $languages, locale: fn () => null);
check('no chosen locale falls back to Accept-Language, a language code becomes its locale', $header->locale() === (function_exists('locale_accept_from_http') ? 'ru-RU' : 'en-US'));

array_map('unlink', glob("$dir/i18n/*"));
rmdir("$dir/i18n");
rmdir($dir);

check('the bundled list has 119 languages', count(Expansa\Translation\Languages::all()) === 119);

/**
 * Mistakes in the plural forms of translation files: a block or a list with another number of forms
 * than the language has, or a translation that lost a block of its string.
 *
 * @param string[] $files Translation files named by locale, e.g. `ru-RU.json`.
 * @return string[]
 */
function formMistakes(array $files): array
{
    $mistakes = [];
    foreach ($files as $file) {
        $locale   = basename($file, '.json');
        $language = array_find(Languages::all(), fn (array $language) => $language['locale'] === $locale)
            ?? array_find(Languages::all(), fn (array $language) => $language['iso_639_1'] === strtok($locale, '-'));
        $forms    = (int) ($language['nplurals'] ?? 2);

        foreach (json_decode((string) file_get_contents($file), true) ?: [] as $string => $translation) {
            $counts = is_array($translation) ? [count($translation)] : Forms::count($translation);
            if (! Forms::has($string) && ! is_array($translation)) {
                continue;
            }
            if (! is_array($translation) && count($counts) !== count(Forms::count($string))) {
                $mistakes[] = basename($file) . ": \"$string\" has " . count($counts) . ' blocks instead of ' . count(Forms::count($string));
            }
            foreach ($counts as $count) {
                if ($count !== $forms) {
                    $mistakes[] = basename($file) . ": \"$string\" has $count forms instead of $forms";
                }
            }
        }
    }

    return $mistakes;
}

$sample = sys_get_temp_dir() . '/ru-RU.json';
file_put_contents($sample, json_encode([
    ':count {file|files}'   => ':count {файл|файлов}',
    'Deleted {# file|# files}.' => ['Удалён # файл.', 'Удалено # файлов.'],
    '{# day|# days} left'   => 'Осталось',
    ':count {item|items}'   => ':count {товар|товара|товаров}',
]));
check('form mistakes are found: missing forms, a short list, a lost block', count(formMistakes([$sample])) === 3);
unlink($sample);

$files    = glob(EX_PATH . '{dashboard,plugins/*,themes/*}/i18n/*.json', GLOB_BRACE) ?: [];
$mistakes = formMistakes($files);
check('translation files have as many plural forms as their languages' . ($mistakes ? ":\n    " . implode("\n    ", $mistakes) : ''), $mistakes === []);

exit($failures > 0 ? 1 : 0);
