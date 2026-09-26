<?php

declare(strict_types=1);

namespace Expansa\Filesystem;

use Expansa\Filesystem\Exceptions\OperationFailed;
use Expansa\Filesystem\Exceptions\UploadRejected;
use Expansa\Filesystem\Internal\Name;

/**
 * Entry point of the local filesystem, the Disk facade instance: files, directories and uploads.
 * Uploads are checked only for the PHP upload error, the size limit and an allowed extension,
 * other rules (MIME type, dimensions, quotas) are checked by the caller before the call.
 *
 * @package Expansa\Filesystem
 */
final class Disk
{
    /**
     * Messages of the PHP upload error codes.
     */
    private const array UPLOAD_ERRORS = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension.',
    ];

    public function file(string $path): File
    {
        return new File($path);
    }

    public function dir(string $path): Directory
    {
        return new Directory($path);
    }

    /**
     * Move an uploaded file into a directory under a sanitized unique name.
     *
     * @param array  $file      Item of $_FILES: `name`, `tmp_name`, `error`, `size`.
     * @param string $directory Created if it does not exist.
     * @return File
     * @throws UploadRejected If PHP rejected the upload or the file is empty, too big or of a not allowed type.
     * @throws OperationFailed If the file can not be moved.
     */
    public function upload(array $file, string $directory): File
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new UploadRejected(self::UPLOAD_ERRORS[$error] ?? 'The upload failed.');
        }

        $tmp  = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if (! is_uploaded_file($tmp)) {
            throw new UploadRejected('No file was uploaded.');
        }

        if ($size <= 0) {
            throw new UploadRejected('File is empty. Please upload something more substantial.');
        }

        if ($size > $this->getMaxUploadSize()) {
            throw new UploadRejected('The uploaded file exceeds the upload_max_filesize directive.');
        }

        $path = Name::unique($this->prepare($directory), $this->basename((string) ($file['name'] ?? '')));
        if (! move_uploaded_file($tmp, $path)) {
            throw new OperationFailed('Something went wrong. The upload failed.');
        }

        return new File($path);
    }

    /**
     * Download a file by URL into a directory under a sanitized unique name.
     *
     * @param string $url       HTTP or HTTPS URL.
     * @param string $directory Created if it does not exist.
     * @return File
     * @throws UploadRejected If the URL is invalid or the type is not allowed.
     * @throws OperationFailed If the download failed.
     */
    public function grab(string $url, string $directory): File
    {
        $url = trim($url);
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
            throw new UploadRejected('File URL is not valid.');
        }

        $path   = Name::unique($this->prepare($directory), $this->basename(basename((string) parse_url($url, PHP_URL_PATH))));
        $stream = @fopen($path, 'wb');
        if ($stream === false) {
            throw new OperationFailed("Unable to write to the file $path");
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_FILE           => $stream,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $done  = curl_exec($curl);
        $error = curl_error($curl);
        fclose($stream);

        if ($done === false) {
            unlink($path);
            throw new OperationFailed("Failed to download $url: $error");
        }

        return new File($path);
    }

    /**
     * Get the upload size limit from upload_max_filesize, in bytes.
     *
     * @return int
     */
    public function getMaxUploadSize(): int
    {
        return (int) ini_parse_quantity((string) ini_get('upload_max_filesize'));
    }

    /**
     * Sanitize the name of an uploaded file and check its extension against MimeType.
     *
     * @param string $name
     * @return string
     * @throws UploadRejected
     */
    private function basename(string $name): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed   = str_replace('|', ',', implode(',', array_keys(new MimeType()->typesList)));
        if ($extension === '' || ! in_array($extension, explode(',', $allowed), true)) {
            throw new UploadRejected('Sorry, you are not allowed to upload this file type.');
        }

        return (Name::sanitize(pathinfo($name, PATHINFO_FILENAME)) ?: 'file') . '.' . $extension;
    }

    /**
     * Create the directory if it does not exist.
     *
     * @param string $directory
     * @return string The directory without the trailing slash.
     * @throws OperationFailed
     */
    private function prepare(string $directory): string
    {
        return new Directory($directory)->create()->path;
    }
}
