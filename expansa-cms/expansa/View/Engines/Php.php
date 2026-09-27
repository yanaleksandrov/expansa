<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Expansa\View\Contracts\Engine;
use Throwable;

/**
 * Includes a PHP template with its variables and returns the trimmed output.
 * The template also sees `$__path` and `$__data`, all its variables as an array.
 *
 * @package Expansa\View
 */
class Php implements Engine
{
    public protected(set) string $lastRendered = '';

    public function render(string $path, array $data = []): string
    {
        $this->lastRendered = $path;

        return $this->evaluatePath($path, $data);
    }

    /**
     * Include a PHP file and return its trimmed output; the output buffers are closed on an exception.
     *
     * @param string               $path
     * @param array<string, mixed> $data
     * @return string
     */
    protected function evaluatePath(string $path, array $data): string
    {
        $level = ob_get_level();

        ob_start();
        try {
            self::evaluate($path, $data);
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return trim(ob_get_clean());
    }

    /**
     * Include the template in a scope without `$this` and with only its variables.
     *
     * @param string               $__path
     * @param array<string, mixed> $__data
     * @return void
     */
    protected static function evaluate(string $__path, array $__data): void
    {
        extract($__data, EXTR_SKIP);

        require $__path;
    }
}
