<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Exceptions;

use UnexpectedValueException;

/**
 * Thrown when Disk::upload() or Disk::grab() rejects the file: PHP upload error, empty or too big file,
 * not allowed extension or invalid URL. The message is safe to show to the user.
 *
 * @package Expansa\Filesystem\Exceptions
 */
final class UploadRejected extends UnexpectedValueException {}
