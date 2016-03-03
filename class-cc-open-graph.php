<?php
/**
 * CC Open Graph
 *
 * @package   CC Open Graph
 * @author    David Cavins
 * @license   GPLv3
 * @copyright 2016 Community Commons
 */

/**
 * @package CC Open Graph
 * @author  David Cavins
 */
class CC_Open_Graph {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'cc-open-graph';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $attributes = array();

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Add "og:" and "fb:" namespaces to the <html> tag.
		add_filter( 'language_attributes', array( $this, 'add_namespaces'), 99 );

		// Add the meta tags that contain the Open Graph data.
		add_action( 'wp_head', array( $this, 'add_meta_html' ), 99 );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Add "og:" and "fb:" namespaces to the <html> tag.
	 *
	 * @since  1.0.0
	 *
	 * @param string $attr A space-separated list of language attributes.
	 *
	 * @return void
	 */
	public function add_namespaces( $attr ) {
		$attr .= ' prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#"';
		return $attr;
	}

	/**
	 * Add the meta tags that contain the Open Graph data.
	 *
	 * @since  1.0.0
	 *
	 * @return html
	 */
	public function add_meta_html() {
		// Create the attribute container we'll use to output the meta tags.
		$attributes = array();

		// Site info
		$attributes['og:locale'] = array( 'type' => 'property', 'content' => 'en_US' );

		$site_name = htmlentities( get_bloginfo( 'name' ) );
		$attributes['og:site_name'] = array( 'type' => 'property', 'content' => $site_name );

		/**
		 * Set typical defaults. These are used on the front page, blog page,
		 * site search, site activity directory.
		 */
		$type = 'website';
		$title = $site_name;

		// A fallback image will be provided if the image url is still empty after all.
		$image_url = '';

		// A fallback description will be provided if the description is still empty after all.
		$description = '';

		// Testing only
		$post_id = 'none set';

		// Most specific case is an article or an article in one of our group components.
		if ( is_single() || apply_filters( 'cc_open_graph_is_single', false ) ) {
			$type = 'article';

			// What post should we be looking at?
			$post_id = apply_filters( 'cc_open_graph_post_id', get_the_ID() );

			// Content title
			$title = apply_filters( 'cc_open_graph_title', get_the_title( $post_id ) );

			// Description (content excerpt)
			$description = apply_filters( 'cc_open_graph_description', $this->get_excerpt_by_id( $post_id ) );

			// Image
			$image_url = '';
			if ( has_post_thumbnail( $post_id ) ) {
				$attachment = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'large' );
				// Attachment will be of the form: array( 'src', 'w', 'h' )
				if ( ! empty( $attachment ) && is_array( $attachment ) ) {
					$image_url = current( $attachment );
				}
			}
			$image_url = apply_filters( 'cc_open_graph_image_url', $image_url );

			// Meta, only for articles.
			$published_date = get_the_date( 'c', $post_id );
			$attributes['article:published_time'] = array( 'type' => 'property', 'content' => $published_date );

		// Cases for being on a user or group page.
		} elseif ( $displayed_user_id = bp_displayed_user_id() ) {

			$title = $site_name . ' Member: ' . bp_core_get_user_displayname( $displayed_user_id );

			// For description, use the most recent public activity item, with fallback.?
			$last_update = $this->get_member_latest_update( $displayed_user_id );
			if ( $last_update ) {
				$description = $last_update;
			}

			$avatar_args = array(
				'item_id'       => $displayed_user_id,
				'object'        => 'user',
				'type'          => 'full',
				'html'          => false,
			);
			$image_url = bp_core_fetch_avatar( $avatar_args );

		} elseif ( bp_is_active( 'groups' ) && $current_group = groups_get_current_group() ) {

			$title = $site_name . ' Hub: ' . htmlentities( $current_group->name );
			// Group descriptions for private and public groups are public.
			$description = trim( htmlentities( strip_tags( strip_shortcodes( bp_get_group_description_excerpt( $current_group ) ) ) ) );
			$avatar_args = array(
				'item_id'       => $current_group->id,
				'object'        => 'group',
				'type'          => 'full',
				'html'          => false,
			);
			// @TODO: Make sure this uses the improved default.
			$image_url = bp_core_fetch_avatar( $avatar_args );

		}

		// Content title
		$attributes['og:title'] = array( 'type' => 'property', 'content' => $title );
		$attributes['name'] = array( 'type' => 'itemprop', 'content' => $title );
		$attributes['twitter:title'] = array( 'type' => 'name', 'content' => $title );

		// URL
		$url = ( is_ssl() ? 'https://' : 'http://' ) .  $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'];
		$attributes['og:url'] = array( 'type' => 'property', 'content' => $url );

		// Type
		$attributes['og:type'] = array( 'type' => 'property', 'content' => $type );

		// Description. Provide fallback if not set.
		if ( ! $description ) {
			$description = htmlentities( get_bloginfo( 'description' ) );
		}
		$attributes['og:description'] = array( 'type' => 'property', 'content' => $description );

		// Image
		// Use our default image if nothing has been set.
		if ( empty( $image_url ) ) {
			$image_url = plugins_url( 'img/community-commons-logo.png', __FILE__ );
		}
		$attributes['og:image'] = array( 'type' => 'property', 'content' => $image_url );
		$attributes['image'] = array( 'type' => 'itemprop', 'content' => $image_url );
		$attributes['twitter:image:src'] = array( 'type' => 'name', 'content' => $image_url );

		// Twitter-specific format
		$attributes['twitter:card'] = array( 'type' => 'name', 'content' => 'summary_large_image' );

		// Output it all.
		?>
		<!-- CC Open Graph Data post_id: <?php echo $post_id; ?>-->
		<?php
		foreach ( $attributes as $key => $value ) {
			?>
			<meta <?php echo $value['type']; ?>="<?php echo $key; ?>" content="<?php echo $value['content']; ?>"/>
			<?php
		}
		?>
		<!-- END CC Open Graph Data -->
		<?php
	}

	// Helper functions
	/**
	 * Get a post excerpt outside of the loop.
	 *
	 * @param int $post_id Post to generate an excerpt for.
	 *
	 * @return string The content of the excerpt.
	 */
	public function get_excerpt_by_id( $post_id ) {
	    $the_post = get_post( $post_id );

	    // If the post has a handcrafted excerpt, use it.
	    if ( ! empty( $the_post->post_excerpt ) ) {
		    $excerpt = $the_post->post_excerpt;
		    $excerpt = strip_tags( strip_shortcodes( $excerpt ) );
	    } else {
		    $excerpt = $the_post->post_content;
		    $excerpt_length = 35; // Sets excerpt length by word count
		    $excerpt = strip_tags( strip_shortcodes( $excerpt ) );
		    $words = explode( ' ', $excerpt, $excerpt_length + 1 );

		    if ( count( $words ) > $excerpt_length ) {
		        array_pop( $words );
		        array_push( $words, '...' );
		        $excerpt = implode( ' ', $words );
		    }
		}

	    return esc_attr( stripslashes( $excerpt ) );
	}

	/**
	 * Get a member's latest update in pretty raw form.
	 *
	 * @param int $user_id User to get the last update for.
	 *
	 * @return string The content of the excerpt.
	 */
	public function get_member_latest_update( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		if ( bp_is_user_inactive( $user_id ) ) {
			return false;
		}

		if ( ! $update = bp_get_user_meta( $user_id, 'bp_latest_update', true ) ) {
			return false;
		}

		// We want this to be pretty plain text.
		return htmlentities( trim( strip_tags( bp_create_excerpt( $update['content'], 358 ) ) ) );
	}

}
