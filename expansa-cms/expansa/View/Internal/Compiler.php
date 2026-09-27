<?php

declare(strict_types=1);

namespace Expansa\View\Internal;

/**
 * Compiles Blade templates to PHP files: `{{ }}`, `{!! !!}`, `@php`, `@verbatim`, control structures,
 * `@include`, `@extends` with sections. Compiled directives reach the manager through `$__env`.
 * A compiled file is reused while it is newer than the template and this compiler.
 *
 * @internal
 * @package Expansa\View
 */
final class Compiler
{
    /**
     * Echo, `@php` and `@verbatim` blocks, kept out of the directive pass.
     *
     * @var string[]
     */
    private array $rawBlocks = [];

    /**
     * `@extends` calls, appended to the end of the template.
     *
     * @var string[]
     */
    private array $layouts = [];

    public function __construct(

        /**
         * Directory of compiled templates, '' for the temp directory.
         */
        public string $cachePath = '',

        /**
         * Reuse a compiled file while it is newer than the template; false compiles on every compile() call.
         */
        public bool $cache = true,
    ) {}

    /**
     * Get the compiled file of the template, compiling it if it is expired.
     *
     * @param string $path Template file.
     * @return string
     */
    public function compile(string $path): string
    {
        $compiled = $this->getCompiledPath($path);

        if ($this->isExpired($path)) {
            if (! is_dir(dirname($compiled))) {
                mkdir(dirname($compiled), 0755, true);
            }

            file_put_contents($compiled, $this->compileString(file_get_contents($path)), LOCK_EX);
        }

        return $compiled;
    }

    /**
     * Whether the template must be compiled: caching is off, or the compiled file is missing or older
     * than the template or this compiler.
     *
     * @param string $path Template file.
     * @return bool
     */
    public function isExpired(string $path): bool
    {
        static $compilerTime = null;

        if (! $this->cache) {
            return true;
        }

        $compiled = $this->getCompiledPath($path);

        return ! is_file($compiled) || filemtime($compiled) < max(filemtime($path), $compilerTime ??= filemtime(__FILE__));
    }

    public function getCompiledPath(string $path): string
    {
        $directory = $this->cachePath !== '' ? $this->cachePath : sys_get_temp_dir() . '/expansa-views';

        return rtrim($directory, '/\\') . '/' . sha1($path) . '.php';
    }

    /**
     * Compile Blade source to PHP.
     *
     * @param string $content
     * @return string
     */
    public function compileString(string $content): string
    {
        $this->rawBlocks = [];

        $content = $this->storeRawVerbatimBlocks($content);
        $content = $this->storeRawPhpBlocks($content);
        $content = $this->storeRawEchoBlocks($content);
        $content = $this->replaceDirectives($content);
        $content = $this->restoreRawBlocks($content);

        if ($this->layouts !== []) {
            $content .= "\n\n" . implode("\n", array_reverse($this->layouts));
            $this->layouts = [];
        }

        return $content;
    }

    private function restoreRawBlocks(string $content): string
    {
        foreach ($this->rawBlocks as $num => $value) {
            $content = str_replace("__THIS_IS_RAW_BLOCK__{$num}__", $value, $content);
        }

        return $content;
    }

    private function storeRawVerbatimBlocks(string $content): string
    {
        if (! str_contains($content, '@verbatim')) {
            return $content;
        }

        return preg_replace_callback('/@verbatim(.*?)@endverbatim/s', fn ($match) => $this->rawBlockPlaceholder($match[1]), $content);
    }

    private function storeRawPhpBlocks(string $content): string
    {
        if (! str_contains($content, '@php')) {
            return $content;
        }

        return preg_replace_callback('/@php(.*?)@endphp/s', fn ($match) => $this->rawBlockPlaceholder("<?php {$match[1]} ?>"), $content);
    }

    private function storeRawEchoBlocks(string $content): string
    {
        $echos = [
            'raw'     => ['{!!', '!!}'],
            'escaped' => ['{{', '}}'],
        ];

        foreach ($echos as $type => $tags) {
            $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s/s', $tags[0], $tags[1]);

            $content = preg_replace_callback($pattern, function ($match) use ($type) {
                if ($match[1] === '@') {
                    return substr($match[0], 1);
                }

                $value = $match[2];

                if (str_ends_with($value, ';')) {
                    $value = substr($value, 0, -1);
                }

                if ($type === 'escaped') {
                    $value = "escape({$value})";
                }

                return $this->rawBlockPlaceholder("<?php echo $value; ?>");
            }, $content);
        }

        return $content;
    }

    private function rawBlockPlaceholder(string $value): string
    {
        $num = array_push($this->rawBlocks, $value) - 1;

        return "__THIS_IS_RAW_BLOCK__{$num}__";
    }

    /**
     * Replace `@name(expression)` with the result of the `compileName()` method, `@@name` stays `@name`.
     *
     * @param string $content
     * @return string
     */
    private function replaceDirectives(string $content): string
    {
        $pattern = "/
            @(?<name>@?[a-z]+)
            (?:[ \t]*)
            (?<expression> \( ( (?>[^()]+) | (?-2) )* \) )?
        /x";

        return preg_replace_callback($pattern, function ($match) {
            if (str_starts_with($match['name'], '@')) {
                return isset($match['expression']) ? $match['name'] . $match['expression'] : $match['name'];
            } elseif (method_exists($this, $method = 'compile' . ucfirst($match[1]))) {
                return $this->$method($match['expression'] ?? null);
            }

            return $match[0];
        }, $content);
    }

    private function stripBrackets(string $expression): string
    {
        return trim($expression, '()');
    }

    private function compilePhp(?string $expression = null): string
    {
        return $expression !== null ? "<?php $expression; ?>" : '<?php ';
    }

    private function compileEndPhp(): string
    {
        return ' ?>';
    }

    private function compileIf(?string $expression): string
    {
        return "<?php if $expression: ?>";
    }

    private function compileUnless(?string $expression): string
    {
        return "<?php if (! $expression): ?>";
    }

    private function compileElseif(?string $expression): string
    {
        return "<?php elseif $expression: ?>";
    }

    private function compileElse(): string
    {
        return '<?php else: ?>';
    }

    private function compileEndif(): string
    {
        return '<?php endif; ?>';
    }

    private function compileEndUnless(): string
    {
        return '<?php endif; ?>';
    }

    private function compileWhile(?string $expression): string
    {
        return "<?php while $expression: ?>";
    }

    private function compileEndwhile(): string
    {
        return '<?php endwhile; ?>';
    }

    private function compileFor(?string $expression): string
    {
        return "<?php for $expression: ?>";
    }

    private function compileEndFor(): string
    {
        return '<?php endfor; ?>';
    }

    private function compileForeach(?string $expression): string
    {
        return "<?php foreach $expression: ?>";
    }

    private function compileEndForeach(): string
    {
        return '<?php endforeach; ?>';
    }

    private function compileSwitch(?string $expression): string
    {
        return "<?php switch $expression: ?>";
    }

    private function compileCase(?string $expression): string
    {
        return "<?php case $expression: ?>";
    }

    private function compileDefault(): string
    {
        return '<?php default: ?>';
    }

    private function compileEndswitch(): string
    {
        return '<?php endswitch; ?>';
    }

    private function compileIsset(?string $expression): string
    {
        return "<?php if(isset{$expression}):  ?>";
    }

    private function compileEndIsset(): string
    {
        return '<?php endif; ?>';
    }

    private function compileEmpty(?string $expression): string
    {
        return "<?php if(empty{$expression}):  ?>";
    }

    private function compileEndEmpty(): string
    {
        return '<?php endif; ?>';
    }

    private function compileSelected(?string $expression): string
    {
        $expression ??= '([])';

        return "<?php if($expression) echo 'selected' ?>";
    }

    private function compileChecked(?string $expression): string
    {
        $expression ??= '([])';

        return "<?php if($expression) echo 'checked' ?>";
    }

    private function compileInclude(string $expression): string
    {
        $expression = $this->stripBrackets($expression);

        return "<?php echo \$__env->create({$expression}, array_diff_key(get_defined_vars(), array_flip(['__data', '__path'])))->render(); ?>";
    }

    private function compileExtends(string $name): string
    {
        $name = $this->stripBrackets($name);

        $this->layouts[] = "<?php echo \$__env->create($name)->render(); ?>";

        return '';
    }

    private function compileSection(string $name): string
    {
        $name = $this->stripBrackets($name);

        return "<?php \$__env->startSection($name); ?>";
    }

    private function compileEndSection(): string
    {
        return "<?php \$__env->stopSection(); ?>";
    }

    private function compileOverwrite(): string
    {
        return "<?php \$__env->stopSection(true); ?>";
    }

    private function compileShow(): string
    {
        return "<?php echo \$__env->yieldSection(); ?>";
    }

    private function compileYield(string $name): string
    {
        $name = $this->stripBrackets($name);

        return "<?php echo \$__env->yieldContent({$name}); ?>";
    }
}
