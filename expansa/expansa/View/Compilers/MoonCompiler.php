<?php

declare(strict_types=1);

namespace Expansa\View\Compilers;

use Expansa\View\Compilers\Traits\CompileCommon;
use Expansa\View\Compilers\Traits\CompileConditions;
use Expansa\View\Compilers\Traits\CompileHtml;
use Expansa\View\Compilers\Traits\CompileIncludes;
use Expansa\View\Compilers\Traits\CompileLayout;
use Expansa\View\Factory;

class MoonCompiler
{
    use CompileCommon;
    use CompileConditions;
    use CompileIncludes;
    use CompileLayout;
    use CompileHtml;

    //protected Finder $finder;
    protected ?string $path = null;

    protected array $rawBlocks = [];

    protected array $layouts = [];

    //protected Factory $factory;

    public function __construct(
        protected bool $shouldCache = true,
        protected ?string $cacheDir = null
    ) {} // phpcs:ignore

    public function setFactory(Factory $factory): void
    {
        //$this->factory = $factory;
    }

    public function setCachePath(string $path): void
    {
        $this->cacheDir = $path;
    }

    public function setShouldCache(bool $cache): void
    {
        $this->shouldCache = $cache;
    }

    public function isExpired(string $path): bool
    {
        if (! $this->shouldCache) {
            return true;
        }

        $compiled = $this->getCompiledPath($path);

        if (file_exists($compiled) && filemtime($compiled) >= filemtime($path)) {
            return false;
        }

        return true;
    }

    public function compile(string $path = null): void
    {
        if (! is_null($path)) {
            $this->setPath($path);
        }

        if (is_null($this->path)) {
            return;
        }

        $content = $this->compileString(file_get_contents($path));

        $this->saveCompiledToCache($content);
    }

    public function compileString(string $content): string
    {
        $content = $this->storeRawBlocks($content);
        $content = $this->compileStatements($content);
        $content = $this->compileRawBlocks($content);

        if (! empty($this->layouts)) {
            $content .= "\n\n" . implode("\n", array_reverse($this->layouts));
            $this->layouts = [];
        }

        return $content;
    }

    protected function storeRawBlocks($content): string
    {
        $content = $this->storeRawVerbatimBlocks($content);
        $content = $this->storeRawPhpBlocks($content);

        return $this->storeRawEchoBlocks($content);
    }

    protected function compileRawBlocks($content): string
    {
        foreach ($this->rawBlocks as $num => $value) {
            $content = str_replace("__THIS_IS_RAW_BLOCK__{$num}__", $value, $content);
        }
        return $content;
    }

    protected function storeRawVerbatimBlocks(string $content): string
    {
        if (! str_contains($content, "@verbatim")) {
            return $content;
        }

        return preg_replace_callback('/@verbatim(.*?)@endverbatim/s', function ($match) {
            return $this->getRawBlockPlaceholder($match[1]);
        }, $content);
    }

    protected function storeRawPhpBlocks(string $content): string
    {
        if (! str_contains($content, "@php")) {
            return $content;
        }

        return preg_replace_callback('/@php(.*?)@endphp/s', function ($match) {
            return $this->getRawBlockPlaceholder("<?php {$match[1]} ?>");
        }, $content);
    }

    protected function storeRawEchoBlocks(string $content): string
    {
        $echos = [
            'raw'     => ['{!!', '!!}'],
            'escaped' => ['{{', '}}'],
        ];

        foreach ($echos as $type => $tags) {
            $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s/s', $tags[0], $tags[1]);

            $content = preg_replace_callback($pattern, function ($match) use ($type) {
                if ($match[1] == '@') {
                    return substr($match[0], 1);
                }

                $value = $match[2];

                if (str_ends_with($value, ';')) {
                    $value = substr($value, 0, -1);
                }

                if ($type === 'escaped') {
                    $value = "escape({$value})";
                }

                return $this->getRawBlockPlaceholder("<?php echo {$value}; ?>");
            }, $content);
        }

        return $content;
    }

    protected function getRawBlockPlaceholder($value): string
    {
        $num = array_push($this->rawBlocks, $value) - 1;
        return "__THIS_IS_RAW_BLOCK__{$num}__";
    }

    protected function compileStatements($content): string
    {
        $pattern = "/
            @(?<name>@?[a-z]+)
            (?:[ \t]*)
            (?<expression> \( ( (?>[^()]+) | (?-2) )* \) )?
        /x";

        return preg_replace_callback($pattern, function ($match) {
            if (str_starts_with($match['name'], '@')) {
                return isset($match['expression']) ? $match['name'] . $match['expression'] : $match['name'];
            } elseif (method_exists($this, $method = "compile" . ucfirst($match[1]))) {
                return $this->$method($match['expression'] ?? null);
            }

            return $match[0];
        }, $content);
    }

    protected function stripBrackets($expression): string
    {
        return trim($expression, '()');
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    protected function saveCompiledToCache(string $content): void
    {
        $path = $this->getCompiledPath($this->path);

        file_put_contents($path, $content, LOCK_EX);
    }

    public function getCompiledPath(string $path): string
    {
        if (is_null($this->cacheDir)) {
            throw new \Exception('Cache path not configured.');
        }

        return rtrim($this->cacheDir, '/') . '/' . sha1($path) . ".php";
    }
}
