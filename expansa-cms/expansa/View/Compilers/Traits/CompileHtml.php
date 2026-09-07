<?php

declare(strict_types=1);

namespace Expansa\View\Compilers\Traits;

trait CompileHtml
{
    protected function compileSelected($expression): string
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "<?php if($expression) echo 'selected' ?>";
    }

    protected function compileChecked($expression): string
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "<?php if($expression) echo 'checked' ?>";
    }
}
