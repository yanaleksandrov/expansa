<?php
namespace Grafema\File;

use Grafema\Url;

class Data {

	use Traits;

	/**
	 * @param string $filepath
	 *
	 * @since 2025.1
	 */
	public function __construct( string $filepath ) {
		$this->filepath = $filepath;
		$this->exists   = $this->isExists();

		if ( ! $this->exists ) {
			$this->createFile();
		}

		$this->filename      = $this->getFileName();
		$this->basename      = $this->getBaseName();
		$this->dirname       = $this->getDirName();
		$this->dirpath       = $this->getDirPath();
		$this->hash          = $this->getHash();
		$this->mime          = $this->getMime();
		$this->type          = $this->getType();
		$this->url           = $this->getUrl();
		$this->extension     = $this->getExtension();
		$this->modified      = $this->getModifiedTime();
		$this->size          = $this->getSize();
		$this->sizeKb        = $this->reformat( $this->size, 'Kb' );
		$this->sizeMb        = $this->reformat( $this->size, 'Mb' );
		$this->sizeGb        = $this->reformat( $this->size, 'Gb' );
		$this->sizeHumanized = $this->humanize( $this->size );
		$this->permission    = $this->getPermission();
		$this->errors        = [];
	}

	/**
	 * Get the file name from a path.
	 *
	 * @since 2025.1
	 */
	protected function getFileName(): string {
		return $this->exists ? pathinfo( $this->filepath, PATHINFO_FILENAME ) : '';
	}

	/**
	 * Get the base name from a path.
	 *
	 * @since 2025.1
	 */
	protected function getBaseName(): string {
		return $this->exists ? pathinfo( $this->filepath, PATHINFO_BASENAME ) : '';
	}

	/**
	 * Get the directory name from a path.
	 *
	 * @since 2025.1
	 */
	protected function getDirName(): string {
		return $this->exists ? basename( pathinfo( $this->filepath, PATHINFO_DIRNAME ) ) : '';
	}

	/**
	 * Get the directory name from a path.
	 *
	 * @since 2025.1
	 */
	protected function getDirPath(): string {
		return $this->exists ? pathinfo( $this->filepath, PATHINFO_DIRNAME ) : '';
	}

	/**
	 * Create unique hash of the file.
	 *
	 * @return string
	 *
	 * @since 2025.1
	 */
	protected function getHash(): string {
		return $this->exists ? hash_file( 'md5', $this->filepath ) : '';
	}

	/**
	 * Get the MIME type of file.
	 *
	 * @since 2025.1
	 */
	protected function getMime(): string {
		return $this->exists ? finfo_file( finfo_open( FILEINFO_MIME_TYPE ), $this->filepath ) : '';
	}

	/**
	 * Get the type of file.
	 *
	 * @since 2025.1
	 */
	protected function getType(): string {
		return $this->exists ? filetype( $this->filepath ) : '';
	}

	/**
	 * Get the type of file.
	 *
	 * @since 2025.1
	 */
	protected function getUrl(): string {
		return Url::site( str_replace( GRFM_PATH, '', $this->filepath ) );
	}

	/**
	 * Get the extension of a file.
	 *
	 * @since 2025.1
	 */
	protected function getExtension(): string {
		return $this->exists ? pathinfo( $this->filepath, PATHINFO_EXTENSION ) : '';
	}

	/**
	 * Get the file's last modification time.
	 *
	 * @since 2025.1
	 */
	protected function getModifiedTime(): int {
		return $this->exists ? filemtime( $this->filepath ) : 0;
	}

	/**
	 * Get the size of a file.
	 *
	 * @since 2025.1
	 */
	protected function getSize(): int {
		return $this->exists ? filesize( $this->filepath ) : 0;
	}

	/**
	 * Get the file permission.
	 *
	 * @since 2025.1
	 */
	protected function getPermission(): int {
		return $this->exists ? substr( sprintf( '%o', fileperms( $this->filepath ) ), -4 ) : 0;
	}

	/**
	 * Determine if a file exists.
	 *
	 * @since 2025.1
	 */
	protected function isExists(): bool {
		return file_exists( $this->filepath );
	}

	/**
	 * Create file if not exists.
	 *
	 * @since 2025.1
	 */
	protected function createFile() {
		if ( ! $this->exists ) {
			$dirpath = dirname( $this->filepath );

			// create directory if not exists
			if ( ! is_dir( $dirpath ) ) {
				mkdir( $dirpath, 0755, true );
			}

			// create file if not exists
			touch( $this->filepath );
		}
	}

	/**
	 * Convert bytes to human-readable file size.
	 *
	 * @param string|int $bytes
	 * @param string $format
	 * @return float
	 *
	 * @since 2025.1
	 */
	protected static function reformat( string|int $bytes, string $format = '' ): float {
		$format  = ucfirst( strtolower( $format ) );
		$formats = [
			'Kb' => 1,
			'Mb' => 2,
			'Gb' => 3,
			'Tb' => 4,
		];

		$result = $bytes / pow( 1024, $formats[ $format ] ?? 0 );
		$result = number_format( $result, 2, '.' );
		$result = rtrim( $result, '0' );
		if ( substr( $result, -1 ) === '.' ) {
			$result = rtrim( $result, '.' );
		}

		return round( floatval( $result ), 2 );
	}

	/**
	 * Convert bytes to human-readable file size.
	 *
	 * @param string|int $bytes
	 * @param string $format
	 * @return string
	 *
	 * @since 2025.1
	 */
	protected static function humanize( string|int $bytes, string $format = '' ): string {
		$format  = ucfirst( strtolower( $format ) );
		$i       = floor( log( $bytes ) / log( 1024 ) );
		$sizes   = [ 'b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb' ];

		$result = 0;
		if ( $i > 0 ) {
			if ( $format && in_array( $format, $sizes, true ) ) {
				$i = array_search( $format, $sizes );
			}

			$result = round( $bytes / pow(1024, $i ), 2 );
			$result = number_format( $result, 2 );
			$result = rtrim( $result, '0' );
			if ( substr( $result, -1 ) === '.' ) {
				$result = rtrim( $result, '.' );
			}
		}

		return trim( sprintf( '%s %s', $result, $sizes[ $i ] ) );
	}

	/**
	 * Retrieves the maximum upload file size in bytes.
	 *
	 * @return int The size in bytes.
	 *
	 * @since 2025.1
	 */
	protected static function getMaxUploadSizeInBytes(): int {
		$maxUploadSize = trim( ini_get( 'upload_max_filesize' ) );
		$last          = strtolower( $maxUploadSize[ strlen( $maxUploadSize ) - 1 ] );
		$maxUploadSize = intval( $maxUploadSize );

		switch ( $last ) {
			case 'g':
				$maxUploadSize *= 1024;
			case 'm':
				$maxUploadSize *= 1024;
			case 'k':
				$maxUploadSize *= 1024;
		}

		return $maxUploadSize;
	}
}
