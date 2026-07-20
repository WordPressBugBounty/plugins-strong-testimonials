<?php
/**
 * Template Name: Default Form
 * Description: Inherits your theme's styles for a seamless look.
 */

$wpmtst_prefix_tokens = function ( $classes ) {
	$tokens    = array_filter( explode( ' ', $classes ) );
	$prefixed  = array_map(
		function ( $token ) {
			return 'wpmtst-' . $token;
		},
		$tokens
	);
	return implode( ' ', $prefixed );
};

$wpmtst_comment_form_class_map = array(
	'client_name'     => 'comment-form-author',
	'email'           => 'comment-form-email',
	'company_website' => 'comment-form-url',
	'post_content'    => 'comment-form-comment',
);

$wpmtst_field_group_class = function ( $classes, $type, $name ) use ( $wpmtst_comment_form_class_map, $wpmtst_prefix_tokens ) {
	$classes = $wpmtst_prefix_tokens( $classes );
	if ( isset( $wpmtst_comment_form_class_map[ $name ] ) ) {
		$classes .= ' ' . $wpmtst_comment_form_class_map[ $name ];
	}
	return $classes;
};
add_filter( 'wpmtst_form_field_group_class', $wpmtst_field_group_class, 10, 3 );
add_filter( 'wpmtst_form_field_label_class', $wpmtst_prefix_tokens );
add_filter( 'wpmtst_form_field_class', $wpmtst_prefix_tokens );
add_filter( 'wpmtst_form_field_before_class', $wpmtst_prefix_tokens );
add_filter( 'wpmtst_form_field_after_class', $wpmtst_prefix_tokens );
add_filter( 'wpmtst_form_field_error_class', $wpmtst_prefix_tokens );
add_filter( 'wpmtst_form_field_wrap_class', $wpmtst_prefix_tokens );
add_filter( 'wpmtst_form_field_counter_class', $wpmtst_prefix_tokens );
add_filter( 'wpmtst_form_field_checkbox_label_class', $wpmtst_prefix_tokens );

$wpmtst_required_symbol = function ( $html ) {
	return str_replace( 'class="required symbol"', 'class="wpmtst-required wpmtst-symbol"', $html );
};
add_filter( 'wpmtst_field_required_symbol', $wpmtst_required_symbol );

$wpmtst_required_notice = function ( $html ) {
	return str_replace( 'class="required-notice"', 'class="wpmtst-required-notice"', $html );
};
add_filter( 'wpmtst_field_required', $wpmtst_required_notice );

$wpmtst_add_button_class = function ( $button_class ) {
	return $button_class . ' wp-element-button';
};
add_filter( 'wpmtst_submit_button_class', $wpmtst_add_button_class );

?>
<div class="strong-view strong-form <?php wpmtst_container_class(); ?>"<?php wpmtst_container_data(); ?>>
		<?php $form_options = get_option( 'wpmtst_form_options' ); ?>
	<?php do_action( 'wpmtst_before_form' ); ?>

	<div class="wpmtst-form wpmtst-form-id-<?php echo esc_attr( WPMST()->atts( 'form_id' ) ); ?>">

		<div class="strong-form-inner">

			<?php if ( isset( $form_options['members_only'] ) && true === $form_options['members_only'] && isset( $form_options['members_only_message'] ) && ! is_user_logged_in() ) : ?>
				<span class="wpmtst-error"><?php echo esc_attr( $form_options['members_only_message'] ); ?></span>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" alt="<?php esc_attr_e( 'Login', 'strong-testimonials' ); ?>">
					<?php esc_html_e( 'Login', 'strong-testimonials' ); ?>
				</a>
			<?php else : ?>
				<?php wpmtst_field_required_notice(); ?>

				<?php
				ob_start();
				wpmtst_form_info();
				$wpmtst_form_attrs = str_replace( 'class="wpmtst-submission-form"', 'class="wpmtst-submission-form comment-form"', ob_get_clean() );
				?>
				<form <?php echo $wpmtst_form_attrs; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from wpmtst_form_info()'s own escaped output plus a literal class string. ?>>

					<?php wpmtst_form_setup(); ?>

					<?php do_action( 'wpmtst_form_before_fields' ); ?>

					<?php wpmtst_all_form_fields(); ?>

					<?php do_action( 'wpmtst_form_after_fields' ); ?>

					<?php wpmtst_form_submit_button(); ?>

				</form>
			<?php endif; ?>
		</div>

	</div>

	<?php
	remove_filter( 'wpmtst_form_field_group_class', $wpmtst_field_group_class, 10 );
	remove_filter( 'wpmtst_form_field_label_class', $wpmtst_prefix_tokens );
	remove_filter( 'wpmtst_form_field_class', $wpmtst_prefix_tokens );
	remove_filter( 'wpmtst_form_field_before_class', $wpmtst_prefix_tokens );
	remove_filter( 'wpmtst_form_field_after_class', $wpmtst_prefix_tokens );
	remove_filter( 'wpmtst_form_field_error_class', $wpmtst_prefix_tokens );
	remove_filter( 'wpmtst_form_field_wrap_class', $wpmtst_prefix_tokens );
	remove_filter( 'wpmtst_form_field_counter_class', $wpmtst_prefix_tokens );
	remove_filter( 'wpmtst_form_field_checkbox_label_class', $wpmtst_prefix_tokens );
	remove_filter( 'wpmtst_field_required_symbol', $wpmtst_required_symbol );
	remove_filter( 'wpmtst_field_required', $wpmtst_required_notice );
	remove_filter( 'wpmtst_submit_button_class', $wpmtst_add_button_class );
	do_action( 'wpmtst_after_form' );
	?>

</div>
