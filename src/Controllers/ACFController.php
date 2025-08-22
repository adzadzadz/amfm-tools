<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class ACFController extends Controller
{
    public $actions = [
        'init' => 'initialize',
        'wp' => 'setKeywordsToCookies'
    ];

    public $filters = [];

    protected function bootstrap()
    {
        // Check if ACF is active
        if (!class_exists('ACF')) {
            error_log('ACF_Helper: Advanced Custom Fields is not active.');
            return;
        }
    }

    public function initialize()
    {
        // WordPress initialization logic
        if ($this->isAdmin()) {
            // Admin-specific initialization
        }
        
        if ($this->isFrontend()) {
            // Frontend-specific initialization
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

    public function getKeywords()
    {
        // Get the current post ID to ensure we're reading from the correct page
        $post_id = get_queried_object_id();
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        // Get the keywords from the ACF field
        $keywords = get_field('amfm_keywords', $post_id);
        $otherKeywords = get_field('amfm_other_keywords', $post_id);

        // Return the keywords as an array
        return [
            'keywords' => $keywords ? explode(',', $keywords) : [],
            'other_keywords' => $otherKeywords ? explode(',', $otherKeywords) : []
        ];
    }
}
