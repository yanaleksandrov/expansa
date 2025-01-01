<?php

declare(strict_types=1);

namespace Grafema\Support;

use Grafema\Support\Exception\PathfinderException;

trait Pathfinder
{
	/**
	 * Perform a glob on the directory.
	 *
	 * @param string $path
	 * @param int $depth
	 * @return array
	 * @throws PathfinderException
	 */
	public function discover( string $path, int $depth = 99 ): array
	{
		if (!is_dir($path)) {
			throw new PathfinderException("The path '$path' is not a directory");
		}

		$search = function ( $path, int $currentDepth ) use ( &$search, $depth ) {
			$flags = GLOB_BRACE | GLOB_NOSORT | GLOB_MARK | GLOB_ERR;

			$files = glob( $path . DIRECTORY_SEPARATOR . '*.php', $flags ) ?? [];
			if ( $currentDepth < $depth ) {
				$folders = glob( $path . '/*', GLOB_ONLYDIR | GLOB_NOSORT ) ?? [];

				foreach ( $folders as $folder ) {
					$files = array_merge( $files, $search( $folder, $currentDepth + 1 ) );
				}
			}

			return $files;
		};

		$files = $search( $path, 0 );

		sort( $files );

		return $files;
	}
}
