<?php

declare(strict_types=1);

use Expansa\Translation\Internal\PluralRule;
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

// plural forms, English rule without a configured language list
$en = [0 => '0 files', 1 => '1 file', 2 => '2 files', 21 => '21 files'];
check('English plural forms without languages', array_all($en, fn ($expected, $n) => $translator->translatePlural(':count file|:count files', $n) === $expected));
check('a negative count uses its absolute value', $translator->translatePlural(':count file|:count files', -1) === '-1 file');
check(':count and other placeholders in a form', $translator->translatePlural('One file in :folder|:count files in :folder', 3, 'Docs') === '3 files in Docs'
    && $translator->translatePlural('One file in :folder|:count files in :folder', 1, 'Docs') === 'One file in Docs');
check('a single form is used for every count', $translator->translatePlural(':count items', 5) === '5 items');
check('an attribute plural', $translator->translateAttributePlural('"one"|"many"', 2) === '&quot;many&quot;');

// the rule of the request locale, en-US here, taken from the configured languages
$russian = new Manager();
$russian->configure(routes: [], pattern: '%s', languages: fn () => [[
    'name' => 'Test', 'native' => 'Test', 'locale' => 'en-US', 'iso_639_1' => 'en', 'country' => 'us',
    'plural' => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
]]);
$ru = [1 => '1 файл', 2 => '2 файла', 5 => '5 файлов', 11 => '11 файлов', 21 => '21 файл', 22 => '22 файла', 111 => '111 файлов'];
check('the plural rule comes from the language list', array_all($ru, fn ($expected, $n) => $russian->translatePlural(':count файл|:count файла|:count файлов', $n) === $expected));
check('a missing form falls back to the last one', $russian->translatePlural(':count файл|:count файлов', 3) === '3 файлов');

$broken = new Manager();
$broken->configure(routes: [], pattern: '%s', languages: fn () => [['locale' => 'en-US', 'plural' => 'system("ls")']]);
check('a broken plural rule falls back to English', $broken->translatePlural('one|many', 1) === 'one' && $broken->translatePlural('one|many', 2) === 'many');

// every rule of the bundled languages compiles and returns a form within nplurals
$languages = require EX_PATH . 'dashboard/data/languages.php';
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
check('no configured languages give an empty list', $translator->languageOptions() === [] && $translator->language('ru') === []);

exit($failures > 0 ? 1 : 0);
