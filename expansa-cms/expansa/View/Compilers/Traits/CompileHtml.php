<?php

declare(strict_types=1);

namespace Expansa\View\Compilers\Traits;

trait CompileHtml
{
    protected function compileSelected(?string $expression): string
    {
        $expression ??= '([])';

        return "<?php if($expression) echo 'selected' ?>";
    }

    protected function compileChecked(?string $expression): string
    {
        $expression ??= '([])';

        return "<?php if($expression) echo 'checked' ?>";
    }
}
