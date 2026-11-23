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

	// Runs before embeds (priority 8) so generated markup is not stripped.
	add_filter( 'wpmtst_the_content', 'wp_kses_post', 6 );
	add_filter( 'wpmtst_the_excerpt', 'wp_kses_post', 6 );

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

/**
 * Encode square brackets so submitted text cannot form a shortcode. Browsers still show them as [ ].
 *
 * @param mixed $value
 *
 * @return mixed
 */
function wpmtst_encode_shortcode_brackets( $value ) {
	if ( ! is_string( $value ) || apply_filters( 'wpmtst_allow_testimonial_shortcodes', false ) ) {
		return $value;
	}

	return str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), $value );
}

/**
 * Whether a testimonial came from the submission form.
 *
 * @param int $post_id
 *
 * @return bool
 */
function wpmtst_is_submitted_testimonial( $post_id ) {
	return $post_id && 'wpm-testimonial' === get_post_type( $post_id ) && '' !== (string) get_post_meta( $post_id, 'submit_date', true );
}

/**
 * The classic editor decodes the stored &#91; &#93;, so submitted testimonials are encoded again on output.
 *
 * @param string $content
 *
 * @return string
 */
function wpmtst_encode_submitted_content( $content ) {
	$post = get_post();
	if ( $post && wpmtst_is_submitted_testimonial( $post->ID ) ) {
		$content = wpmtst_encode_shortcode_brackets( $content );
	}

	return $content;
}
add_filter( 'the_content', 'wpmtst_encode_submitted_content', 7 );
add_filter( 'wpmtst_the_content', 'wpmtst_encode_submitted_content', 7 );
add_filter( 'wpmtst_the_excerpt', 'wpmtst_encode_submitted_content', 7 );

/**
 * Keep the brackets encoded when a submitted testimonial is saved again from the admin.
 *
 * @param array $data
 * @param array $postarr
 *
 * @return array
 */
function wpmtst_encode_saved_submitted_testimonial( $data, $postarr ) {
	if ( ! empty( $postarr['ID'] ) && 'wpm-testimonial' === $data['post_type'] && wpmtst_is_submitted_testimonial( $postarr['ID'] ) ) {
		$data['post_content'] = wpmtst_encode_shortcode_brackets( $data['post_content'] );
		$data['post_excerpt'] = wpmtst_encode_shortcode_brackets( $data['post_excerpt'] );
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'wpmtst_encode_saved_submitted_testimonial', 100, 2 );

/**
 * Encode a new testimonial. Posts wait for wp_insert_post_data because kses decodes the brackets on save.
 *
 * @param array $data
 *
 * @return array
 */
function wpmtst_encode_new_testimonial( $data ) {
	remove_filter( 'wp_insert_post_data', 'wpmtst_encode_new_testimonial', 100 );

	return map_deep( $data, 'wpmtst_encode_shortcode_brackets' );
}
add_filter( 'wpmtst_new_testimonial_meta', 'wpmtst_encode_new_testimonial', 99 );
add_filter(
	'wpmtst_new_testimonial_post',
	function ( $post ) {
		add_filter( 'wp_insert_post_data', 'wpmtst_encode_new_testimonial', 100 );
		return $post;
	},
	99
);

function wpmtst_add_allowed_tags( $tags, $context = '' ) {

	// Only for markup the plugin builds itself (admin editors). Never widen the
	// core contexts (post, comment, ...) that also sanitise user content.
	if ( 'strong_testimonials' !== $context ) {
		return $tags;
	}

	$tags = wp_kses_allowed_html( 'post' );

	$tags['iframe'] = array(
		'src'             => true,
		'height'          => true,
		'width'           => true,
		'frameborder'     => true,
		'allowfullscreen' => true,
		'style'           => true,
		'data-*'          => true,
		'hidden'          => true,
	);
	$tags['input'] = array(
		'class'       => true,
		'id'          => true,
		'name'        => true,
		'value'       => true,
		'type'        => true,
		'placeholder' => true,
		'required'    => true,
		'checked'     => true,
		'title'       => true,
		'style'       => true,
		'data-*'      => true,
		'hidden'      => true,
	);
	$tags['textarea'] = array(
		'class'       => true,
		'id'          => true,
		'name'        => true,
		'value'       => true,
		'type'        => true,
		'placeholder' => true,
		'required'    => true,
		'style'       => true,
		'data-*'      => true,
		'hidden'      => true,
	);
	$tags['select'] = array(
		'class'  => true,
		'id'     => true,
		'name'   => true,
		'value'  => true,
		'type'   => true,
		'style'  => true,
		'data-*' => true,
		'hidden' => true,
	);
	$tags['option']   = array(
		'selected' => true,
		'class'    => true,
		'id'       => true,
		'name'     => true,
		'value'    => true,
		'style'    => true,
		'data-*'   => true,
		'hidden'   => true,
	);
	$tags['optgroup'] = array(
		'class'  => true,
		'id'     => true,
		'name'   => true,
		'value'  => true,
		'label'  => true,
		'style'  => true,
		'data-*' => true,
		'hidden' => true,
	);
	$tags['source']   = array(
		'type' => true,
		'src'  => true,
	);

	$tags['span']['hidden'] = true;

	$tags['img']['srcset'] = true;
	$tags['img']['sizes']  = true;

	$tags['div']['data-*'] = true;

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
