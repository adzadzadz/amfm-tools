<?php
if (!defined('ABSPATH')) exit;

// Extract variables for easier access
$plugin_version = $plugin_version ?? AMFM_TOOLS_VERSION;

// Test update notification - v3.5.3

// Get statistics
global $wpdb;
$posts_with_keywords = 0;
if (function_exists('acf_get_field_groups')) {
    $posts_with_keywords = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
         WHERE meta_key IN ('amfm_keywords', 'amfm_other_keywords') 
         AND meta_value != ''"
    );
}

$excluded_keywords = get_option('amfm_excluded_keywords', []);
$excluded_keywords_count = is_array($excluded_keywords) ? count($excluded_keywords) : 0;
$field_groups_count = function_exists('acf_get_field_groups') ? count(acf_get_field_groups()) : 0;
?>

<!-- Dashboard Layout -->
<div class="row g-2">

    <!-- System Status Cards -->
    <div class="col-xl-3 col-lg-6 col-md-6 fade-in-up">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body px-3 py-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="rounded bg-success bg-opacity-10 p-2">
                        <i class="fas fa-check-circle text-success" style="font-size: 1rem;"></i>
                    </div>
                    <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.65rem;">
                        Online
                    </span>
                </div>
                <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.03em;">Plugin Status</h6>
                <h5 class="fw-bold text-dark mb-1" style="font-size: 1.2rem;">Active & Ready</h5>
                <small class="text-muted" style="font-size: 0.7rem;">All systems operational</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 fade-in-up">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body px-3 py-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="rounded bg-info bg-opacity-10 p-2">
                        <i class="fas fa-database text-info" style="font-size: 1rem;"></i>
                    </div>
                    <div class="text-end">
                        <div class="text-muted" style="font-size: 0.65rem;">Total Posts</div>
                    </div>
                </div>
                <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.03em;">Content Data</h6>
                <h5 class="fw-bold text-dark mb-1" style="font-size: 1.2rem;"><?php echo number_format($posts_with_keywords); ?></h5>
                <small class="text-muted" style="font-size: 0.7rem;">Posts with keywords</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 fade-in-up">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body px-3 py-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="rounded bg-warning bg-opacity-10 p-2">
                        <i class="fas fa-filter text-warning" style="font-size: 1rem;"></i>
                    </div>
                    <div class="text-end">
                        <div class="text-muted" style="font-size: 0.65rem;">Filters</div>
                    </div>
                </div>
                <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.03em;">Keywords</h6>
                <h5 class="fw-bold text-dark mb-1" style="font-size: 1.2rem;"><?php echo number_format($excluded_keywords_count); ?></h5>
                <small class="text-muted" style="font-size: 0.7rem;">Excluded keywords</small>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6 fade-in-up">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body px-3 py-2">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="rounded <?php echo function_exists('acf_get_field_groups') ? 'bg-primary' : 'bg-secondary'; ?> bg-opacity-10 p-2">
                        <i class="fas fa-puzzle-piece <?php echo function_exists('acf_get_field_groups') ? 'text-primary' : 'text-secondary'; ?>" style="font-size: 1rem;"></i>
                    </div>
                    <span class="badge <?php echo function_exists('acf_get_field_groups') ? 'bg-primary' : 'bg-secondary'; ?> text-white px-2 py-1" style="font-size: 0.65rem;">
                        <?php echo function_exists('acf_get_field_groups') ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem; letter-spacing: 0.03em;">ACF Integration</h6>
                <h5 class="fw-bold text-dark mb-1" style="font-size: 1.2rem;"><?php echo function_exists('acf_get_field_groups') ? number_format($field_groups_count) : '0'; ?></h5>
                <small class="text-muted" style="font-size: 0.7rem;">Field groups available</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="col-md-7 col-lg-8 fade-in-up">
        <div class="card border-0 shadow-sm h-100" style="max-width: none !important;">
            <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 d-flex align-items-center">
                        <i class="fas fa-bolt text-primary me-2"></i>
                        Quick Actions
                    </h5>
                    <span class="badge bg-light text-dark">4 tools available</span>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="d-flex flex-column gap-2">
                    <a href="<?php echo admin_url('admin.php?page=amfm-tools-import-export'); ?>" class="text-decoration-none">
                        <div class="d-flex align-items-center px-3 py-2 border border-primary border-opacity-25 rounded hover-lift">
                            <div class="rounded bg-primary bg-opacity-10 p-2 me-3">
                                <i class="fas fa-exchange-alt text-primary" style="font-size: 0.9rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">Import/Export</h6>
                            </div>
                            <div class="ms-2">
                                <i class="fas fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
                            </div>
                        </div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=amfm-tools-shortcodes'); ?>" class="text-decoration-none">
                        <div class="d-flex align-items-center px-3 py-2 border border-success border-opacity-25 rounded hover-lift">
                            <div class="rounded bg-success bg-opacity-10 p-2 me-3">
                                <i class="fas fa-code text-success" style="font-size: 0.9rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">Shortcodes</h6>
                            </div>
                            <div class="ms-2">
                                <i class="fas fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
                            </div>
                        </div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=amfm-tools-elementor'); ?>" class="text-decoration-none">
                        <div class="d-flex align-items-center px-3 py-2 border border-secondary border-opacity-25 rounded hover-lift">
                            <div class="rounded bg-secondary bg-opacity-10 p-2 me-3">
                                <i class="fas fa-puzzle-piece text-secondary" style="font-size: 0.9rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">Elementor Widgets</h6>
                            </div>
                            <div class="ms-2">
                                <i class="fas fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
                            </div>
                        </div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=amfm-tools-utilities'); ?>" class="text-decoration-none">
                        <div class="d-flex align-items-center px-3 py-2 border border-info border-opacity-25 rounded hover-lift">
                            <div class="rounded bg-info bg-opacity-10 p-2 me-3">
                                <i class="fas fa-tools text-info" style="font-size: 0.9rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">Utilities</h6>
                            </div>
                            <div class="ms-2">
                                <i class="fas fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information Panel -->
    <div class="col-md-5 col-lg-4 fade-in-up">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="fw-bold mb-0 d-flex align-items-center">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    System Info
                </h5>
            </div>
            <div class="card-body p-3">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-wordpress text-primary me-2" style="font-size: 0.9rem;"></i>
                            <span style="font-size: 0.85rem;">WordPress</span>
                        </div>
                        <span class="badge bg-light text-dark" style="font-size: 0.75rem;"><?php echo get_bloginfo('version'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-code text-info me-2" style="font-size: 0.9rem;"></i>
                            <span style="font-size: 0.85rem;">PHP Version</span>
                        </div>
                        <span class="badge bg-light text-dark" style="font-size: 0.75rem;"><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-memory text-warning me-2" style="font-size: 0.9rem;"></i>
                            <span style="font-size: 0.85rem;">Memory Limit</span>
                        </div>
                        <span class="badge bg-light text-dark" style="font-size: 0.75rem;"><?php echo ini_get('memory_limit'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-upload text-success me-2" style="font-size: 0.9rem;"></i>
                            <span style="font-size: 0.85rem;">Upload Limit</span>
                        </div>
                        <span class="badge bg-light text-dark" style="font-size: 0.75rem;"><?php echo ini_get('upload_max_filesize'); ?></span>
                    </div>
                </div>
                
                <hr class="my-2">

                <!-- Update Channel Setting -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size: 0.85rem; font-weight: 600;">Update Channel</span>
                        <span class="badge bg-<?php echo get_option('amfm_update_channel', 'stable') === 'development' ? 'warning' : 'success'; ?> text-white" style="font-size: 0.7rem;" id="channel-badge">
                            <?php echo ucfirst(get_option('amfm_update_channel', 'stable')); ?>
                        </span>
                    </div>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="update_channel" id="channel_stable" value="stable" <?php checked(get_option('amfm_update_channel', 'stable'), 'stable'); ?>>
                        <label class="btn btn-outline-success btn-sm" for="channel_stable" style="font-size: 0.75rem;">
                            <i class="fas fa-shield-alt me-1"></i> Stable
                        </label>

                        <input type="radio" class="btn-check" name="update_channel" id="channel_development" value="development" <?php checked(get_option('amfm_update_channel', 'stable'), 'development'); ?>>
                        <label class="btn btn-outline-warning btn-sm" for="channel_development" style="font-size: 0.75rem;">
                            <i class="fas fa-code-branch me-1"></i> Development
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                        <span id="channel-description">
                            <?php if (get_option('amfm_update_channel', 'stable') === 'development'): ?>
                                Get latest features from development branch
                            <?php else: ?>
                                Get stable releases only (recommended)
                            <?php endif; ?>
                        </span>
                    </small>
                </div>

                <div class="text-center">
                    <a href="https://adzbyte.com/" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                        <i class="fas fa-external-link-alt me-1" style="font-size: 0.7rem;"></i>
                        Visit Plugin Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>