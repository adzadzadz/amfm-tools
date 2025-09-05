<?php

/**
 * The ACF schema fields class.
 *
 * Handles registration and management of ACF custom schema fields.
 *
 * @link       https://adzjo.online/adz
 * @since      3.1.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/public/schema
 */

class Amfm_ACF_Schema_Fields {

    /**
     * The plugin name.
     *
     * @since    3.1.0
     * @access   private
     * @var      string    $plugin_name    The plugin name.
     */
    private $plugin_name;

    /**
     * The plugin version.
     *
     * @since    3.1.0
     * @access   private
     * @var      string    $version    The plugin version.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.1.0
     * @param    string    $plugin_name    The plugin name.
     * @param    string    $version        The plugin version.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Initialize ACF fields.
     *
     * @since    3.1.0
     */
    public function init() {
        // Try multiple hooks to ensure field registration works
        add_action('acf/init', array($this, 'register_schema_fields'));
        add_action('acf/include_fields', array($this, 'register_schema_fields'));
        add_action('init', array($this, 'register_schema_fields'), 5);
        
        // Add fallback meta box if ACF isn't working
        add_action('add_meta_boxes', array($this, 'add_schema_meta_box'));
        add_action('save_post', array($this, 'save_schema_meta_box'));
        
        // Add Quick Edit functionality for post list page
        add_action('quick_edit_custom_box', array($this, 'add_quick_edit_custom_box'), 10, 2);
        add_action('admin_footer', array($this, 'quick_edit_javascript'));
        add_action('wp_ajax_save_quick_edit_schema', array($this, 'save_quick_edit_schema'));
        add_action('wp_ajax_get_quick_edit_schema', array($this, 'get_quick_edit_schema'));
        
        // Add custom column to show schema status
        add_filter('manage_post_posts_columns', array($this, 'add_schema_column'));
        add_filter('manage_page_posts_columns', array($this, 'add_schema_column'));
        add_action('manage_post_posts_custom_column', array($this, 'display_schema_column'), 10, 2);
        add_action('manage_page_posts_custom_column', array($this, 'display_schema_column'), 10, 2);
    }

    /**
     * Register ACF schema fields.
     *
     * @since    3.1.0
     */
    public function register_schema_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        // Check if field group already exists to avoid duplication
        $existing_groups = acf_get_local_field_groups();
        if (!empty($existing_groups)) {
            foreach ($existing_groups as $group) {
                if ($group['key'] === 'group_amfm_custom_schema') {
                    return; // Field group already registered
                }
            }
        }

        acf_add_local_field_group(array(
            'key' => 'group_amfm_custom_schema',
            'title' => 'Custom Schema',
            'fields' => array(
                array(
                    'key' => 'field_amfm_custom_schema',
                    'label' => 'Custom Schema JSON',
                    'name' => 'amfm_custom_schema',
                    'type' => 'textarea',
                    'instructions' => 'Enter custom JSON-LD schema markup. You can use shortcodes like {{location brand="mc"}} for dynamic content.',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mc"}},
    {
      "@type": "MedicalCondition",
      "name": "Example Condition",
      "description": "Description here..."
    }
  ]
}',
                    'maxlength' => '',
                    'rows' => 15,
                    'new_lines' => '',
                ),
                array(
                    'key' => 'field_amfm_schema_notes',
                    'label' => 'Schema Notes',
                    'name' => 'amfm_schema_notes',
                    'type' => 'text',
                    'instructions' => 'Optional notes about this schema configuration.',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => 'e.g., Mental health condition schema with location data',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                ),
                array(
                    'key' => 'field_amfm_schema_preview',
                    'label' => 'Schema Preview',
                    'name' => 'amfm_schema_preview',
                    'type' => 'message',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'message' => '<div id="amfm-schema-preview" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto;">
                        <em>Schema preview will appear here when you save the post.</em>
                    </div>',
                    'new_lines' => '',
                    'esc_html' => 0,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                ),
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ),
                ),
            ),
            'menu_order' => 20,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => 'Add custom schema markup for this page/post.',
        ));
    }

    /**
     * Add schema column to post list.
     *
     * @since    3.1.0
     * @param    array    $columns    Existing columns.
     * @return   array                Modified columns.
     */
    public function add_schema_column($columns) {
        $columns['schema_status'] = 'Schema';
        return $columns;
    }

    /**
     * Display schema column content.
     *
     * @since    3.1.0
     * @param    string    $column     Column name.
     * @param    int       $post_id    Post ID.
     */
    public function display_schema_column($column, $post_id) {
        if ($column === 'schema_status') {
            $custom_schema = get_post_meta($post_id, 'amfm_custom_schema', true);
            if (!empty($custom_schema)) {
                $shortcode_count = preg_match_all('/{{[^}]+}}/', $custom_schema, $matches);
                echo '<span style="color: green;">✓</span> ';
                if ($shortcode_count > 0) {
                    echo '<small>(' . $shortcode_count . ' shortcodes)</small>';
                }
            } else {
                echo '<span style="color: #999;">—</span>';
            }
        }
    }

    /**
     * Add Quick Edit custom box.
     *
     * @since    3.1.0
     * @param    string    $column_name    Column name.
     * @param    string    $post_type      Post type.
     */
    public function add_quick_edit_custom_box($column_name, $post_type) {
        static $rendered = false;
        
        // Only render once, regardless of column
        if ($rendered) {
            return;
        }
        
        if (!in_array($post_type, array('post', 'page'))) {
            return;
        }
        
        $rendered = true;
        ?>
        <fieldset class="inline-edit-col-right" style="clear: both; width: 100%; border-top: 1px solid #ddd; padding: 10px 0 0 0; margin-top: 10px;">
            <div class="inline-edit-col">
                <div class="inline-edit-group" style="margin-bottom: 8px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 3px; font-size: 13px;">Custom Schema</label>
                    <textarea name="amfm_custom_schema" rows="3" style="width: 100%; font-family: monospace; font-size: 11px; padding: 4px; resize: vertical;" placeholder='{"@context": "https://schema.org", "@graph": [{{location brand="mc"}}]}'></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-weight: 600; margin-bottom: 3px; font-size: 13px;">Schema Notes</label>
                        <input type="text" name="amfm_schema_notes" placeholder="Optional notes" style="width: 100%; padding: 4px; font-size: 12px;" />
                    </div>
                </div>
                <details style="font-size: 11px; color: #666; margin-top: 5px;">
                    <summary style="cursor: pointer; font-weight: 600;">Available Shortcodes</summary>
                    <div style="margin-top: 5px; padding-left: 10px;">
                        <code>{{location brand="mc"}}</code> - Mission Connection<br>
                        <code>{{location brand="amfm"}}</code> - AMFM locations<br>
                        <code>{{location state="CA"}}</code> - Filter by state<br>
                        <code>{{breadcrumbs}}</code> - Breadcrumb schema
                    </div>
                </details>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Add JavaScript for Quick Edit functionality.
     *
     * @since    3.1.0
     */
    public function quick_edit_javascript() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->post_type, array('post', 'page')) || $screen->base !== 'edit') {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Store original Quick Edit click
            var $wp_inline_edit = inlineEditPost.edit;
            
            // Override Quick Edit
            inlineEditPost.edit = function(id) {
                // Call original function
                $wp_inline_edit.apply(this, arguments);
                
                // Get the post ID
                var post_id = 0;
                if (typeof(id) == 'object') {
                    post_id = parseInt(this.getId(id));
                }
                
                if (post_id > 0) {
                    // Get row data
                    var $post_row = $('#post-' + post_id);
                    var $inline_edit = $('#edit-' + post_id);
                    
                    // Load current schema data via AJAX
                    $.post(ajaxurl, {
                        action: 'get_quick_edit_schema',
                        post_id: post_id,
                        nonce: '<?php echo wp_create_nonce('amfm_quick_edit_schema'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $inline_edit.find('textarea[name="amfm_custom_schema"]').val(response.data.schema);
                            $inline_edit.find('input[name="amfm_schema_notes"]').val(response.data.notes);
                        }
                    });
                }
            };
            
            // Save Quick Edit data
            $(document).on('click', '#bulk-edit .button-primary, .inline-edit-save .save', function() {
                var $row = $(this).closest('tr');
                var post_id = $row.attr('id').replace('edit-', '');
                var schema = $row.find('textarea[name="amfm_custom_schema"]').val();
                var notes = $row.find('input[name="amfm_schema_notes"]').val();
                
                if (post_id) {
                    $.post(ajaxurl, {
                        action: 'save_quick_edit_schema',
                        post_id: post_id,
                        schema: schema,
                        notes: notes,
                        nonce: '<?php echo wp_create_nonce('amfm_quick_edit_schema'); ?>'
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Handle Quick Edit schema save via AJAX.
     *
     * @since    3.1.0
     */
    public function save_quick_edit_schema() {
        check_ajax_referer('amfm_quick_edit_schema', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_die();
        }
        
        update_post_meta($post_id, 'amfm_custom_schema', $_POST['schema']);
        update_post_meta($post_id, 'amfm_schema_notes', sanitize_text_field($_POST['notes']));
        
        wp_die();
    }

    /**
     * Get Quick Edit schema data via AJAX.
     *
     * @since    3.1.0
     */
    public function get_quick_edit_schema() {
        check_ajax_referer('amfm_quick_edit_schema', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error();
        }
        
        $schema = get_post_meta($post_id, 'amfm_custom_schema', true);
        $notes = get_post_meta($post_id, 'amfm_schema_notes', true);
        
        wp_send_json_success(array(
            'schema' => $schema,
            'notes' => $notes
        ));
    }

    /**
     * Validate schema JSON.
     *
     * @since    3.1.0
     * @param    string    $schema_json    The schema JSON to validate.
     * @return   array                     Validation result with 'valid' boolean and 'error' message.
     */
    public function validate_schema_json($schema_json) {
        if (empty($schema_json)) {
            return array('valid' => true, 'error' => '');
        }

        $schema_json = preg_replace('/{{[^}]*}}/', '""', $schema_json);
        
        json_decode($schema_json);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'valid' => false, 
                'error' => 'Invalid JSON: ' . json_last_error_msg()
            );
        }
        
        return array('valid' => true, 'error' => '');
    }

    /**
     * Get available shortcode examples.
     *
     * @since    3.1.0
     * @return   array    Array of shortcode examples.
     */
    public function get_shortcode_examples() {
        return array(
            '{{location}}' => 'All locations for default brand',
            '{{location brand="mc"}}' => 'All locations for Mission Connection brand',
            '{{location region="northwest"}}' => 'All locations in Northwest region',
            '{{location state="CA"}}' => 'All locations in California',
            '{{location brand="mc" state="WA"}}' => 'Mission Connection locations in Washington',
            '{{breadcrumbs}}' => 'Breadcrumb schema (same as byline module)',
        );
    }

    /**
     * Add schema meta box as fallback when ACF isn't working.
     *
     * @since    3.1.0
     */
    public function add_schema_meta_box() {
        // Only add if ACF field isn't registered
        $field_groups = function_exists('acf_get_field_groups') ? acf_get_field_groups() : array();
        $has_schema_field = false;
        
        foreach ($field_groups as $group) {
            if ($group['key'] === 'group_amfm_custom_schema') {
                $has_schema_field = true;
                break;
            }
        }
        
        if (!$has_schema_field) {
            add_meta_box(
                'amfm_custom_schema_box',
                'Custom Schema',
                array($this, 'render_schema_meta_box'),
                array('post', 'page'),
                'normal',
                'high'
            );
        }
    }

    /**
     * Render the schema meta box.
     *
     * @since    3.1.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_schema_meta_box($post) {
        // Add nonce field
        wp_nonce_field('amfm_schema_meta_box', 'amfm_schema_meta_box_nonce');
        
        // Get existing value
        $custom_schema = get_post_meta($post->ID, 'amfm_custom_schema', true);
        $schema_notes = get_post_meta($post->ID, 'amfm_schema_notes', true);
        
        ?>
        <div style="margin-bottom: 20px;">
            <label for="amfm_custom_schema" style="display: block; font-weight: bold; margin-bottom: 5px;">
                Custom Schema JSON
            </label>
            <p style="margin: 5px 0; color: #666;">
                Enter custom JSON-LD schema markup. You can use shortcodes like {{location brand="mc"}} for dynamic content.
            </p>
            <textarea 
                id="amfm_custom_schema" 
                name="amfm_custom_schema" 
                style="width: 100%; height: 400px; font-family: monospace; font-size: 13px;"
                placeholder='{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mc"}},
    {
      "@type": "MedicalCondition",
      "name": "Example Condition",
      "description": "Description here..."
    }
  ]
}'><?php echo esc_textarea($custom_schema); ?></textarea>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label for="amfm_schema_notes" style="display: block; font-weight: bold; margin-bottom: 5px;">
                Schema Notes
            </label>
            <p style="margin: 5px 0; color: #666;">
                Optional notes about this schema configuration.
            </p>
            <input 
                type="text" 
                id="amfm_schema_notes" 
                name="amfm_schema_notes" 
                value="<?php echo esc_attr($schema_notes); ?>" 
                style="width: 100%;"
                placeholder="e.g., Mental health condition schema with location data"
            />
        </div>
        
        <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd;">
            <h4 style="margin-top: 0;">Available Shortcodes:</h4>
            <ul style="margin: 10px 0;">
                <?php foreach ($this->get_shortcode_examples() as $shortcode => $description): ?>
                    <li><code><?php echo esc_html($shortcode); ?></code> - <?php echo esc_html($description); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Save schema meta box data.
     *
     * @since    3.1.0
     * @param    int    $post_id    The post ID.
     */
    public function save_schema_meta_box($post_id) {
        // Check nonce
        if (!isset($_POST['amfm_schema_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['amfm_schema_meta_box_nonce'], 'amfm_schema_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save custom schema
        if (isset($_POST['amfm_custom_schema'])) {
            update_post_meta($post_id, 'amfm_custom_schema', $_POST['amfm_custom_schema']);
        }
        
        // Save schema notes
        if (isset($_POST['amfm_schema_notes'])) {
            update_post_meta($post_id, 'amfm_schema_notes', sanitize_text_field($_POST['amfm_schema_notes']));
        }
    }
}