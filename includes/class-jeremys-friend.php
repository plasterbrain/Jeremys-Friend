<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both
 * the public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    Jeremys_Friend
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 */
class Jeremys_Friend {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $plugin_name
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin as a semver string.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string $version
	 */
	protected $version;
	
	/**
	 * Whether to register custom post types and related functionality.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var bool $use_cpt
	 */
	protected $use_cpt;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @TODO Maybe $use_cpt can be set by the user. Who knows!
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'JEREMYS_FRIEND_VERSION' ) ) {
			$this->version = JEREMYS_FRIEND_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'jeremys-friend';
		
		$this->use_cpt = true;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Jeremys_Friend_Loader. Orchestrates the hooks of the plugin.
	 * - Jeremys_Friend_i18n. Defines internationalization functionality.
	 * - Jeremys_Friend_Admin. Defines all hooks for the admin area.
	 * - Jeremys_Friend_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-jeremys-friend-admin.php';
		require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-jeremys-friend-integrations.php';
	 	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-jeremys-friend-cpt.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		load_plugin_textdomain(
			'jeremys-friend', false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages'
		);
	}
	
	/**
	 * Register all of the hooks related to custom post type registration
	 * and permalinks.
	 *
	 * @since    1.0.0
	 * @access   private
	 */

	private function define_hooks() {
		if ( $this->use_cpt ) {
			$plugin_cpt = new Jeremys_Friend_CPT(
				$this->get_plugin_name(),
				$this->get_version() );
			$plugin_cpt->register_cpts();

			add_filter( 'wp_insert_post_data',
				array( $plugin_cpt, 'set_default_title' ), 10 );
			
			add_action( 'post_type_link',
				array( $plugin_cpt, 'filter_cpt_link' ), 10, 2 );

			add_action( 'post_type_archive_link',
				array( $plugin_cpt, 'filter_cpt_archive_link' ), 10, 2 );
		}
		
		$admin = new Jeremys_Friend_Admin(
			$this->get_plugin_name(),
			$this->get_version(),
			$this->use_cpt );
		$integrations = new Jeremys_Friend_Integrations(
			$this->get_plugin_name(),
			$this->get_version() );
	}
	
	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_bp = new Jeremys_Friend_Buddypress(  );

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
