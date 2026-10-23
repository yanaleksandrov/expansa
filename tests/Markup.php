<?php

declare(strict_types=1);

use Expansa\Security\Markup;
use Expansa\Security\Sanitizer;

// run: php tests/Markup.php
require_once __DIR__ . '/bootstrap.php';

$markup = new Markup();

// the cases of the former Kses filter as behavior, see "Spec" in documentation/Markup.md
$cases = [
    ['plain text', 'plain text'],
    ['AT&T', 'AT&amp;T'],
    ['&amp; &lt; &#65; &#x41; &copy; &nbsp; &foo', '&amp; &lt; A A © &nbsp; &amp;foo'],
    ['a' . "\0" . 'b', 'ab'],
    ['&{alert(1)};', '&amp;{alert(1)};'],
    ['<b>bold</b>', '<b>bold</b>'],
    ['<B>Bold</B>', '<b>Bold</b>'],
    ['<p class="x">p</p>', '<p class="x">p</p>'],
    ['<script>alert(1)</script>', ''],
    ['<img src=x onerror=alert(1)>', '<img src="x">'],
    ['<a href="javascript:alert(1)">x</a>', '<a>x</a>'],
    ['<a href="JaVaScRiPt:alert(1)">x</a>', '<a>x</a>'],
    ['<a href=" javascript:alert(1)">x</a>', '<a>x</a>'],
    ['<a href="java&#115;cript:alert(1)">x</a>', '<a>x</a>'],
    ['<a href="javascript&#58;alert(1)">x</a>', '<a>x</a>'],
    ['<a href="http://example.com" target=_blank rel=\'noopener\'>ok</a>', '<a href="http://example.com" target="_blank" rel="noopener">ok</a>'],
    ['<a href="mailto:a@b.c">m</a>', '<a href="mailto:a@b.c">m</a>'],
    ['<a download>d</a>', '<a download="">d</a>'],
    ['<select><option selected value=1>1</select>', '<select><option selected="" value="1">1</option></select>'],
    ['<br/><br /><hr / >', '<br><br><hr>'],
    ['<img src="a.png" alt=\'x\' width=10 height="20" />', '<img src="a.png" alt="x" width="10" height="20">'],
    ['<div style="background:url(javascript:alert(1))">', '<div></div>'],
    ['<iframe src="https://x.y">', '<iframe src="https://x.y"></iframe>'],
    ['<!-- comment -->', ''],
    ['< b>', '&lt; b&gt;'],
    ['<>', '&lt;&gt;'],
    ['<<b>>', '&lt;<b>&gt;</b>'],
    ['a < b > c', 'a &lt; b &gt; c'],
    ['<b', ''],
    ['<a href="x', ''],
    ['<a =x href="y">z</a>', '<a href="y">z</a>'],
    ['<a href' . "\n=\n" . '"x">', '<a href="x"></a>'],
    ['<a/href="x">', '<a href="x"></a>'],
    ['<svg/onload=alert(1)>', ''],
    ['<IMG SRC=JaVaScRiPt:alert(\'XSS\')>', '<img>'],
    ['<a href="&#x6A;&#x61;&#x76;&#x61;script:alert(1)">', '<a></a>'],
    ['<a href="jav&#x09;ascript:alert(1)">', '<a></a>'],
    ['<table><tr><td colspan=2 width=10%>c</td></tr></table>', '<table><tbody><tr><td colspan="2">c</td></tr></tbody></table>'],
    ['<td>c</td>', 'c'],
    ['<div></div class="x">', '<div></div>'],
    ['<p>Привет, <strong>мир</strong> — ёлка</p>', '<p>Привет, <strong>мир</strong> — ёлка</p>'],
    ['<a title="Note: hi">x</a>', '<a title="Note: hi">x</a>'],
    ['<video src=v.mp4 controls autoplay muted>', '<video src="v.mp4" controls="" autoplay="" muted=""></video>'],
    ['<font color=red>red</font><center>c</center>', 'redc'],
    ['<x-card id=1><b>c</b></x-card>', '<b>c</b>'],
    ['<style>x</style><template><b>t</b></template><textarea>t</textarea>ok', 'ok'],
    ['<p>a<p>b', '<p>a</p><p>b</p>'],
    ["a\r\nb<b>c</b>", "a\nb<b>c</b>"],
    ['<a href="&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058alert(1)">x</a>', '<a>x</a>'],
    ['<a title="&copy=&copy;&amp &notit; &#x26;lt;">x</a>', '<a title="&amp;copy=©&amp; &amp;notit; &amp;lt;">x</a>'],
    ['<a "q>" href=y>', '<a>" href=y&gt;</a>'],
    ['<a href="x>', ''],
    ['<svg/>t<math/>u', 'tu'],
    ['<ul><li>a<li>b</ul>', '<ul><li>a</li><li>b</li></ul>'],
    ['<b><i>x</b>y</i>', '<b><i>x</i></b><i>y</i>'],
];

$differ = [];
foreach ($cases as [$input, $expected]) {
    if (($output = $markup->sanitize($input)) !== $expected) {
        $differ[] = $input . ' => ' . $output;
    }
}
check('behavior on ' . count($cases) . ' tricky inputs' . ($differ ? ': ' . implode(' | ', $differ) : ''), $differ === []);

// protocols
$links = [
    '<a href="javascript&colon;alert(1)">x</a>',
    '<a href="javascript&#0058;alert(1)">x</a>',
    '<a href="javascript&#x003A;alert(1)">x</a>',
    '<a href="JAVASCRIPT&colon;alert(1)">x</a>',
    '<a href="java&#x09;script&colon;alert(1)">x</a>',
    '<a href="&#x0E;javascript:alert(1)">x</a>',
    '<a href="javascript:javascript:alert(1)">x</a>',
    '<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>',
    '<a href="vbscript:msgbox(1)">x</a>',
];
check('every encoding of a disallowed protocol removes the attribute', array_filter($links, fn ($html) => $markup->sanitize($html) !== '<a>x</a>') === []);
check('allowed protocols are kept', $markup->sanitize('<a href="HTTPS&colon;//example.com">x</a>') === '<a href="HTTPS://example.com">x</a>'
    && $markup->sanitize('<a href="mailto:me@example.com">x</a>') === '<a href="mailto:me@example.com">x</a>');
check('relative URLs are kept', $markup->sanitize('<a href="/page?a=1&amp;b=2#top">x</a><a href="page:1.html">y</a>') === '<a href="/page?a=1&amp;b=2#top">x</a><a>y</a>'
    && $markup->sanitize('<a href="docs/a:b">x</a>') === '<a href="docs/a:b">x</a>');
check('every srcset candidate is checked', $markup->sanitize('<img srcset="a.png 1x, javascript:alert(1) 2x">') === '<img>'
    && $markup->sanitize('<img srcset="/a.png 1x, javascript:alert(1) 2x">') === '<img>'
    && $markup->sanitize('<img srcset="a.png 1x, https://x/b.png 2x">') === '<img srcset="a.png 1x, https://x/b.png 2x">');
check('URL attributes are checked whatever their rule says', new Markup(['a' => ['href' => 'text'], 'b' => ['data-*']])->sanitize('<a href="javascript:x">a</a><b data-x="javascript:y">b</b>')
    === '<a>a</a><b data-x="javascript:y">b</b>');

// elements and attributes
check('elements with code are removed with their content', $markup->sanitize('<script>alert(1)</script><style>x</style><noscript>n</noscript>t') === 't');
check('event handlers and style are removed', $markup->sanitize('<img src="a.png" onerror="alert(1)" style="x" alt="A">') === '<img src="a.png" alt="A">');
check('element and attribute names are lower cased', $markup->sanitize('<DIV CLASS="a">x</DIV>') === '<div class="a">x</div>');
check('checks of values: number and a list', $markup->sanitize('<td colspan="x">a</td>') === 'a'
    && $markup->sanitize('<ol start="-2" type="A" reversed><li value="3">x</li></ol>') === '<ol start="-2" type="A" reversed=""><li value="3">x</li></ol>'
    && $markup->sanitize('<a target="_BLANK" dir="sideways">x</a>') === '<a target="_BLANK">x</a>'
    && $markup->sanitize('<img width="10px">') === '<img>');
check('id and name are not allowed', $markup->sanitize('<h2 id="x" name="y">t</h2>') === '<h2>t</h2>');
check('comments, CDATA and processing instructions are removed', $markup->sanitize('a<!-- secret --><![CDATA[c]]><?php x ?></ b>b') === 'ab');
check('the content of an allowed iframe is removed', $markup->sanitize('<iframe src="/e"><b>x</b></iframe>') === '<iframe src="/e"></iframe>');

// entities and encoding
check('invalid entities are disarmed', $markup->sanitize('AT&T &#xZZ; &unknown') === 'AT&amp;T &amp;#xZZ; &amp;unknown');
check('NUL characters are removed', $markup->sanitize("<b>a\0b</b>") === '<b>ab</b>');
check('invalid UTF-8 gives an empty string', $markup->sanitize("ok\xFFok") === '' && $markup->sanitize("<b>\xED\xA0\x80</b>") === '');
check('plain text is returned as is', $markup->sanitize('Привет, мир') === 'Привет, мир');

// custom rules
$custom = new Markup([
    'a'    => ['href' => 'url', 'title' => 'text', 'rel' => ['nofollow', 'noopener']],
    'td'   => ['colspan' => 'number'],
    'span' => ['class'],
], ['https']);
check('custom protocols', $custom->sanitize('<a href="http://x">x</a>') === '<a>x</a>' && $custom->sanitize('<a href="https://x">x</a>') === '<a href="https://x">x</a>'
    && $custom->protocols === ['https']);
check('custom checks', $custom->sanitize('<a rel="nofollow" title="t">x</a><a rel="me">y</a>') === '<a rel="nofollow" title="t">x</a><a>y</a>');
check('a listed attribute takes any value', $custom->sanitize('<span class="a b">x</span>') === '<span class="a b">x</span>');
check('elements outside a custom list are unwrapped', $custom->sanitize('<b>x</b><p>y</p>') === 'xy');
check('each filter keeps its own rules', $custom->sanitize('<p><a href="https://x.y" class=link>a</a></p>') === '<a href="https://x.y">a</a>'
    && $markup->sanitize('<p><a href="https://x.y" class=link>a</a></p>') === '<p><a href="https://x.y" class="link">a</a></p>');

// rules that would let script through are rejected
check('a bare `*` attribute is rejected', throws(fn () => new Markup(['b' => ['*']]), InvalidArgumentException::class)
    && throws(fn () => new Markup(Markup::extend(['*' => ['*']])), InvalidArgumentException::class));
check('forbidden elements and attributes are rejected', throws(fn () => new Markup(['script' => []]), InvalidArgumentException::class)
    && throws(fn () => new Markup(['b' => ['onclick']]), InvalidArgumentException::class)
    && throws(fn () => new Markup(['b' => ['style' => 'text']]), InvalidArgumentException::class)
    && throws(fn () => new Markup(['iframe' => ['srcdoc']]), InvalidArgumentException::class));
check('an unknown check is rejected', throws(fn () => new Markup(['a' => ['title' => 'maxlen']]), InvalidArgumentException::class));

// attributes of every element and prefixes
check('class, title, lang, dir and role are allowed on every element', $markup->sanitize('<p class="lead" title="t" lang="en" dir="ltr" role="note">x</p>') === '<p class="lead" title="t" lang="en" dir="ltr" role="note">x</p>');
check('data-* and aria-* are allowed on every element', $markup->sanitize('<code data-lang="php" aria-label="Code" class="language-php">x</code>') === '<code data-lang="php" aria-label="Code" class="language-php">x</code>');
check('a bare prefix is not an attribute', $markup->sanitize('<b data- aria-=1 data-x=1>x</b>') === '<b data-x="1">x</b>');
check('prefixes do not allow event handlers or directives', $markup->sanitize('<b onclick="x" data-onclick="y" u-text="z" style="w">x</b>') === '<b data-onclick="y">x</b>'
    && new Markup(['b' => ['o*']])->sanitize('<b onclick="x" ok="1">b</b>') === '<b ok="1">b</b>');

// framework directives: names with ":", "@" and "."
check('directives with ":", "@" and "." are removed by default', $markup->sanitize('<b v-on:click="x()" :href="y" @click="z" .prop="w" v-if="ok">b</b>') === '<b>b</b>');
$vue = new Markup(Markup::extend(['*' => ['v-*', ':*', '@*']]));
check('Vue directives pass once their prefixes are allowed', $vue->sanitize('<b v-on:click.prevent="x()" :href="y" @click="z" v-if="ok" onclick="w">b</b>')
    === '<b v-on:click.prevent="x()" :href="y" @click="z" v-if="ok">b</b>');

// SVG and MathML
check('SVG and MathML are removed with their content by default', $markup->sanitize('<svg><a href="/x">a</a></svg><math><mi>x</mi></math>t') === 't');
$svg = new Markup(Markup::extend(['svg' => ['viewbox'], 'path' => ['d']]));
check('SVG passes once its elements are allowed', $svg->sanitize('<svg viewBox="0 0 1 1" onload="x"><path d="M0 0"></path><script>x</script></svg>')
    === '<svg viewbox="0 0 1 1"><path d="M0 0"></path></svg>');

// modern elements
check('HTML5 sections, details and time', $markup->sanitize('<article><header><h2>T</h2></header><details open><summary>S</summary>D</details><time datetime="2025-01-31">today</time></article>')
    === '<article><header><h2>T</h2></header><details open=""><summary>S</summary>D</details><time datetime="2025-01-31">today</time></article>');
check('picture, source and responsive images', $markup->sanitize('<picture><source srcset="a.avif" type="image/avif"><img src="a.jpg" srcset="a2.jpg 2x" sizes="50vw" loading="lazy" decoding="async"></picture>')
    === '<picture><source srcset="a.avif" type="image/avif"><img src="a.jpg" srcset="a2.jpg 2x" sizes="50vw" loading="lazy" decoding="async"></picture>');
check('iframe embeds keep allow, loading and referrerpolicy', $markup->sanitize('<iframe src="https://www.youtube.com/embed/x" allow="autoplay; fullscreen" loading="lazy" referrerpolicy="strict-origin" srcdoc="x"></iframe>')
    === '<iframe src="https://www.youtube.com/embed/x" allow="autoplay; fullscreen" loading="lazy" referrerpolicy="strict-origin"></iframe>');
check('default elements are in alphabetical order', array_keys(Markup::RULES) === (function () {
    $keys = array_keys(Markup::RULES);
    sort($keys, SORT_STRING);

    return $keys;
})());

// Youla directives run JavaScript, so they are never allowed by default, only as extra rules of a trusted page
check('Youla directives are removed by default', $markup->sanitize('<select u-select="fetch(1)" class="a"><option value="1" selected>1</option></select>') === '<select class="a"><option value="1" selected="">1</option></select>');
$extended = Markup::extend(['select' => ['u-select'], 'x-box' => ['id' => 'text']]);
check('extend() adds attributes and elements to the default rules', in_array('u-select', $extended['select'], true) && $extended['x-box'] === ['id' => 'text'] && $extended['p'] === [] && isset($extended['*']));
check('extra rules of markup() are added to the default ones', Sanitizer::markup('<select u-select="{}" class="a" onchange="x"><option>1</option></select>', ['select' => ['u-select']])
    === '<select u-select="{}" class="a"><option>1</option></select>');
check('markup() without extra rules keeps removing directives', Sanitizer::markup('<select u-select="{}"></select>') === '<select></select>');
check('Sanitizer::markup() filters with the default rules', Sanitizer::markup('<b onclick="x">b</b><script>') === '<b>b</b>' && Sanitizer::markup(null) === '' && Sanitizer::markup(42) === '42');

$filter  = new Markup();
$rewrite = Closure::bind(fn () => $this->rules = ['script' => []], $filter, Markup::class);
check('rules can not be changed after the filter is created, even from inside', throws($rewrite, Error::class) && $filter->rules === Markup::RULES);

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

            if (! in_array($tag, $implicit, true) && ($element->namespaceURI !== 'http://www.w3.org/1999/xhtml' || ! isset(Markup::RULES[$tag]))) {
                $problems[] = "<$tag>";
            }

            foreach ($element->attributes as $attribute) {
                $name = strtolower($attribute->name);

                if (str_starts_with($name, 'on') || in_array($name, $never, true)) {
                    $problems[] = $tag . '[' . $name . ']';
                }

                // the URL parser strips controls and spaces around the URL, tabs and newlines inside it
                foreach ($name === 'srcset' ? explode(',', $attribute->value) : [$attribute->value] as $url) {
                    $url = str_replace(["\t", "\n", "\r"], '', trim($url, "\x00..\x20"));
                    if (in_array($name, $urls, true) && preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $scheme)
                        && ! in_array(strtolower($scheme[1]), Markup::PROTOCOLS, true)) {
                        $problems[] = $tag . '[' . $name . ']=' . strtolower($scheme[1]) . ':';
                    }
                }
            }
        }
    }

    return array_values(array_unique($problems));
}

// every vector of the OWASP XSS filter evasion cheat sheet, PortSwigger ones and mutation XSS, one per line
$vectors = array_map(
    fn (string $line) => strtr($line, ['{TAB}' => "\t", '{LF}' => "\n", '{CR}' => "\r", '{NUL}' => "\0", '{FF}' => "\f"]),
    file(__DIR__ . '/fixtures/xss-vectors.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
);
$flagged = array_filter($vectors, fn (string $vector) => dangers($vector) !== []);
check('the XSS check itself recognizes ' . count($flagged) . ' of ' . count($vectors) . ' vectors as dangerous', count($flagged) >= 200);

$passed  = [];
$unfixed = [];
foreach ($vectors as $vector) {
    $output = $markup->sanitize($vector);
    if ($problems = dangers($output)) {
        $passed[] = $vector . ' => ' . implode(', ', $problems);
    }
    // a browser parses the result into the same tree, so filtering it again changes nothing
    if ($markup->sanitize($output) !== $output) {
        $unfixed[] = $vector;
    }
}
check('no XSS vector survives the filter' . ($passed ? ': ' . implode(' | ', $passed) : ''), $passed === []);
check('the result of every vector is stable when parsed again' . ($unfixed ? ': ' . implode(' | ', $unfixed) : ''), $unfixed === []);

exit($failures > 0 ? 1 : 0);
