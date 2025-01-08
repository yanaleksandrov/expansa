<?php
namespace Dashboard\Forms;

use app\View;
use Dashboard\Form;
use Expansa\Hook;
use Expansa\Json;
use Expansa\Safe;
use Expansa\Support\Arr;

trait Traits {

	/**
	 * Add 'form' tag wrapper for form content.
	 *
	 * @param array $attributes
	 * @param string $content
	 * @return string
	 */
	private function wrap( array $attributes, string $content = '' ): string {
		$wrapper = sprintf( "<form%s>\n%s</form>\n", Arr::toHtmlAtts( $attributes ), $content );

		/**
		 * Override form wrapper
		 *
		 * @param array  $wrapper    Form wrapper markup.
		 * @param array  $attributes Form html attributes.
		 * @param string $content    Parsed form content.
		 *
		 * @since 2025.1
		 */
		return Hook::call( 'expansa_form_wrap', $wrapper, $attributes, $content );
	}

	/**
	 * Insert new fields to any place in existing form.
	 *
	 * @param array $fields
	 * @param array $field
	 * @param Form $form
	 */
	private function insertField( array &$fields, array $field, Form $form ): void {
		$index    = false;
		$location = current( array_filter( [ $form->after, $form->before, $form->instead ] ) );
		if ( $location ) {
			$index = array_search( $location, array_column( $fields, 'name' ), true );
		}

		if ( $index !== false ) {
			match ( true ) {
				!!$form->after   => array_splice( $fields, $index + 1, 0, [ $field ] ),
				!!$form->before  => array_splice( $fields, $index, 0, [ $field ] ),
				!!$form->instead => $fields[ $index ] = $field,
			};
		} else  {
			$fields[] = $field;
		}
	}

	/**
	 * Get fields html from array.
	 *
	 * @param array $fields
	 * @param int $step
	 * @return string
	 */
	private function parseFields( array $fields, int $step = 1 ): string {
		$content = '';
		foreach ( $fields as $field ) {
			$name = Safe::name( $field['name'] ?? '' );
			$prop = Safe::prop( $field['name'] ?? '' );
			$type = Safe::id( $field['type'] ?? '' );

			if ( $type === 'tab' && ! isset( $startTab ) ) {
				$startTab = true;
				$content .= View::get( 'views/form/layout-tab-menu', $fields );
			}

			// add required attributes & other manipulations
			$field['attributes'] = Safe::array( $field['attributes'] ?? [] );

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
					'content' => $this->parseFields( $field['fields'] ?? [], $step + 1 ),
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
				$field['conditions'] = $this->parseConditions( $field['conditions'], $fields );
			}

			$prefix   = in_array( $type, [ 'tab', 'step', 'group' ], true ) ? 'layout-' : '';
			$content .= View::get( "views/form/{$prefix}{$type}", $field );
		}
		return $content;
	}

	/**
	 * Generate conditions attributes.
	 *
	 * @param array $conditions
	 * @param array $fields
	 * @return array
	 */
	private function parseConditions( array $conditions, array $fields ): array {
		$expressions = [];

		// parse form values
		$attributes = [];
		foreach ( $fields as $field ) {
			$type    = $field['type'] ?? '';
			$name    = $field['name'] ?? '';
			$options = $field['options'] ?? [];
			if ( $options && $type === 'checkbox' ) {
				$attributes += array_combine( array_keys( $options ), array_column( $options, 'checked' ) );
			} else {
				$attributes[$name] = $field['attributes']['value'] ?? null;
			}
		}

		// parse conditions
		foreach ( $conditions as $condition ) {
			[ 'field' => $field, 'operator' => $operator, 'value' => $value ] = $condition;

			$relatedValue = $attributes[ $field ] ?? null;
			if ( $relatedValue === null || ! $operator ) {
				continue;
			}

			$safeValue    = Safe::attribute( $value );
			$attributeVal = match( gettype( $value ) ) {
				'boolean' => $value === true ? 'true' : 'false',
				'string'  => "'$safeValue'",
				'integer' => $value,
			};

			$values = Json::encode( $value );
			$prop   = Safe::prop( $field );

			$expressions[] = [
				'expression' => match( $operator ) {
					'>',
					'>=',
					'<',
					'<='       => "$prop $operator $attributeVal",
					'!=',
					'!=='      => is_array( $value ) ? "!$values.includes($prop)" : "$prop $operator $attributeVal",
					'==',
					'==='      => is_array( $value ) ? "$values.includes($prop)" : "$prop $operator $attributeVal",
					'contains' => "$values.includes($prop)",
					'pattern'  => "$values.some(value => value.test($prop))",
				},
				'match'      => match( $operator ) {
					'>'        => $relatedValue > $value,
					'>='       => $relatedValue >= $value,
					'<'        => $relatedValue < $value,
					'<='       => $relatedValue <= $value,
					'!='       => $relatedValue != $value,
					'!=='      => $relatedValue !== $value,
					'=='       => $relatedValue == $value,
					'==='      => $relatedValue === $value,
					'contains' => in_array( $relatedValue, $value, true ),
					'pattern'  => false, // TODO
				},
			];
		}

		if ( $expressions ) {
			return [
				'x-show'  => implode( ' && ', array_column( $expressions, 'expression' ) ),
				'x-cloak' => Safe::bool( in_array( false, array_column( $expressions, 'match' ), true ) ),
			];
		}
		return [];
	}
}
