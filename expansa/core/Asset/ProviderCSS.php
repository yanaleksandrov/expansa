<?php

declare(strict_types=1);

namespace Expansa\Asset;

class ProviderCSS implements ProviderInterface
{
	/**
	 * List of type 'css' style tag.
	 *
	 * @since 2025.1
	 */
	private array $whitelist = ['rel', 'id', 'href', 'media'];

	/**
	 * @param string $id
	 * @param string $src
	 * @param array $args
	 * @return array
	 * @since 2025.1
	 */
	public function add( string $id, string $src, array $args ): array
	{
		return array_merge(
			[
				'rel'   => 'stylesheet',
				'id'    => sprintf( '%s-css', $id ),
				'href'  => $src,
				'media' => '',
				// and custom data
				'type'  => 'css',
				'uid'   => $id,
				'path'  => \Expansa\Url::toPath( $src ),
			],
			$args
		);
	}

	/**
	 * Get html tag of resource.
	 *
	 * @param array $asset
	 * @return string
	 */
	public function plug( array $asset ): string
	{
		return sprintf( "\n<link%s/>", ( new Helpers() )->format( $asset, $this->whitelist ) );
	}

	/**
	 * CSS minifier
	 *
	 * @param string $code
	 * @return string
	 * @since 2025.1
	 */
	public function minify( string $code ): string
	{
		// remove comments
		$code = preg_replace( '/\/\*[\s\S]*?\*\//', '', $code, -1 );
		$code = preg_replace( '/\/\/.*$/m', '', $code );

		// remove unnecessary spaces and line breaks
		$code = preg_replace( '/\s+/', ' ', $code );
		$code = str_replace( ["\r\n", "\r", "\n", "\t"], '', $code );

		// remove unnecessary spaces before symbols
		return preg_replace( '/\s*([=:;,+\-*\/<>\(\)\{\}\[\]])\s*/', '$1', $code );
	}
}
