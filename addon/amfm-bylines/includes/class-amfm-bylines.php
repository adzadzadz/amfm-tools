<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://adzjo.online/adz
 * @since      1.0.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/includes
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
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/includes
 * @author     Adrian T. Saycon <adzbite@gmail.com>
 */
class Amfm_Bylines {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Amfm_Bylines_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'AMFM_BYLINES_VERSION' ) ) {
			$this->version = AMFM_BYLINES_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'amfm-bylines';

		$this->load_dependencies();
		$this->set_locale();
		// $this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Amfm_Bylines_Loader. Orchestrates the hooks of the plugin.
	 * - Amfm_Bylines_i18n. Defines internationalization functionality.
	 * - Amfm_Bylines_Admin. Defines all hooks for the admin area.
	 * - Amfm_Bylines_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		$plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH : plugin_dir_path( dirname( __FILE__ ) );

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once $plugin_path . 'includes/class-amfm-bylines-loader.php';

		/**
		 * ACF Related dependencies
		 */
		// require_once $plugin_path . 'includes/plugins/acf.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once $plugin_path . 'includes/class-amfm-bylines-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		// require_once $plugin_path . 'admin/class-amfm-bylines-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once $plugin_path . 'public/class-amfm-bylines-public.php';

		/**
		 * Schema module dependencies
		 */
		require_once $plugin_path . 'public/schema/class-amfm-schema-manager.php';

		$this->loader = new Amfm_Bylines_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Amfm_Bylines_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Amfm_Bylines_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Amfm_Bylines_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'amfm_bylines_register_settings' );

		// add_action('wp_ajax_fetch_pages', 'fetch_pages');
		$this->loader->add_action('wp_ajax_fetch_pages', $plugin_admin, 'fetch_pages');
		$this->loader->add_action('wp_ajax_save_amfm_bylines', $plugin_admin, 'save_amfm_bylines');
		$this->loader->add_action('wp_ajax_remove_amfm_byline', $plugin_admin, 'remove_amfm_byline');
		$this->loader->add_action('wp_ajax_toggle_function', $plugin_admin, 'toggle_function');
		$this->loader->add_action('init', $plugin_admin, 'add_categories_and_tags_to_pages');
		$this->loader->add_action('wp_ajax_update_staff_order', $plugin_admin, 'update_staff_order_callback');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Amfm_Bylines_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// best hook to add my filter manage_bylines_schema
		$this->loader->add_action('init', $plugin_public, 'init');
		// $this->loader->add_action('init', $plugin_public, 'init');

		$this->loader->add_action('wp_ajax_amfm_fetch_posts', $plugin_public, 'amfm_fetch_related_posts');
		$this->loader->add_action('wp_ajax_nopriv_amfm_fetch_posts', $plugin_public, 'amfm_fetch_related_posts');

		/**
		 * Schema module initialization
		 */
		$schema_manager = new Amfm_Schema_Manager( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action('init', $schema_manager, 'init');

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
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
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Amfm_Bylines_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
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
