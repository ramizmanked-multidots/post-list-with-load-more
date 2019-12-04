<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ramizmanked.com
 * @since      1.0.0
 *
 * @package    Post_List_With_Load_More
 * @subpackage Post_List_With_Load_More/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Post_List_With_Load_More
 * @subpackage Post_List_With_Load_More/public
 * @author     Ramiz Manked <ramiz.manked@gmail.com>
 */
class Post_List_With_Load_More_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_shortcode( 'post_list_with_load_more', array( $this, 'render_posts_list' ) );
		add_action("wp_ajax_fetch_posts_list", array( $this, 'fetch_posts_list' ) );
		add_action("wp_ajax_nopriv_fetch_posts_list", array( $this, 'fetch_posts_list' ) );
	}

	public function render_posts_list($atts) {
		ob_start();
		$category_ids = array();
		$post_type = ( ! empty( $atts['post_type'] ) ) ? esc_attr( $atts['post_type'] ) : '';
		$categories = ( ! empty( $atts['categories'] ) ) ? esc_attr( $atts['categories'] ) : '';
		$categories = str_replace(' ', '', $categories);
		if( ! empty( $categories ) ) {
			if ( strpos( $categories, ',' ) !== false ) {
				$categories = explode(',', $categories);
				foreach ( $categories as $category ) {
					$cat_obj = get_category_by_slug( $category );
					if( $cat_obj instanceof WP_Term ) {
						$category_ids[] = $cat_obj->term_id;
					}
				}
			} else {
				$cat_obj = get_category_by_slug( $categories );
				if( $cat_obj instanceof WP_Term ) {
					$category_ids[] = $cat_obj->term_id;
				}
			}
		}
		$tags = ( ! empty( $atts['tags'] ) ) ? esc_attr( $atts['tags'] ) : array();
		$tags = str_replace(' ', '', $tags);
		if ( ! empty( $tags ) ) {
			if ( strpos( $tags, ',' ) !== false ) {
				$tags = explode(',', $tags);
			} else {
				$tags = array( $tags );
			}
		}
		$limit = ( ! empty( $atts['limit'] ) ) ? esc_attr( $atts['limit'] ) : 6;
		$orderby = ( ! empty( $atts['orderby'] ) ) ? esc_attr( $atts['orderby'] ) : 'date';
		$order = ( ! empty( $atts['order'] ) ) ? esc_attr( $atts['order'] ) : 'DESC';
		$args = array(
			'numberposts'      => $limit,
			'orderby'          => $orderby,
			'order'            => $order,
			'post_type'        => $post_type,
			'category__in'     => $category_ids,
			'tag_slug__in'     => $tags,
			'suppress_filters' => false,
		);
		$blogs = get_posts( $args );
		?>
		<div class="custom-posts-wrapper">
			<?php
			if ( count( $blogs ) > 0 ) {
			    $additional_classes = '';
			    $style = get_option('layout_style');
				$additional_classes .= ( '' !== $style && null !== $style ) ? ' style_'.$style : '';
				$loadmore = get_option('loadmore');
				$additional_classes .= ( '' !== $loadmore && null !== $loadmore ) ? ' more_on_'.$loadmore : '';
				?>
				<div class="custom-post-list <?php echo $additional_classes ?>">
					<?php
					foreach ( $blogs as $blog ) {
						$id = $blog->ID;
						$default_image = plugin_dir_url(__FILE__ ) . 'images/noimage.jpg';
						$blog_image = ( ! empty( get_the_post_thumbnail_url($id) ) ) ? esc_url( get_the_post_thumbnail_url($id) ) : $default_image;
						?>
						<div class="custom-post">
							<div class="custom-post-image">
								<img width="345" height="245" src="<?php echo $blog_image ?>" alt="<?php echo esc_attr( get_the_title($id) );?>" />
							</div>
							<div class="custom-post-con">
								<h4 class="custom-post-title"><a href="<?php echo get_the_permalink($id);?>"><?php echo esc_attr( get_the_title($id) );?></a></h4>
								<div class="custom-post-content">
									<?php
									//$blog_content = $blog->post_content;
									$blog_content = $blog->post_content;
									$word_limit = 40;
									if ( str_word_count($blog_content) > $word_limit ) {
										$blog_content = '<p>' . strip_shortcodes( wp_trim_words( $blog_content, $word_limit, '[...]' ) ) . '</p>';
										$blog_content .= '<div class="post-read-more"><a href="'.get_the_permalink($id).'">Load More</a></div>';
									}
									echo $blog_content;
									?>
								</div>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<form class="custom-post-form <?php echo $additional_classes ?>" id="custom-post-form">
					<input id="post-args" type="hidden" value="<?php echo str_replace('"', '\'', json_encode($args)) ?>" />
					<button data-page="1" data-limit="<?php echo $limit ?>">View More</button>
					<img style="display: none" id="load-more-image" height="35" width="35" src="<?php echo plugin_dir_url(__FILE__) . 'images/loading.svg' ?>" alt="Loading" />
				</form>
				<?php
			} else {
				?>
				<p>No posts found.</p>
				<?php
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @return string
	 */
	public function fetch_posts_list() {
		$data = $_POST;
		$post_html = null;
		if ( ! empty ( $data['args'] ) ) {
			$args = json_decode(str_replace("\'", '"', $data['args'] ));
			$args->offset = $data['page'] * $data['limit'];
			$blogs = get_posts( $args );
			if ( count( $blogs ) > 0 ) {
				foreach ( $blogs as $blog ) {
					$id = $blog->ID;
					$default_image = plugin_dir_url(__FILE__ ) . 'images/noimage.jpg';
					$blog_image = ( ! empty( get_the_post_thumbnail_url($id) ) ) ? esc_url( get_the_post_thumbnail_url($id) ) : $default_image;
					$post_html .= '
                <div class="custom-post">
                    <div class="custom-post-image">
                        <img width="345" height="245" src="' . $blog_image . '" alt="' . esc_attr( get_the_title( $id ) ) . '" />
                    </div>
                    <div class="custom-post-con">
                        <h4 class="custom-post-title"><a href="' . get_the_permalink( $id ) . '">' . esc_attr( get_the_title( $id ) ) . '</a></h4>
                        <div class="custom-post-content">';

					$blog_content = $blog->post_content;
					$word_limit = 40;
					if ( str_word_count($blog_content) > $word_limit ) {
						$blog_content = '<p>' . strip_shortcodes( wp_trim_words( $blog_content, $word_limit, '[...]' ) ) . '</p>';
						$blog_content .= '<div class="post-read-more"><a href="'.get_the_permalink($id).'">Load More</a></div>';
					}
					$post_html .= $blog_content;
					$post_html .= '
                        </div>
                    </div>
                </div>';
				}
				if ( count( $blogs ) < $data['limit'] ) {
					echo '<span style="display: none">remove-view-more</span>';
				}
			} else {
				echo '<span style="display: none">remove-view-more</span>';
			}
		}
		echo $post_html;
		exit;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Post_List_With_Load_More_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Post_List_With_Load_More_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/post-list-with-load-more-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Post_List_With_Load_More_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Post_List_With_Load_More_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/post-list-with-load-more-public.js', array( 'jquery' ), $this->version, false );
        wp_localize_script( $this->plugin_name,'myAjax', array( 'ajaxurl' => admin_url('admin-ajax.php') ) );
	}

}
