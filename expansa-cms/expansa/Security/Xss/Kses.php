<?php

declare(strict_types=1);

namespace Expansa\Security\Xss;

use InvalidArgumentException;
use LogicException;

/**
 * HTML filter: keeps only allowed elements, attributes and URL protocols, disarms everything else.
 * Port of kses (https://sourceforge.net/projects/kses), the filter behind wp_kses().
 *
 * @package Expansa\Security\Xss
 */
final class Kses
{
    /**
     * Allowed elements and their attributes, in alphabetical order. A listed attribute takes any value,
     * `name => [check => value]` takes a value that passes one of the checks, see checkAttrVal().
     * The `*` element holds attributes of every element, a name ending with `*` is a prefix.
     */
    public const array ALLOWED_HTML = [
        '*'          => ['aria-*', 'class', 'data-*', 'dir', 'lang', 'role', 'title'],
        'a'          => ['download' => ['valueless' => 'y'], 'href', 'id', 'name', 'rel', 'rev', 'target'],
        'abbr'       => [],
        'address'    => [],
        'article'    => [],
        'aside'      => [],
        'audio'      => self::MEDIA,
        'b'          => [],
        'bdi'        => [],
        'bdo'        => [],
        'blockquote' => ['cite', 'id'],
        'br'         => [],
        'button'     => ['disabled', 'id', 'name', 'type', 'value'],
        'caption'    => ['align'],
        'cite'       => [],
        'code'       => [],
        'col'        => ['span'],
        'colgroup'   => ['span'],
        'data'       => ['value'],
        'dd'         => [],
        'del'        => ['datetime'],
        'details'    => ['open'],
        'dfn'        => [],
        'div'        => ['id'],
        'dl'         => [],
        'dt'         => [],
        'em'         => [],
        'fieldset'   => ['id'],
        'figcaption' => self::ALIGNED,
        'figure'     => self::ALIGNED,
        'footer'     => [],
        'h1'         => self::ALIGNED,
        'h2'         => self::ALIGNED,
        'h3'         => self::ALIGNED,
        'h4'         => self::ALIGNED,
        'h5'         => self::ALIGNED,
        'h6'         => self::ALIGNED,
        'header'     => [],
        'hgroup'     => [],
        'hr'         => [...self::ALIGNED, 'noshade', 'size', 'width'],
        'i'          => ['id'],
        'iframe'     => [
            'allow',
            'allowfullscreen',
            'frameborder',
            'height',
            'id',
            'loading',
            'marginheight',
            'marginwidth',
            'referrerpolicy',
            'sandbox',
            'scrolling',
            'src',
            'width',
        ],
        'img'        => ['align', 'alt', 'border', 'decoding', 'height', 'id', 'loading', 'sizes', 'src', 'srcset', 'width'],
        'ins'        => ['cite', 'datetime', 'id'],
        'kbd'        => ['id'],
        'label'      => ['for', 'id'],
        'legend'     => self::ALIGNED,
        'li'         => ['id', 'value'],
        'main'       => [],
        'mark'       => [],
        'meter'      => ['id'],
        'nav'        => self::ALIGNED,
        'ol'         => ['id', 'reversed', 'start', 'type'],
        'optgroup'   => ['label', 'selected', 'value'],
        'option'     => ['selected', 'value'],
        'p'          => [],
        'picture'    => [],
        'pre'        => [],
        'progress'   => ['id'],
        'q'          => ['cite', 'id'],
        'rp'         => [],
        'rt'         => [],
        'ruby'       => [],
        's'          => [],
        'samp'       => [],
        'section'    => self::ALIGNED,
        'select'     => [],
        'small'      => [],
        'source'     => ['media', 'sizes', 'src', 'srcset', 'type'],
        'span'       => ['align'],
        'strong'     => [],
        'sub'        => [],
        'summary'    => [],
        'sup'        => [],
        'table'      => ['align', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'id', 'rules', 'summary', 'width'],
        'tbody'      => self::TABLE_SECTION,
        'td'         => self::TABLE_CELL,
        'tfoot'      => self::TABLE_SECTION,
        'th'         => self::TABLE_CELL,
        'thead'      => self::TABLE_SECTION,
        'time'       => ['datetime'],
        'title'      => [],
        'tr'         => [...self::TABLE_SECTION, 'bgcolor'],
        'track'      => ['default', 'id', 'kind', 'label', 'src', 'srclang'],
        'u'          => [],
        'ul'         => ['id'],
        'var'        => [],
        'video'      => [...self::MEDIA, 'height', 'playsinline', 'poster', 'width'],
        'wbr'        => ['id'],
    ];

    /**
     * Attributes of headings and sections in ALLOWED_HTML.
     */
    private const array ALIGNED = ['align', 'id'];

    /**
     * Attributes of audio and video in ALLOWED_HTML.
     */
    private const array MEDIA = ['autoplay', 'controls', 'id', 'loop', 'muted', 'preload', 'src'];

    /**
     * Attributes of table rows and row groups in ALLOWED_HTML.
     */
    private const array TABLE_SECTION = ['align', 'char', 'charoff', 'id', 'valign'];

    /**
     * Attributes of table cells in ALLOWED_HTML.
     */
    private const array TABLE_CELL = [
        ...self::TABLE_SECTION,
        'abbr',
        'axis',
        'bgcolor',
        'colspan',
        'headers',
        'height',
        'nowrap',
        'rowspan',
        'scope',
        'width',
    ];

    /**
     * URL protocols allowed at the start of attribute values.
     */
    public const array ALLOWED_PROTOCOLS = [
        'ftp',
        'http',
        'https',
        'irc',
        'mailto',
        'news',
        'nntp',
        'rtsp',
        'sftp',
        'ssh',
        'tel',
        'telnet',
        'webcal',
    ];

    /**
     * Pieces of markup: a lone `<`, a comment, a tag up to `>` or the end, or a lone `>`.
     */
    private const string MARKUP = '%(<(?=[^a-zA-Z!/])|<!--.*?-->|<[^>]*(>|$)|>)%';

    /**
     * Characters of an attribute name: letters and `-`, also `:`, `@` and `.` of framework directives like `v-on:click`.
     */
    private const string NAME = '[-a-zA-Z:@.]';

    /**
     * Well-formed attributes in a row: `name="value"`, `name='value'`, `name=value` or a valueless `name`,
     * the same pieces combineAttributes() parses step by step.
     */
    private const string ATTRIBUTES = '/\G(' . self::NAME . '++)(?:\s*+=\s*+(?:"([^"]*+)"|\'([^\']*+)\'|([^\s"\']++))(?:\s++|$)|\s++(?!=)|$)/';

    /**
     * An attribute name at the current offset.
     */
    private const string ATTRIBUTE_NAME = '/\G' . self::NAME . '+/';

    /**
     * An attribute value: double-quoted, single quoted or unquoted, followed by whitespace or the end.
     */
    private const string VALUE = '/\G(?:"([^"]*)"|\'([^\']*)\'|([^\s"\']+))(?:\s+|$)/';

    /**
     * Characters of an element name.
     */
    private const string NAME_CHARS = '-abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Characters of a plainly written lower case protocol.
     */
    private const string PROTOCOL_CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Longest markup piece, in bytes, whose result is cached, so the cache stays small.
     */
    private const int CACHE_KEY_LENGTH = 256;

    /**
     * Max number of cached markup pieces per filter.
     */
    private const int CACHE_SIZE = 1024;

    /**
     * A protocol at the start of a value: letters, whitespace and entities followed by a colon in any encoding.
     */
    private const string PROTOCOL = '/^((&[^;]*;|[\sA-Za-z0-9])*)(:|&#0*58;|&#[Xx]0*3[Aa];|&colon;)\s*/';

    /**
     * Filtered markup pieces by their source, valid because the rules of a filter never change.
     */
    private array $cache = [];

    /**
     * @throws InvalidArgumentException For a rule with a bare `*` attribute or a check of a wrong type.
     * @throws LogicException           When the rules are set again.
     */
    public function __construct(

        /**
         * Elements and attributes, see ALLOWED_HTML; stored compiled: keyed by the lower case element name,
         * with the attributes of `*` added to every element and the prefixes of an element under its `*` key,
         * e.g. `['data-' => 1]`. Set only once.
         */
        public private(set) array $allowedHtml = self::ALLOWED_HTML {
            set {
                // the tag cache relies on rules that never change
                if (isset($this->allowedHtml)) {
                    throw new LogicException('The rules of a filter can not be changed, create another filter.');
                }

                // the default rules are compiled once per process, they are the same for every filter
                static $defaults = self::compile(self::ALLOWED_HTML);

                $this->allowedHtml = $value === self::ALLOWED_HTML ? $defaults : self::compile($value);
            }
        },

        /**
         * Lower case protocols, see ALLOWED_PROTOCOLS; stored as keys for isset() lookups.
         *
         * @var string[]
         */
        private array $allowedProtocols = self::ALLOWED_PROTOCOLS {
            set => array_fill_keys($value, true);
        },
    ) {} // phpcs:ignore

    /**
     * Get rules with more elements and attributes, e.g. directives of a trusted page.
     *
     * @param array $allowedHtml Elements and attributes to add, see ALLOWED_HTML.
     * @param array $rules       Rules to extend, the default ones by default.
     * @return array
     */
    public static function extend(array $allowedHtml, array $rules = self::ALLOWED_HTML): array
    {
        foreach ($allowedHtml as $element => $attributes) {
            $rules[$element] = [...$rules[$element] ?? [], ...$attributes];
        }

        return $rules;
    }

    /**
     * Compile rules: add the attributes of `*` to every element and move its prefixes under its own `*` key.
     *
     * @param array $allowedHtml
     * @return array Attributes by element, `*` of an element holds its prefixes, e.g. `['data-' => 1]`.
     * @throws InvalidArgumentException For a bare `*`: an empty prefix would allow every attribute, event handlers too.
     */
    private static function compile(array $allowedHtml): array
    {
        $global = self::attributes($allowedHtml['*'] ?? []);
        unset($allowedHtml['*']);

        foreach ($allowedHtml as $element => $attributes) {
            $attributes = [...$global, ...self::attributes($attributes)];

            $prefixes = [];
            foreach ($attributes as $name => $rules) {
                $name = (string) $name;

                if (str_ends_with($name, '*')) {
                    if ($name === '*') {
                        throw new InvalidArgumentException(
                            "The attribute `*` of <$element> would allow every attribute, use a prefix like `data-*`"
                        );
                    }

                    $prefixes[substr($name, 0, -1)] = $rules;

                    unset($attributes[$name]);
                }
            }

            // parsed attribute names never contain "*", so the key can not clash with an attribute
            if ($prefixes !== []) {
                $attributes['*'] = $prefixes;
            }

            $allowedHtml[$element] = $attributes;
        }

        return $allowedHtml;
    }

    /**
     * Turn listed attributes into `name => 1`, keeping the ones with checks; a later entry of a name wins.
     *
     * @param array $attributes E.g. `['href', 'download' => ['valueless' => 'y']]`.
     * @return array<string, mixed>
     */
    private static function attributes(array $attributes): array
    {
        $rules = [];
        foreach ($attributes as $name => $checks) {
            if (is_int($name)) {
                $rules[$checks] = 1;
            } else {
                $rules[$name] = $checks;
            }
        }

        return $rules;
    }

    /**
     * Get the allowed protocols.
     *
     * @return string[]
     */
    public function getAllowedProtocols(): array
    {
        return array_keys($this->allowedProtocols);
    }

    /**
     * Filter HTML: only allowed elements, attributes, protocols and valid entities remain.
     *
     * @param string $html
     * @return string Safe HTML, an empty string for invalid UTF-8.
     */
    public function apply(string $html): string
    {
        if ($html === '' || ! mb_check_encoding($html, 'UTF-8')) {
            return '';
        }

        // plain text without markup, entities and NUL characters is already safe
        if (strpbrk($html, "<>&\0") === false) {
            return $html;
        }

        $html = str_replace("\0", '', $html);

        if (str_contains($html, '&')) {
            // Netscape 4 JavaScript entities, e.g. &{alert(1)};
            if (str_contains($html, '{')) {
                $html = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $html) ?? '';
            }

            $html = self::normalizeEntities($html);
        }

        if (strpbrk($html, '<>') === false) {
            return $html;
        }

        return (string) preg_replace_callback(self::MARKUP, $this->filterMarkup(...), $html);
    }

    /**
     * Filter one piece of markup, reusing the result for a piece this filter has seen before.
     *
     * @param array $match
     * @return string
     */
    private function filterMarkup(array $match): string
    {
        $markup = $match[0];

        if (isset($this->cache[$markup])) {
            return $this->cache[$markup];
        }

        $result = $this->stripTags($markup);

        if (strlen($markup) <= self::CACHE_KEY_LENGTH && count($this->cache) < self::CACHE_SIZE) {
            $this->cache[$markup] = $result;
        }

        return $result;
    }

    /**
     * Filter one piece of markup: disarm a lone `<` or `>`, drop a malformed or not allowed element.
     *
     * @param string $markup
     * @return string
     */
    private function stripTags(string $markup): string
    {

        if ($markup[0] !== '<') {
            return '&gt;';
        }

        if ($markup === '<') {
            return '&lt;';
        }

        // a tag without attributes and spaces, e.g. <p> or </p>, needs no parsing
        if ($markup[-1] === '>') {
            $closing = $markup[1] === '/';
            $tag     = substr($markup, $closing ? 2 : 1, -1);

            if ($tag !== '' && strspn($tag, self::NAME_CHARS) === strlen($tag)) {
                if (! isset($this->allowedHtml[strtolower($tag)])) {
                    return '';
                }

                return $closing ? "</{$tag}>" : "<{$tag}>";
            }
        }

        if (! preg_match('%^<\s*(/\s*)?([-a-zA-Z0-9]+)\s*([^>]*)>?|(<!--.*?-->)$%', $markup, $matches)) {
            return '';
        }

        $comment = $matches[4] ?? '';
        $tag     = $comment !== '' ? '!--' : $matches[2];
        $element = strtolower($tag);

        if (! isset($this->allowedHtml[$element])) {
            return '';
        }

        if ($comment !== '') {
            return $comment;
        }

        // closing tags lose all attributes
        if (trim($matches[1]) !== '') {
            return "</{$tag}>";
        }

        return $this->stripAttributes($tag, $element, $matches[3]);
    }

    /**
     * Rebuild an opening tag with its allowed attributes only, keeping the XHTML closing slash.
     *
     * @param string $tag     Element name as written.
     * @param string $element Lower case element name.
     * @param string $attr    Attributes as written.
     * @return string
     */
    private function stripAttributes(string $tag, string $element, string $attr): string
    {
        $slash    = str_contains($attr, '/') && preg_match('%\s/\s*$%', $attr) ? ' /' : '';
        $allowed = $this->allowedHtml[$element];

        if ($allowed === [] || $attr === '') {
            return "<{$tag}{$slash}>";
        }

        $list = $this->combineAttributes($attr, $allowed);

        return '<' . $tag . (strpbrk($list, '<>') === false ? $list : str_replace(['<', '>'], '', $list)) . $slash . '>';
    }

    /**
     * Parse attributes even from broken markup and keep the allowed ones: unquoted values get quotes,
     * values lose disallowed protocols, unparsable pieces are skipped.
     *
     * @param string $attr
     * @param array  $allowed Attribute rules of the element, prefixes under `*`.
     * @return string Allowed attributes, each one preceded by a space.
     */
    private function combineAttributes(string $attr, array $allowed): string
    {
        $list   = '';
        $mode   = 0;
        $name   = '';
        $offset = 0;
        $length = strlen($attr);

        // 0: attribute name, 1: equals sign or a valueless attribute, 2: value
        while ($offset < $length) {
            $matched = false;

            if ($mode === 0) {
                // well-formed attributes are taken in one call, the steps below handle only the broken part
                if (preg_match_all(self::ATTRIBUTES, $attr, $sets, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL, $offset)) {
                    foreach ($sets as $set) {
                        $offset += strlen($set[0]);
                        $name    = strtolower($set[1]);
                        $rules   = $allowed[$name] ?? (isset($allowed['*']) ? self::prefixRules($allowed['*'], $name) : null);

                        if ($rules !== null) {
                            $list .= isset($set[3])
                                ? $this->attribute($rules, $name, $set[3], "'")
                                : $this->attribute($rules, $name, $set[2] ?? $set[4], '"');
                        }
                    }

                    continue;
                }

                if (preg_match(self::ATTRIBUTE_NAME, $attr, $match, 0, $offset)) {
                    $matched = true;
                    $mode    = 1;
                    $name    = strtolower($match[0]);
                    $offset += strlen($match[0]);
                }
            } elseif ($mode === 1) {
                if (preg_match('/\G\s*=\s*/', $attr, $match, 0, $offset)) {
                    $matched = true;
                    $mode    = 2;
                    $offset += strlen($match[0]);
                } elseif (preg_match('/\G\s+/', $attr, $match, 0, $offset)) {
                    $matched = true;
                    $mode    = 0;
                    $offset += strlen($match[0]);
                    $rules   = $allowed[$name] ?? (isset($allowed['*']) ? self::prefixRules($allowed['*'], $name) : null);

                    if ($rules !== null) {
                        $list .= $this->attribute($rules, $name, null, '');
                    }
                }
            } elseif (preg_match(self::VALUE, $attr, $match, PREG_UNMATCHED_AS_NULL, $offset)) {
                $matched = true;
                $mode    = 0;
                $offset += strlen($match[0]);
                $rules   = $allowed[$name] ?? (isset($allowed['*']) ? self::prefixRules($allowed['*'], $name) : null);

                if ($rules !== null) {
                    $list .= $match[2] !== null
                        ? $this->attribute($rules, $name, $match[2], "'")
                        : $this->attribute($rules, $name, $match[1] ?? $match[3], '"');
                }
            }

            if (! $matched) {
                $offset = self::skipMalformed($attr, $offset);
                $mode   = 0;
            }
        }

        // a valueless attribute at the very end, e.g. "selected"
        if ($mode === 1) {
            $rules = $allowed[$name] ?? (isset($allowed['*']) ? self::prefixRules($allowed['*'], $name) : null);

            if ($rules !== null) {
                $list .= $this->attribute($rules, $name, null, '');
            }
        }

        return $list;
    }

    /**
     * Get the rules of the first prefix an attribute name starts with, e.g. `data-` for `data-id`.
     *
     * @param array  $prefixes
     * @param string $name
     * @return mixed `null` if no prefix matches.
     */
    private static function prefixRules(array $prefixes, string $name): mixed
    {
        foreach ($prefixes as $prefix => $rules) {
            if (str_starts_with($name, (string) $prefix) && $name !== $prefix) {
                return $rules;
            }
        }

        return null;
    }

    /**
     * Build an allowed attribute if its value passes the rules.
     *
     * @param mixed       $rules `1` for any value, or checks, see checkAttrVal().
     * @param string      $name
     * @param string|null $value `null` for a valueless attribute.
     * @param string      $quote Quote of the rebuilt value, unquoted values get double quotes.
     * @return string The attribute preceded by a space, or an empty string.
     */
    private function attribute(mixed $rules, string $name, ?string $value, string $quote): string
    {
        if ($value === null) {
            [$value, $valueless, $whole] = ['', 'y', $name];
        } else {
            $value     = $this->badProtocol($value);
            $valueless = 'n';
            $whole     = $name . '=' . $quote . $value . $quote;
        }

        if (! is_array($rules)) {
            return ' ' . $whole;
        }

        foreach ($rules as $check => $expected) {
            if (self::checkAttrVal($value, $valueless, $check, $expected)) {
                return ' ' . $whole;
            }
        }

        return '';
    }

    /**
     * Check an attribute value against a rule of ALLOWED_HTML.
     *
     * @param string $value
     * @param string $valueless `y` if the attribute has no value, `n` otherwise.
     * @param string $check     `maxlen`, `minlen`, `maxval`, `minval`, `valueless` or `content`; others always pass.
     * @param mixed  $expected
     * @return bool
     * @throws InvalidArgumentException For an expected value of a wrong type.
     */
    private static function checkAttrVal(string $value, string $valueless, string $check, mixed $expected): bool
    {
        $type = match ($check) {
            'maxlen', 'minlen', 'maxval', 'minval' => is_numeric($expected) ? null : 'numeric',
            'valueless'                            => is_string($expected) ? null : 'string',
            'content'                              => is_array($expected) ? null : 'array',
            default                                => null,
        };

        if ($type !== null) {
            throw new InvalidArgumentException("$check must be of type $type, " . get_debug_type($expected) . ' given');
        }

        // maxval and minval accept only a short non-negative integer, so a huge number can not be passed
        return match ($check) {
            'maxlen'    => strlen($value) <= $expected,
            'minlen'    => strlen($value) >= $expected,
            'maxval'    => preg_match('/^\s{0,6}\d{1,6}\s{0,6}$/', $value) === 1 && (int) trim($value) <= $expected,
            'minval'    => preg_match('/^\s{0,6}\d{1,6}\s{0,6}$/', $value) === 1 && (int) trim($value) >= $expected,
            'valueless' => strtolower($expected) === $valueless,
            'content'   => self::matchesContent($value, $expected),
            default     => true,
        };
    }

    /**
     * Check if a value contains one of the patterns, `%` matches any text.
     *
     * @param string   $value
     * @param string[] $patterns
     * @return bool
     */
    private static function matchesContent(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match('/' . strtr(preg_quote($pattern, '/'), ['%' => '.*']) . '/', $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove disallowed protocols from the start of a value, repeatedly, so that `javascript:javascript:` is caught.
     * Whitespace, case and entities in the protocol are understood.
     *
     * @param string $value
     * @return string
     */
    private function badProtocol(string $value): string
    {
        $colon = strpos($value, ':');

        // every other encoding of the colon is an entity: &#58; &#x3A; &colon;
        if ($colon === false && ! str_contains($value, '&#') && ! str_contains($value, '&colon;')) {
            return $value;
        }

        // an allowed protocol written plainly, e.g. "https://x", is kept by the search as is,
        // unless whitespace follows the colon: the search removes it
        if (
            $colon > 0
            && strspn($value, self::PROTOCOL_CHARS) === $colon
            && isset($this->allowedProtocols[substr($value, 0, $colon)])
            && strspn($value, " \t\n\v\f\r", $colon + 1, 1) === 0
        ) {
            return $value;
        }

        do {
            $previous = $value;
            $value    = preg_replace_callback(self::PROTOCOL, $this->badProtocolOnce(...), $value) ?? '';
        } while ($value !== $previous);

        return $value;
    }

    /**
     * Keep a found protocol if it is allowed, drop it otherwise.
     *
     * @param array $match
     * @return string
     */
    private function badProtocolOnce(array $match): string
    {
        // numeric entities and the invisible soft hyphen could hide a protocol
        $protocol = preg_replace(['/&#\d+;/', '/&#[Xx][0-9A-Fa-f]+;/', '/\s/', '/\xad+/'], '', $match[1]) ?? '';
        $protocol = strtolower($protocol);

        return isset($this->allowedProtocols[$protocol]) ? "{$protocol}:" : '';
    }

    /**
     * Skip a piece that combineAttributes() can not parse: up to whitespace, with quoted parts.
     *
     * @param string $attr
     * @param int    $offset
     * @return int Offset after the piece.
     */
    private static function skipMalformed(string $attr, int $offset): int
    {
        preg_match('/\G("[^"]*("|$)|\'[^\']*(\'|$)||\S)*\s*/', $attr, $match, 0, $offset);

        return $offset + max(1, strlen($match[0] ?? ''));
    }

    /**
     * Disarm `&` that does not start a valid entity: `AT&T` becomes `AT&amp;T`, `&#x0041;` becomes `&#x41;`.
     *
     * @param string $html
     * @return string
     */
    private static function normalizeEntities(string $html): string
    {
        $html = preg_replace('/&(?!#\d+;|#[Xx]0*(?:[0-9A-Fa-f]{2})+;|[A-Za-z][A-Za-z0-9]*;)/', '&amp;', $html) ?? '';

        if (! str_contains($html, '&#x') && ! str_contains($html, '&#X')) {
            return $html;
        }

        return preg_replace('/&#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $html) ?? '';
    }
}
