<?php

namespace DMS\Includes\Data_Objects;
class Language extends Data_Object {

	/**
	 * The locale
	 *
	 * @var null|string
	 */
	public ?string $locale;
	/**
	 * The display name
	 *
	 * @var string|null
	 */
	public ?string $display_name;
	/**
	 * The flag url
	 *
	 * @var null|string
	 */
	public ?string $flag_url;

	/**
	 * Create new language
	 *
	 * @param array $data
	 *
	 * @return self
	 */
	public static function create( array $data ): object {
		return new self( $data );
	}

	/**
	 * Find the language
	 *
	 * @param int|null $id
	 *
	 * @return self|null
	 */
	public static function find( ?int $id ): ?object {
		return new self();
	}

	/**
	 * Get the locale
	 *
	 * @return string|null
	 */
	public function get_locale(): ?string {
		return $this->locale;
	}

	/**
	 * Set the locale
	 *
	 * @param string|null $locale
	 *
	 * @return void
	 */
	public function set_locale( ?string $locale ): void {
		$this->locale = $locale;
	}

	/**
	 * Get display name
	 *
	 * @return string|null
	 */
	public function get_display_name(): ?string {
		return $this->display_name;
	}

	/**
	 * Set display name
	 *
	 * @param string|null $display_name
	 *
	 * @return void
	 */
	public function set_display_name( ?string $display_name ): void {
		$this->display_name = $display_name;
	}

	/**
	 * Get flag url
	 *
	 * @return null|string
	 */
	public function get_flag_url(): ?string {
		return $this->flag_url;
	}

	/**
	 * Set the flag url
	 *
	 * @param null|string $flag_url
	 */
	public function set_flag_url( ?string $flag_url ): void {
		$this->flag_url = $flag_url;
	}
}