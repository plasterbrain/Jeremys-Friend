<?php
/**
 * Register the Deal and Job Listing post types to use with the Jeremy theme.
 *
 * @package Jeremys_Friend
 * @since 1.0.0
 */
 
if ( ! class_exists( 'Jeremys_Friend_CPT' ) ) :
class Jeremys_Friend_CPT {
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $plugin_name
	 */
	private $plugin_name;

	/**
	 * The current version of the plugin as a semver string.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $version
	 */
	private $version;
	
	/**
	 * Array of CPT slugs registered by the plug-in.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var array $cpts
	 */
	private $cpts;
	
	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version    	The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		
		$this->cpts = array(
			'deal_cpt' => 'jeremy_deal',
			'job_cpt'  => 'jeremy_job',
		);
	}
	
	public function register_cpts() {
		foreach( $this->cpts as $post_type ) {
			add_action( 'init', array( $this, 'register_' . $post_type ) );
		}
		add_action( 'init', array( $this, 'register_meta' ) );
		
		$cron_name = 'jeremysfriend_maybe_expire_posts';
		add_action( $cron_name, array( $this, 'maybe_expire_posts' ) );
		if ( ! wp_next_scheduled( $cron_name ) ) {
			wp_schedule_event( time(), 'weekly', $cron_name );
		}
	}
	
	/**
	 * Returns an array of all the custom post type names registered with this
	 * plug-in.
	 *
	 * @since 1.0.0
	 * 
	 * @return array  An array of the strings used to register CPTs in WordPress.
	 */
	public function get_cpts() {
		return $this->cpts;
	}
	
	/**
	 * Checks whether the given post type is one of the custom ones registered
	 * with this plug-in.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $post_type   The post type.
	 * @return boolean            Whether the post type is a Jeremy CPT.
	 */
	public function is_our_cpt( $post_type ) {
		return in_array( $post_type, $this->cpts );
	}

	/**
	 * Registers the custom post type Deals under the slug 'jeremy_deal'.
	 * 
	 * @since 1.0.0
	 * 
	 * @see register_post_type()
	 */
	public function register_jeremy_deal() {
		$deal_cpt = $this->cpts['deal_cpt'];
		$args = array(
			'labels'              => array(
				'name'              => __( 'Deals', 'jeremys-friend' ),
				'singular_name'     => __( 'Deal', 'jeremys-friend' ),
				'add_new_item'      => __( 'Add New Deal', 'jeremys-friend' ),
				'edit_item'         => __( 'Edit Deal', 'jeremys-friend' ),
				'new_item'          => __( 'New Deal', 'jeremys-friend' ),
				'view_item'         => __( 'View Deal', 'jeremys-friend' ),
				'view_items'        => __( 'View Deals', 'jeremys-friend' ),
				'search_items'      => __( 'Search Deals', 'jeremys-friend' ),
				'not_found'         => __( 'No deals found', 'jeremys-friend' ),
				'all_items'         => __( 'All Deals', 'jeremys-friend' ),
				'archives'          => __( 'Special Offers', 'jeremys-friend' ),
			),
			'supports'            => array(
				'title',
				'editor', // Use Gutenberg editor if enabled
				'author',
				'excerpt',
				'thumbnail',
				'custom-fields',
				'trackbacks'
			),
			'rewrite'             => array(
				'pages'             => false,
			),
			'public'              => true,
			'capability_type'     => 'post',
			'has_archive'	        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-megaphone',
			'show_in_rest'        => true,
		);
		
		/**
		 * Filters the arguments before registering the Jeremy Deal post type. Do
		 * not use this filter to edit the post type slug! Use
		 * {@see jeremys_friend_cpt_slugs} instead.
		 * 
		 * @since 1.0.0
		 * 
		 * @param array $args The custom post type arguments.
		 * @return array      The filtered arguments.
		 */
		$args = apply_filters( 'jeremys_friend_register_deal', $args );
		
		$slug = $this->get_slug( $deal_cpt );
		// Overwrite the slug if Mr. or Ms. Script Kiddie ignored me
		$args['rewrite']['slug'] = $slug . '/%author%';
		
		register_post_type( $deal_cpt, $args );

		// Use prettier links and hide our ugly plugin-prefixed post type slug. 
		add_rewrite_rule(
			'^' . $slug . '/?$',
			'index.php?post_type=' . $deal_cpt,
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/([^/]+)/?$',
			'index.php?post_type=' . $deal_cpt . '&author_name=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/([^/]+)/page/?([0-9]{1,})/?$',
			'index.php?post_type=' . $deal_cpt . '&author_name=$matches[1]&paged=$matches[2]',
			'top'
		);
		
		add_filter( 'post_type_link', array( $this, 'author_permalinks' ), 10, 4 );
	}
	
	/**
	 * Registers the custom post type "jobs" under the name "jeremy_job" and
	 * adds rewrite rules to enable year- and month-based job archives.
	 * 
	 * @since 1.0.0
	 * 
	 * @see register_post_type()
	 */
	public function register_jeremy_job() {
		$job_cpt = $this->cpts['job_cpt'];
		$args = array(
			'labels'           => array(
				'name'           => __( 'Job Listings', 'jeremys-friend' ),
				'singular_name'  => __( 'Job Listing', 'jeremys-friend' ),
				'add_new_item'   => __( 'Add New Job Listing', 'jeremys-friend' ),
				'edit_item'      => __( 'Edit Job Listing', 'jeremys-friend' ),
				'new_item'       => __( 'New Job Listing', 'jeremys-friend' ),
				'view_item'      => __( 'View Job Listing', 'jeremys-friend' ),
				'view_items'     => __( 'View Job Listing', 'jeremys-friend' ),
				'search_items'   => __( 'Search Jobs', 'jeremys-friend' ),
				'not_found'      => __( 'No listings found', 'jeremys-friend' ),
				'all_items'      => __( 'All Jobs', 'jeremys-friend' ),
				'archives'       => __( 'Jobs'),
			),
			'supports'         => array(
				'title',
				'editor',
				'author',
				'excerpt',
				'custom-fields',
				'revisions',
				'trackbacks',
				'post-formats',
			),
			'public'           => true,
			'capability_type'  => 'post',
			'rewrite'          => array(
				'pages'          => true,
			),
			'has_archive'	     => true,
			'menu_position'    => 5,
			'menu_icon'        => 'dashicons-clipboard',
			'show_in_rest'     => true,
		);
		
		/**
		 * Filters the arguments before registering the Jeremy Job post type. Do
		 * not use this filter to edit the post type slug! Use
		 * {@see jeremys_friend_cpt_slugs} instead.
		 * 
		 * @since 1.0.0
		 * 
		 * @param array $args The custom post type arguments.
		 * @return array      The filtered post type arguments.
		 */
		$args = apply_filters( 'jeremys_friend_register_job', $args );
		
		$slug = $this->get_slug( $job_cpt );
		$args['rewrite']['slug'] = $slug . '/%author%/%year%';
		
		register_post_type( $job_cpt, $args );

		// Use prettier links and hide our ugly plugin-prefixed post type slug.
		add_rewrite_rule(
			$slug . '/?$',
			'index.php?post_type=' .$job_cpt,
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/([^/]+)/?$',
			'index.php?post_type=' . $job_cpt . '&author_name=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/([^/]+)/page/?([0-9]{1,})/?$',
			'index.php?post_type=' . $job_cpt . '&author_name=$matches[1]&paged=$matches[2]',
			'top'
		);

		add_rewrite_rule(
			'^' . $slug . '/([0-9]{4})/([0-9]{1,2})/?$',
			'index.php?post_type=' . $job_cpt . '&year=$matches[1]&monthnum=$matches[2]',
			'top'
		);
		
		add_rewrite_rule(
			'^' . $slug . '/([0-9]{4})/?$',
			'index.php?post_type=' . $job_cpt . '&year=$matches[1]',
			'top'
		);
		
		add_filter( 'post_type_link', array( $this, 'author_permalinks' ), 10, 4 );
	}
	
	/**
	 * Replaces the %author% placeholder with the slug of the post author in
	 * deal post type permalinks.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $post_link The post permalink.
	 * @param WP_Post $post     The post object.
	 * @param bool $keep        Whether to keep the post name. Unused.
	 * @param bool $sample      Whether this is a sample link. Unused.
	 * @return string           The new permalink.
	 */
	public function author_permalinks( $post_link, $post, $keep, $sample ) {
		if ( in_array( $post->post_type, $this->cpts ) ) {
			$author = get_userdata( $post->post_author )->user_nicename;
			$post_link = str_replace( '%author%', sanitize_title( $author ), $post_link );
		}
		return $post_link;
	}
	
	/**
	 * Registers some meta fields for our custom post types, allowing WordPress to
	 * handle sanitization and for these fields to show up in the WP REST API.
	 * 
	 * @since 1.0.0
	 */
	public function register_meta() {
		register_post_meta(
			$this->cpts['deal_cpt'],
			'_jeremysfriend_code',
			array(
				'type'              => 'string',
				'description'       => __( 'The coupon or discount code.', 'jeremy' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_text_field' ),
				'show_in_rest'      => true,
			),
		);
		
		register_post_meta(
			$this->cpts['deal_cpt'],
			'_jeremysfriend_expiry',
			array(
				'type'              => 'string',
				'description'       => __( 'The expiration date for this offer.', 'jeremy' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_text_field' ),
				'show_in_rest'      => true,
			),
		);
		
		register_post_meta(
			$this->cpts['deal_cpt'],
			'_jeremysfriend_fineprint',
			array(
				'type'              => 'string',
				'description'     => __( 'Any fine print for this offer.', 'jeremy' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'wp_kses_post' ),
				'show_in_rest'      => true,
			),
		);
		
		register_post_meta(
			$this->cpts['deal_cpt'],
			'_jeremysfriend_link',
			array(
				'type'              => 'string',
				'description'       => __( 'An external link for this deal.', 'jeremy' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_url' ),
				'show_in_rest'      => true,
			),
		);
		
		register_post_meta(
			$this->cpts['job_cpt'],
			'_jeremysfriend_link',
			array(
				'type'              => 'string',
				'description'       => __( 'An external link for this job listing.', 'jeremy' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_url' ),
				'show_in_rest'      => true,
			),
		);
		
		register_post_meta(
			$this->cpts['job_cpt'],
			'_jeremysfriend_expiry',
			array(
				'type'              => 'string',
				'description'       => __( 'The expiration date for this job listing', 'jeremy' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_text_field' ),
				'show_in_rest'      => true,
			),
		);
	}
	
	/**
	 * Referenced by multiple functions so that the Deal slug is one consistent,
	 * translatable and filterable string.
	 * 
	 * @since 1.0.0
	 * 
	 * @return string $slug  The filtered/translated slug.
	 */
	public function get_slug( $type ) {
		$slugs = array(
			'jeremy_deal' => _x( 'deals', 'Deal CPT slug', 'jeremys-friend' ),
			'jeremy_job'  => _x( 'jobs', 'Job listing CPT slug', 'jeremys-friend' ),
		);
		
		/**
		 * Filters the slugs associated with each custom post type.
		 *
		 * @since 1.0.0
		 * 
		 * @param array @slugs  An array mapping prefixed post type slugs (e.g.
		 *                      "jeremy_deal") to nicer slugs for use in permalinks
		 *                      (e.g. "deals").
		 * @return array        The filtered array.
		 */
		$slugs = apply_filters( 'jeremys_friend_cpt_slugs', $slugs );
		
		if ( array_key_exists( $type, $slugs ) ) {
			return $slugs[$type];
		} else {
				return sanitize_title_with_dashes( $slug );
		}
	}
	
	/**
	 * Sets a default post title if one is not set.
	 * 
	 * @since 1.0.0
	 *
	 * @param array $data  The array of post data to be saved.
	 * @return array       The data with a default title set.
	 */
	function set_default_title( $data ) {
		$title_defaults = array(
			'jeremy_deal' => __( 'Special Offer', 'jeremys-friend' ),
			'jeremy_job'  => __( 'Help Wanted', 'jeremys-friend' ),
		);
		
		if ( array_key_exists( $data['post_type'], $title_defaults ) ) {
			if ( empty( $data['post_title'] ) ) {
				$data['post_title'] = $title_defaults[$data['post_type']];
				$data['post_title'] = sanitize_text_field( $data['post_title'] );
			}
		}
		return $data;
	}
	
	/**
	 * Replaces tags in the rewrite slug for the custom post types with the
	 * post year and month.
	 * 
	 * @since 1.0.0
	 *
	 * @param string $url    The post permalink.
	 * @param WP_Post $post  The current post object.
	 */
	public function filter_cpt_link( $url, $post ) {
		if ( $this->is_our_cpt( get_post_type( $post ) ) ) {
			$url = str_replace( '%year%', get_the_date( 'Y', $post->ID ), $url );
			$url = str_replace( '%monthnum%', get_the_date( 'm', $post->ID ), $url );
		}
		return $url;
	}
	
	/**
	 * Filters the result of get_post_type_archive_link() for Job Listings,
	 * which normally returns the value of rewrite['slug'].
	 * 
	 * @since 1.0.0
	 * 
	 * @see get_post_type_archive_link
	 */
	public function filter_cpt_archive_link( $url, $post_type ) {
		if ( $this->is_our_cpt( $post_type ) ) {
			$url = home_url( user_trailingslashit( $this->get_slug( $post_type ), 'post_type_archive' ) );
		}
		return $url;
	}
	
  /**
   * Sets any deal or job posts with an expiration date in the past to private.
   * 
   * @since 1.0.0
   */
	public function maybe_expire_posts() {
		$expiry_key = '_jeremysfriend_expiry';
		$posts = new WP_Query( array(
			'post_type'    => array( 'jeremy_deal', 'jeremy_job' ),
			'meta_query'   => array(
				array(
					'key'     => $expiry_key,
					'compare' => 'EXISTS',
				),
			),
		) );

		if ( $posts->have_posts() ) {
			$now = current_datetime();
			
			while ( $posts->have_posts() ) {
				$posts->the_post();
				$post_id = get_the_id();
				
				$expires = get_post_meta( $post_id, $expiry_key, true );
				if ( ! is_string( $expires ) ) {
					continue;
				}
				
				$expires = new DateTimeImmutable( $expires );
				if ( $expires < $now ) {
					$this_post = array(
						'ID'     				=> $post_id,
						'post_status'   =>  'private'
					);
					
          echo 'blah';
					wp_update_post( $this_post );
				}
			}
		}
	}
}
endif;