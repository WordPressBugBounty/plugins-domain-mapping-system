<?php

namespace DMS\Includes\Data_Objects;


use DMS\Includes\Exceptions\DMS_Exception;

class Setting extends Data_Object {

	/**
	 * Key of the setting
	 *
	 * @var string
	 */
	public string $key;

	/**
	 * Value of the setting
	 *
	 * @var array|string|int
	 */
	public $value;

	/**
	 * Create new setting
	 *
	 * @param array $data contains key and value of the setting
	 *
	 * @return Setting
	 */
	public static function create( array $data ): Setting {
		return parent::setting_create( $data );
	}

	/**
	 * Update setting
	 *
	 * @param array $data contains key and value of the setting
	 *
	 * @return Setting
	 */
	public static function update( array $data ): Setting {
		return parent::setting_create( $data );
	}

	/**
	 * Find setting by key
	 *
	 * @param string $id The key of the setting
	 *
	 * @return Setting
	 */
	public static function find( $id ): Setting {
		return parent::setting_find( $id );
	}

	/**
	 * Delete setting by key
	 *
	 * @param string $key The key of the setting which must be deleted
	 *
	 * @return bool
	 * @throws DMS_Exception
	 */
	public static function delete( string $key ): bool {
		return parent::setting_delete( $key );
	}

	/**
	 * Setting key getter
	 *
	 * @return string
	 */
	public function get_key():string {
		return $this->key;
	}

	/**
	 * Settings where in
	 *
	 * @param array $keys
	 *
	 * @return array|null
	 */
	public static function where_in( array $keys ): ?array {
		$res = [];
		if ( empty( $keys ) ) {
			return array();
		}
		$options = get_options( $keys );
		if ( empty( $options ) ) {
			return array();
		}
		foreach ( $options as $key => $value ) {
			$res[] = self::make( [ 'key' => $key, 'value' => $value ] );
		}

		return $res;
	}

	/**
	 *
	 * @param $key
	 *
	 * @return void
	 */
	public function set_key( $key ): void {
		$this->key = $key;
	}

	/**
	 * Setting value getter
	 *
	 * @return array|int|string
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Setting value setter
	 *
	 * @param $value
	 *
	 * @return void
	 */
	public function set_value( $value ): void {
		$this->value = $value;
	}
}