<?php

declare(strict_types=1);

namespace Expansa\View\Support;

use Dom\Comment;
use Dom\Element;
use Dom\HTMLDocument;
use Dom\Text;

/**
 * EXPERIMENTAL. Not a drop-in replacement for Html::beautify() - a parallel
 * prototype built on PHP 8.4's native Dom\HTMLDocument (a real, spec-compliant
 * HTML5 parser implemented in C) instead of Html.php's hand-rolled
 * character-by-character tokenizer, to see how close it can get on both
 * output and speed.
 *
 * Memory note: profiling showed the ~80MB peak on a 5000-row page comes
 * entirely from Dom\HTMLDocument::createFromString() building the DOM tree
 * itself (peak is identical whether or not serialization runs afterwards) -
 * there is no streaming/incremental HTML parser exposed by the DOM extension,
 * so this cost is inherent to using it at all and isn't something a yield-based
 * rewrite of the *serializer* below could reduce; that part already adds only
 * a few KB over the tree's own footprint.
 *
 * Known differences from Html::beautify(), by design or current limitation:
 * - Self-closing slashes on void elements (<link ... />) aren't preserved -
 *   the DOM discards that detail (HTML5 doesn't require it), so this class
 *   always prints void elements without one.
 * - <script>/<style> contents are preserved byte-for-byte (like <pre>), not
 *   re-indented the way Html::beautify()'s indent_scripts option can.
 * - Mixed inline content follows the same "unformatted list" concept as
 *   Html::beautify() (default: code, pre, strong, textarea, i - overridable
 *   via $options['unformatted']): a container whose only children are text
 *   and/or unformatted-list elements is folded onto one line; anything else
 *   (e.g. mixing plain text with a <span> or <a>) falls back to one line per
 *   child, same as Html::beautify() forcing a line break before non-listed
 *   start/end tags.
 * - Only tested against documents that already contain <html>/<head>/<body>
 *   (this project's actual usage - see app/Controllers/Web.php); parsing a
 *   bare fragment will have Dom\HTMLDocument imply a full document around it.
 * - Whatever HTML5-parsing-algorithm quirks the DOM extension has (e.g. how
 *   it recovers from malformed nesting) apply here instead of Html.php's own
 *   "climb the open-tag stack" recovery - these can disagree on genuinely
 *   broken markup.
 */
final class HtmlDom
{
    private const PRESERVE_TAGS = ['script', 'style', 'pre', 'textarea'];

    private const VOID_TAGS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    private string $indentString;

    /** @var string[] Elements folded inline with surrounding text instead of onto their own line. */
    private array $inlineTags;

    public function __construct(array $options = [])
    {
        $indentChar = (string) ($options['indent_char'] ?? ' ');
        $indentSize = (int) ($options['indent_size'] ?? 4);
        $this->indentString = str_repeat($indentChar, max(0, $indentSize));

        $this->inlineTags = ! empty($options['unformatted']) && is_array($options['unformatted'])
            ? array_map('strtolower', $options['unformatted'])
            : ['code', 'pre', 'strong', 'textarea', 'i'];
    }

    public function beautify(string $input): string
    {
        $dom = HTMLDocument::createFromString($input, LIBXML_NOERROR);

        $out = '';
        if ($dom->doctype !== null) {
            $out .= '<!DOCTYPE ' . $dom->doctype->name . ">\n";
        }

        if ($dom->documentElement !== null) {
            $out .= $this->serializeElement($dom->documentElement, 0);
        }

        return $out;
    }

    private function indent(int $depth): string
    {
        return str_repeat($this->indentString, max(0, $depth));
    }

    private function serializeElement(Element $element, int $depth): string
    {
        $tag = strtolower($element->tagName);
        $openTag = '<' . $tag . $this->serializeAttributes($element) . '>';

        if (in_array($tag, self::VOID_TAGS, true)) {
            return $this->indent($depth) . $openTag . "\n";
        }

        if (in_array($tag, self::PRESERVE_TAGS, true)) {
            // Preserve exactly as authored - these are whitespace-sensitive.
            return $this->indent($depth) . $openTag . ($element->innerHTML ?? '') . '</' . $tag . ">\n";
        }

        if ($element->childNodes->length === 0) {
            return $this->indent($depth) . $openTag . '</' . $tag . ">\n";
        }

        // Matches Html::beautify() not indenting <head>/<body> under <html>.
        $childDepth = $tag === 'html' ? $depth : $depth + 1;

        [$lines, $hadForcedBreak] = $this->serializeChildLines($element, $childDepth);

        if (count($lines) === 0) {
            return $this->indent($depth) . $openTag . '</' . $tag . ">\n";
        }

        // A single line that came from the text/inline-tag buffer (not from a
        // recursively block-serialized child, and without a source line break
        // forcing a split - see serializeChildLines()) folds onto the same
        // line as the open/close tags, e.g. "Site name <i>*</i>". A single
        // BLOCK child's own output staying on one line (e.g. an empty
        // <div></div>) must NOT be folded again here - Html::beautify() only
        // ever collapses one level.
        if (count($lines) === 1 && $lines[0]['inline'] && ! $hadForcedBreak) {
            $content = $lines[0]['text'];
            $prefix  = $this->indent($childDepth);
            if ($prefix !== '' && str_starts_with($content, $prefix)) {
                $content = substr($content, strlen($prefix));
            }
            $content = rtrim($content, "\n");

            return $this->indent($depth) . $openTag . $content . '</' . $tag . ">\n";
        }

        $body = '';
        foreach ($lines as $line) {
            $body .= $line['text'];
        }

        return $this->indent($depth) . $openTag . "\n" . $body . $this->indent($depth) . '</' . $tag . ">\n";
    }

    /**
     * Walks $element's children building one "line" per run: consecutive text
     * and $inlineTags elements accumulate into a single buffered line (folded
     * together, e.g. "Site name <i>*</i>"), while a block-level child, a
     * comment, or a source line break inside an otherwise-blank text run
     * (mirrors Html::beautify()'s preserve_newlines) flushes that buffer and
     * starts fresh - so a container that mixes inline runs with real block
     * children (text + <i> + a sibling <div>) only collapses the inline part,
     * not the whole thing.
     *
     * @return array{0: list<array{inline: bool, text: string}>, 1: bool} [$lines, $hadForcedBreak]
     *         $hadForcedBreak is true iff a source line break inside a blank text
     *         run was seen anywhere among the children - even one that (like a
     *         trailing "\n" right before the closing tag) didn't itself produce a
     *         second line, it still means the *result* was never eligible to be a
     *         single freely-collapsible run, so the caller must not fold it onto
     *         the parent's line even when serializeChildLines() otherwise
     *         returned exactly one 'inline' line.
     */
    private function serializeChildLines(Element $element, int $depth): array
    {
        $lines          = [];
        $buffer         = '';
        $hadForcedBreak = false;

        $flush = function () use (&$buffer, &$lines, $depth): void {
            if ($buffer !== '') {
                $lines[] = ['inline' => true, 'text' => $this->indent($depth) . $buffer . "\n"];
            }
            $buffer = '';
        };

        foreach ($element->childNodes as $child) {
            if ($child instanceof Text) {
                if (trim($child->data) === '') {
                    // Matches Html::beautify()'s preserve_newlines: a purely
                    // structural whitespace run that contained an actual line
                    // break in the source forces a real line break here too,
                    // even though the surrounding tags are otherwise inline.
                    if (str_contains($child->data, "\n")) {
                        $hadForcedBreak = true;
                        $flush();
                    }
                    continue;
                }
                $text    = $this->escapeText($this->collapseText($child->data));
                $buffer .= ($buffer !== '' ? ' ' : '') . $text;
                continue;
            }

            if ($child instanceof Element) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, $this->inlineTags, true)) {
                    $buffer .= ($buffer !== '' ? ' ' : '') . $this->serializeInline($child);
                    continue;
                }
                $flush();
                $lines[] = ['inline' => false, 'text' => $this->serializeElement($child, $depth)];
                continue;
            }

            if ($child instanceof Comment) {
                $flush();
                $lines[] = ['inline' => false, 'text' => $this->indent($depth) . '<!--' . $child->data . "-->\n"];
            }
        }

        $flush();

        return [$lines, $hadForcedBreak];
    }

    /** Escapes &, < and > - the DOM hands back already-decoded text, so this needs redoing on the way out. */
    private function escapeText(string $text): string
    {
        return htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function serializeInline(Element $element): string
    {
        $tag = strtolower($element->tagName);

        return '<' . $tag . $this->serializeAttributes($element) . '>' . ($element->innerHTML ?? '') . '</' . $tag . '>';
    }

    private function collapseText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Attributes that always keep an explicit value="" even when empty, rather
     * than being folded to a bare name - HTML can't tell "written bare" and
     * "written with an empty value" apart, so a fixed exception list is the
     * closest available match for the handful of attributes where an empty
     * value is meaningful and bare would look like a typo (class, id, ...),
     * while everything else (real boolean attributes, and bare framework
     * directives like u-autocomplete) folds to bare.
     */
    private const NEVER_BARE_ATTRS = ['class', 'id', 'style', 'value', 'href', 'src', 'alt', 'title', 'name', 'placeholder'];

    private function serializeAttributes(Element $element): string
    {
        $out = '';
        foreach ($element->getAttributeNames() as $name) {
            $value = $element->getAttribute($name);

            if ($value === '' && ! in_array($name, self::NEVER_BARE_ATTRS, true)) {
                $out .= ' ' . $name;
                continue;
            }

            // ENT_COMPAT (not ENT_QUOTES): values are always wrapped in double
            // quotes below, so a literal single quote inside never needs
            // escaping - matches how these templates already write e.g.
            // :class="... ? 'a' : 'b'" instead of needlessly turning every '
            // into &#039;.
            $out .= ' ' . $name . '="' . htmlspecialchars($value, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return $out;
    }
}
