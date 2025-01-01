<?php
namespace Dashboard\Table;

use Grafema\Sanitizer;

final class Row
{
	use Traits\Row;

	/**
	 * Add new row.
	 *
	 * @return Row
	 */
	public static function add(): Row {
        return new self();
	}

	/**
	 * Set row tag.
	 *
	 * @param string $tag
	 * @return Row
	 */
	public function tag( string $tag ): Row {
		$this->tag = Sanitizer::tag( $tag );

		return $this;
	}

	/**
	 * Set row attribute.
	 *
	 * @param string $attribute
	 * @param string|int $value
	 * @return Row
	 */
	public function attribute( string $attribute, string|int $value = '' ): Row {
		$attribute = Sanitizer::name( $attribute );
		$value     = Sanitizer::attribute( $value );
		if ( $attribute && $value ) {
			$this->attributes[ $attribute ] = $value;
		}
		return $this;
	}

	/**
	 * Get view template.
	 *
	 * @param string $template
	 * @return Row
	 */
	public function view( string $template ): Row {
		if ( file_exists( $template ) ) {
			$this->view = $template;
		} else {
			$this->view = sprintf( '%s/%s', $this->view, $template );
		}

		return $this;
	}
}
