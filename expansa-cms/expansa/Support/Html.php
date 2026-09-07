<?php

declare(strict_types=1);

namespace Expansa\Support;

/**
 * This file is part of Expansa CMS.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE.md
 */
final class Html
{
    private array $options = [];

    private int $pos = 0;

    private string $token = '';

    private string $currentMode = 'CONTENT';

    private array $tags = [];

    private string $tagType = '';

    private string $tokenText = '';

    private string $lastToken = '';

    private string $lastText = '';

    private string $tokenType = '';

	private string $prevTagText = '';

	private string $prevTagType = '';

    private int $newlines = 0;

    private bool $indentContent = false;

    private int $indentLevel = 0;

    private int $lineCharCount = 0;

    private string $indentString = '';

    private array $whitespace = ["\n", "\r", "\t", ' '];

    /** Character list accepted by strspn()/strcspn() for the whitespace set above. */
    private const WHITESPACE_CHARS = " \t\r\n";

    /** Characters getTag() must inspect one at a time; anything else is bulk-skipped. */
    private const TAG_SPECIAL_CHARS = " \t\r\n'\"=><";

    /** Elements never folded onto one line by collapseTextOnlyElements() - their whitespace is significant. */
    private const NO_COLLAPSE_TAGS = ['pre', 'script', 'style', 'textarea'];

    private string $input = '';

	private int $inputLength = 0;

	/**
	 * Accumulated output, as a plain string rather than an array of fragments.
	 * A document with many tags/lines can push hundreds of thousands of tiny
	 * fragments (one per indent level, per newline, per token) - as an array
	 * each one carries PHP's per-element bucket/zval overhead regardless of how
	 * short the string is, which dwarfs the actual text for a large page. A
	 * string append has none of that: no separate structure, and PHP grows it
	 * with amortized reallocation just like it would the array's own storage.
	 */
	private string $output = '';

	/** The exact string most recently appended to $output, mirroring what "the last array element" used to mean. */
	private string $lastAppendedChunk = '';

	private $cssBeautify = false;

	private $jsBeautify = false;

    // all the single tags for HTML
    private array $singleToken = [
        'br',
        'input',
        'link',
        'meta',
        '!doctype',
        'basefont',
        'base',
        'area',
        'hr',
        'wbr',
        'param',
        'img',
        'source',
        'isindex',
        '?xml',
        'embed',
        '?php',
        '?',
        '?=',
    ];

    public function __construct(array $options = [], $cssBeautify = null, $jsBeautify = null)
    {
        $this->setOptions($options);

        $this->cssBeautify = ($cssBeautify && is_callable($cssBeautify)) ? $cssBeautify : false;
        $this->jsBeautify = ($jsBeautify && is_callable($jsBeautify)) ? $jsBeautify : false;

        $this->pos = 0; // Parser position
        $this->token = '';
        $this->currentMode = 'CONTENT'; // reflects the current Parser mode: TAG/CONTENT

        // An object to hold tags, their position, and their parent-tags, initiated with default values
        $this->tags = [
            'parent'      => 'parent1',
            'parentcount' => 1,
            'parent1'     => '',
        ];

        $this->tagType = '';
        $this->tokenText = $this->lastToken = $this->lastText = $this->tokenType = '';
        $this->newlines = 0;

        $this->indentContent = $this->options['indent_inner_html'];
        $this->indentLevel = 0;
        $this->lineCharCount = 0; // count to see if wrap_line_length was exceeded
        $this->indentString = str_repeat($this->options['indent_char'], $this->options['indent_size']);
    }

    public function setOptions(array $options): void
    {
        $this->options['indent_inner_html'] = (bool) ($options['indent_inner_html'] ?? false);
        $this->options['indent_size'] = (int) ($options['indent_size'] ?? 4);
        $this->options['indent_char'] = (string) ($options['indent_char'] ?? ' ');
        $this->options['wrap_line_length'] = (int) ($options['wrap_line_length'] ?? 32786);
        $this->options['unformatted'] = ! empty($options['unformatted']) && is_array($options['unformatted']) ? $options['unformatted'] : ['code', 'pre', 'strong', 'textarea', 'i'];
        $this->options['preserve_newlines'] = (bool) ($options['preserve_newlines'] ?? true);
        $this->options['max_preserve_newlines'] = $this->options['preserve_newlines'] && isset($options['max_preserve_newlines']) ? (int) $options['max_preserve_newlines'] : 32786;

        if (isset($options['indent_scripts']) && in_array($options['indent_scripts'], ['keep', 'separate', 'normal'], true)) {
            $this->options['indent_scripts'] = $options['indent_scripts'];
        } else {
            $this->options['indent_scripts'] = 'normal';
        }
    }

	/**
	 * Minify html markup.
	 *
	 * @param string $input
	 * @return string
	 */
    public function minify(string $input): string
    {
        return preg_replace(
            [
                '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
                '/[^\S ]+\</s',     // strip whitespaces before tags, except space
                '/(\s)+/s',         // shorten multiple whitespace sequences
                '/<!--(.|\s)*?-->/', // Remove HTML comments
            ],
            [
                '>',
                '<',
                '\\1',
                '',
            ],
            $input
        );
    }

	/**
	 * Beautify html markup.
	 *
	 * @param string $input
	 * @return string
	 */
    public function beautify(string $input): string
    {
        $this->input       = $input; // gets the input for the Parser
        $this->inputLength = strlen($this->input);
        $this->output      = '';
        $this->lastAppendedChunk = '';

        while (true) {
            $t = $this->getToken();

            $this->tokenText = $t[0];
            $this->tokenType = $t[1];

            if ($this->tokenType === 'TK_EOF') {
                break;
            }

            switch ($this->tokenType) {
                case 'TK_TAG_START':
                    $this->appendNewline();
                    $this->printToken($this->tokenText);
                    if ($this->indentContent) {
                        $this->indent();
                        $this->indentContent = false;
                    }
                    $this->currentMode = 'CONTENT';
                    $this->prevTagText = $this->tokenText;
					$this->prevTagType = $this->tokenType;
                    break;
                case 'TK_TAG_STYLE':
                case 'TK_TAG_SCRIPT':
                    $this->appendNewline();
                    $this->printToken($this->tokenText);
                    $this->currentMode = 'CONTENT';
                    break;
                case 'TK_TAG_END':
                    // Print new line only if the tag has no content and has child
                    if ($this->lastToken === 'TK_CONTENT' && $this->lastText === '') {
                        $skipTags = false;
                        $matches = [];
                        preg_match('/\w+/', $this->tokenText, $matches);
                        $tagName = $matches[0] ?? null;

                        $matches = [];
                        preg_match('/(?:<|{{#)\s*(\w+)/', $this->lastAppendedChunk, $matches);
                        $tagExtractedFromLastOutput = $matches[0] ?? null;

                        $isTagWithContent = true;
						preg_match( '/<([a-zA-Z0-9]+)[^>]*>/', $this->prevTagText, $openingMatches );
						preg_match( '/<\/([a-zA-Z0-9]+)[^>]*>/', $this->tokenText, $closingMatches );
						if ( $openingMatches[1] === $closingMatches[1] && $this->lastText === '' ) {
							$isTagWithContent = false;
						}

                        if (
                        	! $skipTags &&
							$isTagWithContent &&
							($tagExtractedFromLastOutput === null || $tagExtractedFromLastOutput[1] !== $tagName)
						) {
                            $this->appendNewline();
                        }
                    }
                    $this->printToken($this->tokenText);
                    $this->currentMode = 'CONTENT';
                    break;
                case 'TK_TAG_SINGLE':
                    // Don't add a newline before elements that should remain unformatted.
                    $matches = [];
                    preg_match('/^\s*<([a-z]+)/i', $this->tokenText, $matches);
                    $tagCheck = $matches ? $matches : null;

                    if ( ! $tagCheck || ! in_array($tagCheck[1], $this->options['unformatted'], true)) {
                        $this->appendNewline();
                    }
                    $this->printToken($this->tokenText);
                    $this->currentMode = 'CONTENT';
                    break;
                case 'TK_CONTENT':
                    $this->printToken($this->tokenText);
                    $this->currentMode = 'TAG';
                    break;
                case 'TK_STYLE':
                case 'TK_SCRIPT':
                    if ($this->tokenText !== '') {
                        $this->appendNewline();
                        $text = $this->tokenText;
                        $beautifier = false;
                        $scriptIndentLevel = 1;

                        if ($this->tokenType === 'TK_SCRIPT') {
                            $beautifier = $this->jsBeautify;
                        } elseif ($this->tokenType === 'TK_STYLE') {
                            $beautifier = $this->cssBeautify;
                        }

                        if ($this->options['indent_scripts'] === 'keep') {
                            $scriptIndentLevel = 0;
                        } elseif ($this->options['indent_scripts'] === 'separate') {
                            $scriptIndentLevel = -$this->indentLevel;
                        }

                        $indentation = $this->getFullIndent($scriptIndentLevel);
                        if ($beautifier) {
                            // call the Beautifier if avaliable
                            $text = $beautifier(preg_replace('/^\s*/', $indentation, $text), $this->options);
                        } else {
                            // simply indent the string otherwise

                            $matches = [];
                            preg_match('/^\s*/', $text, $matches);
                            $white = $matches[0] ?? null;

                            $matches = [];
                            preg_match('/[^\n\r]*$/', $white, $matches);
                            $dummy = $matches[0] ?? null;

                            $level = count(explode($this->indentString, $dummy)) - 1;
                            $reindent = $this->getFullIndent($scriptIndentLevel - $level);

                            $text = preg_replace('/^\s*/', $indentation, $text);
                            $text = preg_replace('/\r\n|\r|\n/', "\n" . $reindent, $text);
                            $text = preg_replace('/\s+$/', '', $text);
                        }

                        if ($text) {
                            $this->printTokenRaw($indentation . trim($text));
                            $this->appendNewline();
                        }
                    }
                    $this->currentMode = 'TAG';
                    break;
            }

            $this->lastToken = $this->tokenType;
            $this->lastText  = $this->tokenText;
        }

        return $this->collapseTextOnlyElements($this->output);
    }

    /**
     * Final pass: an element whose entire content is a plain text run (no nested
     * tags at all) is folded back onto a single line, e.g.
     *   <button ...>
     *       Continue
     *   </button>
     * becomes
     *   <button ...>Continue</button>
     * Left alone for whitespace-sensitive elements (script/style/pre/textarea -
     * those never reach here formatted this way to begin with) and for elements
     * with no text (already handled by the empty-tag logic above).
     */
    private function collapseTextOnlyElements(string $html): string
    {
        // The attribute group allows a literal '>' inside a quoted value (e.g. an
        // arrow function in an Alpine/Youla.js attribute like `key => value`).
        // Quote characters are excluded from the bare-character branch so it can
        // never overlap with the quoted-string branches - without that, the two
        // ways to consume a `"` (as itself, or as a string delimiter) make the
        // engine backtrack exponentially on any tag whose attributes end up not
        // matching the rest of the pattern (PREG_BACKTRACK_LIMIT_ERROR on anything
        // but a tiny input).
        $pattern = '/<([a-zA-Z][a-zA-Z0-9-]*)((?:"[^"]*"|\'[^\']*\'|[^<>"\'])*)>\r?\n\s*([^<]+?)\s*<\/\1>/';

        return preg_replace_callback($pattern, function (array $m): string {
            if (in_array(strtolower($m[1]), self::NO_COLLAPSE_TAGS, true)) {
                return $m[0];
            }

            $text = trim(preg_replace('/\s+/', ' ', $m[3]));
            if ($text === '') {
                return $m[0];
            }

            return "<{$m[1]}{$m[2]}>{$text}</{$m[1]}>";
        }, $html) ?? $html;
    }

    /**
     * Skip over a run of whitespace at the current position in one shot instead of
     * inspecting it one character at a time - strspn()/substr_count() are single
     * native calls regardless of how long the run is.
     */
    private function traverseWhitespace(): bool
    {
        $len = strspn($this->input, self::WHITESPACE_CHARS, $this->pos);

        if ($len === 0) {
            return false;
        }

        $this->newlines = 0;

        if ($this->options['preserve_newlines']) {
            $max   = $this->options['max_preserve_newlines'];
            $count = substr_count($this->input, "\n", $this->pos, $len);
            $this->newlines = $count > $max ? $max + 1 : $count;
        }

        $this->pos += $len;

        return true;
    }

    /**
     * Mirrors the side effect repeated traverseWhitespace() calls would have left on
     * $this->newlines while walking $span one character at a time: it only ever reflects
     * the *last* whitespace run encountered, and is left untouched when there is none.
     * printTokenRaw() later replays that many newlines into the output buffer.
     */
    private function updateNewlinesFromSpan(string $span): void
    {
        if (! preg_match_all('/[ \t\r\n]+/', $span, $matches)) {
            return;
        }

        $lastRun = end($matches[0]);
        $this->newlines = 0;

        if ($this->options['preserve_newlines']) {
            $max   = $this->options['max_preserve_newlines'];
            $count = substr_count($lastRun, "\n");
            $this->newlines = $count > $max ? $max + 1 : $count;
        }
    }

    // function to capture regular content between tags
    private function getContent(): string|array
    {
        if (! isset($this->input[$this->pos]) || $this->input[$this->pos] === '<') {
            return '';
        }

        $nextTag = strpos($this->input, '<', $this->pos);
        $end     = $nextTag === false ? $this->inputLength : $nextTag;
        $span    = substr($this->input, $this->pos, $end - $this->pos);

        // Fast path: bulk-trim/collapse the whole span in a handful of native calls.
        // Only safe when wrap_line_length can't possibly be crossed inside this span -
        // collapsing whitespace only ever shortens it, so if the *uncollapsed* length
        // added to the running column count still stays under the limit, the original
        // per-character wrap check inside this span would never have fired either.
        if ($this->lineCharCount + strlen($span) < $this->options['wrap_line_length']) {
            $this->pos = $end;
            $this->updateNewlinesFromSpan($span);

            $trimmed = trim($span, self::WHITESPACE_CHARS);
            if ($trimmed === '') {
                return '';
            }

            $collapsed = preg_replace('/[ \t\r\n]+/', ' ', $trimmed);
            $this->lineCharCount += strlen($collapsed);

            return $collapsed;
        }

        // Rare slow path (custom, small wrap_line_length): fall back to the original
        // character-at-a-time algorithm so line-wrapping stays pixel-for-pixel identical.
        return $this->getContentWrapped();
    }

    // original character-at-a-time content scanner, kept only for the wrap_line_length
    // edge case the bulk fast path above can't safely handle.
    private function getContentWrapped(): string|array
    {
        $inputChar = '';
        $content   = [];
        $space     = false; // if a space is needed

        while (isset($this->input[$this->pos]) && $this->input[$this->pos] !== '<') {
            if ($this->pos >= $this->inputLength) {
                return count($content) ? implode('', $content) : ['', 'TK_EOF'];
            }

            if ($this->traverseWhitespace()) {
                if (count($content)) {
                    $space = true;
                }
                continue; // don't want to insert unnecessary space
            }

            $inputChar = $this->input[$this->pos];
            ++$this->pos;

            if ($space) {
                if ($this->lineCharCount >= $this->options['wrap_line_length']) { // insert a line when the wrap_line_length is reached
                    $this->printNewline(false, $content);
                    $this->printIndentation($content);
                } else {
                    ++$this->lineCharCount;
                    $content[] = ' ';
                }
                $space = false;
            }
            ++$this->lineCharCount;
            $content[] = $inputChar; // letter at-a-time (or string) inserted to an array
        }

		return implode( '', $content );
    }

    // get the full content of a script or style to pass to js_beautify
    private function getContentsTo(string $name): string|array
    {
        if ($this->pos === $this->inputLength) {
            return ['', 'TK_EOF'];
        }
        $content = '';

        $regArray = [];
        preg_match('#</' . preg_quote($name, '#') . '\\s*>#im', $this->input, $regArray, PREG_OFFSET_CAPTURE, $this->pos);
        $endScript = $regArray ? ($regArray[0][1]) : $this->inputLength; // absolute end of script

        if ($this->pos < $endScript) { // get everything in between the script tags
            $content = substr($this->input, $this->pos, max($endScript - $this->pos, 0));
            $this->pos = $endScript;
        }

        return $content;
    }

    // function to record a tag and its parent in this.tags Object
    private function recordTag(string $tag): void
    {
        if (isset($this->tags[$tag . 'count'])) { // check for the existence of this tag type
            ++$this->tags[$tag . 'count'];
            $this->tags[$tag . $this->tags[$tag . 'count']] = $this->indentLevel; // and record the present indent level
        } else { // otherwise initialize this tag type
            $this->tags[$tag . 'count'] = 1;
            $this->tags[$tag . $this->tags[$tag . 'count']] = $this->indentLevel; // and record the present indent level
        }
        $this->tags[$tag . $this->tags[$tag . 'count'] . 'parent'] = $this->tags['parent']; // set the parent (i.e. in the case of a div this.tags.div1parent)
        $this->tags['parent'] = $tag . $this->tags[$tag . 'count']; // and make this the current parent (i.e. in the case of a div 'div1')
    }

    // function to retrieve the opening tag to the corresponding closer
    private function retrieveTag(string $tag): void
    {
        if (isset($this->tags[$tag . 'count'])) { // if the openener is not in the Object we ignore it
            $tempParent = $this->tags['parent']; // check to see if it's a closable tag.
            while ($tempParent) { // till we reach '' (the initial value);
                if ($tag . $this->tags[$tag . 'count'] === $tempParent) { // if this is it use it
                    break;
                }
                $tempParent = $this->tags[$tempParent . 'parent'] ?? ''; // otherwise keep on climbing up the DOM Tree
            }
            if ($tempParent) { // if we caught something
                $this->indentLevel = $this->tags[$tag . $this->tags[$tag . 'count']]; // set the indentLevel accordingly
                $this->tags['parent'] = $this->tags[$tempParent . 'parent']; // and set the current parent
            }
            unset($this->tags[$tag . $this->tags[$tag . 'count'] . 'parent'] , $this->tags[$tag . $this->tags[$tag . 'count']]); // delete the closed tags parent reference...
            // ...and the tag itself
            if ($this->tags[$tag . 'count'] === 1) {
                unset($this->tags[$tag . 'count']);
            } else {
                --$this->tags[$tag . 'count'];
            }
        }
    }

    // function to get a full tag and parse its type
    private function getTag(bool $peek = false): string|array
    {
        $inputChar = '';
        $content = [];
        $comment = '';
        $space = false;

        $tagStartChar = false;
        $tagStart = 0;
        $origPos = $this->pos;
        $origLineCharCount = $this->lineCharCount;

        do {
            if ($this->pos >= $this->inputLength) {
                if ($peek) {
                    $this->pos = $origPos;
                    $this->lineCharCount = $origLineCharCount;
                }
                return count($content) ? implode('', $content) : ['', 'TK_EOF'];
            }

            // Bulk-skip a run of characters that need no special per-character
            // handling (i.e. anything but whitespace/quotes/=/</>). Only once we're
            // past the first two characters: the comment sniff below needs $content[1]
            // to be exactly one raw character, matching the original char-at-a-time
            // array layout, and '<' is always content[0] on its own already.
            if (count($content) >= 2) {
                $runLen = strcspn($this->input, self::TAG_SPECIAL_CHARS, $this->pos);
                if ($runLen > 1) {
                    if ($space) {
                        // same "no space after = or before >" gate as below; a bulk run
                        // can never itself start with '>' since that's a special char.
                        if ($content[count($content) - 1] !== '=') {
                            if ($this->lineCharCount >= $this->options['wrap_line_length']) {
                                $this->printNewline(false, $content);
                                $this->printIndentation($content);
                            } else {
                                $content[] = ' ';
                                ++$this->lineCharCount;
                            }
                        }
                        $space = false;
                    }

                    $content[]             = substr($this->input, $this->pos, $runLen);
                    $this->pos             += $runLen;
                    $this->lineCharCount   += $runLen;
                    continue;
                }
            }

            $inputChar = $this->input[$this->pos];
            ++$this->pos;

            if (in_array($inputChar, $this->whitespace, true)) { // don't want to insert unnecessary space
                $space = true;
                continue;
            }

            if ($inputChar === "'" || $inputChar === '"') {
                $inputChar .= $this->getUnformatted($inputChar);
                $space = true;
            }

            if ($inputChar === '=') { // no space before =
                $space = false;
            }

            if (count($content) && $content[count($content) - 1] !== '=' && $inputChar !== '>' && $space) {
                // no space after = or before >
                if ($this->lineCharCount >= $this->options['wrap_line_length']) {
                    $this->printNewline(false, $content);
                    $this->printIndentation($content);
                } else {
                    $content[] = ' ';
                    ++$this->lineCharCount;
                }
                $space = false;
            }

            if ($inputChar === '<' && ! $tagStartChar) {
                $tagStart = $this->pos - 1;
                $tagStartChar = '<';
            }

            ++$this->lineCharCount;
            $content[] = $inputChar; // inserts character at-a-time (or string)

            if (isset($content[1]) && $content[1] === '!') { // if we're in a comment, do something special
                // We treat all comments as literals, even more than preformatted tags
                // we just look for the appropriate close tag
                $content = [$this->getComment($tagStart)];
                break;
            }
        } while ($inputChar !== '>');

        $tagComplete = implode('', $content);

        if (str_contains($tagComplete, ' ')) { // if there's whitespace, thats where the tag name ends
            $tagIndex = strpos($tagComplete, ' ');
        } else { // otherwise go with the tag ending
            $tagIndex = strpos($tagComplete, '>');
        }
        if ($tagComplete[0] === '<') {
            $tagOffset = 1;
        } else {
            $tagOffset = $tagComplete[2] === '#' ? 3 : 2;
        }
        $tagCheck = strtolower(substr($tagComplete, $tagOffset, max($tagIndex - $tagOffset, 0)));

        if ($tagComplete[strlen($tagComplete) - 2] === '/'
            || in_array($tagCheck, $this->singleToken, true)) { // if this tag name is a single tag type (either in the list or has a closing /)
            if ( ! $peek) {
                $this->tagType = 'SINGLE';
            }
        } elseif ($tagCheck === 'script') {
            if ( ! $peek) {
                $this->recordTag($tagCheck);
                $this->tagType = 'SCRIPT';
            }
        } elseif ($tagCheck === 'style') {
            if ( ! $peek) {
                $this->recordTag($tagCheck);
                $this->tagType = 'STYLE';
            }
        } elseif ($this->isUnformatted($tagCheck)) { // do not reformat the "unformatted" tags
            $comment = $this->getUnformatted('</' . $tagCheck . '>', $tagComplete); // ...delegate to getUnformatted function

            $content[] = $comment;

            // Preserve collapsed whitespace either before or after this tag.
            if ($tagStart > 0 && in_array($this->input[$tagStart - 1], $this->whitespace, true)) {
                array_splice($content, 0, 0, $this->input[$tagStart - 1]);
            }
            $tagEnd = $this->pos - 1;
            if (in_array($this->input[$tagEnd + 1], $this->whitespace, true)) {
                $content[] = $this->input[$tagEnd + 1];
            }
            $this->tagType = 'SINGLE';
        } elseif ($tagCheck && $tagCheck[0] === '!') { // peek for <! comment
            // for comments content is already correct.
            if ( ! $peek) {
                $this->tagType = 'SINGLE';
                $this->traverseWhitespace();
            }
        } elseif ( ! $peek) {
            if ($tagCheck && $tagCheck[0] === '/') { // this tag is a double tag so check for tag-ending
                $this->retrieveTag(substr($tagCheck, 1)); // remove it and all ancestors
                $this->tagType = 'END';
                $this->traverseWhitespace();
            } else { // otherwise it's a start-tag
                $this->recordTag($tagCheck); // push it on the tag stack
                if (strtolower($tagCheck) !== 'html') {
                    $this->indentContent = true;
                }
                $this->tagType = 'START';

                // Allow preserving of newlines after a start tag
                $this->traverseWhitespace();
            }
        }

        if ($peek) {
            $this->pos = $origPos;
            $this->lineCharCount = $origLineCharCount;
        }

        return implode('', $content); // returns fully formatted tag
    }

    // function to return comment content in its entirety
    private function getComment(int $startPos): string
    {
        $delimiter = '>';
        $matched   = false;

        // Determine the closing delimiter from the opening bytes, same rules as before,
        // but in one pass over a short peeked slice instead of growing the string char by char.
        $peek = substr($this->input, $startPos, 10);
        if (str_starts_with($peek, '<![if')) {
            $delimiter = '<![endif]>';
            $matched   = true;
        } elseif (str_starts_with($peek, '<![cdata[')) {
            $delimiter = ']]>';
            $matched   = true;
        } elseif (str_starts_with($peek, '<![')) {
            $delimiter = ']>';
            $matched   = true;
        } elseif (str_starts_with($peek, '<!--')) {
            $delimiter = '-->';
            $matched   = true;
        }
        unset($matched);

        // Case-sensitive, matching the original character-loop's plain strpos() checks.
        $delimPos = strpos($this->input, $delimiter, $startPos + 1);
        $end      = $delimPos === false ? $this->inputLength : $delimPos + strlen($delimiter);

        $comment   = substr($this->input, $startPos, $end - $startPos);
        $this->pos = $end;

        return $comment;
    }

    // function to return unformatted content in its entirety
    private function getUnformatted(string $delimiter, string|false $origTag = false): string
    {
        if ($origTag && strpos(strtolower($origTag), $delimiter) !== false) {
            return '';
        }

        if ($this->pos >= $this->inputLength) {
            return '';
        }

        // The character loop below only ever did two things to the raw bytes: it left
        // every character untouched except \r and \n, which both individually became a
        // bare "\n" - i.e. it never actually collapsed whitespace runs. That's a single
        // str_replace() over the whole span instead of a per-character branch.
        $delimPos = stripos($this->input, $delimiter, $this->pos);
        $end      = $delimPos === false ? $this->inputLength : $delimPos + strlen($delimiter);

        $raw = substr($this->input, $this->pos, $end - $this->pos);
        $this->pos = $end;

        // lineCharCount behaves like a column counter: it resets to 0 on every \r/\n
        // and otherwise counts up by one per character, so its final value only depends
        // on how many characters follow the last line break in this span (or, if there
        // isn't one, the length of the whole span).
        $lastBreak = strcspn(strrev($raw), "\r\n");
        if ($lastBreak === strlen($raw)) {
            $this->lineCharCount += $lastBreak;
        } else {
            $this->lineCharCount = $lastBreak;
        }

        return str_replace("\r", "\n", $raw);
    }

    // initial handler for token-retrieval
    private function getToken(): array
    {
        if ($this->lastToken === 'TK_TAG_SCRIPT' || $this->lastToken === 'TK_TAG_STYLE') { // check if we need to format javascript
            $type = substr($this->lastToken, 7);
            $token = $this->getContentsTo($type);
            if ( ! is_string($token)) {
                return $token;
            }
            return [$token, 'TK_' . $type];
        }
        if ($this->currentMode === 'CONTENT') {
            $token = $this->getContent();
            if ( ! is_string($token)) {
                return $token;
            }
            return [$token, 'TK_CONTENT'];
        }

        if ($this->currentMode === 'TAG') {
            $token = $this->getTag();
            if ( ! is_string($token)) {
                return $token;
            }
            $tagNameType = 'TK_TAG_' . $this->tagType;
            return [$token, $tagNameType];
        }

        return ['', 'TK_EOF'];
    }

    private function getFullIndent(int $level): string
    {
        // NOTE: `||` (not `?:`) is intentional here, byte-for-byte matching the
        // pre-existing vendor code - it makes $level collapse to a bool (0 or 1),
        // so this only ever returns '' or a single indent unit. Left unchanged to
        // avoid silently changing behavior; not something this optimization pass
        // was asked to fix. Cast to int so strict_types doesn't turn this existing
        // quirk into a TypeError when it reaches str_repeat() below.
        $level = (int) ($this->indentLevel + $level || 0);
        if ($level < 1) {
            return '';
        }

        return str_repeat($this->indentString, $level);
    }

    private function isUnformatted(string $tagCheck): bool
    {
        // is this an HTML5 block-level link?
        if ( ! in_array($tagCheck, $this->options['unformatted'], true)) {
            return false;
        }

        if (strtolower($tagCheck) !== 'a' || ! in_array('a', $this->options['unformatted'], true)) {
            return true;
        }

        // at this point we have a tag; is its first child something we want to remain unformatted?
        $nextTag = $this->getTag(true /* peek. */);

        // test nextTag to see if it is just html tag (no external content)
        $matches = [];
        preg_match('/^\s*<\s*\/?([a-z]*)\s*[^>]*>\s*$/',  $nextTag ? $nextTag : '' , $matches);
        $tag = $matches ? $matches : null;

        // if nextTag comes back but is not an isolated tag, then
        // let's treat the 'a' tag as having content
        // and respect the unformatted option
        if ( ! $tag || in_array($tag, $this->options['unformatted'], true)) {
            return true;
        }
        return false;
    }

    // Array-based variant, kept for the local $content buffers getTag()/getContentWrapped()
    // build up per-tag/per-run - those stay small regardless of document size, so there's
    // nothing to gain by touching them.
    private function printNewline(bool $force, array &$arr): void
    {
        $this->lineCharCount = 0;
        if ( ! $arr || ! count($arr)) {
            return;
        }
        if ($force || ($arr[count($arr) - 1] !== "\n")) { // we might want the extra line
            $arr[] = "\n";
        }
    }

    private function printIndentation(array &$arr): void
    {
        for ($i = 0; $i < $this->indentLevel; ++$i) {
            $arr[] = $this->indentString;
            $this->lineCharCount += strlen($this->indentString);
        }
    }

    /** String-based equivalent of printNewline(), appending straight to $this->output. */
    private function appendNewline(bool $force = false): void
    {
        $this->lineCharCount = 0;
        if ($this->output === '') {
            return;
        }
        if ($force || ! str_ends_with($this->output, "\n")) {
            $this->output .= "\n";
            $this->lastAppendedChunk = "\n";
        }
    }

    /**
     * String-based equivalent of printIndentation(): the original pushed one array
     * element per indent level (so a deeply-nested line cost that many separate
     * pushes); building the whole run once and appending it is the same net text
     * in a single operation.
     */
    private function appendIndentation(): void
    {
        if ($this->indentLevel <= 0) {
            return;
        }
        $indentation = str_repeat($this->indentString, $this->indentLevel);
        $this->output .= $indentation;
        $this->lastAppendedChunk = $indentation;
        $this->lineCharCount += strlen($indentation);
    }

    private function printToken(string $text): void
    {
        if ($text !== '' && str_ends_with($this->output, "\n")) {
            $this->appendIndentation();
            $text = ltrim($text);
        }
        $this->printTokenRaw($text);
    }

    private function printTokenRaw(string $text): void
    {
        if ($text !== '') {
            if (strlen($text) > 1 && $text[strlen($text) - 1] === "\n") {
                // unformatted tags can grab newlines as their last character
                $chunk = substr($text, 0, -1);
                $this->output .= $chunk;
                $this->lastAppendedChunk = $chunk;
                $this->appendNewline();
            } else {
                $this->output .= $text;
                $this->lastAppendedChunk = $text;
            }
        }

        for ($n = 0; $n < $this->newlines; ++$n) {
            $this->appendNewline($n > 0);
        }
        $this->newlines = 0;
    }

    private function indent(): void
    {
        ++$this->indentLevel;
    }
}
