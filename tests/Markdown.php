<?php

declare(strict_types=1);

use Expansa\Translation\Markdown;

// run: php tests/Markdown.php
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$failures = 0;

function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

check('headings of every level', Markdown::render('# Заголовок') === '<h1>Заголовок</h1>' && Markdown::render('###### Заголовок') === '<h6>Заголовок</h6>');
check('bold and italic inside a heading', Markdown::render('## Текст с **жирным** и *курсивом*') === '<h2>Текст с <strong>жирным</strong> и <em>курсивом</em></h2>');
check('quote', Markdown::render('> Это цитата.') === '<blockquote>Это цитата.</blockquote>');
check('bold and italic in a sentence', Markdown::render('Это **важно** и *курсив*') === 'Это <strong>важно</strong> и <em>курсив</em>');
check('link', Markdown::render('Ссылка на [Google](https://www.google.com).') === 'Ссылка на <a href="https://www.google.com">Google</a>.');
check('image', Markdown::render('![Логотип](https://example.com/logo.png)') === '<img src="https://example.com/logo.png" alt="Логотип"/>');
check('plain text stays as is', Markdown::render('Обычный текст') === 'Обычный текст');

exit($failures > 0 ? 1 : 0);
