<?php
/**
 * View Functions
 */

/**
 * Return the default view settings.
 *
 * @param bool $unfiltered
 * @since 2.30.5
 *
 * @return array
 */
function wpmtst_get_view_default( $unfiltered = false ) {
	$default = get_option( 'wpmtst_view_default' );
	if ( ! $unfiltered ) {
		$default = apply_filters( 'wpmtst_view_default', $default );
	}

	return $default;
}

/**
 * @return array|mixed|null|object
 */
function wpmtst_get_views() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'strong_views';

	$wpdb->show_errors();
	$results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->hide_errors();

	if ( $wpdb->last_error ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

		// The table is missing (e.g. dropped by a DB restore/cleanup) - try to recreate it instead of taking the site's testimonials down.
		if ( ! $table_exists && wpmtst_create_views_table() ) {
			$wpdb->show_errors();
			$results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->hide_errors();
		}

		// Still failing (recreation failed, or a transient DB error) - log it and degrade gracefully rather than deactivating the plugin.
		if ( $wpdb->last_error ) {
			error_log( 'Strong Testimonials: could not read the views table - ' . $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return apply_filters( 'wpmtst_views_query_results', array() );
		}
	}

	return apply_filters( 'wpmtst_views_query_results', $results );
}

/**
 * @param $views
 *
 * @return mixed
 */
function wpmtst_unserialize_views( $views ) {
	foreach ( $views as $key => $view ) {
		$views[ $key ]['data'] = unserialize( $view['value'] );
	}

	return $views;
}

/**
 * @param $id
 *
 * @return array
 */
function wpmtst_get_view( $id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'strong_views';
	$row        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return $row;
}

/**
 * Find the view for the single template.
 *
 * @return bool|array
 */
function wpmtst_find_single_template_view() {
	$views = wpmtst_get_views();
	/*
	 * [id] => 1
	 * [name] => TEST
	 * [value] => {serialized_array}
	 */

	foreach ( $views as $view ) {
		$view_data = maybe_unserialize( $view['value'] );
		if ( isset( $view_data['mode'] ) && 'single_template' === $view_data['mode'] ) {
			return $view_data;
		}
	}

	return false;
}
