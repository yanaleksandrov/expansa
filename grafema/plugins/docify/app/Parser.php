<?php
namespace Docify\App;

/**
 * Documentation block parser.
 */
class Parser {

	// For multi value index.
	private int $index = 0;

	private bool $is_multi = false;

	// Current key.
	private string $key = 'content';

	// Comments exploded by new line.
	private array $lines = [];

	// Things to return
	private array $result = [];

	// Start parse the comment.
	public function run( string $comment ) {
		$this->lines = explode( "\n", $comment );

		foreach ( $this->lines as $line ) {
			$trim_line = ltrim( $line );

			// Skip for opening and closing comment.
			if ( preg_match( '!^\/\*{2,}$!', $line ) || preg_match( '!^\*{1,}\/$!', $trim_line ) ) {
				continue;
			}

			if ( preg_match( '!^[ *]*@([\w_]+)!', $line ) ) {
				$this->parseMeta( $line );
			} else {
				$this->parseComment( $trim_line );
			}
		}

		return $this->result;
	}

	private function parseMeta( string $line ): void {
		if ( preg_match( '!^[ *]*@([\w_]+) (.*)!', $line, $match ) ) {
			if ( isset( $this->result[ $this->key ] ) ) {
				if ( ! $this->is_multi ) {
					if ( $this->key === 'return' ) {
						$this->result[ $this->key ]['description'] = rtrim( $this->result[ $this->key ]['description'] );
					} else {
						$this->result[ $this->key ] = rtrim( $this->result[ $this->key ] );
					}
				} else {
					if ( isset( $this->result[ $this->key ][ $this->index ]['description'] ) ) {
						$this->result[ $this->key ][ $this->index ]['description'] = rtrim( $this->result[ $this->key ][ $this->index ]['description'] );
					}
				}
			}
			$this->key = strtolower( $match[1] );
			// Multi-value
			if ( in_array( $match[1], [ 'example', 'param' ], true ) ) {
				$this->is_multi = true;
				if ( ! isset( $this->result[ $this->key ] ) ) {
					$this->result[ $this->key ] = [];
				}
				$this->index = count( $this->result[ $this->key ] );
			} else {
				$this->is_multi = false;
				$this->index    = 0;
			}

			// @param {Type} {Name} {Description, Default value.}
			if ( $match[1] === 'param' ) {
				if ( preg_match( '!([\w|]+) ([$\w]+) (.+)!', $match[2], $matc ) ) {
					$default = '';
					if ( preg_match( '!Default ([\w]+)!i', $matc[3], $mat ) ) {
						$default = $mat[1];
					}

					$this->result[ $this->key ][ $this->index ] = array(
						'name'        => $matc[2],
						'type'        => $matc[1],
						'default'     => $default,
						'description' => $matc[3],
					);
				}
				// @return {Type} {Description}
			} elseif ( $match[1] === 'return' ) {
				if ( preg_match( '!([\w|]+) ?(.+)?!', $match[2], $mat ) ) {
					$this->result[ $this->key ] = array( 'type' => $mat[1] );
					if ( isset( $mat[2] ) ) {
						$this->result[ $this->key ]['description'] = $mat[2];
					}
				}
				// @example {Number} {Title}
			} elseif ( $match[1] === 'example' ) {
				if ( preg_match( '!([#0-9]+) (.+)!', $match[2], $mat ) ) {
					$this->result[ $this->key ][ $this->index ] = array(
						'title'       => $mat[2],
						'number'      => preg_replace( '![^0-9]!', '', $mat[1] ),
						'description' => '',
					);
				}
				// @{key} {value}
			} else {
				$this->result[ $match[1] ] = $match[2];
			}
			// single key @{Key}
		} elseif ( preg_match( '!^[ *]*@([\w_]+)!', $line, $match ) ) {
			$this->result[ $match[1] ] = true;
		}
	}

	private function parseComment( string $line ): void {
		$value = trim( $line, '*' );
		if ( $this->is_multi ) {
			$this->result[ $this->key ][ $this->index ]['description'] .= "\n$value";
		} else {
			if ( $this->key === 'return' ) {
				$this->result[ $this->key ]['description'] ??= '';
				$this->result[ $this->key ]['description']  .= "\n$value";
			} else {
				$this->result[ $this->key ] ??= '';
				$this->result[ $this->key ]  .= "\n$value";
			}
		}
	}
}
