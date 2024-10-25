<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Mapping_Meta;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Utils\Helper;

class Mapping_Meta_Repository {

	/**
	 * Allowed methods
	 */
	const ALLOWED_METHODS = [
		'create',
		'update',
		'delete',
	];

	/**
	 * Dynamically create, update, delete mappings
	 *
	 * @param int $mapping_id
	 * @param array $params
	 *
	 * @return array
	 * @throws DMS_Exception
	 */
	public function batch( int $mapping_id, array $params ): array {
		$results = [];

		foreach ( $params as $param ) {
			if ( empty( $param['method'] ) || ! in_array( $param['method'], self::ALLOWED_METHODS ) ) {
				throw new DMS_Exception( 'unknown_method', __( 'Unknown method', 'domain-mapping-system' ) );
			}

			$method = $param['method'];
			$data   = $param['data'];

			$errors  = [];
			$success = true;

			foreach ( $data as $item ) {
				$result = null;
				switch ( $method ) {
					case 'create':
						$item['mapping_id'] = $mapping_id;
						$result             = Mapping_Meta::create( $item );
						break;
					case 'update':
						$item['mapping_id'] = $mapping_id;
						if ( ! empty( Mapping_Meta::where( [
							'mapping_id' => $mapping_id,
							'key'        => $item['key']
						] ) ) ) {
							$result = Mapping_Meta::update( $item['key'], $mapping_id, $item );
						} else {
							$result = Mapping_Meta::create( $item );
						}
						break;
					case 'delete':
						$result = Mapping_Meta::delete( $item['key'], $mapping_id );
						break;
				}

				if ( Helper::is_dms_error( $result ) ) {
					$errors[] = $result;
					$success  = false;
				}
			}

			$results[] = [
				'method'  => $method,
				'success' => $success,
				'errors'  => $errors
			];
		}

		return $results;
	}
} 