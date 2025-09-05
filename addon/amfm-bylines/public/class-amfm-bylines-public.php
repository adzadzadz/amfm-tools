<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://adzjo.online/adz
 * @since      1.0.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/public
 * @author     Adrian T. Saycon <adzbite@gmail.com>
 */
class Amfm_Bylines_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Amfm_Bylines_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Amfm_Bylines_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$plugin_url = defined('AMFM_BYLINES_PLUGIN_URL') ? AMFM_BYLINES_PLUGIN_URL . 'public/' : plugin_dir_url(__FILE__);
		// wp_enqueue_style($this->plugin_name, $plugin_url . 'css/amfm-bylines-public.css', array(), $this->version, 'all');
		wp_enqueue_style($this->plugin_name . "-public", $plugin_url . 'css/amfm-bylines-public.min.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Amfm_Bylines_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Amfm_Bylines_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$plugin_url = defined('AMFM_BYLINES_PLUGIN_URL') ? AMFM_BYLINES_PLUGIN_URL . 'public/' : plugin_dir_url(__FILE__);
		// wp_enqueue_script($this->plugin_name, $plugin_url . 'js/amfm-bylines-public.js', array('jquery'), $this->version, false);
		wp_enqueue_script(
			$this->plugin_name,
			$plugin_url . 'js/amfm-bylines-public.min.js',
			array('jquery'),
			$this->version,
			false
		);

		wp_localize_script($this->plugin_name, 'amfmLocalize', array(
			'author' => $this->is_tagged('authored-by'),
			'editor' => $this->is_tagged('edited-by'),
			'reviewedBy' => $this->is_tagged('medically-reviewed-by') && $this->is_tagged('medicalwebpage', $precise = true),
			'author_page_url' => $this->get_byline_url('author'),
			'editor_page_url' => $this->get_byline_url('editor'),
			'reviewer_page_url' => $this->get_byline_url('reviewedBy'),
			'in_the_press_page_url' => $this->get_byline_url('inThePress'),
			'has_social_linkedin' => $this->get_linkedin_url(),
		));

		$plugin_url = defined('AMFM_BYLINES_PLUGIN_URL') ? AMFM_BYLINES_PLUGIN_URL . 'public/' : plugin_dir_url(__FILE__);
		wp_enqueue_script(
			$this->plugin_name . "-elementor-widgets",
			$plugin_url . 'js/amfm-elementor-widgets.min.js', // Adjust the path to your JS file
			['jquery'],
			$this->version,
			true
		);

		// Pass data to JavaScript
		wp_localize_script($this->plugin_name . "-elementor-widgets", 'amfm_ajax_object', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('amfm_nonce'),
			'post_id'  => get_the_ID()
		]);
	}

	public function init()
	{
		// check if option amfm_use_staff_cpt is enabled or not
		$use_staff_cpt = get_option('amfm_use_staff_cpt');
		if ($use_staff_cpt) {
			$this->manage_bylines_schema_with_staff_cpt();
			$this->setStaffMeta();
		} else {
			$this->manage_bylines_schema();
		}
		$this->run_shortcodes();

		$this->run_elementor();
	}

	/**
	 * Set the staff meta description
	 * 
	 * @return void
	 */
	public function setStaffMeta()
	{
		add_filter('rank_math/frontend/description', function ($description) {
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
		});
	}

	/**
	 * Manage bylines schema with staff cpt
	 * Returned data model from cpt and db are very different. Need to handle them differently as well.
	 */
	public function manage_bylines_schema_with_staff_cpt()
	{
		add_filter('rank_math/json_ld', function ($data) {

			if (is_singular('post') || is_page()) {
				
				// get this post/page author, editor, and reviewedBy tags
				$author_byline = $this->get_byline('author', true);
				$editor_byline = $this->get_byline('editor', true);
				$reviewer_byline = $this->get_byline('reviewedBy', true);


				// In this context get_byline returns the post object. I need to get the custom field values from the post object
				// Build the author array and should contain bylines with author tags
				$author_schema = array();
				$editor_schema = array();
				$reviewer_schema = array();

				if ($author_byline) {
					$author_data = get_fields($author_byline->ID);
					$author_schema = array(
						'@type' => 'Person',
						'url' => get_permalink($author_byline->ID),
						'image' => get_the_post_thumbnail_url($author_byline->ID),
						'name' => $author_byline->post_title,
						'honorificSuffix' => $author_data['honorific_suffix'] ?? '',
						'description' => $author_data['description'] ?? '',
						'jobTitle' => $author_data['job_title'] ?? '',
						'hasCredential' => array(
							'@type' => 'EducationalOccupationalCredential',
							'name' => $author_data['credential_name'] ?? ''
						),
						'worksFor' => array(
							'@type' => 'Organization',
							'name' => $author_data['works_for'] ?? ''
						)
					);
				}

				if ($editor_byline) {
					$editor_data = get_fields($editor_byline->ID);
					$editor_schema = array(
						'@type' => 'Person',
						'url' => get_permalink($editor_byline->ID),
						'image' => get_the_post_thumbnail_url($editor_byline->ID),
						'name' => $editor_byline->post_title,
						'honorificSuffix' => $editor_data['honorific_suffix'] ?? '',
						'description' => $editor_data['description'] ?? '',
						'jobTitle' => $editor_data['job_title'] ?? '',
						'hasCredential' => array(
							'@type' => 'EducationalOccupationalCredential',
							'name' => $editor_data['credential_name'] ?? ''
						),
						'worksFor' => array(
							'@type' => 'Organization',
							'name' => $editor_data['works_for'] ?? ''
						)
					);
				}

				if ($reviewer_byline) {
					$reviewer_data = get_fields($reviewer_byline->ID);
					$reviewer_schema = array(
						'@type' => 'Person',
						'url' => get_permalink($reviewer_byline->ID),
						'image' => get_the_post_thumbnail_url($reviewer_byline->ID),
						'name' => $reviewer_byline->post_title,
						'honorificSuffix' => $reviewer_data['honorific_suffix'] ?? '',
						'description' => $reviewer_data['description'] ?? '',
						'jobTitle' => $reviewer_data['job_title'] ?? '',
						'hasCredential' => array(
							'@type' => 'EducationalOccupationalCredential',
							'name' => $reviewer_data['credential_name'] ?? ''
						),
						'worksFor' => array(
							'@type' => 'Organization',
							'name' => $reviewer_data['works_for'] ?? ''
						)
					);
				}

				foreach ($data as $key => $schema) {

					if (isset($schema['@type']) && ($schema['@type'] === 'Article' || $schema['@type'] === 'MedicalWebPage')) {
						// Remove existing author, editor, and reviewedBy if they exist
						if (isset($data[$key]['author'])) {
							unset($data[$key]['author']);
						}
						if (isset($data[$key]['editor'])) {
							unset($data[$key]['editor']);
						}
						if (isset($data[$key]['reviewedBy'])) {
							unset($data[$key]['reviewedBy']);
						}

						// force schema type to MedicalWebPage if the post is tagged with medicalwebpage
						$is_medical_webpage = has_tag('medicalwebpage', get_queried_object_id());
						if ($is_medical_webpage) {
							$data[$key]['@type'] = 'MedicalWebPage';
						}

						// Add author, editor, and reviewedBy in the desired order
						if (!empty($author_schema)) {
							$data[$key]['author'] = $author_schema;
						}
						if (!empty($editor_schema)) {
							$data[$key]['editor'] = $editor_schema;
						}
						if (!empty($reviewer_schema) && isset($data[$key]['@type']) && $data[$key]['@type'] === 'MedicalWebPage') {
							$data[$key]['reviewedBy'] = $reviewer_schema;
						}
					}
				}

				return $data;
			} elseif (is_singular('staff')) {

				// Use person schema if the post is a staff post
				$staff = get_post();
				$staff_data = get_fields($staff->ID);

				// Create ProfilePage schema for staff pages
				$profile_page_schema = array(
					'@type' => 'ProfilePage',
					'@id' => get_permalink($staff->ID) . '#profilepage',
					'url' => get_permalink($staff->ID),
					'name' => $staff->post_title,
					'dateCreated' => get_the_date('c'),
					'dateModified' => get_the_modified_date('c'),
					'mainEntity' => array(
						'@type' => 'Person',
						'@id' => get_permalink($staff->ID) . '#person',
						'name' => $staff->post_title,
						'jobTitle' => $staff_data['job_title'] ?? '',
						'honorificSuffix' => $staff_data['honorific_suffix'] ?? '',
						'hasCredential' => array(
							'@type' => 'EducationalOccupationalCredential',
							'name' => $staff_data['credential_name'] ?? ''
						),
						'worksFor' => array(
							'@type' => 'Organization',
							'name' => $staff_data['works_for'] ?? ''
						),
						'url' => get_permalink($staff->ID),
						'image' => get_the_post_thumbnail_url($staff->ID),
						'sameAs' => array_filter(array(
							$staff_data['linkedin_url'] ?? ''
						)),
						'description' => $staff_data['description'] ?? '',
						'knowsAbout' => $staff_data['knows_about'] ?? '',
						'alumniOf' => array(
							'@type' => 'EducationalOrganization',
							'name' => $staff_data['alumni_of'] ?? ''
						),
						'award' => $staff_data['award'] ?? ''
					)
				);

				// Add ProfilePage to the data array instead of creating a separate key
				$data[] = $profile_page_schema;

			}

			return $data;
		}, 99, 1);
	}

	public function manage_bylines_schema()
	{
		add_filter('rank_math/json_ld', function ($data, $jsonld) {
			if (is_singular('post') || is_page()) {

				// Get the byline data
				$author_byline = $this->get_byline('author');
				$editor_byline = $this->get_byline('editor');
				$reviewer_byline = $this->get_byline('reviewedBy');

				// Build the author array and should contain bylines with author tags
				$author_schema = array();
				$editor_schema = array();
				$reviewer_schema = array();

				if ($author_byline) {
					$author_data = json_decode($author_byline->data, true);
					$author_schema = array(
						'@type' => 'Person',
						'url' => $author_data['page_url'],
						'image' => $author_byline->profile_image,
						'name' => str_replace("'", "", str_replace("\\", "", $author_byline->byline_name)),
						'honorificSuffix' => $author_data['honorificSuffix'],
						'description' => str_replace("'", "", str_replace("\\", "", $author_byline->description)),
						'jobTitle' => $author_data['jobTitle'],
						'hasCredential' => array(
							'@type' => $author_data['hasCredential']['@type'],
							'name' => $author_data['hasCredential']['name']
						),
						'worksFor' => array(
							// '@type' => $author_data['worksFor']['@type'],
							'@type' => 'Organization',
							'name' => $author_data['worksFor']['name']
						)
					);
				}

				if ($editor_byline) {
					$editor_data = json_decode($editor_byline->data, true);
					$editor_schema = array(
						'@type' => 'Person',
						'url' => $editor_data['page_url'],
						'image' => $editor_byline->profile_image,
						'name' => str_replace("'", "", str_replace("\\", "", $editor_byline->byline_name)),
						'honorificSuffix' => $editor_data['honorificSuffix'],
						'description' => str_replace("'", "", str_replace("\\", "", $editor_byline->description)),
						'jobTitle' => $editor_data['jobTitle'],
						'hasCredential' => array(
							'@type' => $editor_data['hasCredential']['@type'],
							'name' => $editor_data['hasCredential']['name']
						),
						'worksFor' => array(
							// '@type' => $editor_data['worksFor']['@type'],
							'@type' => 'Organization',
							'name' => $editor_data['worksFor']['name']
						)
					);
				}

				if ($reviewer_byline) {
					$reviewer_data = json_decode($reviewer_byline->data, true);
					$reviewer_schema = array(
						'@type' => 'Person',
						'url' => $reviewer_data['page_url'],
						'image' => $reviewer_byline->profile_image,
						'name' => str_replace("'", "", str_replace("\\", "", $reviewer_byline->byline_name)),
						'honorificSuffix' => $reviewer_data['honorificSuffix'],
						'description' => str_replace("'", "", str_replace("\\", "", $reviewer_byline->description)),
						'jobTitle' => $reviewer_data['jobTitle'],
						'hasCredential' => array(
							'@type' => $reviewer_data['hasCredential']['@type'],
							'name' => $reviewer_data['hasCredential']['name']
						),
						'worksFor' => array(
							// '@type' => $reviewer_data['worksFor']['@type'],
							'@type' => 'Organization',
							'name' => $reviewer_data['worksFor']['name']
						)
					);
				}

				foreach ($data as $key => $schema) {
					if (isset($schema['@type']) && ($schema['@type'] === 'Article' || $schema['@type'] === 'MedicalWebPage')) {
						// Remove existing author, editor, and reviewedBy if they exist
						if (isset($data[$key]['author'])) {
							unset($data[$key]['author']);
						}
						if (isset($data[$key]['editor'])) {
							unset($data[$key]['editor']);
						}
						if (isset($data[$key]['reviewedBy'])) {
							unset($data[$key]['reviewedBy']);
						}

						// force schema type to MedicalWebPage if the post is tagged with medicalwebpage
						$is_medical_webpage = has_tag('medicalwebpage', get_queried_object_id());
						if ($is_medical_webpage) {
							$data[$key]['@type'] = 'MedicalWebPage';
						}

						// Add author, editor, and reviewedBy in the desired order
						if (!empty($author_schema)) {
							$data[$key]['author'] = $author_schema;
						}
						if (!empty($editor_schema)) {
							$data[$key]['editor'] = $editor_schema;
						}
						if (!empty($reviewer_schema) && isset($data[$key]['@type']) && $data[$key]['@type'] === 'MedicalWebPage') {
							$data[$key]['reviewedBy'] = $reviewer_schema;
						}
					}
				}

				return $data;
			}
		}, 99, 2);
	}

	/**
	 * Get the byline data from the database
	 * Allows using "Staff CPT" if available
	 * 
	 * @param string $type
	 * @param boolean $use_cpt
	 * @return object|false
	 */
	public function get_byline($type, $use_cpt = false)
	{
		$tags = get_the_tags();

		$is_medical_webpage = has_tag('medicalwebpage', get_queried_object_id());

		if ($use_cpt) {
			if ($tags) {
				foreach ($tags as $tag) {
					if (strpos($tag->slug, 'authored-by') === 0 && $type === 'author') {
						return $this->get_staff($tag->slug);
					}

					if (strpos($tag->slug, 'edited-by') === 0 && $type === 'editor') {
						return $this->get_staff($tag->slug);
					}

					if ($is_medical_webpage && strpos($tag->slug, 'medically-reviewed-by') === 0 && $type === 'reviewedBy') {
						return $this->get_staff($tag->slug);
					}

					// In the press
					if (strpos($tag->slug, 'in-the-press-by') === 0 && $type === 'inThePress') {
						return $this->get_staff($tag->slug);
					}

				}
			}
		} else {
			global $wpdb;
			$table_name = $wpdb->prefix . 'amfm_bylines';

			if ($tags) {
				foreach ($tags as $tag) {
					if (strpos($tag->slug, 'authored-by') === 0 && $type === 'author') {
						return $wpdb->get_row("SELECT * FROM $table_name WHERE authorTag = '$tag->slug'");
					}

					if (strpos($tag->slug, 'edited-by') === 0 && $type === 'editor') {
						return $wpdb->get_row("SELECT * FROM $table_name WHERE editorTag = '$tag->slug'");
					}

					if ($is_medical_webpage && strpos($tag->slug, 'medically-reviewed-by') === 0 && $type === 'reviewedBy') {
						return $wpdb->get_row("SELECT * FROM $table_name WHERE reviewedByTag = '$tag->slug'");
					}
				}
			}
		}

		return false;
	}

	private function get_staff($tag)
	{
		// Get cpt "Staff" post data by given tag
		$staff = get_posts(array(
			'post_type' => 'staff',
			'numberposts' => 1,
			'tag' => $tag
		));

		if (empty($staff)) {
			return false;
		}
		return $staff[0];
	}

	/**
	 * Get the byline url
	 */
	private function get_byline_url($type)
	{
		$use_staff_cpt = get_option('amfm_use_staff_cpt');

		$byline = $this->get_byline($type, $use_staff_cpt);
		if (!$byline)
			return "No byline found";

		if ($use_staff_cpt) {
			$url = get_permalink($byline->ID);
		} else {
			$byline_data = json_decode($byline->data, true);
			$url = $byline_data['page_url'];
		}
		return preg_replace('/^https?:\/\//', '', $url);
	}

	/**
	 * Check if the post is tagged with the given tag
	 * 
	 * @param string $tag
	 * @param string $type
	 * @return boolean
	 */
	private function is_tagged($tag, $precise = false)
	{
		if ($precise) {
			return has_tag($tag, get_queried_object_id());
		}

		$tags = get_the_tags();

		if (!$precise && $tags) {
			foreach ($tags as $t) {
				if (strpos($t->slug, $tag) === 0) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Retrieve the LinkedIn URL for a staff member.
	 *
	 * This method checks if the current post is of the 'staff' post type and if the Advanced Custom Fields (ACF) plugin function `get_field` is available.
	 * If both conditions are met, it retrieves the LinkedIn URL from the custom field associated with the staff member.
	 *
	 * @return string|false The LinkedIn URL if available, or false if not.
	 */
	private function get_linkedin_url()
	{
		if (is_singular('staff') && function_exists('get_field')) {
			$staff_id = get_the_ID();
			return get_field('linkedin_url', $staff_id);
		}
		return false;
	}

	// create a shortcode to display bylines
	public function run_shortcodes()
	{
		$plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH . 'public/' : plugin_dir_path(__FILE__);
		include $plugin_path . 'partials/shortcodes/info.php';

		if (class_exists('ACF')) {
			include $plugin_path . 'partials/shortcodes/acf.php';
		}
	}

	public function run_elementor()
	{
		// Elementor Widgets
		// if (did_action( 'elementor/loaded' )) {
			require_once 'class-elementor-posts-widget.php';
			require_once 'class-elementor-staff-widget.php';
			require_once 'class-elementor-bylines-widget.php';
			require_once 'class-elementor-show-widget.php';
			require_once 'class-elementor-featured-images-widget.php';
		// }
	}

	public function amfm_fetch_related_posts()
	{
		check_ajax_referer('amfm_nonce', 'security');

		$widget_id = isset($_POST['widget_id']) ? sanitize_text_field($_POST['widget_id']) : false;
		$filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
		$posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 5;
		$paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
		$current_post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		$post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';

		if (!$widget_id) {
			wp_send_json(['content' => '<p>No related posts found.</p>']);
		}

		if (!$current_post_id) {
			wp_send_json(['content' => '<p>No related posts found.</p>']);
		}

		// Fetch the current post object
		$post = get_post($current_post_id);

		if (!$post) {
			wp_send_json(['content' => '<p>No related posts found.</p>']);
		}

		// Get tags of the current post
		$tags = wp_get_post_tags($post->ID);

		if (!$tags) {
			wp_send_json(['content' => '<p>No related posts found.</p>']);
		}

		// Filter tags based on the selected filter
		$tag_prefix = '';
		switch ($filter) {
			case 'author':
				$tag_prefix = 'authored-by-';
				break;
			case 'editor':
				$tag_prefix = 'edited-by-';
				break;
			case 'reviewer':
				$tag_prefix = 'reviewed-by-';
				break;
		}

		if ($tag_prefix) {
			$tags = array_filter($tags, function ($tag) use ($tag_prefix) {
				return strpos($tag->slug, $tag_prefix) === 0;
			});
		}

		if (empty($tags)) {
			wp_send_json(['content' => '<p>No related posts found.</p>']);
		}

		$tag_ids = array_map(function ($tag) {
			return $tag->term_id;
		}, $tags);

		// Query posts
		$args = [
			'post_type'      => $post_type === 'both' ? ['post', 'page'] : $post_type,
			'posts_per_page' => $posts_per_page,
			'post__not_in'   => [$post->ID],
			'tag__in'        => $tag_ids,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'paged'          => $paged,
		];

		$query = new WP_Query($args);

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

			// Pagination links
			$big = 999999999;
			$pagination = paginate_links([
				'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
				'format'    => '?paged=%#%',
				'current'   => max(1, $paged),
				'total'     => $query->max_num_pages,
				'prev_text' => '&laquo; Previous',
				'next_text' => 'Next &raquo;',
			]);

			wp_send_json([
				'content'    => ob_get_clean(),
				'pagination' => $pagination,
			]);
		} else {
			wp_send_json(['content' => '<p>No related posts found.</p>']);
		}
		wp_reset_postdata();
	}
}
