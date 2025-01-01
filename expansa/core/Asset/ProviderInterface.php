<?php

namespace Expansa\Asset;

interface ProviderInterface
{
	/**
	 * @param string $id
	 * @param string $src
	 * @param array $args
	 * @return array
	 */
	public function add( string $id, string $src, array $args ): array;

	/**
	 * @param array $asset
	 * @return string
	 */
	public function plug( array $asset ): string;

	/**
	 * @param string $code
	 * @return string
	 */
	public function minify( string $code ): string;
}
