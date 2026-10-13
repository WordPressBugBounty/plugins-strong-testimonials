<?php
/**
 * Filters
 */

/**
 * Remove whitespace between tags. Helps prevent double wpautop in plugins
 * like Posts For Pages and Custom Content Shortcode.
 *
 * @param $html
 *
 * @since 2.3
 *
 * @return mixed
 */
function wpmtst_remove_whitespace( $html ) {
	$options = get_option( 'wpmtst_options' );
	$saved_value       = isset( $options['remove_whitespace'] ) ? (bool) $options['remove_whitespace'] : true;
	if ( apply_filters( 'wpmtst_remove_whitespace_enabled', $saved_value ) ) {
		$html = preg_replace( '~>\s+<~', '><', $html );
	}

	return $html;
}
add_filter( 'strong_view_html', 'wpmtst_remove_whitespace' );
add_filter( 'strong_view_form_html', 'wpmtst_remove_whitespace' );

/**
 * Content filters.
 *
 * @since 2.33.0 Moved to `init` action.
 */
function wpmtst_content_filters() {

	add_filter( 'wpmtst_the_content', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
	add_filter( 'wpmtst_the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
	add_filter( 'wpmtst_the_content', 'wptexturize' );
	add_filter( 'wpmtst_the_content', 'wpautop' );
	add_filter( 'wpmtst_the_content', 'shortcode_unautop' );
	add_filter( 'wpmtst_the_content', 'prepend_attachment' );

	if ( version_compare( get_bloginfo( 'version' ), '5.5', '>=' ) ) {
		add_filter( 'wpmtst_the_content', 'wp_filter_content_tags' );
	} else {
		add_filter( 'wpmtst_the_content', 'wp_make_content_images_responsive' );
	}

	add_filter( 'wpmtst_the_content', 'convert_smilies', 20 );

	add_filter( 'wpmtst_the_excerpt', 'wptexturize' );
	add_filter( 'wpmtst_the_excerpt', 'convert_smilies' );
	add_filter( 'wpmtst_the_excerpt', 'convert_chars' );
	add_filter( 'wpmtst_the_excerpt', 'wpautop' );
	add_filter( 'wpmtst_the_excerpt', 'shortcode_unautop' );
	add_filter( 'wpmtst_the_excerpt', 'convert_smilies', 20 );
}
add_action( 'init', 'wpmtst_content_filters' );

function wpmtst_add_allowed_tags( $tags, $context = '' ) {

	// These extra tags are allowed only in the plugin's own trusted rendering
	// context, never in the core contexts (post, comment, ...) that also
	// sanitise user-submitted content. Form controls and data-* are omitted on
	// purpose: the plugin prints its own forms directly (not through kses), and
	// allowing them here is what enabled form-injection and Interactivity XSS.
	if ( 'strong_testimonials' !== $context ) {
		return $tags;
	}

	$tags['iframe'] = array(
		'src'             => true,
		'height'          => true,
		'width'           => true,
		'frameborder'     => true,
		'allowfullscreen' => true,
		'style'           => true,
		'hidden'          => true,
	);
	$tags['source'] = array(
		'type' => true,
		'src'  => true,
	);

	$tags['span']['hidden'] = true;

	$tags['img']['srcset'] = true;
	$tags['img']['sizes']  = true;

	$tags['noscript'] = array();

	$tags['style'] = array(
		'types' => true,
	);

	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'wpmtst_add_allowed_tags', 10, 2 );


function wpmtst_safe_style_css( $styles ) {
	$styles[] = 'display';
	return $styles;
}
add_filter( 'safe_style_css', 'wpmtst_safe_style_css' );


/**
 * Change single testimonial slug.
 */
add_filter( 'wpmtst_post_type', 'wpmtst_change_testimonial_slug' );
function wpmtst_change_testimonial_slug( $args ) {

	$options    = get_option( 'wpmtst_options' );
	$saved_slug = isset( $options['single_testimonial_slug'] ) ? $options['single_testimonial_slug'] : '';
	$slug       = apply_filters( 'wpmtst_single_testimonial_slug', $saved_slug );

	if ( '' !== $slug ) {
		$args['rewrite']['slug'] = $slug;
	}

	return $args;
}


/**
 * Disable custom post url
 */
add_filter( 'wpmtst_post_type', 'wpmtst_disable_permalink', 999 );
function wpmtst_disable_permalink( $args ) {

	$options         = get_option( 'wpmtst_options' );
	$saved_value     = isset( $options['disable_rewrite'] ) && $options['disable_rewrite'];
	$disable_rewrite = apply_filters( 'wpmtst_disable_rewrite', $saved_value );

	if ( $disable_rewrite ) {
		$args['rewrite'] = false;
		$args['public']  = false;
	}

	return $args;
}
