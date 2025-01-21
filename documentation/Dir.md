# Directory

A PHP class that represents a directory on a filesystem.

With it, you can:

- Get the files in directory, including `glob`-like patterns
- Obtain the total size
- Delete it recursively

## Creating an Instance

Simply path the full path to the directory to the constructor:

```php
$directory = new Dir( '/path/to/dir' );
```

## Checking it Exists

The directory needn't exist when you create an instance; use the `exists()` method to check that it does.

```php
if ( $directory->exists ) {
	// do something
}
```	

## Checking it's a Directory

It might also a good idea to check that the path you provided is actually a directory, not a file.

```php
if ( ! $directory->isDirectory() ) {
	// looks like it's a file!
}
```	

## Creating it

To create a directory, pass the path to the constructor and then call `create()`.

```php
$directory = new Directory( '/path/to/new/directory' );
$directory->create();
```

By default, the mode is set to `0777`, but you can override this by passing it as an argument:

```php
$directory->create( 0755 );
```

Alternatively, you can use `createIfDoesNotExist()` which, as the name suggests, will create it if it does not already exist.

> This action is recursive; i.e. it will create any necessary parent directories.

## Listing Files

Call `getFiles()` to get a list of the files in the directory.

```php
$files = $directory->getFiles();
```

This will return an array with the full path to the files in the directory, excluding directories.

To include the directories:

```php
$files = $directory->getFiles( '*' );
```

To get all the files in a directory including any subdirectories, pass `true` as the second argument:

```php
$files = $directory->getFiles( '*.txt', true );
```

To include directories, pass `true` as the second argument.

## Getting the Total Size

To get the total size, in bytes, of a directory:

```php
$size = $directory->getSize();
```

## Deleting a Directory

> Use with caution!

To delete a directory, its contents, and it's subdirectories, simply call `delete()`.
