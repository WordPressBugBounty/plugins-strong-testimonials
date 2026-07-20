<?php
/**
 * Template Name: Default
 * Description: Inherits your theme's styles for a seamless look.
 */

do_action( 'wpmtst_before_view' );

?>

<div class="strong-view <?php wpmtst_container_class(); ?>"<?php wpmtst_container_data(); ?>>
	<?php do_action( 'wpmtst_view_header' ); ?>

	<div class="strong-content <?php wpmtst_content_class(); ?>">
		<?php do_action( 'wpmtst_before_content', $atts ); ?>
		<?php
		// Drop the unprefixed "testimonial" class — this template only uses wpmtst-* classes.
		$wpmtst_strip_unprefixed_post_class = function ( $classes ) {
			return implode( ' ', array_diff( explode( ' ', $classes ), array( 'testimonial' ) ) );
		};
		add_filter( 'wpmtst_post_class', $wpmtst_strip_unprefixed_post_class );

		while ( $query->have_posts() ) :
			$query->the_post();
			?>
			<?php
			$wpmtst_avatar_html = wpmtst_get_thumbnail();
			/*
			 * Read the configured height directly rather than parsing it back out of the
			 * rendered <img> tag — WordPress doesn't always emit width/height attributes
			 * that match the requested size (e.g. non-croppable images), which threw the
			 * overlap math off for some photos.
			 */
			$wpmtst_avatar_height = 0;
			if ( $wpmtst_avatar_html ) {
				$wpmtst_avatar_height = 'custom' === WPMST()->atts( 'thumbnail_size' )
					? (int) WPMST()->atts( 'thumbnail_height' )
					: 100;
			}
			?>
			<div class="<?php wpmtst_post_class( $atts ); ?> wp-block-post wp-block-group" style="--wpmtst-avatar-size: <?php echo esc_attr( $wpmtst_avatar_height ); ?>px;">
				<div class="wpmtst-testimonial-inner">
				<?php do_action( 'wpmtst_before_testimonial' ); ?>

					<?php if ( $wpmtst_avatar_html ) : ?>
						<figure class="wp-block-image wpmtst-testimonial-avatar"><?php echo $wpmtst_avatar_html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already filtered/escaped by wpmtst_get_thumbnail(). ?></figure>
					<?php endif; ?>

					<?php wpmtst_the_title( 'h2', 'wp-block-heading wpmtst-testimonial-heading' ); ?>

					<?php
					if ( isset( $atts['client_section'] ) && is_array( $atts['client_section'] ) ) {
						foreach ( $atts['client_section'] as $field ) {
							if ( 'author' === ( $field['type'] ?? '' ) ) {
								$wpmtst_author_value = wpmtst_get_field( $field['field'] );
								if ( $wpmtst_author_value ) {
									printf(
										'<h2 class="wp-block-heading wpmtst-testimonial-field %s">%s</h2>',
										esc_attr( $field['class'] ?? '' ),
										wp_kses_post( $wpmtst_author_value )
									);
								}
								continue;
							}
							echo wpmtst_the_custom_field_as_paragraph( $field );
						}
					}
					?>

					<div class="wpmtst-testimonial-content">
						<?php wpmtst_the_content(); ?>
						<?php do_action( 'wpmtst_after_testimonial_content' ); ?>
					</div>

					<?php do_action( 'wpmtst_after_testimonial', $atts ); ?>
				</div>

			</div>
		<?php endwhile; ?>

		<?php
		remove_filter( 'wpmtst_post_class', $wpmtst_strip_unprefixed_post_class );
		do_action( 'wpmtst_after_content', $atts );
		?>
	</div>

	<?php do_action( 'wpmtst_view_footer' ); ?>
</div>

<?php do_action( 'wpmtst_after_view' ); ?>
