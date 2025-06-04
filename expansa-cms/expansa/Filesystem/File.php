<?php

declare(strict_types=1);

namespace Expansa\Filesystem;

use DateTime;
use Expansa\Filesystem\Contracts\CommonInterface;
use Expansa\Filesystem\Contracts\FileInterface;

/**
 * The File class provides a convenient and easy-to-use API for working with files.
 * It supports working with various types: CSV, SVG and images of different formats.
 *
 * You can perform a wide range of operations: reading and writing to a file,
 * downloading and capturing, moving and copying files, and much more.
 */
class File extends EntryHandler implements CommonInterface, FileInterface
{
    public function chmod(int $mode = 0755): File
    {
        if ($this->exists && ! chmod($this->path, $mode)) {
            $this->errors[] = t('Failed to update file access rights');
        }
        return $this;
    }

    public function clean(): File
    {
        if ($this->exists) {
            $handle = fopen($this->path, 'w');
            if ($handle) {
                fclose($handle);
            } else {
                $this->errors[] = t('Failed to open the file for writing.');
            }
        } else {
            $this->errors[] = t('The file does not exist or you don\'t have permission to edit the file.');
        }

        return $this;
    }

    public function copy(string $name): File
    {
        if ($this->exists) {
            $newPath = sprintf('%s/%s.%s', $this->dirpath, $name, $this->extension);
            $dirPath = dirname($newPath);

            if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true)) {
                $this->errors[] = t('Failed to create the directory.');
            } elseif (!is_file($newPath) && !copy($this->path, $newPath)) {
                $this->errors[] = is_file($newPath)
                    ? t('File already exists at the destination.')
                    : t('Failed to copy the file.');
            }
        }

        return $this;
    }

    public function delete(): bool
    {
        if ($this->exists) {
            if (unlink($this->path)) {
                return true;
            }
        }
        return false;
    }

    public function download(): void
    {
        if ($this->exists) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $this->basename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $this->sizeB);

            // read the file and output it to the browser
            ob_clean();
            flush();
            readfile($this->path);
            exit;
        }
    }

    public function move(string $to): File
    {
        if ($this->exists) {
            $directory = dirname($to);
            $filepath  = $directory . DIRECTORY_SEPARATOR . $this->filename;

            if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
                $this->errors[] = t('Failed to create directory "%s".', $directory);
                return $this;
            }

            if (rename($this->path, $filepath)) {
                return new self($filepath);
            }

            $this->errors[] = t('Failed to move the file.');
        }
        return $this;
    }

    public function read(): string
    {
        return $this->exists ? (file_get_contents($this->path) ?: '') : '';
    }

    public function rename(string $name): File
    {
        if (!$this->exists) {
            $this->errors[] = t('File not exists at the destination.');
        } else {
            $newPath = $this->dirpath . DIRECTORY_SEPARATOR . $this->sanitizeName($name);
            if (!rename($this->path, $newPath)) {
                $this->errors[] = t('Failed to rename file to "%s".', $newPath);
            } else {
                return new self($newPath);
            }
        }

        return $this;
    }

    public function rewrite(array $content): File
    {
        if ($this->exists && is_readable($this->path) && filesize($this->path) > 0) {
            $file_content = file_get_contents($this->path);

            foreach ($content as $field => $value) {
                $file_content = str_replace($field, $value, $file_content);
            }

            file_put_contents($this->path, $file_content);
        }
        return $this;
    }

    public function touch(?int $time = null, ?int $atime = null): File
    {
        if ($this->exists) {
            $time  = $time ?? time();
            $atime = $atime ?? $time;

            if (!touch($this->path, $time, $atime)) {
                $this->errors[] = t('Failed to update the timestamps for ":filePath".', $this->path);
            }

            $this->modified = (new DateTime())->setTimestamp($time)->format('Y-m-d H:i:s');
        }
        return $this;
    }

    public function write(mixed $content, bool $after = true): File
    {
        $this->createFile();

        if (!is_writable($this->path)) {
            $this->errors[] = t("The file is not writable: ':path'", $this->path);
            return $this;
        }

        $fp = fopen($this->path, $after ? 'a' : 'w');
        if (!$fp) {
            $this->errors[] = t("The file cannot be opened: ':path'", $this->path);
        } else {
            if (fwrite($fp, $content) === false) {
                $this->errors[] = t("It is not possible to write to the file: ':path'", $this->path);
            }
            fclose($fp);

            // file is changed, update data about file, e.g.: "size" etc.
            return new self($this->path);
        }

        return $this;
    }
}
