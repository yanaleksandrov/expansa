<?php

declare(strict_types=1);

namespace Expansa\Log\Handlers;

use Expansa\Log\Level;
use Expansa\Log\LogRecord;

/**
 * Writes a file per day, `app.log` becomes `app-2025-01-31.log`, and keeps only the latest $maxFiles.
 * Old files are removed when a day's file is opened for the first time.
 *
 * @package Expansa\Log\Handlers
 */
class RotatingFile extends File
{
    /**
     * Glob of the Y-m-d date part in file names.
     */
    private const string DATE_GLOB = '[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]';

    /**
     * Date of the current file.
     */
    private string $date = '';

    public function __construct(

        /**
         * Path without the date, it is inserted before the extension.
         */
        protected readonly string $basePath,

        /**
         * Number of files to keep, 0 keeps all of them.
         */
        protected readonly int $maxFiles = 7,

        /**
         * Minimum level of the records to handle.
         */
        Level|int|string $level = Level::Debug,
    )
    {
        parent::__construct($this->datedPath(date('Y-m-d')), $level);
    }

    public function handle(LogRecord $record): bool
    {
        $date = $record->datetime->format('Y-m-d');

        if ($date !== $this->date) {
            $this->close();
            $this->date = $date;
            $this->path = $this->datedPath($date);
            $this->rotate();
        }

        return parent::handle($record);
    }

    /**
     * Remove the oldest files, so that $maxFiles are left together with the current one.
     *
     * @return void
     */
    protected function rotate(): void
    {
        if ($this->maxFiles <= 0) {
            return;
        }

        $files = glob($this->datedPath(self::DATE_GLOB)) ?: [];
        $files = array_diff($files, [$this->path]);
        if (count($files) < $this->maxFiles) {
            return;
        }

        // Y-m-d names sort by date
        rsort($files);
        foreach (array_slice($files, $this->maxFiles - 1) as $file) {
            @unlink($file);
        }
    }

    private function datedPath(string $date): string
    {
        $info = pathinfo($this->basePath);
        $name = $info['filename'] . '-' . $date . (isset($info['extension']) ? '.' . $info['extension'] : '');

        return $info['dirname'] . DIRECTORY_SEPARATOR . $name;
    }
}
