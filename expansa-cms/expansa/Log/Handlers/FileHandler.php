<?php

declare(strict_types=1);

namespace Expansa\Log\Handlers;

use Expansa\Log\Exception\LogException;
use Expansa\Log\Level;
use Expansa\Log\LogRecord;

/**
 * Appends records to a file, creating its directory on the first write.
 * The file stays open until the handler is destroyed, so a request opens it once.
 *
 * @package Expansa\Log\Handlers
 */
class FileHandler extends AbstractHandler
{
    /**
     * The open file, `null` until the first write.
     *
     * @var resource|null
     */
    protected $stream = null;

    /**
     * Create the handler, the file is opened on the first write.
     *
     * @param string           $path
     * @param Level|int|string $level
     */
    public function __construct(
        protected string $path,
        Level|int|string $level = Level::Debug
    )
    {
        parent::__construct($level);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function handle(LogRecord $record): bool
    {
        return $this->write($this->getFormatter()->format($record));
    }

    /**
     * Close the file, the next write opens it again.
     *
     * @return void
     */
    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    /**
     * Append a formatted record to the file.
     *
     * @param string $text
     * @return bool
     * @throws LogException If the file can not be opened.
     */
    protected function write(string $text): bool
    {
        $this->stream ??= $this->open($this->path);

        return fwrite($this->stream, $text) === strlen($text);
    }

    /**
     * Open a file for appending, creating its directory.
     *
     * @param string $path
     * @return resource
     * @throws LogException
     */
    protected function open(string $path)
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new LogException("Unable to create the log directory $dir");
        }

        $stream = @fopen($path, 'a');
        if ($stream === false) {
            throw new LogException("Unable to open the log file $path");
        }

        return $stream;
    }
}
