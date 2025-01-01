<?php

namespace Expansa\Support;

final class Collection
{
	/**
	 * Iterates over data elements provided by a callback function using a generator.
	 *
	 * Use this method to sort through fairly large amounts of data. Although the method is slower than the
	 * native iteration through foreach, it is less memory-intensive because it loads only the current
	 * iteration into memory and not the entire array.
	 *
	 * Although Collection::each is memory efficient, it may be slower for small datasets due
	 * to the overhead of generating items on demand. For small and medium-sized datasets,
	 * it is better to use the usual brute force functions.
	 *
	 * @param callable $dataCallback A callback function that generates data one element at a time.
	 * @param callable $callback     A callback function that processes each element.
	 * @return void
	 */
	public static function each( callable $dataCallback, callable $callback ): void
	{
		// a generator that creates data one element at a time
		$generator = ( function() use ( $dataCallback ) {
			$items = $dataCallback();
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					yield $item;
				}
			}
		} )();

		// use a generator to process the data
		foreach ( $generator as $index => $item ) {
			$callback( $item, $index );
		}
	}
}
