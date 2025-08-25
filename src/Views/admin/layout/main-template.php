<?php
if (!defined('ABSPATH')) exit;

/**
 * Main Admin Page Template
 * 
 * This template provides a consistent layout for all AMFM Tools admin pages
 * 
 * Required variables:
 * - $page_title: string - The main page title
 * - $page_subtitle: string - The subtitle/description
 * - $page_icon: string - The emoji icon for the page
 * - $page_content: string - The main page content HTML
 * 
 * Optional variables:
 * - $show_results: bool - Whether to show import/action results
 * - $results: array - Results data to display
 * - $results_type: string - Type of results (for display)
 */

// Set defaults for optional variables
$show_results = $show_results ?? false;
$results = $results ?? null;
$results_type = $results_type ?? 'Action';
?>

<div class="wrap amfm-admin-page">
    <div class="amfm-container">
        <!-- Enhanced Header -->
        <div class="amfm-header">
            <div class="amfm-header-content">
                <div class="amfm-header-main">
                    <div class="amfm-header-logo">
                        <span class="amfm-icon"><?php echo esc_html($page_icon); ?></span>
                    </div>
                    <div class="amfm-header-text">
                        <h1><?php echo esc_html($page_title); ?></h1>
                        <p class="amfm-subtitle"><?php echo esc_html($page_subtitle); ?></p>
                    </div>
                </div>
                <div class="amfm-header-actions">
                    <div class="amfm-version-badge">
                        v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($show_results && $results): ?>
        <!-- Results Display -->
        <div class="amfm-results-section">
            <?php
            $class = $results['errors'] > 0 ? 'notice-warning' : 'notice-success';
            ?>
            <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
                <p><strong><?php echo esc_html($results_type); ?> Results:</strong></p>
                <p>Success: <?php echo esc_html($results['success']); ?> | Errors: <?php echo esc_html($results['errors']); ?></p>
                <?php if (!empty($results['details'])): ?>
                    <details>
                        <summary>View Details</summary>
                        <ul>
                            <?php foreach ($results['details'] as $detail): ?>
                                <li><?php echo esc_html($detail); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="amfm-tab-content">
            <?php echo $page_content; ?>
        </div>
    </div>
</div>