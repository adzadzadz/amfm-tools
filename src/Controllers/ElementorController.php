<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

class ElementorController extends Controller
{
    public $actions = [
        'init' => 'initialize'
    ];

    public $filters = [];

    protected function bootstrap()
    {
        // Additional initialization if needed
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
}
