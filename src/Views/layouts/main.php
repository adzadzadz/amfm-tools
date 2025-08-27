<?php
/**
 * Layout Template: main
 * 
 * This layout wraps view content in a structured admin page.
 * Includes the AMFM Tools header and navigation tabs.
 * 
 * Available variables:
 * - $content: The rendered view content
 * - $title: Page/section title (if set)
 * - $active_tab: Current active tab for navigation
 * - All variables passed from the controller
 * 
 * Usage: View::render('viewname', $data, true, 'layouts/main')
 * Disable: View::render('viewname', $data, true, false)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get plugin configuration for context
$config = \AdzWP\Core\Config::getInstance();
$plugin_name = $config->get('plugin.name', 'AMFM Tools');
$plugin_slug = $config->get('plugin.slug', 'amfm-tools');
$active_tab = $active_tab ?? 'dashboard';
?>

<div class="wrap amfm-admin-page">
    <div class="amfm-container">
        <!-- Enhanced Header -->
        <div class="amfm-header">
            <div class="amfm-header-content">
                <div class="amfm-header-main">
                    <div class="amfm-header-logo">
                        <span class="amfm-icon">🛠️</span>
                    </div>
                    <div class="amfm-header-text">
                        <h1><?php echo esc_html($plugin_name); ?></h1>
                        <p class="amfm-subtitle">Advanced Features Management</p>
                    </div>
                </div>
                <div class="amfm-header-actions">
                    <div class="amfm-version-badge">
                        v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Tab Content -->
        <div class="amfm-tab-content">
            <?php if (isset($content)): ?>
                <?php echo $content; ?>
            <?php else: ?>
                <div class="amfm-no-content">
                    <p>No content available for this section.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
/**
 * Action hook for adding custom content after the AMFM admin template
 * 
 * @param string $template_name Template name
 * @param array $template_data All data passed to template
 */
do_action('amfm_after_admin_layout', 'main', get_defined_vars());
?>
