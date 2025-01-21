<?php

declare(strict_types=1);

namespace Expansa\Pulse;

use Error;
use Exception;
use Expansa\I18n;
use Expansa\Db;

/**
 * A utility class for debugging PHP applications. Provides methods to manage error reporting,
 * measure execution time, memory usage, and generate detailed error outputs. This class is
 * designed to be used in a development environment to facilitate troubleshooting and performance
 * analysis.
 *
 * @package Expansa
 */
final class Debug
{
    public function start(string $viewPath, callable $callback, bool $isShowErrors = false): void
    {
        $bench = new Bench();

        if ($isShowErrors) {
            ini_set('error_reporting', E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        }

        if (is_callable($callback)) {
            try {
                $bench->start();
                $callback();
                $bench->end();

                $queries = count(Db::log());

                echo "{$queries}Q {$bench->getTime()}s {$bench->getMemoryPeak()}";
            } catch (Error | Exception $e) {
                extract($this->getData($e), EXTR_SKIP);

                ob_start();
                include $viewPath;
                echo ob_get_clean();
            }
        }
    }

    private function getData(mixed $e): array
    {
        $title = I18n::_t('Fatal Error');

        $description = I18n::_t('Find on line :lineNumber in file :filepath', $e->getLine(), $e->getFile());
        $description = preg_replace('/[a-z0-9_\-]*\.php/i', '$1<u>$0</u>', $description);
        $description = preg_replace('/[0-9]/i', '$1<em>$0</em>', $description);
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

    private function parseErrorCode(mixed $e): string
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
