<?php

declare(strict_types=1);

use Expansa\Translation\Translator;

// run: php tests/Markdown.php
require_once __DIR__ . '/bootstrap.php';

$translator = new Translator();
$render     = fn (string $text): string => $translator->translate($text);

check('headings of every level', $render('# Заголовок') === '<h1>Заголовок</h1>' && $render('###### Заголовок') === '<h6>Заголовок</h6>');
check('bold and italic inside a heading', $render('## Текст с **жирным** и *курсивом*') === '<h2>Текст с <strong>жирным</strong> и <em>курсивом</em></h2>');
check('quote', $render('> Это цитата.') === '<blockquote>Это цитата.</blockquote>');
check('bold and italic in a sentence', $render('Это **важно** и *курсив*') === 'Это <strong>важно</strong> и <em>курсив</em>');
check('link', $render('Ссылка на [Google](https://www.google.com).') === 'Ссылка на <a href="https://www.google.com">Google</a>.');
check('image', $render('![Логотип](https://example.com/logo.png)') === '<img src="https://example.com/logo.png" alt="Логотип"/>');
check('plain text stays as is', $render('Обычный текст') === 'Обычный текст');

exit($failures > 0 ? 1 : 0);
