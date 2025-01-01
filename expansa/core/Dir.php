<?php
namespace Expansa;

/**
 * Class Directory.
 *
 * A class that represents a directory on a file system.
 */
final class Dir {

	/**
	 * The path to this directory.
	 */
	public string $path;

	/**
	 * Permissions of directory.
	 */
	public ?int $permissions;

	/**
	 * Check directory is exists.
	 */
	public bool $exists = false;

	/**
	 * Directory constructor.
	 *
	 * @param string $path
	 */
	public function __construct( string $path )
	{
		$this->path   = Sanitizer::path( $path );
		$this->exists = file_exists( $this->path );
		if ( is_dir( $this->path ) ) {
			$this->permissions = substr( sprintf( '%o', fileperms( $this->path ) ), -4 );
		}
	}

	/**
	 * Create this directory, if it does not already exist.
	 *
	 * @param int $mode
	 * @return Dir
	 */
	public function create( int $mode = 0755 ): Dir
	{
		if ( ! is_dir( $this->path ) ) {
			mkdir( $this->path, $mode, true );
		}

		return $this;
	}

	/**
	 * Recursively clear the folder from the contents.
	 *
	 * @param string $filepath
	 * @return Dir
	 */
	public function clear( string $filepath = '' ): Dir
	{
		$path = $filepath ?: $this->path;
		if ( is_dir( $path ) ) {
			$paths = glob( $path . '/*' );

			foreach ( $paths as $filepath ) {
				if ( file_exists( $filepath ) ) {
					unlink( $filepath );
				} elseif ( is_dir( $filepath ) ) {
					$this->clear( $filepath );
					rmdir( $filepath );
				}
			}
		}

		return $this;
	}

	/**
	 * Recursively delete a directory.
	 */
	public function delete(): Dir
	{
		if ( is_dir( $this->path ) ) {
			$this->clear();
			if ( rmdir( $this->path ) ) {
				$this->path = '';
			}
		}

		return $this;
	}

	/**
	 * Rename directory.
	 *
	 * @param string $dirname
	 * @return Dir
	 */
	public function rename( string $dirname ): Dir
	{
		if ( is_dir( $this->path ) ) {
			$dirpath = sprintf( '%s%s%s', dirname( $this->path ), DIRECTORY_SEPARATOR, $dirname );
			if ( ! is_dir( $dirpath ) && rename( $this->path, $dirpath ) ) {
				$this->path = $dirpath;
			}
		}

		return $this;
	}

	/**
	 * @param int $depth
	 * @return array
	 */
	public function getFolders( int $depth = 0 ): array
	{
		$search = function ( string $path, int $current_depth ) use ( &$search, $depth ) {
			$flags   = GLOB_ONLYDIR | GLOB_NOSORT | GLOB_ERR;
			$folders = glob( Sanitizer::path( $path . '/*' ), $flags );

			if ( $current_depth < $depth ) {
				$subfolders = glob( $path . '/*', GLOB_ONLYDIR | GLOB_NOSORT );

				foreach ( $subfolders as $folder ) {
					$folders = array_merge( $folders, $search( $folder, $current_depth + 1 ) );
				}
			}

			return $folders;
		};

		$folders = $search( $this->path, 0 );

		sort( $folders );

		return $folders;
	}

	/**
	 * Get folders tree.
	 *
	 * @return array
	 */
	public function getFoldersTree(): array {
		$search = function ( string $path ) use ( &$search ): array {
			$path  = rtrim( $path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
			$items = glob( $path . '*', GLOB_NOSORT );

			$tree = [];
			foreach ( $items as $item ) {
				if ( is_dir( $item ) ) {
					$tree[basename( $item )] = $search( $item );
				}
			}
			return $tree;
		};

		$findFolders = $search( $this->path );

		ksort( $findFolders );

		return [ basename( $this->path ) => $findFolders ];
	}

	/**
	 * Perform a glob on the directory.
	 *
	 * @param string $pattern
	 * @param int $depth
	 * @return array
	 */
	public function getFiles( string $pattern = '*', int $depth = 0 ): array
	{
		$search = function ( $path, int $current_depth ) use ( &$search, $pattern, $depth ) {
			$flags = GLOB_BRACE | GLOB_NOSORT | GLOB_MARK | GLOB_ERR;
			$files = glob( Sanitizer::path( $path . '/' . $pattern ), $flags );

			if ( $current_depth < $depth ) {
				$folders = glob( $path . '/*', GLOB_ONLYDIR | GLOB_NOSORT );

				foreach ( $folders as $folder ) {
					$files = array_merge( $files, $search( $folder, $current_depth + 1 ) );
				}
			}

			return $files ?: [];
		};

		$files = $search( $this->path, 0 );

		sort( $files );

		return $files;
	}

	/**
	 * Get the total size of the directory.
	 */
	public function getSize(): int
	{
		$files = $this->getFiles( '*', 9999 );
		$size  = 0;

		foreach ( $files as $file ) {
			$size += filesize( $file );
		}

		return $size;
	}

	/**
	 * Get path to directory.
	 */
	public function getPath(): string
	{
		return $this->path;
	}
}
