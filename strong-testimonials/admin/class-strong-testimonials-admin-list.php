<?php
/**
 * Class Strong_Testimonials_Admin_List
 *
 * @since 2.28.0
 */
class Strong_Testimonials_Admin_List {

	/**
	 * Strong_Testimonials_Admin_List constructor.
	 */
	public function __construct() {}

	/**
	 * Initialize.
	 */
	public static function init() {
		self::add_actions();
	}

	/**
	 * Add actions and filters.
	 */
	public static function add_actions() {
		add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
		add_filter( 'manage_wpm-testimonial_posts_columns', array( __CLASS__, 'add_thumbnail_column' ) );
		add_filter( 'manage_edit-wpm-testimonial_columns', array( __CLASS__, 'edit_columns' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'add_taxonomy_filters' ) );
		add_filter( 'manage_edit-wpm-testimonial_sortable_columns', array( __CLASS__, 'manage_sortable_columns' ) );
		add_action( 'manage_wpm-testimonial_posts_custom_column', array( __CLASS__, 'custom_columns' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'post_row_actions' ), 10, 2 );

		add_filter( 'manage_wpm-testimonial_posts_columns', array( __CLASS__, 'add_submitted_on_column' ) );
	}

	/**
	 * Add post ID to post row actions.
	 *
	 * @param $actions
	 * @param $post
	 * @since 2.32.2
	 *
	 * @return array
	 */
	public static function post_row_actions( $actions, $post ) {
		if ( 'wpm-testimonial' === $post->post_type ) {
			$actions = array( 'id' => '<span>ID: ' . $post->ID . '</span>' ) + $actions;
		}

		return $actions;
	}

	/**
	 * Add custom columns to the admin list.
	 *
	 * @param $columns
	 *
	 * @since 1.4.0
	 * @since 2.5.1  Added comments column.
	 *
	 * @return array
	 */
	public static function edit_columns( $columns ) {
		$fields = wpmtst_get_all_fields();

		$comments = isset( $columns['comments'] ) ? $columns['comments'] : '';

		/*
		INCOMING COLUMNS = Array (
			[cb] => <input type="checkbox" />
			[title] => Title
			[comments] => <span class="vers comment-grey-bubble" title="Comments"><span class="screen-reader-text">Comments</span></span>
			[date] => Date
			[search_exclude] => Search Exclude   // other plugin
			[strong_thumbnail] => Thumbnail
		)
		*/

		// 1. remove [thumbnail] (may be re-added in custom field loop) and [date]
		unset( $columns['strong_thumbnail'], $columns['date'] );

		if ( $comments ) {
			unset( $columns['comments'] );
		}

		// 2. insert [order] after [cb]
		if ( ! self::is_column_sorted() && ! self::is_viewing_trash() && class_exists( 'Strong_Testimonials_Order' ) ) {
			$columns = array_merge(
				array_slice( $columns, 0, 1 ),
				array( 'handle' => 'Order' ),
				array_slice( $columns, 1, null )
			);
		}

		// 3. insert [excerpt] after [title]
		$key           = 'title';
		$offset        = array_search( $key, array_keys( $columns ), true ) + 1;
		$fields_to_add = array( 'post_excerpt' => esc_html__( 'Excerpt', 'strong-testimonials' ) );

		// 4. add custom fields
		foreach ( $fields as $key => $field ) {
			if ( isset( $field['admin_table'] ) ) {
				if ( 'post_title' === $field['name'] ) {
					continue;
				} elseif ( 'featured_image' === $field['name'] ) {
					$fields_to_add['strong_thumbnail'] = esc_html__( 'Thumbnail', 'strong-testimonials' );
				} elseif ( 'rating' === $field['input_type'] ) {
					$fields_to_add[ $field['name'] ] = esc_html__( 'Rating', 'strong-testimonials' );
				} else {
					$fields_to_add[ $field['name'] ] = apply_filters( 'wpmtst_l10n', $field['label'], 'strong-testimonials-form-fields', $field['name'] . ' : label' );
				}
			}
		}

		// 5. add [category], [comments] and [date]
		// The slug "categories" slug is reserved by WordPress.
		if ( wpmtst_get_cat_count() ) {
			$fields_to_add['category'] = esc_html__( 'Categories', 'strong-testimonials' );
		}

		if ( $comments ) {
			$fields_to_add['comments'] = $comments;
		}

		$fields_to_add['date'] = esc_html__( 'Date', 'strong-testimonials' );

		$options = get_option( 'wpmtst_options' );
		if ( isset( $options['include_platform'] ) && true === $options['include_platform'] ) {
			$fields_to_add['platform'] = esc_html__( 'Platform', 'strong-testimonials' );
		}

		// Push other added columns like [search_exclude] to the end.
		$columns = array_merge(
			array_slice( $columns, 0, $offset ),
			$fields_to_add,
			array_slice( $columns, $offset, null )
		);

		return $columns;
	}

	/**
	 * Show custom values
	 *
	 * @param $column
	 */
	public static function custom_columns( $column ) {
		global $post;

		switch ( $column ) {
			case 'post_id':
				echo absint( $post->ID );
				break;

			case 'post_content':
				$allowed_tags = array(
					'a'      => array(
						'href'  => array(),
						'title' => array(),
					),
					'p'      => array(),
					'strong' => array(),
					'em'     => array(),
					'ul'     => array(),
					'ol'     => array(),
					'li'     => array(),
					'br'     => array(),
					'span'   => array( 'class' => array() ),
				);
				echo wp_kses( substr( $post->post_content, 0, 100 ) . '&hellip;', $allowed_tags );
				break;

			case 'post_excerpt':
				echo wp_kses_post( $post->post_excerpt );
				break;

			case 'strong_thumbnail':
				echo wp_kses_post( wpmtst_get_thumbnail( array( 60, 60 ) ) );
				break;

			case 'category':
				$categories = get_the_terms( 0, 'wpm-testimonial-category' );
				if ( $categories && ! is_wp_error( $categories ) ) {
					$list = array();
					foreach ( $categories as $cat ) {
						$list[] = $cat->name;
					}
					echo esc_html( implode( ', ', $list ) );
				}
				break;

			case 'handle':
				if ( current_user_can( 'edit_post', $post->ID ) && ! self::is_column_sorted() && ! self::is_viewing_trash() ) {
					echo '<div class="handle"><div class="help"></div><div class="help-in-motion"></div></div>';
				}
				break;

			case 'platform':
				$platform = get_post_meta( $post->ID, 'platform', true );

				if ( $platform ) {
					$icon_src = apply_filters( 'wpmtst_platform_icon_src', WPMTST_ASSETS_IMG . '/platform_icons/' . $platform . '.svg', $platform, $post );
					?>
						<img title="<?php echo esc_attr( __( 'posted on ', 'strong-testimonials' ) . $platform ); ?>" width="26" height="26" src="<?php echo esc_url( $icon_src ); ?>"/>
					<?php
				}

				break;

			case 'submission_date':
				$submission_date = get_post_meta( $post->ID, 'submit_date', true );
				if ( $submission_date ) {
					$timestamp = strtotime( $submission_date );
					$formatted = date_i18n( 'Y/m/d', $timestamp ) . ' at ' . date_i18n( 'g:i a', $timestamp );
					echo esc_html( $formatted );
				} else {
					echo '—';
				}
				break;

			default:
				// custom field?
				$custom = get_post_custom();
				$fields = wpmtst_get_custom_fields();

				if ( isset( $custom[ $column ] ) && $custom[ $column ][0] ) {
					if ( isset( $fields[ $column ] ) ) {
						switch ( $fields[ $column ]['input_type'] ) {
							case 'rating':
								wpmtst_star_rating_display( $custom[ $column ][0], 'in-table-list' );
								break;
							case 'checkbox':
								echo $custom[ $column ][0] ? 'yes' : 'no';
								break;
							default:
								echo wp_kses_post( $custom[ $column ][0] );
						}
					}
				} elseif ( isset( $fields[ $column ] ) ) {
					if ( 'checkbox' === $fields[ $column ]['input_type'] ) {
						echo 'no';
					} else {
						// display nothing
					}
				}
		}
	}

	/**
	 * Add thumbnail column to admin list
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public static function add_thumbnail_column( $columns ) {
		$columns['strong_thumbnail'] = esc_html__( 'Thumbnail', 'strong-testimonials' );

		return $columns;
	}

	/**
	 * Add thumbnail column to admin list
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public static function add_submitted_on_column( $columns ) {
		$columns['submission_date'] = esc_html__( 'Submitted On', 'strong-testimonials' );

		return $columns;
	}

	/**
	 * Make columns sortable.
	 *
	 * @param $columns
	 *
	 * @since 1.12.0
	 * @since 2.2.0 category
	 *
	 * @return mixed
	 */
	public static function manage_sortable_columns( $columns ) {
		$columns['client_name']     = 'client_name';
		$columns['category']        = 'categories';
		$columns['date']            = 'date';
		$columns['submission_date'] = 'submission_date';

		return $columns;
	}

	/**
	 * Add category filter to testimonial list table.
	 *
	 * @since 2.2.0
	 */
	public static function add_taxonomy_filters() {
		global $typenow;

		if ( 'wpm-testimonial' !== $typenow ) {
			return;
		}

		$taxonomies = array( 'wpm-testimonial-category' );

		foreach ( $taxonomies as $tax ) {
			$tax_obj = get_taxonomy( $tax );
			$args    = array(
				'show_option_all'   => $tax_obj->labels->all_items,
				'show_option_none'  => '',
				'option_none_value' => '-1',
				'orderby'           => 'NAME',
				'order'             => 'ASC',
				'show_count'        => 1,
				'hide_empty'        => 1,
				'child_of'          => 0,
				'exclude'           => '',
				'echo'              => 1,
				'selected'          => isset( $_GET[ $tax ] ) ? sanitize_text_field( wp_unslash( $_GET[ $tax ] ) ) : '',
				'hierarchical'      => 1,
				'name'              => $tax,
				'id'                => $tax,
				'class'             => 'postform',
				'depth'             => 0,
				'tab_index'         => 0,
				'taxonomy'          => $tax,
				'hide_if_empty'     => true,
				'value_field'       => 'slug',
			);

			wp_dropdown_categories( $args );
		}
	}

	/**
	 * Sort columns.
	 *
	 * @since 1.12.0
	 *
	 * @param $query
	 */
	public static function pre_get_posts( $query ) {
		// Only in main WP query AND if an orderby query variable is designated.
		if ( is_admin() && $query->is_main_query() && 'wpm-testimonial' === $query->get( 'post_type' ) ) {
			if ( 'client_name' === $query->get( 'orderby' ) ) {
				$query->set( 'meta_key', 'client_name' );
				$query->set( 'orderby', 'meta_value' );
			}
			if ( 'submission_date' === $query->get( 'orderby' ) ) {
				$query->set( 'meta_key', 'submit_date' );
				$query->set( 'orderby', 'meta_value' );
			}
		}
	}

	/**
	 * Check if we are viewing the Trash.
	 *
	 * @since 1.16.0
	 */
	public static function is_viewing_trash() {
		return isset( $_GET['post_status'] ) && 'trash' === $_GET['post_status'];
	}

	/**
	 * Check if a column in admin list table is sorted.
	 *
	 * @since 1.16.0
	 */
	public static function is_column_sorted() {
		return isset( $_GET['orderby'] ) || ( isset( $_SERVER['REQUEST_URI'] ) && strstr( sanitize_url( $_SERVER['REQUEST_URI'] ), 'action=edit' ) ) || strstr( sanitize_url( $_SERVER['REQUEST_URI'] ), 'wp-admin/post-new.php' );
	}
}

Strong_Testimonials_Admin_List::init();
