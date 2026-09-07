<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Throwable;

class PhpEngine extends Engine
{
    #[\Override]
    public function get(string $path, array $data = []): string
    {
        $this->lastRendered = $path;

        return $this->evaluatePath($path, $data);
    }

    protected function evaluatePath(string $path, array $data): string
    {
        $obLevel = ob_get_level();

        ob_start();
        try {
            $this->require($path, $data);
        } catch (Throwable $e) {
            $this->handleException($e, $obLevel);
        }
        return trim(ob_get_clean());
    }

    protected function require(string $__path, array $__data): void
    {
        (function () use ($__path, $__data) {
            extract($__data, EXTR_SKIP);
            require $__path;
        })();
    }

    protected function handleException(Throwable $e, int $obLevel): void
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw $e;
    }
}
