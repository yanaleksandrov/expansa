<?php

declare(strict_types=1);

namespace Expansa\View\Compilers\Traits;

trait CompileHtml
{
    protected function compileClass($expression): string
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "class=\"<?php echo array_to_css_classes{$expression}; ?>\"";
    }

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

    protected function compileScss($file): string
    {
        $file = trim($file, "()\"'");

        return "<style>$file $this->path</style>";
    }
}
