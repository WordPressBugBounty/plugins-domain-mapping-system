<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Mapping_Value;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Utils\Helper;

class Mapping_Value_Repository {

	/**
	 * Allowed request methods
	 */
	const ALLOWED_METHODS = [
		'create',
		'update',
		'delete',
	];

	/**
	 * Create multiple mapping values
	 *
	 * @param array $params Params which must be updated
	 *
	 * @return array
	 * @throws DMS_Exception
	 */
	public function batch( $params ): array {
		$results = [];

		foreach ( $params as $param ) {
			if ( empty( $param['method'] ) || ! in_array( $param['method'], self::ALLOWED_METHODS ) ) {
				throw new DMS_Exception( 'unknown_method', __( 'Unknown method', 'domain-mapping-system' ) );
			}

			$method = $param['method'];
			$data   = $param['data'];

			$errors  = [];
			$values  = [];
			$success = true;

			foreach ( $data as $item ) {
				$result = null;
				switch ( $method ) {
					case 'create':
						$result = Mapping_Value::create( $item );
						break;
					case 'update':
						$result = Mapping_Value::update( $item['id'], $item );
						break;
					case 'delete':
						$result = Mapping_Value::delete( $item['id'] );
						break;
				}

				if ( Helper::is_dms_error( $result ) ) {
					$errors[] = $result;
					$success  = false;
				} else {
					$values[] = $result;
				}
			}

			$results[] = [
				'method'  => $method,
				'success' => $success,
				'errors'  => $errors,
				'data'    => $values
			];
		}

		return $results;
	}

	/**
	 * Create or update mapping value by given params
	 *
	 * @param array $params
	 * @param int $mapping_id
	 *
	 * @return Mapping_Value
	 * @throws DMS_Exception
	 */
	public function create( int $mapping_id, array $params ): Mapping_Value {
		$data    = [
			'object_type' => ! empty( $params['object_type'] ) ? sanitize_text_field( $params['object_type'] ) : '',
			'object_id'   => ! empty( $params['object_id'] ) ? ( int ) $params['object_id'] : null,
			'primary'     => ! empty( $params['primary'] ) ? sanitize_text_field( $params['primary'] ) : 0,
			'mapping_id'  => sanitize_text_field( $mapping_id )
		];
		$mapping = Mapping_Value::where( $data );
		if ( empty( $mapping ) ) {
			if ( ! empty( $params['id'] ) ) {
				return Mapping_Value::update( $params['id'], $data );
			} else {
				return Mapping_Value::create( $data );
			}
		} else {
			return $mapping[0];
		}
	}

	/**
	 * Delete multiple mapping values by mapping id
	 *
	 * @param int $id
	 * @param $count
	 *
	 * @return true
	 * @throws DMS_Exception
	 */
	public function delete_items( int $id, $count ): bool {
		$mapping_values = Mapping_Value::where( [ 'mapping_id' => $id ], 0, $count );
		if ( ! empty( $mapping_values ) ) {
			foreach ( $mapping_values as $value ) {
				Mapping_Value::delete( $value->id );
			}
		}

		return true;
	}
}