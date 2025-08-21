<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Asset_Manager {
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles( $hook ) {
        // Handle both main menu and submenu hooks
        if ( $hook !== 'toplevel_page_amfm' && $hook !== 'amfm_page_amfm-tools' ) {
            return;
        }
        
        wp_enqueue_style( 
            'amfm-admin-style', 
            plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/admin-style.css',
            array(),
            AMFM_Admin::get_version()
        );
        
        wp_enqueue_script(
            'amfm-admin-script',
            plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/admin-script.js',
            array('jquery'),
            AMFM_Admin::get_version(),
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('amfm-admin-script', 'amfmAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'exportNonce' => wp_create_nonce('amfm_export_nonce'),
        ));
    }
}