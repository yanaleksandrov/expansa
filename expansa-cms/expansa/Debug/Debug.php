<?php

declare(strict_types=1);

namespace Expansa\Debug;

use Throwable;
use TypeError;

/**
 * Renders the debug page for uncaught errors: message, trace and the code around the failing line.
 *
 * Strings are English: the page is for developers and must work when translations are broken.
 *
 * @package Expansa\Debug
 */
final class Debug
{
    /**
     * Output the debug page for an uncaught error.
     *
     * @param Throwable $e
     * @param string    $viewPath Template that receives title, description, context, details, traces and code.
     * @return void
     */
    public function render(Throwable $e, string $viewPath): void
    {
        extract($this->getData($e), EXTR_SKIP);

        include $viewPath;
    }

    private function getData(Throwable $e): array
    {
        $title = 'Fatal Error';

        $description = sprintf('On line %d in %s', $e->getLine(), htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8'));
        $description = preg_replace('/[a-z0-9_\-]*\.php/i', '$1<u>$0</u>', $description);
        $description = preg_replace('/(\d+)/', '<em>$1</em>', $description);
        $description = preg_replace('/[\(\)#\[\]\':]/i', '$1<ss>$0</ss>', $description);

        $traces = [];
        foreach ($e->getTrace() as $trace) {
            if (empty($trace['file'])) {
                continue;
            }

            $traces[] = (object) [
                'file' => $trace['file'],
                'line' => $trace['line'] ?? '',
            ];
        }

        $context = $e->getMessage();
        $details = $e instanceof TypeError ? $this->parseTypeError($e) : [];

        $code = $this->parseErrorCode($e);

        return compact('title', 'description', 'context', 'details', 'traces', 'code');
    }

    private function parseTypeError(TypeError $e): array
    {
        $data = [];
        foreach ($e->getTrace()[0]['args'] ?? [] as $key => $value) {
            $data[] = (object) [
                'key'   => $key,
                'type'  => gettype($value),
                'value' => $value,
            ];
        }

        return $data;
    }

    private function parseErrorCode(Throwable $e): string
    {
        if (empty($e->getTrace()[0])) {
            return '';
        }

        // lines around the error, the file may be gone (eval, deleted cache)
        $lines = @file($e->getFile()) ?: [];

        return trim(implode('', array_slice($lines, max(0, $e->getLine() - 10), 30)));
    }
}
