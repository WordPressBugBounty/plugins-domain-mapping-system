<?php

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Setting;

if ( ! empty( $dms_fs ) && $dms_fs instanceof \Freemius ):
	// Get screen options
	$items_per_page = get_option( 'dms_mappings_per_page', 10 );
	$values_per_mapping = get_option( 'dms_values_per_mapping', 5 );
	include_once plugin_dir_path( __FILE__ ) . 'screen-options.php';
	?>
    <div class="dms-n"></div>
<?php endif; ?>
