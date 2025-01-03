<?php

declare(strict_types=1);

namespace Expansa;

/**
 * Represents a field associated with an object, allowing for retrieval, addition,
 * updating, and deletion of field values in a database.
 *
 * @since 2025.1
 */
final class Field extends Field\Schema {

	use Field\Traits;

	/**
	 * Initializes the object ID and the table name based on the provided object.
	 *
	 * @param mixed $object The object associated with the field (e.g., User instance).
	 *
	 * @since 2025.1
	 */
	public function __construct( mixed $object ) {
		[
			$this->entityId,
			$this->entityColumn,
			$this->table,
			$this->cacheGroup,
		] = match ( true ) {
			$object instanceof User => [
				$object->id,
				'user_id',
				sprintf( '%s_fields', $object::$table ),
				sprintf( 'user-fields-%d', $object->id ),
			],
			$object instanceof Post => [
				$object->id,
				'post_id',
				sprintf( '%s_fields', $object->table ),
				sprintf( 'post-fields-%d', $object->id ),
			],
			default => [ null, null, null, null ],
		};
	}

	/**
	 * Retrieves the value of a specific field for the associated object.
	 *
	 * Benchmark when there are 1 million rows: 1 time - 0.00068 sec, 100000 times - 0.05 sec
	 *
	 * @param string      $key      The key of the field to retrieve. If empty, get all fields of object.
	 * @param bool        $isSingle Whether to limit the result to a single value (default: true).
	 * @return mixed                The field value or null if the object ID is not set.
	 *
	 * @since 2025.1
	 */
	public function get( mixed $key = '', bool $isSingle = true ): mixed {
		if ( ! $this->entityId ) {
			return null;
		}

		return Cache::get( $key, $this->cacheGroup, function() use ( $key, $isSingle ) {
			$conditions = [ $this->entityColumn => $this->entityId ];

			if ( $key ) {
				$conditions['key'] = $key;
			}

			if ( $key && $isSingle ) {
				return Db::get( $this->table, 'value', $conditions );
			}

			$fields = Db::select( $this->table, [ 'key', 'value' ], $conditions );
			if ( is_array( $fields ) ) {
				$result = [];
				foreach ( $fields as $field ) {
					$result[ $field['key'] ][] = $field['value'];
				}
				return $result;
			}

//			$table  = EX_DB_PREFIX . $this->table;
//			$query = "
//			    SELECT JSON_OBJECTAGG(`key`, `values`) AS `result`
//			    FROM (
//			        SELECT `key`, JSON_ARRAYAGG(`value`) AS `values`
//			        FROM {$table}
//			        WHERE `user_id` = :user_id
//			        GROUP BY `key`
//			    ) AS aggregated;
//			";
//			$data = Db::query( $query, [ ':user_id' => $this->entityId ] )->fetchColumn();
//			if ( is_string( $data ) ) {
//				return json_decode( $data );
//			}

			return null;
		} );
	}

	/**
	 * Adds a new field value for the associated object.
	 *
	 * Benchmark when there are 1 million rows: 1 time - 0.00029 sec, 100000 times - 26.15 sec
	 *
	 * @param string $key      The key of the field to add.
	 * @param mixed  $value    The value to be added.
	 * @param bool   $isUnique Whether the field key must be unique (default: true).
	 * @return bool            True if the field was added successfully, false otherwise.
	 *
	 * @since 2025.1
	 */
	public function add( string $key, mixed $value, bool $isUnique = true ): bool {
		if ( ! $this->entityId || $this->isEmpty( $value ) ) {
			return false;
		}

		if ( $isUnique ) {
			$count = Db::count( $this->table, [ $this->entityColumn => $this->entityId, 'key' => $key ] );
			if ( $count > 0 ) {
				return false;
			}
		}

		$isInsert = Db::insert(
			$this->table,
			[
				$this->entityColumn => $this->entityId,
				'key'               => $key,
				'value'             => $value
			]
		)->rowCount() > 0;

		if ( $isInsert ) {
			Cache::add( $key, $value, $this->cacheGroup );
		}
		return $isInsert;
	}

	/**
	 * Updates an existing field value for the associated object.
	 *
	 * @param string $key     The key of the field to update.
	 * @param mixed $value    The new value for the field.
	 * @param mixed $oldValue The old value of the field (optional).
	 * @return bool           True if the field was updated successfully, false otherwise.
	 *
	 * @since 2025.1
	 */
	public function update( string $key, mixed $value, mixed $oldValue = '' ): bool {
		if ( ! $this->entityId ) {
			return false;
		}

		$data       = [ 'value' => $oldValue ? [ $oldValue => $value ] : $value ];
		$conditions = [ 'key' => $key, $this->entityColumn => $this->entityId ];

		$query = $oldValue
			? Db::replace( $this->table, $data, $conditions )
			: Db::update( $this->table, $data, $conditions );

		$isSuccessful = $query->rowCount() > 0;

		if ( $isSuccessful ) {
			Cache::add( $key, $value, $this->cacheGroup );
		}

		return $isSuccessful;
	}

	/**
	 * Deletes a field for the associated object.
	 *
	 * @param string $key  The key of the field to delete.
	 * @param mixed $value The value of the field to delete (optional).
	 * @return bool        True if the field was deleted successfully, false otherwise.
	 *
	 * @since 2025.1
	 */
	public function delete( string $key = '', mixed $value = '' ): bool {
		if ( ! $this->entityId || $this->isEmpty( $value ) ) {
			return false;
		}

		$conditions = [ $this->entityColumn => $this->entityId ];

		if ( $key ) {
			$conditions['key'] = $key;
		}

		if ( $value ) {
			$conditions['value'] = $value;
		}

		return Db::delete( $this->table, $conditions )->rowCount() > 0;
	}

	/**
	 * Add, update or delete field.
	 *
	 * @param string $key
	 * @param mixed|string $value
	 * @param bool $isUnique
	 * @return bool
	 *
	 * @since 2025.1
	 */
	public function mutate( string $key = '', mixed $value = '', bool $isUnique = true ): bool {
		if ( ! $this->entityId || $this->isEmpty( $value ) ) {
			return false;
		}

		if ( empty( $value ) ) {
			return $this->delete( $key );
		}

		$oldValue = $this->get( $key );
		if ( ! empty( $oldValue ) ) {
			return $this->update( $key, $value, $oldValue );
		}

		return $this->add( $key, $value, $isUnique );
	}

	/**
	 * Bulk insert fields.
	 *
	 * Benchmark when there are 1 million rows (insert, update and delete in the ratio of each action 33%): 100000 rows - 29.25 sec
	 * TODO: method need refactor
	 *
	 * @param array $fields
	 * @param int $chunkSize
	 * @return array
	 *
	 * @since 2025.1
	 */
	public function import( array $fields, int $chunkSize = 10000 ): array {
		$result = [ 'deleted' => 0, 'inserted' => 0, 'updated' => 0 ];

		$insertData  = [];
		$updateData  = [];
		$updateQuery = [];

		$existsFields = self::get();

		foreach ( $fields as $key => $value ) {
			if ( ! is_string( $key ) || $this->isEmpty( $value ) ) {
				continue;
			}

			$insertItem = [
				$this->entityColumn => $this->entityId,
				'key'               => $key,
				'value'             => $value,
			];

			if ( ! isset( $existsFields[ $key ] ) ) {
				$insertData[] = $insertItem;
			} elseif ( $value !== $existsFields[ $key ] ) {
				$updateData[ $key ] = "WHEN :key_$key THEN :value_$key";

				$updateQuery[":key_{$key}"]   = $key;
				$updateQuery[":value_{$key}"] = $value;
			}
		}

		$deleteDate = array_diff( array_keys( $existsFields ?? [] ), array_keys( $fields ) );
		if ( $deleteDate ) {
			$deleteDateParts = array_chunk( $deleteDate, $chunkSize, false );
			foreach ( $deleteDateParts as $deleteDatePart ) {
				$result['deleted'] += Db::delete(
					$this->table,
					[
						'AND' => [
							$this->entityColumn => $this->entityId,
							'key'               => $deleteDatePart,
						]
					]
				)->rowCount();
			}
		}

		if ( $insertData ) {
			$insertDataParts = array_chunk( $insertData, $chunkSize, false );
			foreach ( $insertDataParts as $i => $insertDataPart ) {
				$result['inserted'] += Db::insert( $this->table, $insertDataPart )->rowCount();
			}
		}

		if ( $updateData ) {
			$updateDataParts  = array_chunk( $updateData, $chunkSize, true );
			$updateQueryParts = array_chunk( $updateQuery, $chunkSize * 2, true );
			foreach ( $updateDataParts as $i => $updateDataPart ) {
				$whenPart = implode( "\n\t", $updateDataPart );
				$inPart   = implode(', ', array_map( fn( $k ) => ":key_$k", array_keys( $updateDataPart ) ) );

				$sql = "
				UPDATE <{$this->table}> 
					SET value = CASE `key`
						{$whenPart}
					END
					WHERE `key` IN ({$inPart})
				";

				$result['updated'] += Db::query( $sql, $updateQueryParts[ $i ] )->rowCount();
			}
		}

		return $result;
	}

	/**
	 * Check value is empty.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	protected function isEmpty( mixed $value ): bool {
		return $value === null || $value === '' || ( is_array( $value ) && empty( $value ) );
	}
}
