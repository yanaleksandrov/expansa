<?php

namespace Grafema;

/**
 * The File class provides a convenient and easy-to-use API for working with files.
 * It supports working with various types: CSV, SVG and images of different formats.
 *
 * You can perform a wide range of operations: reading and writing to a file,
 * downloading and capturing, moving and copying files, and much more.
 *
 * @since 2025.1
 */
final class File extends File\Data {

	/**
	 * Write content to file.
	 *
	 * @param mixed $content
	 * @param bool $after
	 * @return File
	 *
	 * @since 2025.1
	 */
	public function write( mixed $content, bool $after = true ): File {
		$this->createFile();

		$file = $this;
		$fp   = fopen( $this->filepath, $after ? 'a' : 'w' );
		if ( ! $fp ) {
			$file->errors[] = new Error( 'file-manipulation', I18n::_f( "The file cannot be opened: ':filepath'", $this->filepath ) );
		} else {
			$file = new self( $this->filepath );
			// writing $content to open file
			if ( fwrite( $fp, $content ) === false ) {
				$file->errors[] = new Error( 'file-manipulation', I18n::_f( "It is not possible to write to a file: ':filepath'", $this->filepath ) );
			}
		}
		fclose( $fp );

		return $file;
	}

	/**
	 * Rewrite some strings in file.
	 *
	 * @param array $content
	 * @return File
	 *
	 * @since 2025.1
	 */
	public function rewrite( array $content ): File {
		if ( $this->exists && is_readable( $this->filepath ) && filesize( $this->filepath ) > 0 ) {
			$file_content = file_get_contents( $this->filepath );

			foreach ( $content as $field => $value ) {
				$file_content = str_replace( $field, $value, $file_content );
			}

			file_put_contents( $this->filepath, $file_content );
		}
		return $this;
	}

	/**
	 * Move file to directory.
	 *
	 * @param string $dirpath
	 * @return File
	 *
	 * @since 2025.1
	 */
	public function relocate( string $dirpath ): File {
		$directory = ( new Dir( $dirpath ) )->create()->getPath();
		if ( is_dir( $directory ) ) {
			$this->dirpath = $directory;
		}
		rename( $this->filepath, $this->dirpath . DIRECTORY_SEPARATOR . $this->filename );

		return $this;
	}

	/**
	 * Copy file.
	 *
	 * @param string $new_name
	 * @param string $postfix
	 * @return File
	 *
	 * @since 2025.1
	 */
	public function copy( string $new_name = '', string $postfix = '' ): File {
		if ( $this->exists ) {
			if ( ! empty( $new_name ) ) {
				$filename = $new_name;
			}

			// TODO: add a check that the name of the new file was not the same as the old one
			$new_filepath = sprintf( '%s/%s.%s', $this->dirpath, $this->filename . $postfix, $this->extension );
			if ( ! is_file( $new_filepath ) ) {
				copy( $this->filepath, $new_filepath );
			}

			// rewrite path to file
			if ( is_file( $new_filepath ) ) {
				$this->filepath = $new_filepath;
			}
		}
		return $this;
	}

	/**
	 * Delete files.
	 *
	 * @since 2025.1
	 */
	public function delete(): File {
		if ( $this->exists ) {
			unlink( $this->filepath );
		}
		return $this;
	}

	/**
	 * Upload file to server.
	 *
	 * @param array $file
	 * @return File data about current upload or errors list
	 *
	 * @since 2025.1
	 */
	public function upload( array $file ): File {
		$maxFileSize = parent::getMaxUploadSizeInBytes();
		$mimeTypes   = parent::getAllowedMimeTypes();
		$mimes       = implode( ',', array_values( $mimeTypes ) );
		$extensions  = str_replace( '|', ',', implode( ',', array_keys( $mimeTypes ) ) );

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
			I18n::_t( 'Sorry, you are not allowed to upload this file type.' )
		)->extend(
			'error:equal',
			I18n::_t( 'An error occurred while uploading the file, please try again.' ),
			function( $validator, $value, $comparison_value ) {
				$value            = intval( $value );
				$comparison_value = intval( $comparison_value );

				// Courtesy of php.net, the strings that describe the error indicated in $_FILES[{form field}]['error'].
				$uploadErrorMessages = [
					false,
					I18n::_f( 'The uploaded file exceeds the %1$s directive in %2$s.', 'upload_max_filesize', 'php.ini' ),
					I18n::_f( 'The uploaded file exceeds the %s directive that was specified in the HTML form.', 'MAX_FILE_SIZE' ),
					I18n::_t( 'The uploaded file was only partially uploaded.' ),
					I18n::_t( 'No file was uploaded.' ),
					'',
					I18n::_t( 'Missing a temporary folder.' ),
					I18n::_t( 'Failed to write file to disk.' ),
					I18n::_t( 'File upload stopped by extension.' ),
				];

				$validator->messages['error:equal'] = $uploadErrorMessages[ $value ];

				return $value === $comparison_value;
			}
		)->extend(
			'size:min',
			I18n::_t( 'File is empty. Please upload something more substantial.' )
		)->extend(
			'size:max',
			I18n::_f( 'The maximum file size is :fileMaxSize.', self::humanize( $maxFileSize, 'Mb' ) )
		)->apply();

		/**
		 * If the incoming data has been checked for validity, continue uploading
		 *
		 * @since 2025.1
		 */
		if ( $validator instanceof Validator ) {
			return new Error( 'file-upload', $validator );
		}

		$basename = Sanitizer::filename( $file['name'] ?? '' );
		$filepath = sprintf( '%s%s', $targetDir, $basename );
		if ( empty( $basename ) ) {
			return new Error( 'file-upload', I18n::_t( 'File name must not contain illegal characters and must not be empty.' ) );
		}

		/**
		 * Check that the uploaded file is unique.
		 *
		 * @since 2025.1
		 */
		if ( is_file( $filepath ) ) {
			$filename  = pathinfo( $basename, PATHINFO_FILENAME );
			$extension = pathinfo( $basename, PATHINFO_EXTENSION );

			// check that the existing and uploaded file are the same
			if ( hash_file( 'md5', $filepath ) === hash_file( 'md5', $file['tmp_name'] ) ) {
				unlink( $file['tmp_name'] );

				return new Error( 'file-upload', I18n::_t( 'File already exists.' ) );
			} else {
				// make sure that the file name in the folder is unique
				$suffix = 1;
				while ( is_file( $filepath ) ) {
					$suffix++;
					$filepath = sprintf( '%s%s-%d.%s', $targetDir, $filename, $suffix, $extension );
				}
			}
		}

		/**
		 * Create new file.
		 *
		 * @since 2025.1
		 */
		if ( ! isset( $_file ) ) {
			if ( ! is_dir( $targetDir ) ) {
				mkdir( $targetDir, 0755, true );
			}

			$uploaded = move_uploaded_file( $file['tmp_name'], $filepath );
			if ( $uploaded ) {
				$_file = new self( $filepath );
			} else {
				return new Error( 'file-upload', I18n::_t( 'Something went wrong, upload is failed.' ) );
			}
		}

		/**
		 * Filters the data array for the uploaded file.
		 *
		 * @since 2025.1
		 */
		return Hook::call( 'grafema_file_uploaded', $_file );
	}

	/**
	 * Upload file via URL.
	 *
	 * @param string $url
	 * @return File
	 *
	 * @since 2025.1
	 */
	public function grab( string $url ): File {
		$url = Sanitizer::url( $url );
		if ( empty( $url ) ) {
			return new Error( 'file-grab', I18n::_t( 'File URL is not valid.' ) );
		}

		$basename   = basename( $url );
		$extension  = pathinfo( $url, PATHINFO_EXTENSION );
		$filepath   = sprintf( '%s%s', $targetDir, $basename );

		if ( empty( $extension ) ) {
			return new Error( 'file-grab', I18n::_t( 'The file cannot be grabbed because it does not contain an extension.' ) );
		}

		if ( ! is_dir( $targetDir ) ) {
			mkdir( $targetDir, 0755, true );
		}

		$ch   = curl_init( $url );
		$file = fopen( $filepath, 'wb' );

		curl_setopt( $ch, CURLOPT_FILE, $file );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		curl_exec( $ch );

		// check errors
		$error = curl_errno( $ch );
		if ( $error ) {
			$errors = [
				1  => I18n::_t( 'The URL you passed to the libcurl function uses an unsupported protocol.' ),
				3  => I18n::_t( 'The URL you provided is not properly formatted.' ),
				6  => I18n::_t( 'Couldn\'t resolve the host specified in the URL.' ),
				7  => I18n::_t( 'Failed to connect to the remote host.' ),
				8  => I18n::_t( 'The server sent a strange reply to a FTP-related command.' ),
				9  => I18n::_t( 'Access denied to the resource on the server.' ),
				18 => I18n::_t( 'The file transfer was only partially completed.' ),
				22 => I18n::_t( 'The HTTP server returned an error code.' ),
				23 => I18n::_t( 'An error occurred when writing received data to a local file.' ),
				25 => I18n::_t( 'The upload failed.' ),
				27 => I18n::_t( 'A memory allocation request failed.' ),
				28 => I18n::_t( 'The operation timed out.' ),
				35 => I18n::_t( 'A problem occurred while establishing an SSL/TLS connection.' ),
				37 => I18n::_t( 'The FTP server couldn\'t retrieve the specified file.' ),
				47 => I18n::_t( 'Too many redirects were followed during the request.' ),
				51 => I18n::_t( 'The remote server\'s SSL certificate or SSH md5 fingerprint was deemed not OK.' ),
				52 => I18n::_t( 'The server returned nothing during the request.' ),
				56 => I18n::_t( 'Failure with receiving network data.' ),
				58 => I18n::_t( 'Problem with the local client certificate.' ),
				63 => I18n::_t( 'The requested file size exceeds the allowed limits.' ),
				67 => I18n::_t( 'Failure with sending network data.' ),
				94 => I18n::_t( 'The last received HTTP, FTP, or SMTP response code.' ),
				95 => I18n::_t( 'An SSL cipher problem occurred.' ),
				99 => I18n::_t( 'Something went wrong when uploading the file.' ),
			];

			return new Error( 'file-upload', $errors[ $error ] ?? $errors[99] );
		}

		curl_close( $ch );
		fclose( $file );

		$_file = new self( $filepath );
		print_r( $_file );

		/**
		 * Filters the data array for the grabbed file.
		 *
		 * @since 2025.1
		 */
		return Hook::call( 'grafema_file_grabbed', $_file );
	}

	/**
	 * Download file.
	 *
	 * @since 2025.1
	 */
	public function download(): void {
		if ( $this->exists ) {
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $this->basename . '"' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . $this->size );

			// read the file and output it to the browser
			ob_clean();
			flush();
			readfile( $this->filepath );
			exit;
		}
	}

	/**
	 * Read file content.
	 *
	 * @return string
	 *
	 * @since 2025.1
	 */
	public function read(): string {
		return file_get_contents( $this->filepath );
	}

	/**
	 * Sanitizer & normalize file name: remove special chars & spaces.
	 * Check for a file with the same name, add a prefix until we find a free name.
	 *
	 * @param string $filename
	 * @return File
	 *
	 * @since 2025.1
	 */
	public function setName( string $filename ): File {
		// remove not allowed symbols & letters
		$filename = mb_convert_encoding( htmlspecialchars( mb_strtolower( $this->filename ) ), 'UTF-8' );
		$formats  = mb_convert_encoding(
			'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª',
			'ISO-8859-1',
			'UTF-8'
		);
		$replace = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyrr                                 ';

		// remove whitespaces
		$filename = str_replace(
			['-----', '----', '---', '--'],
			'-',
			str_replace( ' ', '-', trim( strtr( $filename, $formats, $replace ) ) )
		);

		// find existing files with same name & add prefix
		$filename = sprintf( '%s.%s', $filename, $this->extension );
		$filename = str_replace( ' ', '-', $filename );
		$filepath = sprintf( '%s%s', $this->dirpath, $filename );
		if ( is_file( $filepath ) ) {
			$i      = 1;
			$prefix = '-' . $i;

			while ( is_file( $this->dirpath . $filename . $prefix . '.' . $this->extension ) ) {
				$prefix = '-' . ++$i;
			}
			$filename = sprintf( '%s%s.%s', $filename, $prefix, $this->extension );
		}

		rename( $this->filepath, $this->dirpath . DIRECTORY_SEPARATOR . $filename );

		return $this;
	}

	/**
	 * Change the mode of a file.
	 *
	 * @param int $permission
	 * @return File
	 *
	 * @since 2025.1
	 */
	public function setPermission( int $permission ): File {
		if ( ! chmod( $this->filepath, $permission ) ) {
			$this->errors[] = new Error( 'file-manipulations', I18n::_t( 'Failed to update file access rights' ) );
		}
		return $this;
	}
}
