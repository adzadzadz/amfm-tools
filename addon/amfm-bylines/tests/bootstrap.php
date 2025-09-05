<?php
require_once dirname( __FILE__ ) . '/../../../../wp-load.php';
require_once dirname( __FILE__ ) . '/../../../../wp-includes/pluggable.php';

function _manually_load_plugin() {
    require dirname( __FILE__ ) . '/../amfm-bylines.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array( 'amfm-bylines/amfm-bylines.php' ),
);
?>