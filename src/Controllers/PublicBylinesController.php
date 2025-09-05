<?php

namespace App\Controllers;

use AdzWP\Core\Controller;

/**
 * Public Bylines Controller - handles public-facing bylines functionality using Staff CPT
 * 
 * Migrated from amfm-bylines plugin, manages schema, SEO, and byline data for Staff CPT
 */
class PublicBylinesController extends Controller
{
    /**
     * WordPress init action - Initialize bylines functionality
     */
    public function actionInit()
    {
        if ($this->isFrontend()) {
            $this->initializeFrontend();
        }
    }

    /**
     * Initialize frontend functionality
     */
    private function initializeFrontend()
    {
        // Use staff CPT schema and meta handling
        add_filter('rank_math/json_ld', [$this, 'manageBylinesSchema'], 99, 2);
        add_filter('rank_math/frontend/description', [$this, 'filterRankMathFrontendDescription'], 10, 1);
    }

    /**
     * WordPress wp_enqueue_scripts action - Enqueue public assets using AssetManager
     */
    public function actionWpEnqueueScripts()
    {
        // Register public styles
        \AdzWP\Core\AssetManager::registerStyle('amfm-bylines-public', [
            'url' => AMFM_TOOLS_URL . 'assets/css/bylines-public.css',
            'contexts' => ['frontend'],
            'version' => AMFM_TOOLS_VERSION,
            'media' => 'all'
        ]);

        // Register public scripts
        \AdzWP\Core\AssetManager::registerScript('amfm-bylines-public', [
            'url' => AMFM_TOOLS_URL . 'assets/js/bylines-public.js',
            'dependencies' => ['jquery'],
            'contexts' => ['frontend'],
            'version' => AMFM_TOOLS_VERSION,
            'in_footer' => true,
            'localize' => [
                'object_name' => 'amfmLocalize',
                'data' => [
                    'author' => $this->isTagged('authored-by'),
                    'editor' => $this->isTagged('edited-by'),
                    'reviewedBy' => $this->isTagged('medically-reviewed-by') && $this->isTagged('medicalwebpage', true),
                    'author_page_url' => $this->getBylineUrl('author'),
                    'editor_page_url' => $this->getBylineUrl('editor'),
                    'reviewer_page_url' => $this->getBylineUrl('reviewedBy'),
                    'in_the_press_page_url' => $this->getBylineUrl('inThePress'),
                    'has_social_linkedin' => $this->getLinkedinUrl(),
                ]
            ]
        ]);

        // Register Elementor widgets script
        \AdzWP\Core\AssetManager::registerScript('amfm-elementor-widgets', [
            'url' => AMFM_TOOLS_URL . 'assets/js/elementor-widgets.js',
            'dependencies' => ['jquery'],
            'contexts' => ['frontend'],
            'version' => AMFM_TOOLS_VERSION,
            'in_footer' => true,
            'localize' => [
                'object_name' => 'amfm_ajax_object',
                'data' => [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('amfm_nonce'),
                    'post_id' => get_the_ID()
                ]
            ]
        ]);
    }

    /**
     * Manage bylines schema using Staff CPT
     */
    public function manageBylinesSchema($data, $jsonld = null)
    {
        if (is_singular('post') || is_page()) {
            $author_byline = $this->getByline('author');
            $editor_byline = $this->getByline('editor');
            $reviewer_byline = $this->getByline('reviewedBy');

            $author_schema = [];
            $editor_schema = [];
            $reviewer_schema = [];

            if ($author_byline) {
                $author_data = get_fields($author_byline->ID);
                $author_schema = [
                    '@type' => 'Person',
                    'url' => get_permalink($author_byline->ID),
                    'image' => get_the_post_thumbnail_url($author_byline->ID),
                    'name' => $author_byline->post_title,
                    'honorificSuffix' => $author_data['honorific_suffix'] ?? '',
                    'description' => $author_data['description'] ?? '',
                    'jobTitle' => $author_data['job_title'] ?? '',
                    'hasCredential' => [
                        '@type' => 'EducationalOccupationalCredential',
                        'name' => $author_data['credential_name'] ?? ''
                    ],
                    'worksFor' => [
                        '@type' => 'Organization',
                        'name' => $author_data['works_for'] ?? ''
                    ]
                ];
            }

            if ($editor_byline) {
                $editor_data = get_fields($editor_byline->ID);
                $editor_schema = [
                    '@type' => 'Person',
                    'url' => get_permalink($editor_byline->ID),
                    'image' => get_the_post_thumbnail_url($editor_byline->ID),
                    'name' => $editor_byline->post_title,
                    'honorificSuffix' => $editor_data['honorific_suffix'] ?? '',
                    'description' => $editor_data['description'] ?? '',
                    'jobTitle' => $editor_data['job_title'] ?? '',
                    'hasCredential' => [
                        '@type' => 'EducationalOccupationalCredential',
                        'name' => $editor_data['credential_name'] ?? ''
                    ],
                    'worksFor' => [
                        '@type' => 'Organization',
                        'name' => $editor_data['works_for'] ?? ''
                    ]
                ];
            }

            if ($reviewer_byline) {
                $reviewer_data = get_fields($reviewer_byline->ID);
                $reviewer_schema = [
                    '@type' => 'Person',
                    'url' => get_permalink($reviewer_byline->ID),
                    'image' => get_the_post_thumbnail_url($reviewer_byline->ID),
                    'name' => $reviewer_byline->post_title,
                    'honorificSuffix' => $reviewer_data['honorific_suffix'] ?? '',
                    'description' => $reviewer_data['description'] ?? '',
                    'jobTitle' => $reviewer_data['job_title'] ?? '',
                    'hasCredential' => [
                        '@type' => 'EducationalOccupationalCredential',
                        'name' => $reviewer_data['credential_name'] ?? ''
                    ],
                    'worksFor' => [
                        '@type' => 'Organization',
                        'name' => $reviewer_data['works_for'] ?? ''
                    ]
                ];
            }

            // Update schema data
            foreach ($data as $key => $schema) {
                if (isset($schema['@type']) && ($schema['@type'] === 'Article' || $schema['@type'] === 'MedicalWebPage')) {
                    // Remove existing entries
                    unset($data[$key]['author'], $data[$key]['editor'], $data[$key]['reviewedBy']);

                    // Force MedicalWebPage schema if tagged
                    if (has_tag('medicalwebpage', get_queried_object_id())) {
                        $data[$key]['@type'] = 'MedicalWebPage';
                    }

                    // Add schema data
                    if (!empty($author_schema)) {
                        $data[$key]['author'] = $author_schema;
                    }
                    if (!empty($editor_schema)) {
                        $data[$key]['editor'] = $editor_schema;
                    }
                    if (!empty($reviewer_schema) && ($data[$key]['@type'] === 'MedicalWebPage')) {
                        $data[$key]['reviewedBy'] = $reviewer_schema;
                    }
                }
            }

            return $data;
        } elseif (is_singular('staff')) {
            return $this->addStaffProfileSchema($data);
        }

        return $data;
    }

    /**
     * Add staff profile schema
     */
    private function addStaffProfileSchema($data)
    {
        $staff = get_post();
        $staff_data = get_fields($staff->ID);

        $profile_page_schema = [
            '@type' => 'ProfilePage',
            '@id' => get_permalink($staff->ID) . '#profilepage',
            'url' => get_permalink($staff->ID),
            'name' => $staff->post_title,
            'dateCreated' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'mainEntity' => [
                '@type' => 'Person',
                '@id' => get_permalink($staff->ID) . '#person',
                'name' => $staff->post_title,
                'jobTitle' => $staff_data['job_title'] ?? '',
                'honorificSuffix' => $staff_data['honorific_suffix'] ?? '',
                'hasCredential' => [
                    '@type' => 'EducationalOccupationalCredential',
                    'name' => $staff_data['credential_name'] ?? ''
                ],
                'worksFor' => [
                    '@type' => 'Organization',
                    'name' => $staff_data['works_for'] ?? ''
                ],
                'url' => get_permalink($staff->ID),
                'image' => get_the_post_thumbnail_url($staff->ID),
                'sameAs' => array_filter([
                    $staff_data['linkedin_url'] ?? ''
                ]),
                'description' => $staff_data['description'] ?? '',
                'knowsAbout' => $staff_data['knows_about'] ?? '',
                'alumniOf' => [
                    '@type' => 'EducationalOrganization',
                    'name' => $staff_data['alumni_of'] ?? ''
                ],
                'award' => $staff_data['award'] ?? ''
            ]
        ];

        $data[] = $profile_page_schema;
        return $data;
    }

    /**
     * RankMath frontend description filter - Set staff meta description
     */
    public function filterRankMathFrontendDescription($description)
    {
        if (is_singular('staff')) {
            $meta_description = get_field('staff_meta_description');
            if (empty($meta_description)) {
                $meta_description = get_field('description');
            }
            if ($meta_description) {
                return wp_strip_all_tags($meta_description);
            }
        }
        return $description;
    }

    /**
     * Get byline data from Staff CPT
     */
    public function getByline($type)
    {
        $tags = get_the_tags();
        $is_medical_webpage = has_tag('medicalwebpage', get_queried_object_id());

        if ($tags) {
            foreach ($tags as $tag) {
                $prefix_map = [
                    'author' => 'authored-by',
                    'editor' => 'edited-by',
                    'reviewedBy' => 'medically-reviewed-by',
                    'inThePress' => 'in-the-press-by'
                ];

                if (isset($prefix_map[$type]) && strpos($tag->slug, $prefix_map[$type]) === 0) {
                    if ($type === 'reviewedBy' && !$is_medical_webpage) {
                        continue;
                    }
                    return $this->getStaff($tag->slug);
                }
            }
        }

        return false;
    }

    /**
     * Get staff post by tag
     */
    private function getStaff($tag)
    {
        $staff = get_posts([
            'post_type' => 'staff',
            'numberposts' => 1,
            'tag' => $tag
        ]);

        return empty($staff) ? false : $staff[0];
    }

    /**
     * Get byline URL
     */
    public function getBylineUrl($type)
    {
        $byline = $this->getByline($type);

        if (!$byline) {
            return "No byline found";
        }

        $url = get_permalink($byline->ID);
        return preg_replace('/^https?:\/\//', '', $url);
    }

    /**
     * Check if post is tagged with specific tag
     */
    private function isTagged($tag, $precise = false)
    {
        if ($precise) {
            return has_tag($tag, get_queried_object_id());
        }

        $tags = get_the_tags();
        if ($tags) {
            foreach ($tags as $t) {
                if (strpos($t->slug, $tag) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get LinkedIn URL for staff member
     */
    private function getLinkedinUrl()
    {
        if (is_singular('staff') && function_exists('get_field')) {
            return get_field('linkedin_url', get_the_ID());
        }
        return false;
    }

    /**
     * WordPress wp_ajax action - AJAX handler for fetching related posts
     */
    public function actionWpAjaxAmfmFetchRelatedPosts()
    {
        check_ajax_referer('amfm_nonce', 'security');

        $widget_id = isset($_POST['widget_id']) ? sanitize_text_field($_POST['widget_id']) : false;
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 5;
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        $current_post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';

        if (!$widget_id || !$current_post_id) {
            wp_send_json(['content' => '<p>No related posts found.</p>']);
        }

        // Get related posts using the same logic as in the original
        $result = $this->getRelatedPosts($current_post_id, $filter, $posts_per_page, $paged, $post_type);
        wp_send_json($result);
    }

    /**
     * WordPress wp_ajax_nopriv action - AJAX handler for fetching related posts (non-logged users)
     */
    public function actionWpAjaxNoprivAmfmFetchRelatedPosts()
    {
        $this->actionWpAjaxAmfmFetchRelatedPosts();
    }

    /**
     * Get related posts based on tags
     */
    private function getRelatedPosts($current_post_id, $filter, $posts_per_page, $paged, $post_type)
    {
        $post = get_post($current_post_id);
        if (!$post) {
            return ['content' => '<p>No related posts found.</p>'];
        }

        $tags = wp_get_post_tags($post->ID);
        if (!$tags) {
            return ['content' => '<p>No related posts found.</p>'];
        }

        // Filter tags based on filter type
        $tag_prefix = '';
        switch ($filter) {
            case 'author':
                $tag_prefix = 'authored-by-';
                break;
            case 'editor':
                $tag_prefix = 'edited-by-';
                break;
            case 'reviewer':
                $tag_prefix = 'medically-reviewed-by-';
                break;
        }

        if ($tag_prefix) {
            $tags = array_filter($tags, function ($tag) use ($tag_prefix) {
                return strpos($tag->slug, $tag_prefix) === 0;
            });
        }

        if (empty($tags)) {
            return ['content' => '<p>No related posts found.</p>'];
        }

        $tag_ids = array_map(function ($tag) {
            return $tag->term_id;
        }, $tags);

        // Query posts
        $args = [
            'post_type' => $post_type === 'both' ? ['post', 'page'] : $post_type,
            'posts_per_page' => $posts_per_page,
            'post__not_in' => [$post->ID],
            'tag__in' => $tag_ids,
            'orderby' => 'date',
            'order' => 'DESC',
            'paged' => $paged,
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            ob_start();
            echo '<div class="amfm-related-posts">';
            while ($query->have_posts()) {
                $query->the_post();
                echo '<div class="amfm-related-post-item">';
                echo '<div class="amfm-related-post-title">' . get_the_title() . '</div>';
                echo '<a class="amfm-related-post-link amfm-read-more" href="' . get_permalink() . '">Read More</a>';
                echo '</div>';
            }
            echo '</div>';

            // Pagination
            $big = 999999999;
            $pagination = paginate_links([
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => max(1, $paged),
                'total' => $query->max_num_pages,
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
            ]);

            wp_reset_postdata();

            return [
                'content' => ob_get_clean(),
                'pagination' => $pagination,
            ];
        } else {
            return ['content' => '<p>No related posts found.</p>'];
        }
    }
}