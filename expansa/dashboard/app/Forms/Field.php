<?php
namespace Dashboard\Forms;

use Expansa\Sanitizer;
use Expansa\View;

final class Field {

	/**
	 * Get fields html from array.
	 *
	 * @param array $fields
	 * @param int $step
	 * @return string
	 */
	public function parse( array $fields, int $step = 1 ): string {
		$content = '';
		foreach ( $fields as $field ) {
			$name = Sanitizer::name( $field['name'] ?? '' );
			$prop = Sanitizer::prop( $field['name'] ?? '' );
			$type = Sanitizer::id( $field['type'] ?? '' );

			if ( $type === 'tab' && ! isset( $startTab ) ) {
				$startTab = true;
				$content .= View::get( 'views/form/layout-tab-menu', $fields );
			}

			// add required attributes & other manipulations
			$field['attributes'] = Sanitizer::array( $field['attributes'] ?? [] );

			match ( $type ) {
				'step'     => $field['attributes']['x-wizard:step'] ??= '',
				'textarea' => $field['attributes']['x-textarea'] ??= '',
				'select'   => $field['attributes']['x-select'] ??= '',
				'date'     => $field['attributes']['x-datepicker'] ??= '',
				'submit'   => $field['attributes']['name'] ??= $name,
				default    => '',
			};

			if ( ! in_array( $type, [ 'tab', 'step', 'group', 'submit' ], true ) ) {
				$field['attributes'] = [ 'type' => $type, 'name' => $name, 'x-model.fill' => $prop, ...$field['attributes'] ];
			}

			if ( in_array( $type, [ 'tab', 'step', 'group' ], true ) ) {
				$field = [
					'content' => $this->parse( $field['fields'] ?? [], $step + 1 ),
					'step'    => $step++,
					...$field,
				];
			}

			if ( in_array( $type, [ 'select' ], true ) ) {
				unset( $field['attributes']['type'] );
			}

			if ( in_array( $type, [ 'date' ], true ) ) {
				$field['attributes']['type'] = 'text';
			}

			if ( in_array( $type, [ 'color', 'date', 'datetime-local', 'email', 'month', 'range', 'search', 'tel', 'text', 'time', 'url', 'week' ], true ) ) {
				$type = 'input';
			}

			if ( ! empty( $field['label'] ) && ( $field['attributes']['required'] ?? false ) ) {
				$field['label'] = sprintf( '%s %s', $field['label'], '<i class="t-red">*</i>' );
			}

			// parse conditions
			if ( ! empty( $field['conditions'] ) ) {
				$field['conditions'] = Condition::parse( $field['conditions'], $fields );
			}

			$prefix   = in_array( $type, [ 'tab', 'step', 'group' ], true ) ? 'layout-' : '';
			$content .= View::get( "views/form/{$prefix}{$type}", $field );
		}
		return $content;
	}
}
