<?php

namespace adz\controllers;

use AdzWP\WordPressController as Controller;

class ACFController extends Controller {

    public $actions = [
        'wp' => 'setKeywordsToCookies'
    ];

    protected function bootstrap()
    {
        if (!class_exists('ACF')) {
            error_log('ACF_Helper: Advanced Custom Fields is not active.');
            return;
        }
    }

    public function setKeywordsToCookies()
    {
        // Skip cookie setting for AJAX, iframe, and admin requests
        if (wp_doing_ajax() || is_admin() || (defined('IFRAME_REQUEST') && IFRAME_REQUEST)) {
            return;
        }
        
        // Skip for Gravity Forms AJAX requests
        if (isset($_POST['gform_ajax']) || isset($_GET['gf_page'])) {
            return;
        }
        
        // Skip if this appears to be an embedded/iframe request
        if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'iframe') {
            return;
        }
        
        // Get the keywords from the ACF field
        $keywords = $this->getKeywords();

        // Set the keywords in cookies
        if (!empty($keywords['keywords'])) {
            setcookie('amfm_keywords', json_encode($keywords['keywords']), time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        }

        if (!empty($keywords['other_keywords'])) {
            setcookie('amfm_other_keywords', json_encode($keywords['other_keywords']), time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }

    private function getKeywords()
    {
        global $post;
        
        if (!$post) {
            return array('keywords' => array(), 'other_keywords' => array());
        }

        $keywords = get_field('keywords', $post->ID);
        $other_keywords = get_field('other_keywords', $post->ID);

        // Process keywords based on original ACF Helper logic
        return array(
            'keywords' => is_array($keywords) ? $keywords : array(),
            'other_keywords' => is_array($other_keywords) ? $other_keywords : array()
        );
    }

}