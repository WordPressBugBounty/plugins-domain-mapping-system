<?php

namespace DMS\Includes\Repositories;

use DMS\Includes\Data_Objects\Mapping_Meta;
use DMS\Includes\Exceptions\DMS_Exception;
use DMS\Includes\Utils\Helper;

class Mapping_Meta_Repository {

	/** Allowed methods */
	const ALLOWED_METHODS = [ 'create', 'update', 'delete' ];

	/**
	 * Batch process mapping metadata.
	 *
	 * @param int $mapping_id The mapping ID.
	 * @param array $params An array of parameters with methods and data.
	 *
	 * @return array Results for each batch operation.
	 * @throws DMS_Exception If an unknown method is encountered.
	 */
	public function batch( int $mapping_id, array $params ): array {
		$results = [];

		foreach ( $params as $param ) {
			$this->validate_method( $param['method'] );
			$method = $param['method'];
			$data   = $param['data'];

			$results[] = $this->process_batch_method( $method, $data, $mapping_id );
		}

		return $results;
	}

	/**
	 * Validate the provided method.
	 *
	 * @param mixed $method The method to validate.
	 *
	 * @throws DMS_Exception If the method is unknown.
	 */
	private function validate_method( $method ) {
		if ( empty( $method ) || ! in_array( $method, self::ALLOWED_METHODS ) ) {
			throw new DMS_Exception( 'unknown_method', __( 'Unknown method', 'domain-mapping-system' ) );
		}
	}

	/**
	 * Process batch operations based on the method.
	 *
	 * @param string $method The method (create, update, delete).
	 * @param array $data The data for the operation.
	 * @param int $mapping_id The mapping ID.
	 *
	 * @return array The result of the batch operation.
	 * @throws DMS_Exception
	 */
	private function process_batch_method( string $method, array $data, int $mapping_id ): array {
		$errors  = [];
		$success = true;

		foreach ( $data as $item ) {
			$item   = $this->validate_and_sanitize_item( $item );
			$result = $this->execute_method( $method, $item, $mapping_id );

			if ( Helper::is_dms_error( $result ) ) {
				$errors[] = $result;
				$success  = false;
			}
		}

		return [
			'method'  => $method,
			'success' => $success,
			'errors'  => $errors,
		];
	}

	/**
	 * Validate and sanitize an item.
	 *
	 * @param array $item The item to validate and sanitize.
	 *
	 * @return array The sanitized item.
	 * @throws DMS_Exception
	 */
	private function validate_and_sanitize_item( array $item ): array {
		if ( ! isset( $item['key'], $item['value'] ) ) {
			throw new DMS_Exception( 'missing_item_keys', __( 'Missing required keys in item', 'domain-mapping-system' ) );
		}

		return [
			'key'   => sanitize_text_field( $item['key'] ),
			'value' => $this->sanitize_value( $item['value'] ),
		];
	}

	/**
	 * Sanitize a value, allowing for nested arrays and validating HTML tags.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return mixed The sanitized value or WP_Error on disallowed tags.
	 * @throws DMS_Exception
	 */
	private function sanitize_value( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'sanitize_value' ], $value );
		}

		$allowed_tags = Helper::get_allowed_tags();

		// Extract tags from the value
		preg_match_all( '/<([a-zA-Z0-9]+)[^>]*>/', $value, $matches );
		$tags = array_unique( $matches[1] );

		// Check for disallowed tags
		foreach ( $tags as $tag ) {
			if ( ! in_array( strtolower( $tag ), $allowed_tags, true ) ) {
				throw new DMS_Exception( 'rest_invalid_param', sprintf( __( 'The body parameter contains disallowed tag: %s.' ), $tag ), [ 'status' => 400 ] );
			}
		}

		return $value;
	}

	/**
	 * Execute the appropriate method (create, update, delete).
	 *
	 * @param string $method The method to execute.
	 * @param array $item The item data.
	 * @param int $mapping_id The mapping ID.
	 *
	 * @return bool|Mapping_Meta|object|null The result of the operation.
	 * @throws DMS_Exception
	 */
	private function execute_method( string $method, array $item, int $mapping_id ) {
		switch ( $method ) {
			case 'create':
				return Mapping_Meta::create( $item );
			case 'update':
				$item['mapping_id'] = $mapping_id;

				return $this->mapping_meta_exists( $mapping_id, $item['key'] )
					? Mapping_Meta::update( $item['key'], $mapping_id, $item )
					: Mapping_Meta::create( $item );
			case 'delete':
				return Mapping_Meta::delete( $item['key'], $mapping_id );
			default:
				return null;
		}
	}

	/**
	 * Check if a mapping exists for the given ID and key.
	 *
	 * @param int $mapping_id The mapping ID.
	 * @param string $key The key to check.
	 *
	 * @return bool True if mapping exists, false otherwise.
	 */
	private function mapping_meta_exists( int $mapping_id, string $key ): bool {
		return ! empty( Mapping_Meta::where( [ 'mapping_id' => $mapping_id, 'key' => $key ] ) );
	}
}
