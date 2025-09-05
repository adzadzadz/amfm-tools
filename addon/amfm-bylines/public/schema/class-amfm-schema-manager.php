<?php

/**
 * The schema manager class.
 *
 * Coordinates all schema functionality including custom schema fields,
 * shortcode parsing, location data handling, and schema merging.
 *
 * @link       https://adzjo.online/adz
 * @since      3.1.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/public/schema
 */

class Amfm_Schema_Manager {

    /**
     * The plugin name.
     *
     * @since    3.1.0
     * @access   private
     * @var      string    $plugin_name    The plugin name.
     */
    private $plugin_name;

    /**
     * The plugin version.
     *
     * @since    3.1.0
     * @access   private
     * @var      string    $version    The plugin version.
     */
    private $version;

    /**
     * The ACF fields handler.
     *
     * @since    3.1.0
     * @access   private
     * @var      Amfm_ACF_Schema_Fields    $acf_fields    The ACF fields handler.
     */
    private $acf_fields;

    /**
     * The shortcode parser.
     *
     * @since    3.1.0
     * @access   private
     * @var      Amfm_Shortcode_Parser    $shortcode_parser    The shortcode parser.
     */
    private $shortcode_parser;

    /**
     * The location handler.
     *
     * @since    3.1.0
     * @access   private
     * @var      Amfm_Location_Handler    $location_handler    The location handler.
     */
    private $location_handler;

    /**
     * The schema merger.
     *
     * @since    3.1.0
     * @access   private
     * @var      Amfm_Schema_Merger    $schema_merger    The schema merger.
     */
    private $schema_merger;

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.1.0
     * @param    string    $plugin_name    The plugin name.
     * @param    string    $version        The plugin version.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->load_dependencies();
        $this->init_components();
    }

    /**
     * Load the required dependencies.
     *
     * @since    3.1.0
     * @access   private
     */
    private function load_dependencies() {
        $plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH . 'public/schema/' : plugin_dir_path(__FILE__);
        require_once $plugin_path . 'class-amfm-acf-schema-fields.php';
        require_once $plugin_path . 'class-amfm-shortcode-parser.php';
        require_once $plugin_path . 'class-amfm-location-handler.php';
        require_once $plugin_path . 'class-amfm-schema-merger.php';
    }

    /**
     * Initialize the schema components.
     *
     * @since    3.1.0
     * @access   private
     */
    private function init_components() {
        $this->acf_fields = new Amfm_ACF_Schema_Fields($this->plugin_name, $this->version);
        $this->location_handler = new Amfm_Location_Handler($this->plugin_name, $this->version);
        $this->shortcode_parser = new Amfm_Shortcode_Parser($this->plugin_name, $this->version, $this->location_handler);
        $this->schema_merger = new Amfm_Schema_Merger($this->plugin_name, $this->version);
    }

    /**
     * Initialize the schema manager.
     *
     * @since    3.1.0
     */
    public function init() {
        add_action('wp_head', array($this, 'output_custom_schema'), 15);
        
        $this->acf_fields->init();
    }

    /**
     * Output custom schema to the frontend.
     *
     * @since    3.1.0
     */
    public function output_custom_schema() {
        global $post;

        if (!$post) {
            return;
        }

        $custom_schema = get_field('amfm_custom_schema', $post->ID);
        
        if (empty($custom_schema)) {
            return;
        }

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AMFM Schema Manager: Processing schema for post ID ' . $post->ID);
            error_log('AMFM Schema Manager: Raw schema: ' . substr($custom_schema, 0, 200));
        }

        $parsed_schema = $this->shortcode_parser->parse($custom_schema);
        
        if ($parsed_schema === false) {
            error_log('AMFM Schema Manager: Failed to parse schema for post ID ' . $post->ID);
            return;
        }
        
        $final_schema = $this->schema_merger->merge_with_byline_schema($parsed_schema);
        
        if (!empty($final_schema)) {
            echo '<script type="application/ld+json">' . wp_json_encode($final_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
        }
    }

    /**
     * Get the ACF fields handler.
     *
     * @since    3.1.0
     * @return   Amfm_ACF_Schema_Fields    The ACF fields handler.
     */
    public function get_acf_fields() {
        return $this->acf_fields;
    }

    /**
     * Get the shortcode parser.
     *
     * @since    3.1.0
     * @return   Amfm_Shortcode_Parser    The shortcode parser.
     */
    public function get_shortcode_parser() {
        return $this->shortcode_parser;
    }

    /**
     * Get the location handler.
     *
     * @since    3.1.0
     * @return   Amfm_Location_Handler    The location handler.
     */
    public function get_location_handler() {
        return $this->location_handler;
    }

    /**
     * Get the schema merger.
     *
     * @since    3.1.0
     * @return   Amfm_Schema_Merger    The schema merger.
     */
    public function get_schema_merger() {
        return $this->schema_merger;
    }
}