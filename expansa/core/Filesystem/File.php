<?php

declare(strict_types=1);

namespace Expansa\Filesystem;

use DateTime;
use Expansa\I18n;
use Expansa\Validator;
use Expansa\Filesystem\Contracts\FileInterface;
use Expansa\Filesystem\Contracts\CommonInterface;

/**
 * The File class provides a convenient and easy-to-use API for working with files.
 * It supports working with various types: CSV, SVG and images of different formats.
 *
 * You can perform a wide range of operations: reading and writing to a file,
 * downloading and capturing, moving and copying files, and much more.
 */
class File extends EntryHandler implements CommonInterface, FileInterface
{
    public function grab(string $url): File
    {
        $url = $this->sanitizeUrl($url);
        if (!$url) {
            $this->errors[] = I18n::_t('File URL is not valid.');

            return $this;
        }

        $basename  = basename($url);
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        $filepath  = sprintf('%s%s', $targetDir, $basename);

        if (!$extension) {
            $this->errors[] = I18n::_t('The file cannot be grabbed because it does not contain an extension.');

            return $this;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $ch   = curl_init($url);
        $file = fopen($filepath, 'wb');

        curl_setopt($ch, CURLOPT_FILE, $file);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);

        // check errors
        $error = curl_errno($ch);
        if ($error) {
            $errors = [
                1  => I18n::_t('The URL you passed to the libcurl function uses an unsupported protocol.'),
                3  => I18n::_t('The URL you provided is not properly formatted.'),
                6  => I18n::_t('Couldn\'t resolve the host specified in the URL.'),
                7  => I18n::_t('Failed to connect to the remote host.'),
                8  => I18n::_t('The server sent a strange reply to a FTP-related command.'),
                9  => I18n::_t('Access denied to the resource on the server.'),
                18 => I18n::_t('The file transfer was only partially completed.'),
                22 => I18n::_t('The HTTP server returned an error code.'),
                23 => I18n::_t('An error occurred when writing received data to a local file.'),
                25 => I18n::_t('The upload failed.'),
                27 => I18n::_t('A memory allocation request failed.'),
                28 => I18n::_t('The operation timed out.'),
                35 => I18n::_t('A problem occurred while establishing an SSL/TLS connection.'),
                37 => I18n::_t('The FTP server couldn\'t retrieve the specified file.'),
                47 => I18n::_t('Too many redirects were followed during the request.'),
                51 => I18n::_t('The remote server\'s SSL certificate or SSH md5 fingerprint was deemed not OK.'),
                52 => I18n::_t('The server returned nothing during the request.'),
                56 => I18n::_t('Failure with receiving network data.'),
                58 => I18n::_t('Problem with the local client certificate.'),
                63 => I18n::_t('The requested file size exceeds the allowed limits.'),
                67 => I18n::_t('Failure with sending network data.'),
                94 => I18n::_t('The last received HTTP, FTP, or SMTP response code.'),
                95 => I18n::_t('An SSL cipher problem occurred.'),
                99 => I18n::_t('Something went wrong when uploading the file.'),
            ];

            $this->errors[] = $errors[ $error ] ?? $errors[99];
        }

        curl_close($ch);
        fclose($file);

        return new self($filepath);
    }

    public function chmod(int $mode = 0755): File
    {
        if ($this->exists && ! chmod($this->path, $mode)) {
            $this->errors[] = I18n::_t('Failed to update file access rights');
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
                $this->errors[] = I18n::_t('Failed to open the file for writing.');
            }
        } else {
            $this->errors[] = I18n::_t('The file does not exist or you don\'t have permission to edit the file.');
        }

        return $this;
    }

    public function copy(string $to): File
    {
        if ($this->exists) {
            $newPath = sprintf('%s/%s.%s', $to, $this->filename, $this->extension);
            $dirPath = dirname($newPath);

            if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true)) {
                $this->errors[] = I18n::_t('Failed to create the directory.');
            } elseif (!is_file($newPath) && !copy($this->path, $newPath)) {
                $this->errors[] = is_file($newPath)
                    ? I18n::_t('File already exists at the destination.')
                    : I18n::_t('Failed to copy the file.');
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
                $this->errors[] = I18n::_f('Failed to create directory "%s".', $directory);
                return $this;
            }

            if (rename($this->path, $filepath)) {
                return new self($filepath);
            }

            $this->errors[] = I18n::_t('Failed to move the file.');
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
            $this->errors[] = I18n::_t('File not exists at the destination.');
        } else {
            $newPath = $this->dirpath . DIRECTORY_SEPARATOR . $this->sanitizeName($name);
            if (!rename($this->path, $newPath)) {
                $this->errors[] = I18n::_f('Failed to rename file to "%s".', $newPath);
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
                $this->errors[] = I18n::_f('Failed to update the timestamps for ":filePath".', $this->path);
            }

            $this->modified = (new DateTime())->setTimestamp($time)->format('Y-m-d H:i:s');
        }
        return $this;
    }

    public function upload(array $file): File
    {
        $maxFileSize = $this->getMaxUploadSizeInBytes();
        $mimeTypes   = (new MimeType())->typesList;
        $mimes       = implode(',', array_values($mimeTypes));
        $extensions  = str_replace('|', ',', implode(',', array_keys($mimeTypes)));

        /**
         * Check file validation.
         *
         * @since 2025.1
         */
        $validator = ( new Validator(
            $file,
            [
                'type'     => 'type:' . $mimes,
                'error'    => 'equal:0',
                'tmp_name' => 'required',
                'size'     => 'min:0|max:' . $maxFileSize,
                'name'     => 'extension:' . $extensions,
            ]
        ) )->extend(
            'type:type',
            I18n::_t('Sorry, you are not allowed to upload this file type.')
        )->extend(
            'error:equal',
            I18n::_t('An error occurred while uploading the file, please try again.'),
            function ($validator, $value, $comparison_value) {
                $value            = intval($value);
                $comparison_value = intval($comparison_value);

                // Courtesy of php.net, the strings that describe the error indicated in $_FILES[{form field}]['error'].
                $uploadErrorMessages = [
                    false,
                    I18n::_f('The uploaded file exceeds the :maxUploadFileSize.', 'upload_max_filesize'),
                    I18n::_f('The uploaded file exceeds the :maxFileSize directive.', 'MAX_FILE_SIZE'),
                    I18n::_t('The uploaded file was only partially uploaded.'),
                    I18n::_t('No file was uploaded.'),
                    '',
                    I18n::_t('Missing a temporary folder.'),
                    I18n::_t('Failed to write file to disk.'),
                    I18n::_t('File upload stopped by extension.'),
                ];

                $validator->messages['error:equal'] = $uploadErrorMessages[ $value ];

                return $value === $comparison_value;
            }
        )->extend(
            'size:min',
            I18n::_t('File is empty. Please upload something more substantial.')
        )->extend(
            'size:max',
            I18n::_f('The maximum file size is :maxFileSize.', self::humanize($maxFileSize))
        )->apply();

        /**
         * If the incoming data has been checked for validity, continue uploading
         *
         * @since 2025.1
         */
        if ($validator instanceof Validator) {
            $this->errors[] = $validator;
        }

        $basename = $this->sanitizeName($file['name'] ?? '');
        $filepath = sprintf('%s%s', $targetDir, $basename);
        if (!$basename) {
            $this->errors[] = I18n::_t('File name must not contain illegal characters and must not be empty.');
        }

        /**
         * Check that the uploaded file is unique.
         *
         * @since 2025.1
         */
        if (is_file($filepath)) {
            $filename  = pathinfo($basename, PATHINFO_FILENAME);
            $extension = pathinfo($basename, PATHINFO_EXTENSION);

            // check that the existing and uploaded file are the same
            if (hash_file('md5', $filepath) === hash_file('md5', $file['tmp_name']) && unlink($file['tmp_name'])) {
                $this->errors[] = I18n::_t('File already exists.');
            } else {
                // make sure that the file name in the folder is unique
                $suffix = 1;
                while (is_file($filepath)) {
                    $suffix++;
                    $filepath = sprintf('%s%s-%d.%s', $targetDir, $filename, $suffix, $extension);
                }
            }
        }

        /**
         * Create new file.
         *
         * @since 2025.1
         */
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $uploaded = move_uploaded_file($file['tmp_name'], $filepath);
        if ($uploaded) {
            return new self($filepath);
        } else {
            $this->errors[] = I18n::_t('Something went wrong, upload is failed.');
        }

        return $this;
    }

    public function write(mixed $content, bool $after = true): File
    {
        $this->createFile();

        if (!is_writable($this->path)) {
            $this->errors[] = I18n::_f("The file is not writable: ':path'", $this->path);
            return $this;
        }

        $fp = fopen($this->path, $after ? 'a' : 'w');
        if (!$fp) {
            $this->errors[] = I18n::_f("The file cannot be opened: ':path'", $this->path);
        } else {
            if (fwrite($fp, $content) === false) {
                $this->errors[] = I18n::_f("It is not possible to write to the file: ':path'", $this->path);
            }
            fclose($fp);

            // file is changed, update data about file, e.g.: "size" etc.
            return new self($this->path);
        }

        return $this;
    }
}
