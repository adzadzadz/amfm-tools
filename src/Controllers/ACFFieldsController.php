<?php

namespace App\Controllers;

use AdzWP\Core\Controller;
use App\Services\ACFService;

class ACFFieldsController extends Controller
{
    public $actions = [
        'acf/include_fields' => 'registerFields',
        'init' => 'registerPostTypes',
    ];

    public $filters = [
        'enter_title_here' => [
            'callback' => 'customTitlePlaceholder',
            'priority' => 10,
            'accepted_args' => 2
        ]
    ];

    private $acfService;

    protected function bootstrap()
    {
        $this->acfService = new ACFService();
    }

    /**
     * Register ACF fields
     */
    public function registerFields()
    {
        $this->acfService->registerFieldGroups();
    }

    /**
     * Register custom post types
     */
    public function registerPostTypes()
    {
        $this->acfService->registerPostTypes();
    }

    /**
     * Custom title placeholders for post types
     */
    public function customTitlePlaceholder($default, $post)
    {
        switch ($post->post_type) {
            case 'staff':
                return 'Staff Name';
            case 'ceu':
                return 'CEU Title';
            default:
                return $default;
        }
    }

    /**
     * Get ACF service for admin pages
     */
    public function getACFService()
    {
        return $this->acfService;
    }
}