<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMFM_Menu_Manager {
    
    /**
     * Add admin menu under AMFM main menu
     */
    public function add_admin_menu() {
        // Check if main AMFM menu exists, if not create it
        if ( ! $this->main_menu_exists() ) {
            add_menu_page(
                __('AMFM', 'amfm-tools'), // Page title
                __('AMFM', 'amfm-tools'), // Menu title
                'manage_options', // Capability
                'amfm', // Menu slug
                array( $this, 'admin_page_callback' ), // Callback function
                'dashicons-admin-tools', // Icon
                2 // Position
            );
        }
        
        // Add Tools submenu
        add_submenu_page(
            'amfm',
            __('Tools', 'amfm-tools'),
            __('Tools', 'amfm-tools'),
            'manage_options',
            'amfm-tools',
            array( $this, 'admin_page_callback' )
        );
    }
    
    /**
     * Check if main AMFM menu already exists
     */
    private function main_menu_exists() {
        global $menu;
        if ( ! is_array( $menu ) ) {
            return false;
        }
        
        foreach ( $menu as $menu_item ) {
            if ( isset( $menu_item[2] ) && $menu_item[2] === 'amfm' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Admin page callback - delegate to page renderer
     */
    public function admin_page_callback() {
        $page_renderer = new AMFM_Page_Renderer();
        $page_renderer->render_admin_page();
    }
}