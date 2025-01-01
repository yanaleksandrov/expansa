<?php
namespace Dashboard\Forms;

use Expansa\Json;
use Expansa\Sanitizer;

final class Condition {

	/**
	 * Generate conditions attributes.
	 *
	 * @param array $conditions
	 * @param array $fields
	 *
	 * @return array
	 */
	public static function parse( array $conditions, array $fields ): array {
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

			$safeValue    = Sanitizer::attribute( $value );
			$attributeVal = match( gettype( $value ) ) {
				'boolean' => $value === true ? 'true' : 'false',
				'string'  => "'$safeValue'",
				'integer' => $value,
			};

			$values = Json::encode( $value );
			$prop   = Sanitizer::prop( $field );

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
				'x-cloak' => Sanitizer::bool( in_array( false, array_column( $expressions, 'match' ), true ) ),
			];
		}
		return [];
	}
}
