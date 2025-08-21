<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Settings_Manager {
    
    /**
     * Handle excluded keywords update
     */
    public function handle_excluded_keywords_update() {
        if ( ! isset( $_POST['amfm_excluded_keywords_nonce'] ) || 
             ! wp_verify_nonce( $_POST['amfm_excluded_keywords_nonce'], 'amfm_excluded_keywords_update' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $excluded_keywords = isset( $_POST['excluded_keywords'] ) ? sanitize_textarea_field( $_POST['excluded_keywords'] ) : '';
        
        // Convert textarea input to array
        $keywords_array = array_filter( array_map( 'trim', explode( "\n", $excluded_keywords ) ) );
        
        // Update the option
        update_option( 'amfm_excluded_keywords', $keywords_array );
        
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success"><p>Excluded keywords updated successfully!</p></div>';
        });
        
        wp_redirect( admin_url( 'admin.php?page=amfm-tools&tab=shortcodes&updated=1' ) );
        exit;
    }

    /**
     * Handle Elementor widgets update
     */
    public function handle_elementor_widgets_update() {
        if ( ! isset( $_POST['amfm_elementor_widgets_nonce'] ) || 
             ! wp_verify_nonce( $_POST['amfm_elementor_widgets_nonce'], 'amfm_elementor_widgets_update' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $enabled_widgets = isset( $_POST['enabled_widgets'] ) ? array_map( 'sanitize_text_field', $_POST['enabled_widgets'] ) : array();
        
        // Update the option
        update_option( 'amfm_elementor_enabled_widgets', $enabled_widgets );
        
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success"><p>Elementor widget settings updated successfully!</p></div>';
        });
        
        wp_redirect( admin_url( 'admin.php?page=amfm-tools&tab=elementor&updated=1' ) );
        exit;
    }

    /**
     * Handle component settings update
     */
    public function handle_component_settings_update() {
        if ( ! isset( $_POST['amfm_component_settings_nonce'] ) || 
             ! wp_verify_nonce( $_POST['amfm_component_settings_nonce'], 'amfm_component_settings_update' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $enabled_components = isset( $_POST['enabled_components'] ) ? array_map( 'sanitize_text_field', $_POST['enabled_components'] ) : array();
        
        // Update the option
        update_option( 'amfm_enabled_components', $enabled_components );
        
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success"><p>Component settings updated successfully!</p></div>';
        });
        
        wp_redirect( admin_url( 'admin.php?page=amfm-tools&tab=dashboard&updated=1' ) );
        exit;
    }

    /**
     * AJAX handler for component settings update
     */
    public function ajax_component_settings_update() {
        check_ajax_referer( 'amfm_component_settings_update', 'amfm_component_settings_nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $enabled_components = isset( $_POST['enabled_components'] ) ? array_map( 'sanitize_text_field', $_POST['enabled_components'] ) : array();
        
        // Always ensure core components are included
        $core_components = array( 'acf_helper', 'import_export' );
        $enabled_components = array_unique( array_merge( $enabled_components, $core_components ) );
        
        // Update the option
        update_option( 'amfm_enabled_components', $enabled_components );
        
        wp_die( 'success' );
    }

    /**
     * AJAX handler for elementor widgets update
     */
    public function ajax_elementor_widgets_update() {
        check_ajax_referer( 'amfm_elementor_widgets_update', 'amfm_elementor_widgets_nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $enabled_widgets = isset( $_POST['enabled_widgets'] ) ? array_map( 'sanitize_text_field', $_POST['enabled_widgets'] ) : array();
        
        // Update the option
        update_option( 'amfm_elementor_enabled_widgets', $enabled_widgets );
        
        wp_die( 'success' );
    }
}