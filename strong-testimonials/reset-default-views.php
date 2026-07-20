<?php
global $wpdb;

$table = $wpdb->prefix . 'strong_views';

foreach ( array( 'single_template', 'display', 'form' ) as $mode ) {
	$pattern = '%s:4:"mode";s:' . strlen( $mode ) . ':"' . $mode . '";%';
	$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE value LIKE %s", $pattern ) );
	echo "Deleted $mode rows: $deleted\n";
}

delete_option( 'wpmtst_default_views' );
echo "wpmtst_default_views option deleted.\n";
