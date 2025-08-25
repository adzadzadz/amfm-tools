<?php
if (!defined('ABSPATH')) exit;

// Extract variables
$active_tab = $active_tab ?? 'import-export';
$show_results = $show_results ?? false;
$show_category_results = $show_category_results ?? false;
$results = $results ?? null;
$category_results = $category_results ?? null;
$post_types = $post_types ?? [];
$selected_post_type = $selected_post_type ?? '';
$post_type_taxonomies = $post_type_taxonomies ?? [];
$all_field_groups = $all_field_groups ?? [];

// Default to export tab
$current_subtab = 'export';
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
                        <h1>AMFM Tools</h1>
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

        <?php if ($show_results || $show_category_results) : ?>
            <!-- Import Results Section -->
            <div class="amfm-results-section">
                <?php if ($show_results && $results) : ?>
                <h2>Import Results</h2>
                
                <div class="amfm-stats">
                    <div class="amfm-stat amfm-stat-success">
                        <div class="amfm-stat-number"><?php echo esc_html($results['success']); ?></div>
                        <div class="amfm-stat-label">Successful Updates</div>
                    </div>
                    <div class="amfm-stat amfm-stat-error">
                        <div class="amfm-stat-number"><?php echo esc_html($results['errors']); ?></div>
                        <div class="amfm-stat-label">Errors</div>
                    </div>
                </div>

                <?php if (!empty($results['details'])) : ?>
                    <div class="amfm-details">
                        <h3>Detailed Log</h3>
                        <div class="amfm-log">
                            <?php foreach ($results['details'] as $detail) : ?>
                                <div class="amfm-log-item">
                                    <?php echo esc_html($detail); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($show_category_results && $category_results) : ?>
                <h2>Category Import Results</h2>
                
                <div class="amfm-stats">
                    <div class="amfm-stat amfm-stat-success">
                        <div class="amfm-stat-number"><?php echo esc_html($category_results['success']); ?></div>
                        <div class="amfm-stat-label">Successful Assignments</div>
                    </div>
                    <div class="amfm-stat amfm-stat-error">
                        <div class="amfm-stat-number"><?php echo esc_html($category_results['errors']); ?></div>
                        <div class="amfm-stat-label">Errors</div>
                    </div>
                </div>

                <?php if (!empty($category_results['details'])) : ?>
                    <div class="amfm-details">
                        <h3>Detailed Log</h3>
                        <div class="amfm-log">
                            <?php foreach ($category_results['details'] as $detail) : ?>
                                <div class="amfm-log-item">
                                    <?php echo esc_html($detail); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <div class="amfm-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=amfm-tools-import-export')); ?>" class="button button-primary">
                        Continue Import/Export
                    </a>
                </div>
            </div>
        <?php else : ?>
            <!-- Import/Export Features Section -->
            <div class="amfm-shortcodes-section">
                <div class="amfm-shortcodes-header">
                    <h2>
                        <span class="amfm-shortcodes-icon">🔄</span>
                        Import & Export Tools
                    </h2>
                    <p>Manage your data with comprehensive import and export functionality. Click on any tool to get started.</p>
                </div>

                <!-- Feature Cards -->
                <div class="amfm-components-grid">
                    <div class="amfm-component-card amfm-tab-card active" data-tab="export">
                        <div class="amfm-component-header">
                            <div class="amfm-component-icon">📤</div>
                            <div class="amfm-component-badge">Ready</div>
                        </div>
                        <div class="amfm-component-body">
                            <h3 class="amfm-component-title">Export Data</h3>
                            <p class="amfm-component-description">Export posts with ACF fields, taxonomies, and metadata to CSV format for backup or migration.</p>
                            <div class="amfm-component-status">
                                <span class="amfm-status-indicator active"></span>
                                <span class="amfm-status-text">Click to Export</span>
                            </div>
                        </div>
                    </div>

                    <div class="amfm-component-card amfm-tab-card" data-tab="keywords">
                        <div class="amfm-component-header">
                            <div class="amfm-component-icon">📥</div>
                            <div class="amfm-component-badge">Ready</div>
                        </div>
                        <div class="amfm-component-body">
                            <h3 class="amfm-component-title">Import Keywords</h3>
                            <p class="amfm-component-description">Import keywords from CSV files to assign to posts via ACF fields with batch processing support.</p>
                            <div class="amfm-component-status">
                                <span class="amfm-status-indicator"></span>
                                <span class="amfm-status-text">Click to Import</span>
                            </div>
                        </div>
                    </div>

                    <div class="amfm-component-card amfm-tab-card" data-tab="categories">
                        <div class="amfm-component-header">
                            <div class="amfm-component-icon">📋</div>
                            <div class="amfm-component-badge">Ready</div>
                        </div>
                        <div class="amfm-component-body">
                            <h3 class="amfm-component-title">Import Categories</h3>
                            <p class="amfm-component-description">Import category assignments from CSV to organize your content with taxonomy management.</p>
                            <div class="amfm-component-status">
                                <span class="amfm-status-indicator"></span>
                                <span class="amfm-status-text">Click to Import</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Import/Export Drawer -->
<div id="amfm-import-export-drawer" class="amfm-drawer">
    <div class="amfm-drawer-overlay" onclick="closeImportExportDrawer()"></div>
    <div class="amfm-drawer-content">
        <div class="amfm-drawer-header">
            <h2 id="amfm-drawer-title">Import/Export Tool</h2>
            <button type="button" class="amfm-drawer-close" onclick="closeImportExportDrawer()">&times;</button>
        </div>
        <div class="amfm-drawer-body" id="amfm-drawer-body">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<style>
/* Import/Export Modern Styling */
.amfm-shortcodes-section {
    margin-bottom: 30px;
}

.amfm-shortcodes-header {
    margin-bottom: 30px;
}

.amfm-shortcodes-header h2 {
    display: flex;
    align-items: center;
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 600;
    color: #333;
}

.amfm-shortcodes-icon {
    margin-right: 12px;
    font-size: 32px;
}

.amfm-shortcodes-header p {
    margin: 0;
    font-size: 16px;
    color: #666;
    line-height: 1.5;
}

/* Component Grid */
.amfm-components-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

/* Tab Cards (Modern Card Design) */
.amfm-tab-card {
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.amfm-tab-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.amfm-tab-card.active::before {
    opacity: 1;
}

.amfm-tab-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.amfm-tab-card.active {
    border-color: #667eea;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.2);
}

/* Component Card Base Styles */
.amfm-component-card {
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.amfm-component-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px 15px;
    border-bottom: 1px solid #f1f5f9;
}

.amfm-component-icon {
    font-size: 28px;
    margin-right: 12px;
}

.amfm-component-body {
    padding: 20px 25px 25px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.amfm-component-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 12px 0;
    color: #1e293b;
    line-height: 1.2;
}

.amfm-component-description {
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
    margin: 0 0 20px 0;
    flex-grow: 1;
}

.amfm-component-status {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: auto;
}

.amfm-status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #cbd5e1;
    transition: background-color 0.3s ease;
}

.amfm-status-indicator.active {
    background-color: #22c55e;
    box-shadow: 0 0 8px rgba(34, 197, 94, 0.4);
}

.amfm-status-text {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.amfm-component-badge {
    background: #22c55e;
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2);
}

/* Content Section Styles */
.amfm-import-export-section {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-top: 30px;
    border: 2px solid #667eea;
}

.amfm-section-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    text-align: center;
}

.amfm-section-header h2 {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 0 10px 0;
    color: #333;
    font-size: 24px;
    font-weight: 600;
}

.amfm-section-icon {
    margin-right: 10px;
    font-size: 28px;
}

.amfm-section-header p {
    margin: 0;
    color: #666;
    font-size: 16px;
}

/* Import Instructions Styles */
.import-instructions {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 30px;
    border-left: 4px solid #0073aa;
}

.import-instructions h3 {
    margin-top: 0;
    color: #333;
}

.import-instructions ul {
    margin: 10px 0 20px 20px;
}

.csv-example {
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.csv-example code {
    display: block;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: #d73e48;
    line-height: 1.5;
}

/* Modern Form Styles */
.export-section, .upload-section, .import-options {
    margin-bottom: 30px;
}

.export-section h3, .upload-section h3, .import-options h3 {
    color: #1e293b;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e2e8f0;
}

.option-section, .taxonomy-section, .acf-section {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.option-section h4, .taxonomy-section h4, .acf-section h4 {
    color: #334155;
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 15px 0;
}

.option-section label, .taxonomy-section label, .acf-section label {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    cursor: pointer;
    color: #475569;
    font-size: 14px;
    font-weight: 500;
    padding: 8px 0;
    transition: color 0.2s ease;
}

.option-section label:hover, .taxonomy-section label:hover, .acf-section label:hover {
    color: #1e293b;
}

.option-section input, .taxonomy-section input, .acf-section input {
    margin-right: 12px;
    transform: scale(1.1);
}

/* Modern Upload Styles */
input[type="file"] {
    width: 100%;
    padding: 20px;
    border: 3px dashed #cbd5e1;
    border-radius: 12px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #64748b;
    font-size: 14px;
    text-align: center;
}

input[type="file"]:hover {
    border-color: #667eea;
    background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
    color: #3730a3;
}

/* Modern Select Styling */
select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    font-size: 14px;
    color: #334155;
    margin-bottom: 20px;
    transition: border-color 0.3s ease;
}

select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Modern Button Styling */
.button-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 12px 24px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3) !important;
}

.button-primary:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
}

/* Results section styles */
.amfm-results-section {
    background: #fff;
    padding: 30px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.amfm-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.amfm-stat {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 6px;
    text-align: center;
    min-width: 120px;
}

.amfm-stat-success {
    border-left: 4px solid #46b450;
}

.amfm-stat-error {
    border-left: 4px solid #dc3232;
}

.amfm-stat-number {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.amfm-stat-success .amfm-stat-number {
    color: #46b450;
}

.amfm-stat-error .amfm-stat-number {
    color: #dc3232;
}

.amfm-log {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    max-height: 300px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.5;
}

.amfm-log-item {
    margin-bottom: 5px;
    padding: 3px 0;
    border-bottom: 1px solid #eee;
}

/* Tab Pane Visibility */
.amfm-tab-pane {
    display: none;
}

.amfm-tab-pane.active {
    display: block;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .amfm-components-grid {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .amfm-shortcodes-header {
        padding: 20px;
    }
    
    .amfm-shortcodes-header h2 {
        font-size: 24px;
    }
    
    .amfm-shortcodes-icon {
        font-size: 28px;
    }
    
    .amfm-components-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .amfm-stats {
        flex-direction: column;
        gap: 15px;
    }
    
    .amfm-import-export-section {
        padding: 20px;
        margin-top: 20px;
    }
    
    .amfm-section-header h2 {
        font-size: 20px;
    }
    
    .amfm-component-header {
        padding: 15px 20px 12px;
    }
    
    .amfm-component-body {
        padding: 15px 20px 20px;
    }
}

@media (max-width: 480px) {
    .amfm-shortcodes-header {
        padding: 15px;
        text-align: left;
    }
    
    .amfm-shortcodes-header h2 {
        font-size: 20px;
        flex-direction: column;
        gap: 8px;
    }
    
    .amfm-shortcodes-icon {
        margin-right: 0;
    }
    
    .amfm-import-export-section {
        padding: 15px;
    }
    
    input[type="file"] {
        padding: 15px;
    }
}

/* Drawer Styles */
.amfm-drawer {
    position: fixed;
    top: 32px; /* Account for WordPress admin bar */
    left: 0;
    width: 100%;
    height: calc(100% - 32px);
    z-index: 10000;
    visibility: hidden;
    opacity: 0;
    transition: all 0.3s ease;
}

/* Responsive admin bar height */
@media screen and (max-width: 782px) {
    .amfm-drawer {
        top: 46px;
        height: calc(100% - 46px);
    }
}

@media screen and (max-width: 600px) {
    .amfm-drawer {
        top: 0;
        height: 100%;
    }
}

.amfm-drawer.amfm-drawer-open {
    visibility: visible;
    opacity: 1;
}

.amfm-drawer-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.amfm-drawer-content {
    position: absolute;
    top: 0;
    right: -800px;
    width: 800px;
    height: 100%;
    background: #fff;
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
    transition: right 0.3s ease;
    overflow-y: auto;
    border-radius: 12px 0 0 12px;
}

.amfm-drawer-open .amfm-drawer-content {
    right: 0;
}

.amfm-drawer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    border-bottom: 2px solid #e2e8f0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: sticky;
    top: 0;
    z-index: 10;
}

.amfm-drawer-header h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 600;
    color: white;
}

.amfm-drawer-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: white;
    padding: 8px;
    line-height: 1;
    border-radius: 6px;
    transition: background 0.3s ease;
}

.amfm-drawer-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.amfm-drawer-body {
    padding: 30px;
    background: #fafafa;
}

/* Modern Drawer Content Styling */
.amfm-drawer-section {
    margin-bottom: 30px;
}

.amfm-drawer-section h3 {
    color: #1e293b;
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.amfm-drawer-description {
    color: #64748b;
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 25px;
}

.amfm-modern-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.amfm-form-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.amfm-form-group label {
    font-weight: 600;
    color: #374151;
    font-size: 16px;
}

.amfm-form-group h4 {
    margin: 0 0 15px 0;
    color: #1e293b;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.amfm-form-group select,
.amfm-form-group input[type="file"] {
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    background: #fff;
}

.amfm-form-group select:focus,
.amfm-form-group input[type="file"]:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.amfm-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.amfm-checkbox-grid label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    padding: 8px 0;
    transition: color 0.2s ease;
}

.amfm-checkbox-grid label:hover {
    color: #1e293b;
}

.amfm-checkbox-grid input[type="checkbox"] {
    width: 16px;
    height: 16px;
    margin: 0;
    transform: scale(1.1);
}

.amfm-field-group {
    grid-column: 1 / -1;
    border-top: 2px solid #e2e8f0;
    padding-top: 15px;
    margin-top: 15px;
}

.amfm-field-group:first-child {
    border-top: none;
    margin-top: 0;
    padding-top: 0;
}

.amfm-group-title {
    display: block;
    color: #1e293b;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #cbd5e1;
}

.amfm-checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
}

.amfm-checkbox-label input[type="checkbox"] {
    width: 16px;
    height: 16px;
    margin: 0;
    transform: scale(1.1);
}

.amfm-form-help {
    color: #64748b;
    font-size: 13px;
    font-style: italic;
    margin-top: 5px;
}

.amfm-form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 2px solid #e2e8f0;
    margin-top: 10px;
}

.amfm-primary-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 14px 28px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    text-transform: none !important;
    letter-spacing: normal !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3) !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.amfm-primary-btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
}

.amfm-info-box {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #bae6fd;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
}

.amfm-info-box h4 {
    color: #0c4a6e;
    margin: 0 0 15px 0;
    font-size: 18px;
    font-weight: 600;
}

.amfm-info-box p {
    color: #075985;
    margin: 0 0 15px 0;
    line-height: 1.6;
}

.amfm-requirements-list {
    margin: 15px 0;
    padding-left: 20px;
}

.amfm-requirements-list li {
    color: #075985;
    margin-bottom: 8px;
    line-height: 1.5;
}

.amfm-requirements-list strong {
    color: #0c4a6e;
    font-weight: 600;
}

.amfm-code-example {
    background: #1e293b;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.amfm-code-example h5 {
    color: #f1f5f9;
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 600;
}

.amfm-code-example code {
    color: #94a3b8;
    font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
    font-size: 13px;
    line-height: 1.6;
    display: block;
}

.amfm-no-fields {
    color: #64748b;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

/* Mobile drawer responsiveness */
@media (max-width: 900px) {
    .amfm-drawer-content {
        width: 100%;
        right: -100%;
        border-radius: 0;
    }
    
    .amfm-checkbox-grid {
        grid-template-columns: 1fr;
    }
    
    .amfm-drawer-body {
        padding: 20px;
    }
    
    .amfm-form-actions {
        justify-content: center;
    }
}
</style>

<script>
// Import/Export tool data
const importExportData = {
    'export': {
        name: 'Export Data',
        icon: '📤',
        content: `
            <div class="amfm-drawer-section">
                <h3>📤 Export Posts with ACF Fields</h3>
                <p class="amfm-drawer-description">Export your posts with Advanced Custom Fields data to CSV format for backup, migration, or analysis purposes.</p>
                
                <form method="post" action="" id="amfm_export_form" class="amfm-modern-form">
                    <?php echo wp_nonce_field('amfm_export_nonce', 'amfm_export_nonce', true, false); ?>
                    
                    <div class="amfm-form-group">
                        <label for="export_post_type">Select Post Type to Export</label>
                        <select name="export_post_type" id="export_post_type" required>
                            <option value="">Choose a post type...</option>
                            <?php foreach ($post_types as $post_type): ?>
                            <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($selected_post_type, $post_type->name); ?>>
                                <?php echo esc_html($post_type->label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="export-options" style="display: <?php echo $selected_post_type ? 'block' : 'none'; ?>;">
                        <div class="amfm-form-group">
                            <h4>📋 Post Fields</h4>
                            <div class="amfm-checkbox-grid">
                                <label><input type="checkbox" name="export_columns[]" value="ID" checked> <span>Post ID</span></label>
                                <label><input type="checkbox" name="export_columns[]" value="post_title" checked> <span>Title</span></label>
                                <label><input type="checkbox" name="export_columns[]" value="post_content"> <span>Content</span></label>
                                <label><input type="checkbox" name="export_columns[]" value="post_excerpt"> <span>Excerpt</span></label>
                                <label><input type="checkbox" name="export_columns[]" value="post_date" checked> <span>Date</span></label>
                                <label><input type="checkbox" name="export_columns[]" value="post_status" checked> <span>Status</span></label>
                                <label><input type="checkbox" name="export_columns[]" value="post_author"> <span>Author</span></label>
                            </div>
                        </div>

                        <div class="amfm-form-group taxonomy-section" id="taxonomy-section" style="display: none;">
                            <h4>🏷️ Taxonomies</h4>
                            <div id="taxonomy-checkboxes" class="amfm-checkbox-grid">
                                <!-- Dynamic content will be loaded here -->
                            </div>
                        </div>

                        <div class="amfm-form-group">
                            <h4>🔧 ACF Fields</h4>
                            <div id="acf-checkboxes" class="amfm-checkbox-grid">
                                <?php if (!empty($all_field_groups)) : ?>
                                    <?php foreach ($all_field_groups as $group) : ?>
                                        <div class="amfm-field-group">
                                            <strong class="amfm-group-title"><?php echo esc_html($group['title']); ?></strong>
                                            <?php if (!empty($group['fields'])) : ?>
                                                <?php foreach ($group['fields'] as $field) : ?>
                                                    <label><input type="checkbox" name="export_acf_fields[]" value="<?php echo esc_attr($field['name']); ?>"> <span><?php echo esc_html($field['label']); ?></span></label>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="amfm-no-fields">No ACF field groups found. Make sure ACF is active and has field groups configured.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="amfm-form-actions">
                            <button type="submit" name="amfm_export" class="button button-primary amfm-primary-btn">
                                📤 Export to CSV
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        `
    },
    'keywords': {
        name: 'Import Keywords',
        icon: '📥',
        content: `
            <div class="amfm-drawer-section">
                <h3>📥 Import Keywords from CSV</h3>
                <p class="amfm-drawer-description">Import keywords from CSV files to assign to posts via ACF fields with batch processing support.</p>
                
                <div class="amfm-info-box">
                    <h4>📋 CSV Format Requirements</h4>
                    <p>Your CSV file should include these columns:</p>
                    <ul class="amfm-requirements-list">
                        <li><strong>post_id</strong> - The ID of the post to update</li>
                        <li><strong>keywords</strong> - Comma-separated keywords (for amfm_keywords field)</li>
                        <li><strong>other_keywords</strong> (optional) - Additional keywords (for amfm_other_keywords field)</li>
                    </ul>
                    
                    <div class="amfm-code-example">
                        <h5>💡 Example CSV Content:</h5>
                        <code>post_id,keywords,other_keywords<br>
123,"keyword1,keyword2,keyword3","extra1,extra2"<br>
456,"test,sample","additional"</code>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="amfm-modern-form">
                    <?php echo wp_nonce_field('amfm_keywords_import_nonce', 'amfm_keywords_import_nonce', true, false); ?>
                    
                    <div class="amfm-form-group">
                        <label>📁 Upload CSV File</label>
                        <input type="file" name="keywords_csv_file" id="keywords_csv_file" accept=".csv" required>
                        <small class="amfm-form-help">Maximum file size: 2MB</small>
                    </div>

                    <div class="amfm-form-group">
                        <h4>⚙️ Import Options</h4>
                        <label class="amfm-checkbox-label">
                            <input type="checkbox" name="overwrite_existing" value="1"> 
                            <span>Overwrite existing keywords</span>
                        </label>
                        <small class="amfm-form-help">If unchecked, new keywords will be appended to existing ones.</small>
                    </div>

                    <div class="amfm-form-actions">
                        <button type="submit" name="amfm_import_keywords" class="button button-primary amfm-primary-btn">
                            📥 Import Keywords
                        </button>
                    </div>
                </form>
            </div>
        `
    },
    'categories': {
        name: 'Import Categories',
        icon: '📋',
        content: `
            <div class="amfm-drawer-section">
                <h3>📋 Import Categories from CSV</h3>
                <p class="amfm-drawer-description">Import category assignments from CSV to organize your content with comprehensive taxonomy management.</p>
                
                <div class="amfm-info-box">
                    <h4>📋 CSV Format Requirements</h4>
                    <p>Your CSV file should include these columns:</p>
                    <ul class="amfm-requirements-list">
                        <li><strong>post_id</strong> - The ID of the post to update</li>
                        <li><strong>categories</strong> - Comma-separated category names or IDs</li>
                        <li><strong>taxonomy</strong> (optional) - Taxonomy name (defaults to 'category')</li>
                    </ul>
                    
                    <div class="amfm-code-example">
                        <h5>💡 Example CSV Content:</h5>
                        <code>post_id,categories,taxonomy<br>
123,"News,Technology,Updates",category<br>
456,"Product Reviews","custom_taxonomy"</code>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="amfm-modern-form">
                    <?php echo wp_nonce_field('amfm_categories_import_nonce', 'amfm_categories_import_nonce', true, false); ?>
                    
                    <div class="amfm-form-group">
                        <label>📁 Upload CSV File</label>
                        <input type="file" name="categories_csv_file" id="categories_csv_file" accept=".csv" required>
                        <small class="amfm-form-help">Maximum file size: 2MB</small>
                    </div>

                    <div class="amfm-form-group">
                        <h4>⚙️ Import Options</h4>
                        <label class="amfm-checkbox-label">
                            <input type="checkbox" name="create_missing_categories" value="1" checked> 
                            <span>Create missing categories</span>
                        </label>
                        <small class="amfm-form-help">If unchecked, missing categories will be skipped.</small>
                        
                        <label class="amfm-checkbox-label">
                            <input type="checkbox" name="replace_categories" value="1"> 
                            <span>Replace existing categories</span>
                        </label>
                        <small class="amfm-form-help">If unchecked, new categories will be added to existing ones.</small>
                    </div>

                    <div class="amfm-form-actions">
                        <button type="submit" name="amfm_import_categories" class="button button-primary amfm-primary-btn">
                            📋 Import Categories
                        </button>
                    </div>
                </form>
            </div>
        `
    }
};

// Handle card clicks to open drawers
document.addEventListener('DOMContentLoaded', function() {
    const tabCards = document.querySelectorAll('.amfm-tab-card');
    
    tabCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const toolType = this.getAttribute('data-tab');
            openImportExportDrawer(toolType);
        });
    });
});

// Drawer functions
function openImportExportDrawer(toolType) {
    const drawer = document.getElementById('amfm-import-export-drawer');
    const title = document.getElementById('amfm-drawer-title');
    const body = document.getElementById('amfm-drawer-body');
    
    if (importExportData[toolType]) {
        const data = importExportData[toolType];
        
        title.innerHTML = data.icon + ' ' + data.name;
        body.innerHTML = data.content;
        
        drawer.classList.add('amfm-drawer-open');
        document.body.style.overflow = 'hidden';
        
        // Re-initialize post type selection if export drawer
        if (toolType === 'export') {
            initializeExportForm();
        }
    }
}

function closeImportExportDrawer() {
    const drawer = document.getElementById('amfm-import-export-drawer');
    drawer.classList.remove('amfm-drawer-open');
    document.body.style.overflow = '';
}

// Initialize export form functionality
function initializeExportForm() {
    const exportPostTypeSelect = document.getElementById('export_post_type');
    if (exportPostTypeSelect) {
        exportPostTypeSelect.addEventListener('change', handlePostTypeSelection);
    }
}

function handlePostTypeSelection() {
    const postType = this.value;
    const exportOptions = document.querySelector('.export-options');
    const taxonomySection = document.getElementById('taxonomy-section');
    
    if (postType) {
        exportOptions.style.display = 'block';
        
        // Load taxonomies for selected post type
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'amfm_get_post_type_taxonomies',
                post_type: postType,
                nonce: '<?php echo wp_create_nonce('amfm_ajax_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const taxonomyCheckboxes = document.getElementById('taxonomy-checkboxes');
                taxonomyCheckboxes.innerHTML = '';
                
                if (data.data.length > 0) {
                    taxonomySection.style.display = 'block';
                    data.data.forEach(taxonomy => {
                        const label = document.createElement('label');
                        label.innerHTML = `<input type="checkbox" name="export_taxonomies[]" value="${taxonomy.name}"> <span>${taxonomy.label}</span>`;
                        taxonomyCheckboxes.appendChild(label);
                    });
                } else {
                    taxonomySection.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error loading taxonomies:', error);
        });
    } else {
        exportOptions.style.display = 'none';
        taxonomySection.style.display = 'none';
    }
}

// Close drawer with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImportExportDrawer();
    }
});
</script>