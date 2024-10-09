<?php

namespace DMS\Includes\Repositories;


class Wp_Object_Repository {

	public function paginate( $objects, $total_items, $per_page, $current_page ): array {
		$pagination         = [
			'total_items'  => $total_items,
			'current_page' => $current_page,
			'per_page'     => $per_page,
			'total_pages'  => ceil( $total_items / $per_page ),
		];
		$res['objects']     = array_values($objects);
		$res['_pagination'] = $pagination;

		return $res;
	}
}
