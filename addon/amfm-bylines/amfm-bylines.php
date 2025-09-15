<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'AMFM_BYLINES_VERSION' ) ) {
	define( 'AMFM_BYLINES_VERSION', '3.1.2' );
}

$plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH : plugin_dir_path( __FILE__ );

require_once $plugin_path . 'includes/class-amfm-bylines-loader.php';
require_once $plugin_path . 'includes/class-amfm-bylines-i18n.php';
require_once $plugin_path . 'public/class-amfm-bylines-public.php';
require_once $plugin_path . 'public/schema/class-amfm-schema-manager.php';

$loader = new Amfm_Bylines_Loader();

$plugin_i18n = new Amfm_Bylines_i18n();
$loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

$plugin_public = new Amfm_Bylines_Public( 'amfm-bylines', AMFM_BYLINES_VERSION );
$loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
$loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
$loader->add_action('init', $plugin_public, 'init');
$loader->add_action('wp_ajax_amfm_fetch_posts', $plugin_public, 'amfm_fetch_related_posts');
$loader->add_action('wp_ajax_nopriv_amfm_fetch_posts', $plugin_public, 'amfm_fetch_related_posts');

$schema_manager = new Amfm_Schema_Manager( 'amfm-bylines', AMFM_BYLINES_VERSION );
$loader->add_action('init', $schema_manager, 'init');

$loader->run();