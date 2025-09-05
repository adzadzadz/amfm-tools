<?php
use PHPUnit\Framework\TestCase;

class Test_Amfm_Bylines_Admin extends TestCase {

    private $admin;

    protected function setUp(): void {
        // Mock the plugin name and version
        $plugin_name = 'amfm-bylines';
        $version = '1.0.0';

        // Create an instance of the class
        $this->admin = new Amfm_Bylines_Admin($plugin_name, $version);
    }

    public function test_enqueue_scripts() {
        // Mock WordPress functions
        WP_Mock::wpFunction('wp_enqueue_media', array(
            'times' => 1
        ));
        WP_Mock::wpFunction('wp_enqueue_script', array(
            'times' => 2,
            'args' => array(
                'bootstrap-js',
                'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
                array('jquery'),
                '5.2.3',
                true
            )
        ));
        WP_Mock::wpFunction('plugin_dir_url', array(
            'times' => 1,
            'args' => __FILE__,
            'return' => 'http://example.com/wp-content/plugins/amfm-bylines/'
        ));
        WP_Mock::wpFunction('wp_localize_script', array(
            'times' => 1,
            'args' => array(
                'amfm-bylines',
                'amfmLocalize',
                array(
                    'ajax_url' => 'http://example.com/wp-admin/admin-ajax.php',
                    'pageSelectorNonce' => 'nonce',
                    'saveBylineNonce' => 'nonce',
                    'removeBylineNonce' => 'nonce'
                )
            )
        ));
        WP_Mock::wpFunction('admin_url', array(
            'times' => 1,
            'args' => 'admin-ajax.php',
            'return' => 'http://example.com/wp-admin/admin-ajax.php'
        ));
        WP_Mock::wpFunction('wp_create_nonce', array(
            'times' => 3,
            'return' => 'nonce'
        ));

        // Call the method
        $this->admin->enqueue_scripts();

        // Verify that the functions were called
        WP_Mock::assertHooksAdded();
    }
}