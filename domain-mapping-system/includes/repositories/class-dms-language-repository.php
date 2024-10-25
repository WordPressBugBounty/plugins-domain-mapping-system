<?php

namespace DMS\Includes\Repositories;

class Language_Repository {

	/**
	 * Get all languages
	 * This method is designed for translation integrations
	 *
	 * @return mixed|null
	 */
	public function get_items() {
		return apply_filters( 'dms_available_languages', array() );
	}
}