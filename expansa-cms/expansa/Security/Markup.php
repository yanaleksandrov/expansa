<?php

declare(strict_types=1);

namespace Expansa\Security;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\XPath;
use InvalidArgumentException;

/**
 * HTML filter against XSS: keeps only the allowed elements, attributes and URL protocols.
 *
 * The fragment is parsed by the HTML5 parser the way a browser parses it, the tree is filtered by the rules
 * and serialized back, so a browser builds the same tree from the result. The behavior is specified
 * in documentation/Markup.md.
 *
 * @package Expansa\Security
 */
final class Markup
{
    /**
     * URL protocols allowed in links and sources.
     */
    public const array PROTOCOLS = ['ftp', 'http', 'https', 'irc', 'mailto', 'sftp', 'sms', 'tel', 'webcal'];

    /**
     * Allowed elements and their attributes with checks, `*` holds the attributes of every element.
     */
    public const array RULES = [
        '*'          => [
            'aria-*' => 'text',
            'class'  => 'text',
            'data-*' => 'text',
            'dir'    => ['auto', 'ltr', 'rtl'],
            'lang'   => 'text',
            'role'   => 'text',
            'title'  => 'text',
        ],
        'a'          => [
            'download' => 'text',
            'href'     => 'url',
            'hreflang' => 'text',
            'rel'      => 'text',
            'target'   => ['_blank', '_parent', '_self', '_top'],
            'type'     => 'text',
        ],
        'abbr'       => [],
        'address'    => [],
        'article'    => [],
        'aside'      => [],
        'audio'      => [
            'autoplay' => 'text',
            'controls' => 'text',
            'loop'     => 'text',
            'muted'    => 'text',
            'preload'  => ['', 'auto', 'metadata', 'none'],
            'src'      => 'url',
        ],
        'b'          => [],
        'bdi'        => [],
        'bdo'        => [],
        'blockquote' => ['cite' => 'url'],
        'br'         => [],
        'button'     => [
            'disabled' => 'text',
            'type'     => ['button'],
            'value'    => 'text',
        ],
        'caption'    => [],
        'cite'       => [],
        'code'       => [],
        'col'        => ['span' => 'number'],
        'colgroup'   => ['span' => 'number'],
        'data'       => ['value' => 'text'],
        'dd'         => [],
        'del'        => [
            'cite'     => 'url',
            'datetime' => 'text',
        ],
        'details'    => [
            'name' => 'text',
            'open' => 'text',
        ],
        'dfn'        => [],
        'div'        => [],
        'dl'         => [],
        'dt'         => [],
        'em'         => [],
        'fieldset'   => ['disabled' => 'text'],
        'figcaption' => [],
        'figure'     => [],
        'footer'     => [],
        'h1'         => [],
        'h2'         => [],
        'h3'         => [],
        'h4'         => [],
        'h5'         => [],
        'h6'         => [],
        'header'     => [],
        'hgroup'     => [],
        'hr'         => [],
        'i'          => [],
        'iframe'     => [
            'allow'           => 'text',
            'allowfullscreen' => 'text',
            'height'          => 'number',
            'loading'         => ['eager', 'lazy'],
            'referrerpolicy'  => 'text',
            'sandbox'         => 'text',
            'src'             => 'url',
            'width'           => 'number',
        ],
        'img'        => [
            'alt'      => 'text',
            'decoding' => ['async', 'auto', 'sync'],
            'height'   => 'number',
            'loading'  => ['eager', 'lazy'],
            'sizes'    => 'text',
            'src'      => 'url',
            'srcset'   => 'url',
            'width'    => 'number',
        ],
        'ins'        => [
            'cite'     => 'url',
            'datetime' => 'text',
        ],
        'kbd'        => [],
        'label'      => ['for' => 'text'],
        'legend'     => [],
        'li'         => ['value' => 'number'],
        'main'       => [],
        'mark'       => [],
        'meter'      => [
            'high'    => 'number',
            'low'     => 'number',
            'max'     => 'number',
            'min'     => 'number',
            'optimum' => 'number',
            'value'   => 'number',
        ],
        'nav'        => [],
        'ol'         => [
            'reversed' => 'text',
            'start'    => 'number',
            'type'     => ['1', 'a', 'A', 'i', 'I'],
        ],
        'optgroup'   => [
            'disabled' => 'text',
            'label'    => 'text',
        ],
        'option'     => [
            'disabled' => 'text',
            'label'    => 'text',
            'selected' => 'text',
            'value'    => 'text',
        ],
        'p'          => [],
        'picture'    => [],
        'pre'        => [],
        'progress'   => [
            'max'   => 'number',
            'value' => 'number',
        ],
        'q'          => ['cite' => 'url'],
        'rp'         => [],
        'rt'         => [],
        'ruby'       => [],
        's'          => [],
        'samp'       => [],
        'section'    => [],
        'select'     => [
            'disabled' => 'text',
            'multiple' => 'text',
            'size'     => 'number',
        ],
        'small'      => [],
        'source'     => [
            'height' => 'number',
            'media'  => 'text',
            'sizes'  => 'text',
            'src'    => 'url',
            'srcset' => 'url',
            'type'   => 'text',
            'width'  => 'number',
        ],
        'span'       => [],
        'strong'     => [],
        'sub'        => [],
        'summary'    => [],
        'sup'        => [],
        'table'      => [],
        'tbody'      => [],
        'td'         => [
            'colspan' => 'number',
            'headers' => 'text',
            'rowspan' => 'number',
        ],
        'tfoot'      => [],
        'th'         => [
            'abbr'    => 'text',
            'colspan' => 'number',
            'headers' => 'text',
            'rowspan' => 'number',
            'scope'   => ['col', 'colgroup', 'row', 'rowgroup'],
        ],
        'thead'      => [],
        'time'       => ['datetime' => 'text'],
        'tr'         => [],
        'track'      => [
            'default' => 'text',
            'kind'    => ['captions', 'chapters', 'descriptions', 'metadata', 'subtitles'],
            'label'   => 'text',
            'src'     => 'url',
            'srclang' => 'text',
        ],
        'u'          => [],
        'ul'         => [],
        'var'        => [],
        'video'      => [
            'autoplay'    => 'text',
            'controls'    => 'text',
            'height'      => 'number',
            'loop'        => 'text',
            'muted'       => 'text',
            'playsinline' => 'text',
            'poster'      => 'url',
            'preload'     => ['', 'auto', 'metadata', 'none'],
            'src'         => 'url',
            'width'       => 'number',
        ],
        'wbr'        => [],
    ];

    /**
     * Elements that can not be allowed: their content is code or raw text,
     * or they change the page outside the fragment.
     */
    private const array FORBIDDEN = [
        'base'      => true,
        'link'      => true,
        'meta'      => true,
        'noembed'   => true,
        'noframes'  => true,
        'noscript'  => true,
        'plaintext' => true,
        'script'    => true,
        'style'     => true,
        'template'  => true,
        'xmp'       => true,
    ];

    /**
     * Elements removed with their content when they are not allowed, the others are replaced by their content.
     */
    private const array DROPPED = self::FORBIDDEN + [
        'applet'   => true,
        'embed'    => true,
        'frame'    => true,
        'frameset' => true,
        'head'     => true,
        'iframe'   => true,
        'math'     => true,
        'object'   => true,
        'param'    => true,
        'svg'      => true,
        'textarea' => true,
        'title'    => true,
    ];

    /**
     * Attributes that can not be allowed, besides `on*` event handlers.
     */
    private const array FORBIDDEN_ATTRIBUTES = [
        'formaction' => true,
        'srcdoc'     => true,
        'style'      => true,
    ];

    /**
     * Attributes holding a URL, checked as `url` whatever their rule says.
     */
    private const array URL_ATTRIBUTES = [
        'action'      => true,
        'background'  => true,
        'cite'        => true,
        'codebase'    => true,
        'data'        => true,
        'dynsrc'      => true,
        'href'        => true,
        'imagesrcset' => true,
        'longdesc'    => true,
        'lowsrc'      => true,
        'manifest'    => true,
        'poster'      => true,
        'src'         => true,
        'srcset'      => true,
        'xlink:href'  => true,
    ];

    private const string HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';

    /**
     * Context element of the parsed fragments, shared by all filters.
     */
    private static ?Element $body = null;

    /**
     * Attributes of each element with their compiled checks: `true` for any value, `url`, `number`
     * or a set of values; `*` holds the attributes of every element.
     *
     * @var array<string, array<string, true|string|array<string, true>>>
     */
    private readonly array $attributes;

    /**
     * Attribute prefixes of each element with their checks, the element's own before the ones of `*`.
     *
     * @var array<string, array<string, true|string|array<string, true>>>
     */
    private readonly array $prefixes;

    /**
     * Allowed protocols as keys.
     *
     * @var array<string, true>
     */
    private readonly array $schemes;

    /**
     * Compile the rules once, the filter is reused for any number of fragments.
     *
     * @throws InvalidArgumentException If the rules allow a forbidden element or attribute, every attribute
     *                                  or have an unknown check.
     */
    public function __construct(

        /**
         * Allowed elements and their attributes, in the format of RULES.
         *
         * @var array<string, array<int|string, string|array<int, string>>>
         */
        public readonly array $rules = self::RULES,

        /**
         * Allowed URL protocols, lower cased.
         *
         * @var string[]
         */
        public readonly array $protocols = self::PROTOCOLS,
    ) {
        $attributes = [];
        $prefixes   = [];

        foreach ($rules as $element => $list) {
            if (isset(self::FORBIDDEN[$element])) {
                throw new InvalidArgumentException("The <$element> element can not be allowed.");
            }

            $attributes[$element] = [];
            foreach ($list as $key => $value) {
                [$name, $check] = is_int($key) ? [$value, 'text'] : [$key, $value];

                if ($name === '*') {
                    throw new InvalidArgumentException("A bare `*` attribute of <$element> would allow every attribute.");
                }
                if (str_starts_with($name, 'on') || isset(self::FORBIDDEN_ATTRIBUTES[$name])) {
                    throw new InvalidArgumentException("The `$name` attribute can not be allowed.");
                }

                $check = self::compile($name, $check);
                if (str_ends_with($name, '*')) {
                    $prefixes[$element][substr($name, 0, -1)] = $check;
                } else {
                    $attributes[$element][$name] = $check;
                }
            }
        }

        $common = $prefixes['*'] ?? [];
        foreach ($prefixes as $element => $list) {
            $prefixes[$element] = $list + $common;
        }

        $this->attributes = $attributes;
        $this->prefixes   = $prefixes;
        $this->schemes    = array_fill_keys($protocols, true);
    }

    /**
     * Get the rules with the elements and attributes added, e.g. Youla directives of a trusted page.
     *
     * @param array $rules Elements and their attributes to add, in the format of RULES.
     * @param array $base  Rules to add them to.
     * @return array
     */
    public static function extend(array $rules, array $base = self::RULES): array
    {
        foreach ($rules as $element => $attributes) {
            $base[$element] = [...$base[$element] ?? [], ...$attributes];
        }

        return $base;
    }

    /**
     * Keep only the allowed elements, attributes and URL protocols of the HTML fragment.
     *
     * @param string $html
     * @return string Empty for invalid UTF-8.
     */
    public function sanitize(string $html): string
    {
        if (! mb_check_encoding($html, 'UTF-8')) {
            return '';
        }

        // without markup and entities there is nothing to parse
        if (strpbrk($html, "<>&\r\0") === false) {
            return $html;
        }

        $body = self::$body ??= self::createBody();

        $body->innerHTML = $html;
        if (str_contains($html, '<')) {
            $this->filter($body, $html);
        }

        return $body->innerHTML;
    }

    /**
     * Remove the comments and filter every element of the parsed fragment.
     *
     * @param Element $body
     * @param string  $html Source of the fragment, to skip the passes it does not need.
     * @return void
     */
    private function filter(Element $body, string $html): void
    {
        // comments come only from "<!", "<?" and "</" not followed by a letter
        if (preg_match('~<[!?]|</[^a-z]~i', $html) === 1) {
            foreach (new XPath($body->ownerDocument)->query('.//comment()', $body) as $comment) {
                $comment->parentNode->removeChild($comment);
            }
        }

        // elements of other namespaces come only from <svg> and <math>
        $foreign    = stripos($html, '<svg') !== false || stripos($html, '<math') !== false;
        $attributes = $this->attributes;
        $common     = $attributes['*'] ?? [];

        // a copy of the live list: removed elements stay in it, their descendants are filtered in vain but harmlessly
        foreach (iterator_to_array($body->getElementsByTagName('*'), false) as $element) {
            $name = $element->localName;

            if ($foreign && $element->namespaceURI !== self::HTML_NAMESPACE) {
                $name = strtolower($name);
                if (! isset($attributes[$name])) {
                    $element->remove();
                    continue;
                }
            }

            $allowed = $attributes[$name] ?? null;
            if ($allowed === null) {
                if (isset(self::DROPPED[$name])) {
                    $element->remove();
                } else {
                    $element->replaceWith(...$element->childNodes);
                }
                continue;
            }

            foreach ($element->getAttributeNames() as $attribute) {
                $check = $allowed[$attribute] ?? $common[$attribute] ?? $this->prefixed($name, $attribute);
                if ($check === true) {
                    continue;
                }
                if ($check === null) {
                    $element->removeAttribute($attribute);
                    continue;
                }

                $value = $element->getAttribute($attribute);

                // most links are relative or start with an allowed protocol as is: no scheme before "/"
                if ($check === 'url' && $attribute !== 'srcset' && $attribute !== 'imagesrcset') {
                    $colon = strcspn($value, ':/?#');
                    $next  = $value[$colon] ?? '';
                    if ($next === '/' || $next === ':' && isset($this->schemes[substr($value, 0, $colon)])) {
                        continue;
                    }
                }

                if (! $this->passes($check, $attribute, $value)) {
                    $element->removeAttribute($attribute);
                }
            }

            // the content of an iframe is raw text that a browser does not show
            if ($name === 'iframe' && $element->firstChild !== null) {
                $element->textContent = '';
            }
        }
    }

    /**
     * Get the check of an attribute allowed by a prefix like `data-*`.
     *
     * @param string $element
     * @param string $attribute
     * @return true|string|array<string, true>|null Null when the attribute is not allowed.
     */
    private function prefixed(string $element, string $attribute): true|string|array|null
    {
        if (str_starts_with($attribute, 'on') || isset(self::FORBIDDEN_ATTRIBUTES[$attribute])) {
            return null;
        }

        foreach ($this->prefixes[$element] ?? $this->prefixes['*'] ?? [] as $prefix => $check) {
            if (str_starts_with($attribute, $prefix) && $attribute !== $prefix) {
                return isset(self::URL_ATTRIBUTES[$attribute]) && ! is_array($check) ? 'url' : $check;
            }
        }

        return null;
    }

    /**
     * Whether the value passes the check: `url`, `number` or a set of values.
     *
     * @param string|array<string, true> $check
     * @param string                     $attribute
     * @param string                     $value
     * @return bool
     */
    private function passes(string|array $check, string $attribute, string $value): bool
    {
        return match ($check) {
            'url'    => $attribute === 'srcset' || $attribute === 'imagesrcset' ? $this->isSrcset($value) : $this->isUrl($value),
            'number' => preg_match('/^-?\d+(?:\.\d+)?$/', $value) === 1,
            default  => isset($check[$value]) || isset($check[strtolower($value)]),
        };
    }

    /**
     * Whether the URL is relative or has an allowed protocol.
     *
     * @param string $url
     * @return bool
     */
    private function isUrl(string $url): bool
    {
        // a browser drops controls and spaces around a URL, tabs and newlines inside it
        $url   = str_replace(["\t", "\n", "\r"], '', trim($url, "\x00..\x20"));
        $colon = strcspn($url, ':/?#');

        return ($url[$colon] ?? '') !== ':' || isset($this->schemes[strtolower(substr($url, 0, $colon))]);
    }

    /**
     * Whether the URL of every candidate of the srcset is relative or has an allowed protocol.
     *
     * @param string $srcset
     * @return bool
     */
    private function isSrcset(string $srcset): bool
    {
        foreach (explode(',', $srcset) as $candidate) {
            $candidate = ltrim($candidate);
            if (! $this->isUrl(substr($candidate, 0, strcspn($candidate, " \t\n\f\r")))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compile the check of an attribute rule, URL attributes are always checked as URLs.
     *
     * @param string $attribute
     * @param mixed  $check `text`, `url`, `number` or a list of values.
     * @return true|string|array<string, true>
     * @throws InvalidArgumentException If the check is unknown.
     */
    private static function compile(string $attribute, mixed $check): true|string|array
    {
        if (is_array($check)) {
            return array_fill_keys([...$check, ...array_map(strtolower(...), $check)], true);
        }

        if (! in_array($check, ['text', 'url', 'number'], true)) {
            throw new InvalidArgumentException(
                "Unknown check of the `$attribute` attribute, expected text, url, number or a list of values."
            );
        }

        return isset(self::URL_ATTRIBUTES[rtrim($attribute, '*')]) ? 'url' : ($check === 'text' ? true : $check);
    }

    /**
     * Create the context element in which fragments are parsed, as a browser parses markup inserted into a page.
     *
     * @return Element
     */
    private static function createBody(): Element
    {
        $document = HTMLDocument::createEmpty();
        $body     = $document->createElement('body');
        $document->append($body);

        return $body;
    }
}
