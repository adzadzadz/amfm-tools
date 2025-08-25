<?php
if (!defined('ABSPATH')) exit;

// Extract variables for easier access
$plugin_version = $plugin_version ?? '2.1.0';
?>

<div class="wrap amfm-admin-page">
    <div class="amfm-container">
        <!-- Simple Header -->
        <div class="amfm-header">
            <div class="amfm-header-content">
                <div class="amfm-header-main">
                    <div class="amfm-header-logo">
                        <span class="amfm-icon">🛠️</span>
                    </div>
                    <div class="amfm-header-text">
                        <h1>AMFM Tools Dashboard</h1>
                        <p class="amfm-subtitle">Version <?php echo esc_html($plugin_version); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Under Development Hero -->
        <div class="amfm-hero" style="text-align: center; padding: 80px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white; margin-top: 20px;">
            <div class="amfm-hero-icon" style="font-size: 64px; margin-bottom: 20px;">🚧</div>
            <h2 style="font-size: 36px; margin: 0 0 16px 0; font-weight: 300;">Under Development</h2>
            <p style="font-size: 18px; margin: 0; opacity: 0.9;">The dashboard is currently being redesigned for a better experience.</p>
            <p style="font-size: 14px; margin-top: 20px; opacity: 0.7;">Use the menu items above to access specific features.</p>
        </div>

    </div>
</div>