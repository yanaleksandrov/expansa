<?php

declare(strict_types=1);

use Expansa\Security\Sanitizer;
use Expansa\Security\Xss\Kses;

// run: php tests/Kses.php
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$failures = 0;

function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

$kses = new Kses();

// outputs of the original kses port with the current rules, the rewrite must keep them byte for byte
$golden = [
    ['plain text', 'plain text'],
    ['AT&T', 'AT&amp;T'],
    ['&amp; &lt; &#65; &#x41; &#X0041; &#xABC; &#0065; &nbsp; &foo', '&amp; &lt; &#65; &#x41; &#x41; &amp;#xABC; &#0065; &nbsp; &amp;foo'],
    ['a' . "\0" . 'b', 'ab'],
    ['&{alert(1)};', ''],
    ['& { x', ''],
    ['<b>bold</b>', '<b>bold</b>'],
    ['<B>Bold</B>', '<B>Bold</B>'],
    ['<p class="x">p</p>', '<p class="x">p</p>'],
    ['<script>alert(1)</script>', 'alert(1)'],
    ['<img src=x onerror=alert(1)>', '<img src="x">'],
    ['<a href="javascript:alert(1)">x</a>', '<a href="alert(1)">x</a>'],
    ['<a href="JaVaScRiPt:alert(1)">x</a>', '<a href="alert(1)">x</a>'],
    ['<a href=" javascript:alert(1)">x</a>', '<a href="alert(1)">x</a>'],
    ['<a href="java&#115;cript:alert(1)">x</a>', '<a href="alert(1)">x</a>'],
    ['<a href="javascript&#58;alert(1)">x</a>', '<a href="alert(1)">x</a>'],
    ['<a href="javascript&#x3A;alert(1)">', '<a href="alert(1)">'],
    ['<a href="javascript:javascript:alert(1)">', '<a href="alert(1)">'],
    ['<a href="http://example.com" target=_blank rel=\'noopener\'>ok</a>', '<a href="http://example.com" target="_blank" rel=\'noopener\'>ok</a>'],
    ['<a href="mailto:a@b.c">m</a>', '<a href="mailto:a@b.c">m</a>'],
    ['<a download>d</a>', '<a download>d</a>'],
    ['<a download="x">d</a>', '<a>d</a>'],
    ['<option selected value=1>', '<option selected value="1">'],
    ['<br/>', '<br>'],
    ['<br />', '<br>'],
    ['<hr / >', '<hr>'],
    ['<img src="a.png" alt=\'x\' width=10 height="20" />', '<img src="a.png" alt=\'x\' width="10" height="20" />'],
    ['<div style="background:url(javascript:alert(1))">', '<div>'],
    ['<iframe src="https://x.y">', '<iframe src="https://x.y">'],
    ['<!-- comment -->', ''],
    ['< b>', '&lt; b&gt;'],
    ['<>', '&lt;&gt;'],
    ['< >', '&lt; &gt;'],
    ['<<b>>', '&lt;<b>&gt;'],
    ['a < b > c', 'a &lt; b &gt; c'],
    ['<b', '<b>'],
    ['<a href="x', '<a>'],
    ['<a href=\'x', '<a>'],
    ['<a href=>', '<a>'],
    ['<a =x href="y">z</a>', '<a href="y">z</a>'],
    ['<a "q" href="y">', '<a href="y">'],
    ['<a href="x"y>', '<a>'],
    ['<a href
=
"x">', '<a href="x">'],
    ['<a	href=\'x\'	class=y>', '<a href=\'x\' class="y">'],
    ['<a/href="x">', '<a href="x">'],
    ['<a class =broken"x" href="y">', '<a href="y">'],
    ['<svg/onload=alert(1)>', ''],
    ['<IMG SRC=JaVaScRiPt:alert(\'XSS\')>', '<IMG>'],
    ['<a href="&#x6A;&#x61;&#x76;&#x61;script:alert(1)">', '<a href="alert(1)">'],
    ['<a href="jav&#x09;ascript:alert(1)">', '<a href="alert(1)">'],
    ['<table border=1 cellpadding="2"><tr><td colspan=2>c</td></tr></table>', '<table border="1" cellpadding="2"><tr><td colspan="2">c</td></tr></table>'],
    ['</div class="x">', '</div>'],
    ['</ b>', '</b>'],
    ['<p>Привет, <strong>мир</strong> — ёлка</p>', '<p>Привет, <strong>мир</strong> — ёлка</p>'],
    ['<a title="Note: hi">x</a>', '<a title="hi">x</a>'],
    ['<video src=v.mp4 controls autoplay muted>', '<video src="v.mp4" controls autoplay muted>'],
    ['<a href="ftp://x" rel=a class>', '<a href="ftp://x" rel="a" class>'],
    ['<td nowrap width=10%>', '<td nowrap width="10%">'],
    ['<a href=x class=a"b>', '<a href="x">'],
];

$differ = [];
foreach ($golden as [$input, $expected]) {
    if ($kses->apply($input) !== $expected) {
        $differ[] = $input;
    }
}
check('matches the original kses on ' . count($golden) . ' tricky inputs' . ($differ ? ': ' . implode(' | ', $differ) : ''), $differ === []);

// protocols
$links = [
    '<a href="javascript&colon;alert(1)">x</a>',
    '<a href="javascript&#0058;alert(1)">x</a>',
    '<a href="javascript&#x003A;alert(1)">x</a>',
    '<a href="JAVASCRIPT&colon;alert(1)">x</a>',
    '<a href="java&#x09;script&colon;alert(1)">x</a>',
    '<a href="javascript:javascript:alert(1)">x</a>',
    '<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>',
    '<a href="vbscript:msgbox(1)">x</a>',
];
check('every encoding of a disallowed protocol is removed', array_filter($links, fn ($html) => preg_match('/(java|vb)script|data:/i', $kses->apply($html))) === []);
check('&colon; is understood as a colon', $kses->apply('<a href="javascript&colon;alert(1)">x</a>') === '<a href="alert(1)">x</a>');
check('allowed protocols are kept and normalized', $kses->apply('<a href="HTTPS&colon;//example.com">x</a>') === '<a href="https://example.com">x</a>'
    && $kses->apply('<a href="mailto:me@example.com">x</a>') === '<a href="mailto:me@example.com">x</a>');
check('relative URLs are kept', $kses->apply('<a href="/page?a=1&amp;b=2#top">x</a>') === '<a href="/page?a=1&amp;b=2#top">x</a>');

// elements and attributes
check('disallowed elements are removed with their attributes, the text stays', $kses->apply('<script>alert(1)</script><style>x</style>') === 'alert(1)x');
check('event handlers and style are removed', $kses->apply('<img src="a.png" onerror="alert(1)" style="x" alt="A">') === '<img src="a.png" alt="A">');
check('closing tags lose attributes', $kses->apply('</div onclick="x">') === '</div>');
check('element case is kept, attribute names are lower cased', $kses->apply('<DIV CLASS="a">') === '<DIV class="a">');
check('unquoted values get quotes', $kses->apply('<td colspan=2 width=50%>') === '<td colspan="2" width="50%">');
check('XHTML slash is kept after attributes, as in the original', $kses->apply('<img src="a" />') === '<img src="a" />' && $kses->apply('<br />') === '<br>');
check('lone angle brackets are escaped', $kses->apply('1 < 2 > 0') === '1 &lt; 2 &gt; 0');
check('comments are removed', $kses->apply('a<!-- secret -->b') === 'ab');

// entities and encoding
check('invalid entities are disarmed', $kses->apply('AT&T &#xZZ; &unknown') === 'AT&amp;T &amp;#xZZ; &amp;unknown');
check('valid entities are kept, hex ones lose leading zeros', $kses->apply('&amp; &#169; &#x000A9; &copy;') === '&amp; &#169; &#xA9; &copy;');
check('Netscape JavaScript entities are removed', $kses->apply('a&{alert(1)};b') === 'ab');
check('NUL characters are removed', $kses->apply("<b>a\0b</b>") === '<b>ab</b>');
check('invalid UTF-8 gives an empty string', $kses->apply("ok\xFFok") === '' && $kses->apply("\xED\xA0\x80") === '');
check('plain text is returned as is', $kses->apply('Привет, мир') === 'Привет, мир');

// custom rules
$custom = new Kses([
    'a'      => ['href' => 1, 'title' => ['maxlen' => 5], 'download' => ['valueless' => 'y']],
    'input'  => ['size' => ['maxval' => 50, 'minval' => 1], 'name' => ['minlen' => 2]],
    'span'   => ['class' => ['content' => ['icon-%']]],
], ['https']);
check('custom protocols', $custom->apply('<a href="http://x">x</a>') === '<a href="//x">x</a>' && $custom->getAllowedProtocols() === ['https']);
check('maxlen and valueless checks', $custom->apply('<a title="short" download>') === '<a title="short" download>'
    && $custom->apply('<a title="too long" download="x">') === '<a>');
check('maxval, minval and minlen checks', $custom->apply('<input size=10 name=ab>') === '<input size="10" name="ab">'
    && $custom->apply('<input size=0 name=a>') === '<input size="0">'
    && $custom->apply('<input size=1000000 name=a>') === '<input>');
check('content check', $custom->apply('<span class="icon-home">') === '<span class="icon-home">' && $custom->apply('<span class="evil">') === '<span>');
check('elements outside a custom list are removed', $custom->apply('<b>x</b>') === 'x');

$html  = '<p><a href="https://x.y" class=link onclick="x()">a</a></p>';
$first = $kses->apply($html);
check('a cached tag gives the same result', $kses->apply($html) === $first && $first === '<p><a href="https://x.y" class="link">a</a></p>');
check('each filter caches with its own rules', $custom->apply($html) === '<a href="https://x.y">a</a>' && $kses->apply($html) === $first);

/**
 * Find what could run script in a browser: parse the HTML as a browser does, in a body and in a table row.
 */
function dangers(string $html): array
{
    $urls     = ['href', 'src', 'cite', 'poster', 'data', 'action', 'formaction', 'background', 'lowsrc', 'dynsrc', 'xlink:href', 'srcset'];
    $never    = ['style', 'srcdoc', 'formaction', 'action', 'background', 'dynsrc', 'lowsrc', 'xlink:href'];
    $implicit = ['html', 'head', 'body', 'table', 'tbody', 'tr'];
    $problems = [];

    foreach (['<body>', '<body><table><tbody><tr>'] as $context) {
        $document = Dom\HTMLDocument::createFromString('<!DOCTYPE html>' . $context . $html, LIBXML_NOERROR);

        foreach ($document->getElementsByTagName('*') as $element) {
            $tag = strtolower($element->localName);

            if (! in_array($tag, $implicit, true) && ($element->namespaceURI !== 'http://www.w3.org/1999/xhtml' || ! isset(Kses::ALLOWED_HTML[$tag]))) {
                $problems[] = "<$tag>";
            }

            foreach ($element->attributes as $attribute) {
                $name = strtolower($attribute->name);

                if (str_starts_with($name, 'on') || in_array($name, $never, true)) {
                    $problems[] = $tag . '[' . $name . ']';
                }

                // the URL parser strips controls and spaces around the URL, tabs and newlines inside it
                $url = str_replace(["\t", "\n", "\r"], '', trim($attribute->value, "\x00..\x20"));
                if (in_array($name, $urls, true) && preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $scheme)
                    && ! in_array(strtolower($scheme[1]), Kses::ALLOWED_PROTOCOLS, true)) {
                    $problems[] = $tag . '[' . $name . ']=' . strtolower($scheme[1]) . ':';
                }
            }
        }
    }

    return array_values(array_unique($problems));
}

// popular vectors of the OWASP XSS filter evasion cheat sheet and PortSwigger, one per line
$vectors = array_map(
    fn (string $line) => strtr($line, ['{TAB}' => "\t", '{LF}' => "\n", '{CR}' => "\r", '{NUL}' => "\0", '{FF}' => "\f"]),
    file(__DIR__ . '/fixtures/xss-vectors.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
);
$flagged = array_filter($vectors, fn (string $vector) => dangers($vector) !== []);
check('the XSS check itself recognizes ' . count($flagged) . ' of ' . count($vectors) . ' vectors as dangerous', count($flagged) >= 130);

$passed = [];
foreach ($vectors as $vector) {
    if ($problems = dangers($kses->apply($vector))) {
        $passed[] = $vector . ' => ' . implode(', ', $problems);
    }
}
check('no XSS vector survives the filter' . ($passed ? ': ' . implode(' | ', $passed) : ''), $passed === []);

$broken = new Kses(['a' => ['title' => ['maxlen' => 'many']]]);
try {
    $broken->apply('<a title="x">');
    $thrown = false;
} catch (InvalidArgumentException $e) {
    $thrown = $e->getMessage() === 'maxlen must be of type numeric, string given';
}
check('a rule with a wrong type throws', $thrown);

// attributes of every element and prefixes
check('class, title, lang, dir and role are allowed on every element', $kses->apply('<p class="lead" title="t" lang="en" dir="ltr" role="note">x</p>') === '<p class="lead" title="t" lang="en" dir="ltr" role="note">x</p>');
check('data-* and aria-* are allowed on every element', $kses->apply('<code data-lang="php" aria-label="Code" class="language-php">x</code>') === '<code data-lang="php" aria-label="Code" class="language-php">x</code>');
check('a bare prefix is not an attribute', $kses->apply('<b data- aria-=1 data-x=1>x</b>') === '<b data-x="1">x</b>');
check('prefixes do not allow event handlers or directives', $kses->apply('<b onclick="x" data-onclick="y" u-text="z" style="w">x</b>') === '<b data-onclick="y">x</b>');
check('the protocol check applies to data-* values', $kses->apply('<b data-url="javascript:alert(1)">x</b>') === '<b data-url="alert(1)">x</b>');

// framework directives: names with ":", "@" and "."
check('directives with ":", "@" and "." are removed by default', $kses->apply('<b v-on:click="x()" :href="y" @click="z" .prop="w" v-if="ok">b</b>') === '<b>b</b>');
$vue = new Kses(Kses::extend(['*' => ['v-*', ':*', '@*']]));
check('Vue directives pass once their prefixes are allowed', $vue->apply('<b v-on:click.prevent="x()" :href="y" @click="z" v-if="ok" onclick="w">b</b>')
    === '<b v-on:click.prevent="x()" :href="y" @click="z" v-if="ok">b</b>');
check('a bare ":" or "@" is not an attribute', $vue->apply('<b : @ :x="1">b</b>') === '<b :x="1">b</b>');

// new elements and attributes
check('HTML5 sections, details and time', $kses->apply('<article><header><h2>T</h2></header><details open><summary>S</summary>D</details><time datetime="2025-01-31">today</time></article>')
    === '<article><header><h2>T</h2></header><details open><summary>S</summary>D</details><time datetime="2025-01-31">today</time></article>');
check('picture, source and responsive images', $kses->apply('<picture><source srcset="a.avif" type="image/avif"><img src="a.jpg" srcset="a2.jpg 2x" sizes="50vw" loading="lazy" decoding="async"></picture>')
    === '<picture><source srcset="a.avif" type="image/avif"><img src="a.jpg" srcset="a2.jpg 2x" sizes="50vw" loading="lazy" decoding="async"></picture>');
check('video with a source', $kses->apply('<video controls playsinline><source src="v.mp4" type="video/mp4"></video>') === '<video controls playsinline><source src="v.mp4" type="video/mp4"></video>');
check('list and table attributes', $kses->apply('<ol start="3" reversed type="a"><li value="5">x</li></ol><table><colgroup span="2"><col span="1"></colgroup></table>')
    === '<ol start="3" reversed type="a"><li value="5">x</li></ol><table><colgroup span="2"><col span="1"></colgroup></table>');
check('iframe embeds keep allow, loading and referrerpolicy', $kses->apply('<iframe src="https://www.youtube.com/embed/x" allow="autoplay; fullscreen" loading="lazy" referrerpolicy="strict-origin" srcdoc="x"></iframe>')
    === '<iframe src="https://www.youtube.com/embed/x" allow="autoplay; fullscreen" loading="lazy" referrerpolicy="strict-origin"></iframe>');
check('default elements are in alphabetical order', array_keys(Kses::ALLOWED_HTML) === (function () {
    $keys = array_keys(Kses::ALLOWED_HTML);
    sort($keys, SORT_STRING);

    return $keys;
})());

$listed = new Kses(['a' => ['href', 'title' => ['maxlen' => 3], 'rel' => 1], 'b' => ['class']]);
check('attributes may be listed, mixed with checks and the `=> 1` form', $listed->apply('<a href="/x" title="abc" rel="n" id="i"><b class="c">x</b></a>') === '<a href="/x" title="abc" rel="n"><b class="c">x</b></a>'
    && $listed->apply('<a title="long">x</a>') === '<a>x</a>');
check('extend() accepts listed attributes', new Kses(Kses::extend(['b' => ['data-x', 'onclick' => ['maxlen' => 0]]]))->apply('<b id="i" data-x="1">x</b>') === '<b data-x="1">x</b>');

// Youla directives run JavaScript, so they are never allowed by default, only as extra rules of a trusted page
check('Youla directives are removed by default', $kses->apply('<select u-select="fetch(1)" class="a"><option value="1" selected>1</option></select>') === '<select class="a"><option value="1" selected>1</option></select>');
$extended = Kses::extend(['select' => ['u-select' => 1], 'x-box' => ['id' => 1]]);
check('extend() adds attributes and elements to the default rules', $extended['select'] === ['u-select' => 1] && $extended['x-box'] === ['id' => 1] && $extended['p'] === [] && isset($extended['*']));
check('extra rules of markup() are added to the default ones', Sanitizer::markup('<select u-select="{}" class="a" onchange="x"><option>1</option></select>', ['select' => ['u-select' => 1]])
    === '<select u-select="{}" class="a"><option>1</option></select>');
$filter  = new Kses();
$rewrite = Closure::bind(fn () => $this->allowedHtml = ['script' => []], $filter, Kses::class);
try {
    $rewrite();
    $locked = false;
} catch (LogicException) {
    $locked = true;
}
check('rules can not be changed after the filter is created, even from inside', $locked && count($filter->allowedHtml) === count(Kses::ALLOWED_HTML) - 1);

$directive = '<select u-select="fetch(2)">';
check('the shared cache never gives the result of other rules', new Kses(Kses::extend(['select' => ['u-select']]))->apply($directive) === $directive
    && new Kses()->apply($directive) === '<select>'
    && new Kses(Kses::ALLOWED_HTML, ['https'])->apply('<a href="http://x">') === '<a href="//x">');
check('markup() without extra rules keeps removing directives', Sanitizer::markup('<select u-select="{}">') === '<select>');

check('Sanitizer::markup() filters with the default rules', Sanitizer::markup('<b onclick="x">b</b><script>') === '<b>b</b>' && Sanitizer::markup(null) === '' && Sanitizer::markup(42) === '42');

exit($failures > 0 ? 1 : 0);
