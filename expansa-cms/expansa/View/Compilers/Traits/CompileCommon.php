<?php

declare(strict_types=1);

namespace Expansa\View\Compilers\Traits;

trait CompileCommon
{
    protected function compilePhp(?string $expression = null): string
    {
        if ($expression !== null) {
            return "<?php $expression; ?>";
        }
        return '<?php ';
    }

    protected function compileEndPhp(): string
    {
        return ' ?>';
    }

    protected function compileIf(?string $expression): string
    {
        return "<?php if $expression: ?>";
    }

    protected function compileUnless(?string $expression): string
    {
        return "<?php if (! $expression): ?>";
    }

    protected function compileElseif(?string $expression): string
    {
        return "<?php elseif $expression: ?>";
    }

    protected function compileElse(): string
    {
        return '<?php else: ?>';
    }

    protected function compileEndif(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileEndUnless(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileWhile(?string $expression): string
    {
        return "<?php while $expression: ?>";
    }

    protected function compileEndwhile(): string
    {
        return '<?php endwhile; ?>';
    }

    protected function compileFor(?string $expression): string
    {
        return "<?php for $expression: ?>";
    }

    protected function compileEndFor(): string
    {
        return '<?php endfor; ?>';
    }

    protected function compileForeach(?string $expression): string
    {
        return "<?php foreach $expression: ?>";
    }

    protected function compileEndForeach(): string
    {
        return '<?php endforeach; ?>';
    }

    protected function compileSwitch(?string $expression): string
    {
        return "<?php switch $expression: ?>";
    }

    protected function compileCase(?string $expression): string
    {
        return "<?php case $expression: ?>";
    }

    protected function compileDefault(): string
    {
        return '<?php default: ?>';
    }

    protected function compileEndswitch(): string
    {
        return '<?php endswitch; ?>';
    }

    protected function compileIsset(?string $expression): string
    {
        return "<?php if(isset{$expression}):  ?>";
    }

    protected function compileEndIsset(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileEmpty(?string $expression): string
    {
        return "<?php if(empty{$expression}):  ?>";
    }

    protected function compileEndEmpty(): string
    {
        return '<?php endif; ?>';
    }
}
