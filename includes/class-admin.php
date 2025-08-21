<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Admin {
    
    private $menu_manager;
    private $asset_manager;
    private $csv_importer;
    private $settings_manager;
    private $ajax_handler;
    private $data_exporter;
    
    /**
     * Get plugin version
     *
     * @return string
     */
    public static function get_version() {
        return defined( 'AMFM_TOOLS_VERSION' ) ? AMFM_TOOLS_VERSION : '2.1.0';
    }
    
    public static function init() {
        $instance = new self();
        $instance->load_dependencies();
        $instance->setup_hooks();
    }
    
    private function load_dependencies() {
        // Load admin classes
        require_once dirname( __FILE__ ) . '/admin/class-menu-manager.php';
        require_once dirname( __FILE__ ) . '/admin/class-asset-manager.php';
        require_once dirname( __FILE__ ) . '/admin/class-csv-importer.php';
        require_once dirname( __FILE__ ) . '/admin/class-settings-manager.php';
        require_once dirname( __FILE__ ) . '/admin/class-ajax-handler.php';
        require_once dirname( __FILE__ ) . '/admin/class-data-exporter.php';
        require_once dirname( __FILE__ ) . '/admin/class-page-renderer.php';
        
        // Initialize components
        $this->menu_manager = new AMFM_Menu_Manager();
        $this->asset_manager = new AMFM_Asset_Manager();
        $this->csv_importer = new AMFM_CSV_Importer();
        $this->settings_manager = new AMFM_Settings_Manager();
        $this->ajax_handler = new AMFM_AJAX_Handler();
        $this->data_exporter = new AMFM_Data_Exporter();
    }
    
    private function setup_hooks() {
        // Menu hooks
        add_action( 'admin_menu', array( $this->menu_manager, 'add_admin_menu' ) );
        
        // Asset hooks
        add_action( 'admin_enqueue_scripts', array( $this->asset_manager, 'enqueue_admin_styles' ) );
        
        // Import/export hooks
        add_action( 'admin_init', array( $this->csv_importer, 'handle_csv_upload' ) );
        add_action( 'admin_init', array( $this->csv_importer, 'handle_category_csv_upload' ) );
        add_action( 'admin_init', array( $this->data_exporter, 'handle_export' ) );
        
        // Settings hooks
        add_action( 'admin_init', array( $this->settings_manager, 'handle_excluded_keywords_update' ) );
        add_action( 'admin_init', array( $this->settings_manager, 'handle_elementor_widgets_update' ) );
        
        // AJAX hooks
        add_action( 'wp_ajax_amfm_get_post_type_taxonomies', array( $this->ajax_handler, 'ajax_get_post_type_taxonomies' ) );
        add_action( 'wp_ajax_amfm_export_data', array( $this->ajax_handler, 'ajax_export_data' ) );
    }
}