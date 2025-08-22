<?php
if (!defined('ABSPATH')) exit;

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
?>

<div class="wrap amfm-admin-page">
    <div class="amfm-container">
        <!-- Header -->
        <div class="amfm-header">
            <h1><span class="amfm-icon">🛠️</span> AMFM Tools</h1>
            <p class="amfm-subtitle">Advanced Custom Field Management & Performance Optimization Tools</p>
        </div>

        <!-- Tabs Navigation -->
        <div class="amfm-tabs-nav">
            <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=dashboard'); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">🎛️</span>
                Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=import-export'); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'import-export' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">📊</span>
                Import/Export
            </a>
            <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=shortcodes'); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'shortcodes' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">📄</span>
                Shortcodes
            </a>
            <a href="<?php echo admin_url('admin.php?page=amfm-tools&tab=elementor'); ?>" 
               class="amfm-tab-link <?php echo $active_tab === 'elementor' ? 'active' : ''; ?>">
                <span class="amfm-tab-icon">🎨</span>
                Elementor
            </a>
        </div>

        <!-- Tab Content -->
        <?php if ($active_tab === 'dashboard') : ?>
            <?php include __DIR__ . '/dashboard.php'; ?>
        <?php elseif ($active_tab === 'import-export') : ?>
            <?php include __DIR__ . '/import-export.php'; ?>
        <?php elseif ($active_tab === 'shortcodes') : ?>
            <?php include __DIR__ . '/shortcodes.php'; ?>
        <?php elseif ($active_tab === 'elementor') : ?>
            <?php include __DIR__ . '/elementor.php'; ?>
        <?php endif; ?>
    </div>
</div>