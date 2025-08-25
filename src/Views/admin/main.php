<?php
if (!defined('ABSPATH')) exit;

$active_tab = $active_tab ?? 'dashboard';
?>

<div class="wrap amfm-admin-page">
    <div class="amfm-container">
        <!-- Header -->
        <div class="amfm-header">
            <h1><span class="amfm-icon">🛠️</span> AMFM Tools</h1>
            <p class="amfm-subtitle">Advanced Features Management</p>
        </div>

        <!-- Tabs Navigation -->
        <div class="amfm-tabs-nav">
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=dashboard')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">🎛️</span>
                Dashboard
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=import-export')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'import-export' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">📊</span>
                Import/Export
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=shortcodes')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'shortcodes' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">📄</span>
                Shortcodes
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools&tab=elementor')); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'elementor' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">🎨</span>
                Elementor
            </a>
        </div>

        <!-- Tab Content will be rendered by specific tab templates -->
    </div>
</div>