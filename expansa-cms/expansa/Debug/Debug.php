<?php

declare(strict_types=1);

namespace Expansa\Debug;

use Throwable;

/**
 * Renders the debug page for uncaught errors: message, trace and the code around the failing line.
 *
 * @package Expansa
 */
class Debug
{
    /**
     * Output the debug page for an uncaught error.
     *
     * @param string $viewPath Template that receives title, description, context, details, traces and code.
     */
    public function render(Throwable $e, string $viewPath): void
    {
        extract($this->getData($e), EXTR_SKIP);

        include $viewPath;
    }

    private function getData(Throwable $e): array
    {
        $title = t('Fatal Error');

        $description = t('On line :lineNumber in :filepath', $e->getLine(), $e->getFile());
        $description = preg_replace('/[a-z0-9_\-]*\.php/i', '$1<u>$0</u>', $description);
        $description = preg_replace('/(\d+)/', '<em>$1</em>', $description);
        $description = preg_replace('/[\(\)#\[\]\':]/i', '$1<ss>$0</ss>', $description);

        $traces     = [];
        $tracesList = $e->getTrace();
        if ($tracesList) {
            foreach ($tracesList as $trace) {
                if (empty($trace['file'])) {
                    continue;
                }

                $traces[] = (object) [
                    'file' => $trace['file'] ?? '',
                    'line' => $trace['line'] ?? '',
                ];
            }
        }

        $context = $e->getMessage();
        $details = match (true) {
            $e instanceof \TypeError => $this->parseTypeError($e),
            default => [],
        };

        $code = $this->parseErrorCode($e);

        return compact('title', 'description', 'context', 'details', 'traces', 'code');
    }

    private function parseTypeError(\TypeError $e): array
    {
        $data = [];

        $errorTrace     = current($e->getTrace());
        $errorTraceArgs = $errorTrace['args'] ?? [];
        if ($errorTraceArgs) {
            foreach ($errorTraceArgs as $key => $value) {
                $data[] = (object) [
                    'key'   => $key,
                    'type'  => gettype($value),
                    'value' => $value,
                ];
            }
        }
        return $data;
    }

    private function parseErrorCode(Throwable $e): string
    {
        $trace = $e->getTrace();

        $code = '';
        if (empty($trace[0])) {
            return $code;
        }

        try {
            $file = $e->getFile();
            $line = $e->getLine();

            // get lines of code around the error so that the context is visible
            $lines = file($file);
            $code  = implode('', array_slice($lines, max(0, $line - 10), 30));
        } catch (\ReflectionException $e) {
        }

        return trim($code);
    }
}
