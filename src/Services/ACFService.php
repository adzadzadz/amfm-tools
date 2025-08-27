<?php

namespace App\Services;

class ACFService
{
    /**
     * Field groups configuration
     */
    private $field_groups = [];
    
    /**
     * Custom post types configuration
     */
    private $post_types = [];

    public function __construct()
    {
        $this->setupFieldGroups();
        $this->setupPostTypes();
    }

    /**
     * Setup field groups configuration
     */
    private function setupFieldGroups()
    {
        $this->field_groups = [
            'group_67edb6e5589ea' => [
                'key' => 'group_67edb6e5589ea',
                'title' => 'CEU',
                'fields' => [
                    [
                        'key' => 'field_67edb764c3180',
                        'label' => 'Subtitle',
                        'name' => 'subtitle',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_67edb7a7c3181',
                        'label' => 'Date',
                        'name' => 'date',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_67edb7ecc3182',
                        'label' => 'Time',
                        'name' => 'time',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_67fd4e83a6676',
                        'label' => 'Registration Link',
                        'name' => 'registration_link',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_67edb7ffc3183',
                        'label' => 'Description',
                        'name' => 'description',
                        'type' => 'textarea',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_67edb82ac3184',
                        'label' => 'Learning Objectives',
                        'name' => 'learning_objectives',
                        'type' => 'wysiwyg',
                        'required' => 0,
                        'default_value' => '',
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ],
                    [
                        'key' => 'field_67edb6e5c32fc',
                        'label' => 'Author',
                        'name' => 'author',
                        'type' => 'post_object',
                        'required' => 0,
                        'post_type' => ['staff'],
                        'return_format' => 'object',
                        'multiple' => 0,
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'ceu',
                        ],
                    ],
                ],
                'position' => 'normal',
                'active' => true,
            ],

            'group_6785868418204' => [
                'key' => 'group_6785868418204',
                'title' => 'Page Data',
                'fields' => [
                    [
                        'key' => 'field_6785868526a3c',
                        'label' => 'Location',
                        'name' => 'location',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'page',
                        ],
                    ],
                ],
                'position' => 'normal',
                'active' => true,
            ],

            'group_675375a800734' => [
                'key' => 'group_675375a800734',
                'title' => 'Staff',
                'fields' => [
                    [
                        'key' => 'field_675375a8a3896',
                        'label' => 'Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_6758b20d63b1b',
                        'label' => 'Region',
                        'name' => 'region',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_6753837a88ef5',
                        'label' => 'Description',
                        'name' => 'description',
                        'type' => 'textarea',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_675a1f9562651',
                        'label' => 'Honorific Suffix',
                        'name' => 'honorific_suffix',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_675a1fb162652',
                        'label' => 'Credential Type',
                        'name' => 'credential_type',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => 'EducationalOccupationalCredential',
                    ],
                    [
                        'key' => 'field_675a1fba62653',
                        'label' => 'Credential Name',
                        'name' => 'credential_name',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_675a1fc162654',
                        'label' => 'Works For',
                        'name' => 'works_for',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => 'AMFM Mental Health Treatment',
                    ],
                    [
                        'key' => 'field_675a2056d26a0',
                        'label' => 'Linkedin Url',
                        'name' => 'linkedin_url',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_675375e180573',
                        'label' => 'Email',
                        'name' => 'email',
                        'type' => 'email',
                        'required' => 0,
                        'default_value' => '',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'staff',
                        ],
                    ],
                ],
                'position' => 'acf_after_title',
                'active' => true,
                'description' => 'Staff Data',
            ],

            'group_68500b028842d' => [
                'key' => 'group_68500b028842d',
                'title' => 'SEO',
                'fields' => [
                    [
                        'key' => 'field_68500b04f89a9',
                        'label' => 'Keywords',
                        'name' => 'amfm_keywords',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                    [
                        'key' => 'field_68500b53f89aa',
                        'label' => 'Other Keywords',
                        'name' => 'amfm_other_keywords',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => '',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'post',
                        ],
                    ],
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'page',
                        ],
                    ],
                ],
                'position' => 'side',
                'menu_order' => 1,
                'active' => true,
            ],
        ];
    }

    /**
     * Setup custom post types configuration
     */
    private function setupPostTypes()
    {
        $this->post_types = [
            'ceu' => [
                'labels' => [
                    'name' => 'CEUs',
                    'singular_name' => 'CEU',
                    'menu_name' => 'CEU',
                    'add_new_item' => 'Add New CEU',
                ],
                'public' => true,
                'show_in_rest' => true,
                'menu_position' => 4,
                'menu_icon' => 'dashicons-calendar-alt',
                'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
                'taxonomies' => ['category', 'post_tag'],
                'has_archive' => 'ceu',
                'rewrite' => ['with_front' => false, 'feeds' => false],
            ],
            
            'staff' => [
                'labels' => [
                    'name' => 'Staff',
                    'singular_name' => 'Staff',
                    'menu_name' => 'Staff',
                    'featured_image' => 'Profile Image',
                    'set_featured_image' => 'Set Profile Image',
                ],
                'public' => true,
                'hierarchical' => true,
                'show_in_rest' => true,
                'menu_position' => 3,
                'menu_icon' => 'dashicons-groups',
                'supports' => ['title', 'thumbnail'],
                'taxonomies' => ['category', 'post_tag'],
                'has_archive' => 'staff',
                'rewrite' => ['with_front' => false, 'feeds' => false],
            ],
        ];
    }

    /**
     * Check if field group exists
     */
    public function fieldGroupExists($group_key)
    {
        if (!function_exists('acf_get_local_field_group')) {
            return false;
        }
        
        $existing_group = acf_get_local_field_group($group_key);
        return !empty($existing_group);
    }

    /**
     * Register field groups if they don't exist
     */
    public function registerFieldGroups()
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        foreach ($this->field_groups as $group_key => $group_config) {
            if (!$this->fieldGroupExists($group_key)) {
                // Convert simplified config to full ACF format
                $full_config = $this->expandFieldGroupConfig($group_config);
                acf_add_local_field_group($full_config);
                
                error_log("AMFM ACF: Registered field group '{$group_config['title']}' ({$group_key})");
            }
        }
    }

    /**
     * Register custom post types if they don't exist
     */
    public function registerPostTypes()
    {
        foreach ($this->post_types as $post_type => $config) {
            if (!post_type_exists($post_type)) {
                register_post_type($post_type, $config);
                error_log("AMFM ACF: Registered post type '{$post_type}'");
            }
        }
    }

    /**
     * Get all configured field groups
     */
    public function getFieldGroups()
    {
        return $this->field_groups;
    }

    /**
     * Get all configured post types
     */
    public function getPostTypes()
    {
        return $this->post_types;
    }

    /**
     * Get currently active ACF field groups
     */
    public function getActiveFieldGroups()
    {
        if (!function_exists('acf_get_local_field_groups')) {
            return [];
        }

        return acf_get_local_field_groups();
    }

    /**
     * Expand simplified field group config to full ACF format
     */
    private function expandFieldGroupConfig($config)
    {
        // Add default properties
        $defaults = [
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
        ];

        $full_config = array_merge($defaults, $config);

        // Expand fields with default properties
        if (!empty($full_config['fields'])) {
            $full_config['fields'] = array_map([$this, 'expandFieldConfig'], $full_config['fields']);
        }

        return $full_config;
    }

    /**
     * Expand simplified field config to full ACF format
     */
    private function expandFieldConfig($field)
    {
        $defaults = [
            'aria-label' => '',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ],
            'maxlength' => '',
            'allow_in_bindings' => 0,
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
        ];

        return array_merge($defaults, $field);
    }
}