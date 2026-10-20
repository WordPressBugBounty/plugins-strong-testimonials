<?php

function wpmtst_get_form_preview() {

	if ( ! isset( $_POST['fields'] ) ) {
		exit;
	}

	check_ajax_referer( 'wpmtst-admin', 'security' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( -1, 403 );
	}

	// parse_str decodes too; no need to use urldecode
	parse_str( stripslashes_deep( $_POST['fields'] ), $preview );

	$new_fields = array();
	$fields     = apply_filters( 'wpmtst_fields', get_option( 'wpmtst_fields' ) );

	$saved_shortcodes = array();
	$saved_forms      = get_option( 'wpmtst_custom_forms' );
	if ( is_array( $saved_forms ) ) {
		foreach ( $saved_forms as $saved_form ) {
			if ( empty( $saved_form['fields'] ) || ! is_array( $saved_form['fields'] ) ) {
				continue;
			}
			foreach ( $saved_form['fields'] as $saved_field ) {
				if ( ! isset( $saved_field['name'] ) ) {
					continue;
				}
				foreach ( array( 'shortcode_on_form', 'before', 'after' ) as $saved_key ) {
					if ( ! empty( $saved_field[ $saved_key ] ) ) {
						$saved_shortcodes[ $saved_field['name'] . '|' . $saved_key . '|' . sanitize_text_field( $saved_field[ $saved_key ] ) ] = true;
					}
				}
			}
		}
	}
	$shortcode_regex = get_shortcode_regex();

	// Input callbacks come only from registered field types, never from the request.
	$type_actions = array();
	if ( isset( $fields['field_types'] ) && is_array( $fields['field_types'] ) ) {
		foreach ( $fields['field_types'] as $field_group ) {
			foreach ( (array) $field_group as $field_type ) {
				if ( ! empty( $field_type['input_type'] ) && ! empty( $field_type['action_input'] ) && ! isset( $type_actions[ $field_type['input_type'] ] ) ) {
					$type_actions[ $field_type['input_type'] ] = $field_type['action_input'];
				}
			}
		}
	}

	foreach ( $preview['fields'] as $key => $field ) {
		/*
		 * Before merging onto base field, catch fields that are "off"
		 * which the form does not submit. Otherwise, the default "on"
		 * would override the requested (but not submitted) "off".
		 */
		$field['show_label'] = isset( $field['show_label'] ) ? 1 : 0;
		$field['required']   = isset( $field['required'] ) ? 1 : 0;

		// Type-specific options (e.g. select_options) are kept as plain text.
		$extra = array_diff_key( $field, $fields['field_base'] );

		$field = array_merge( $fields['field_base'], $field );
		$field = array_intersect_key( $field, $fields['field_base'] );
		$field = array_merge( map_deep( $extra, 'sanitize_text_field' ), $field );

		$field['action_input'] = ( is_string( $field['input_type'] ) && isset( $type_actions[ $field['input_type'] ] ) ) ? $type_actions[ $field['input_type'] ] : '';

		if ( 'none' === $field['input_type'] ) {
			$field['input_type'] = 'text';
		}

		$field['name']  = sanitize_text_field( $field['name'] );
		$field['label'] = sanitize_text_field( $field['label'] );
		// TODO Replace this special handling
		if ( 'checkbox' === $field['input_type'] ) {
			$field['default_form_value'] = wpmtst_sanitize_checkbox( $field, 'default_form_value' );
		} else {
			$field['default_form_value'] = sanitize_text_field( $field['default_form_value'] );
		}
		$field['placeholder'] = sanitize_text_field( $field['placeholder'] );
		$field['before']      = sanitize_text_field( $field['before'] );
		$field['after']       = sanitize_text_field( $field['after'] );

		foreach ( array( 'shortcode_on_form', 'before', 'after' ) as $exec_key ) {
			$request_val = isset( $field[ $exec_key ] ) ? $field[ $exec_key ] : '';
			if ( '' === $request_val || ! preg_match( '/' . $shortcode_regex . '/', $request_val ) ) {
				continue;
			}
			$lookup = $field['name'] . '|' . $exec_key . '|' . sanitize_text_field( $request_val );
			if ( ! isset( $saved_shortcodes[ $lookup ] ) ) {
				$field[ $exec_key ] = wpmtst_form_field_shortcode_notice();
			}
		}

		// add to fields array in display order
		$new_fields[] = $field;
	}

	ob_start();
	include WPMTST_ADMIN . 'partials/templates/form-preview-template.php';
	$html = ob_get_contents();
	ob_end_clean();
	echo $html;

	exit;
}
add_action( 'wp_ajax_wpmtst_get_form_preview', 'wpmtst_get_form_preview' );
