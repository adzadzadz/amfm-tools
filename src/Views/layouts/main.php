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

<div class="wrap">
    <div class="container-fluid p-0">
        <!-- Clean Header (hidden on elementor page) -->
        <?php if ($active_tab !== 'elementor'): ?>
        <div class="bg-white border-bottom">
            <div class="container-fluid px-4 py-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-tools text-primary fs-4"></i>
                            </div>
                            <div>
                                <h1 class="h4 mb-0 text-dark fw-bold"><?php echo esc_html(isset($title) && !empty($title) ? $title : $plugin_name); ?></h1>
                                <?php if (isset($subtitle) && !empty($subtitle)): ?>
                                <p class="text-muted mb-0 small"><?php echo esc_html($subtitle); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex align-items-center justify-content-md-end justify-content-start gap-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 small">
                                v<?php echo esc_html(AMFM_TOOLS_VERSION); ?>
                            </span>
                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 small">
                                <i class="fas fa-check-circle me-1"></i>
                                Active
                            </span>
                            <?php if (function_exists('acf_get_field_groups')): ?>
                            <span class="badge bg-info bg-opacity-10 text-info px-2 py-1 small">
                                <i class="fas fa-puzzle-piece me-1"></i>
                                ACF
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation Breadcrumb -->
        <?php if (isset($active_tab) && $active_tab !== 'dashboard'): ?>
        <div class="bg-light border-bottom">
            <div class="container-fluid px-4 py-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="<?php echo admin_url('admin.php?page=amfm-tools'); ?>" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo ucfirst(str_replace(['-', '_'], ' ', $active_tab)); ?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main Content Area -->
        <div class="bg-body">
            <div class="container-fluid px-4 py-4">
                <?php if (isset($content)): ?>
                    <?php echo $content; ?>
                <?php else: ?>
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="alert alert-info border-0 shadow-sm">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fs-4 me-3 text-info"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">No Content Available</h6>
                                        <p class="mb-0 small">No content is available for this section at the moment.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
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
