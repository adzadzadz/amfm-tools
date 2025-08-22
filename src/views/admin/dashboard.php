<?php
$results = get_transient('amfm_csv_import_results');
$category_results = get_transient('amfm_category_csv_import_results');
$show_results = !empty($results) && isset($_GET['imported']) && $_GET['imported'] === 'keywords';
$show_category_results = !empty($category_results) && isset($_GET['imported']) && $_GET['imported'] === 'categories';
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

if ($show_results) {
    delete_transient('amfm_csv_import_results');
}
if ($show_category_results) {
    delete_transient('amfm_category_csv_import_results');
}
?>

<div class="wrap amfm-admin-page">
    <div class="amfm-container">
        <!-- Tabs Navigation -->
        <div class="amfm-tabs-nav">
            <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=dashboard'); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon"><›</span>
                Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=import-export'); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'import-export' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">=Ê</span>
                Import/Export
            </a>
            <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=shortcodes'); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'shortcodes' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">=Ä</span>
                Shortcodes
            </a>
            <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=elementor'); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'elementor' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">>é</span>
                Elementor Widgets
            </a>
            <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=optimization'); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'optimization' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">¡</span>
                Optimization
            </a>
        </div>

        <!-- Tab Content -->
        <div class="amfm-tab-content">
            <?php
            switch ($active_tab) {
                case 'dashboard':
                    include 'dashboard-tab.php';
                    break;
                case 'import-export':
                    include 'import-export-tab.php';
                    break;
                case 'shortcodes':
                    include 'shortcodes-tab.php';
                    break;
                case 'elementor':
                    include 'elementor-tab.php';
                    break;
                case 'optimization':
                    include 'optimization-tab.php';
                    break;
                default:
                    include 'dashboard-tab.php';
            }
            ?>
        </div>
    </div>
</div>

<?php if ($show_results): ?>
    <script>
    jQuery(document).ready(function($) {
        // Show import results modal or notification
        console.log('Import results:', <?php echo json_encode($results); ?>);
    });
    </script>
<?php endif; ?>

<?php if ($show_category_results): ?>
    <script>
    jQuery(document).ready(function($) {
        // Show category import results modal or notification  
        console.log('Category import results:', <?php echo json_encode($category_results); ?>);
    });
    </script>
<?php endif; ?>