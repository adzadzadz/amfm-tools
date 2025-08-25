<?php

namespace App\Services;

use AdzWP\Core\Service;
use AdzWP\Core\View;

/**
 * Page Template Service - provides consistent page layouts
 * 
 * Centralizes page rendering with a unified template system
 */
class PageTemplateService extends Service
{
    /**
     * Render a page using the main template
     * 
     * @param array $config Page configuration
     * @return string Rendered HTML
     */
    public function renderPage(array $config): string
    {
        // Validate required parameters
        $required = ['page_title', 'page_subtitle', 'page_icon', 'page_content'];
        foreach ($required as $param) {
            if (!isset($config[$param])) {
                throw new \InvalidArgumentException("Missing required parameter: {$param}");
            }
        }

        // Set template variables
        $template_vars = [
            'page_title' => $config['page_title'],
            'page_subtitle' => $config['page_subtitle'], 
            'page_icon' => $config['page_icon'],
            'page_content' => $config['page_content'],
            'show_results' => $config['show_results'] ?? false,
            'results' => $config['results'] ?? null,
            'results_type' => $config['results_type'] ?? 'Action'
        ];

        // Render using the main template
        return View::render('admin/layout/main-template', $template_vars);
    }

    /**
     * Output a page directly (for use in controllers)
     * 
     * @param array $config Page configuration
     */
    public function displayPage(array $config): void
    {
        echo $this->renderPage($config);
    }

    /**
     * Create a card-based content section
     * 
     * @param array $cards Array of card configurations
     * @param string $grid_class CSS class for the grid container
     * @return string Rendered cards HTML
     */
    public function renderCards(array $cards, string $grid_class = 'amfm-components-grid'): string
    {
        $html = '<div class="' . esc_attr($grid_class) . '">';
        
        foreach ($cards as $card) {
            $html .= $this->renderCard($card);
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a single card
     * 
     * @param array $card Card configuration
     * @return string Rendered card HTML
     */
    private function renderCard(array $card): string
    {
        $defaults = [
            'icon' => '📄',
            'title' => 'Card Title',
            'description' => 'Card description',
            'enabled' => true,
            'core' => false,
            'actions' => []
        ];

        $card = array_merge($defaults, $card);
        $card_class = 'amfm-component-card';
        $card_class .= $card['enabled'] ? ' amfm-component-enabled' : ' amfm-component-disabled';
        $card_class .= $card['core'] ? ' amfm-component-core' : '';

        $html = '<div class="' . esc_attr($card_class) . '">';
        
        // Card header
        $html .= '<div class="amfm-component-header">';
        $html .= '<div class="amfm-component-icon">' . esc_html($card['icon']) . '</div>';
        
        if (!empty($card['toggle'])) {
            $html .= '<div class="amfm-component-toggle">';
            if ($card['core']) {
                $html .= '<span class="amfm-core-label">Core</span>';
            } else {
                // Add toggle switch HTML if needed
                $html .= $card['toggle'];
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        // Card body
        $html .= '<div class="amfm-component-body">';
        $html .= '<h3 class="amfm-component-title">' . esc_html($card['title']) . '</h3>';
        $html .= '<p class="amfm-component-description">' . esc_html($card['description']) . '</p>';

        // Status (if not using custom cards)
        if (isset($card['show_status']) && $card['show_status']) {
            $html .= '<div class="amfm-component-status">';
            $html .= '<span class="amfm-status-indicator"></span>';
            $html .= '<span class="amfm-status-text">';
            if ($card['core']) {
                $html .= 'Always Active';
            } else {
                $html .= $card['enabled'] ? 'Enabled' : 'Disabled';
            }
            $html .= '</span></div>';
        }

        // Actions
        if (!empty($card['actions'])) {
            $html .= '<div class="amfm-component-actions">';
            foreach ($card['actions'] as $action) {
                $html .= $this->renderAction($action);
            }
            $html .= '</div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Render an action button
     * 
     * @param array $action Action configuration
     * @return string Rendered action HTML
     */
    private function renderAction(array $action): string
    {
        $defaults = [
            'type' => 'button',
            'text' => 'Action',
            'class' => 'amfm-info-button',
            'onclick' => '',
            'data' => []
        ];

        $action = array_merge($defaults, $action);
        
        $html = '<button type="' . esc_attr($action['type']) . '"';
        $html .= ' class="' . esc_attr($action['class']) . '"';
        
        if ($action['onclick']) {
            $html .= ' onclick="' . esc_attr($action['onclick']) . '"';
        }

        foreach ($action['data'] as $key => $value) {
            $html .= ' data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        $html .= '>' . esc_html($action['text']) . '</button>';
        
        return $html;
    }

    /**
     * Create a simple two-card layout for import/export style pages
     * 
     * @param array $cards Array with exactly 2 card configurations
     * @return string Rendered cards HTML with custom grid
     */
    public function renderSimpleCards(array $cards): string
    {
        if (count($cards) !== 2) {
            throw new \InvalidArgumentException('Simple cards layout requires exactly 2 cards');
        }

        $html = '<div class="amfm-import-export-grid">';
        
        foreach ($cards as $card) {
            $html .= $this->renderSimpleCard($card);
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a simple card (for import/export style)
     * 
     * @param array $card Card configuration
     * @return string Rendered card HTML
     */
    private function renderSimpleCard(array $card): string
    {
        $html = '<div class="amfm-import-export-card">';
        
        // Card header
        $html .= '<div class="amfm-card-header">';
        $html .= '<div class="amfm-card-icon">' . esc_html($card['icon']) . '</div>';
        $html .= '<h3 class="amfm-card-title">' . esc_html($card['title']) . '</h3>';
        $html .= '</div>';

        // Card body
        $html .= '<div class="amfm-card-body">';
        $html .= '<p class="amfm-card-description">' . esc_html($card['description']) . '</p>';

        // Actions
        if (!empty($card['actions'])) {
            $html .= '<div class="amfm-card-actions">';
            foreach ($card['actions'] as $action) {
                $html .= $this->renderSimpleAction($action);
            }
            $html .= '</div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Render a simple action button
     * 
     * @param array $action Action configuration  
     * @return string Rendered action HTML
     */
    private function renderSimpleAction(array $action): string
    {
        $html = '<button type="button" class="amfm-primary-button"';
        
        if (isset($action['onclick'])) {
            $html .= ' onclick="' . esc_attr($action['onclick']) . '"';
        }

        $html .= '>' . esc_html($action['text']) . '</button>';
        
        return $html;
    }
}