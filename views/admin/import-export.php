<?php if (!defined('ABSPATH')) exit; ?>

<div class="amfm-tab-content">
    <div class="amfm-import-export">
        <h2>Import/Export Data</h2>
        <p>Import and export keywords, categories, and other data using CSV files.</p>
        
        <div class="amfm-cards-container">
            <div class="amfm-card">
                <h3>📥 Import Keywords</h3>
                <p>Upload a CSV file to import keywords data.</p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('amfm_import_keywords', 'import_nonce'); ?>
                    <input type="file" name="keywords_csv" accept=".csv" required>
                    <button type="submit" name="import_keywords" class="button button-primary">Import Keywords</button>
                </form>
            </div>
            
            <div class="amfm-card">
                <h3>📤 Export Keywords</h3>
                <p>Download all keywords data as a CSV file.</p>
                <form method="post">
                    <?php wp_nonce_field('amfm_export_keywords', 'export_nonce'); ?>
                    <button type="submit" name="export_keywords" class="button">Export Keywords</button>
                </form>
            </div>
        </div>
    </div>
</div>