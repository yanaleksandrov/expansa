<?php

declare(strict_types=1);

use Expansa\Filesystem\Directory;
use Expansa\Filesystem\Disk;
use Expansa\Filesystem\Exceptions\OperationFailed;
use Expansa\Filesystem\Exceptions\UploadRejected;
use Expansa\Filesystem\File;

// run: php tests/Filesystem.php
require_once __DIR__ . '/bootstrap.php';

$root = sys_get_temp_dir() . '/expansa-fs-' . getmypid();
$disk = new Disk();

$file = $disk->file("$root/a/note.txt");
check('a missing file does not exist', ! $file->exists && $file->bytes === 0 && $file->read() === '' && $file->mime === '');
check('path parts come from the path', $file->basename === 'note.txt' && $file->filename === 'note' && $file->extension === 'txt' && $file->dirname === 'a');

$file->write('hello');
check('write() creates the file with its directory', $file->exists && $file->read() === 'hello');

$file->write(' world');
check('write() appends by default and metadata follows the disk', $file->read() === 'hello world' && $file->bytes === 11 && $file->size === '11 b');

$file->write('{name}', append: false)->replace(['{name}' => 'shop']);
check('write(append: false) overwrites and replace() swaps substrings', $file->read() === 'shop');

$copy = $file->copy('copy');
check('copy() returns the copy next to the file', $copy instanceof File && $copy->path === "$root/a/copy.txt" && $copy->read() === 'shop');
check('copy() over an existing file throws', throws(fn () => $file->copy('copy'), OperationFailed::class));

$copy->rename('Ünïcode Name');
check('rename() sanitizes the name and keeps the extension', $copy->basename === 'unicode-name.txt' && is_file("$root/a/unicode-name.txt"));

$copy->move("$root/b/");
check('move() keeps the name in the new directory', $copy->path === "$root/b/unicode-name.txt" && $copy->exists);

$disk->file("$root/b/note.txt")->write('x');
$moved = $disk->file("$root/a/note.txt")->move("$root/b");
check('move() picks a free name on a conflict', $moved->basename === 'note-2.txt');

$empty = $disk->file("$root/a/empty.txt")->write('abc')->clean();
check('clean() empties a file', $empty->exists && $empty->bytes === 0);

$dir = $disk->dir("$root/b");
check('files() lists matching files sorted', array_map('basename', $dir->files('*.txt')) === ['note-2.txt', 'note.txt', 'unicode-name.txt']);
check('bytes of a directory sums its files', $dir->bytes === 4 + 1 + 4);

$disk->dir("$root/b/c/d")->create();
check('directories() goes down to the depth', count($dir->directories()) === 1 && count($dir->directories(1)) === 2);
check('tree() nests subdirectories', $dir->tree() === ['b' => ['c' => ['d' => []]]]);

$dirCopy = $dir->copy('e');
check('a directory copy has the same contents', $dirCopy instanceof Directory && is_file("$root/e/note.txt") && is_dir("$root/e/c/d"));

$dir->clean();
check('clean() empties a directory and keeps it', $dir->exists && $dir->files() === []);

check('delete() removes a directory with its contents', $dirCopy->delete() && ! is_dir("$root/e"));
check('delete() of a missing entry is false', ! $disk->file("$root/none.txt")->delete() && ! $disk->dir("$root/none")->delete());

check('upload() rejects a failed upload', throws(fn () => $disk->upload(['error' => UPLOAD_ERR_PARTIAL], $root), UploadRejected::class));
check('upload() rejects a file that was not uploaded', throws(fn () => $disk->upload(['name' => 'a.txt', 'tmp_name' => "$root/a/note.txt", 'error' => 0, 'size' => 1], $root), UploadRejected::class));
check('grab() rejects a not HTTP URL', throws(fn () => $disk->grab('file:///etc/passwd', $root), UploadRejected::class));

$disk->dir($root)->delete();
check('the scratch directory is removed', ! is_dir($root));

exit($failures > 0 ? 1 : 0);
