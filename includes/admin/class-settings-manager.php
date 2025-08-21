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
}