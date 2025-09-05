<?php

/**
 * The location handler class.
 *
 * Handles master location JSON data and generates location-based schema.
 *
 * @link       https://adzjo.online/adz
 * @since      3.1.0
 *
 * @package    Amfm_Bylines
 * @subpackage Amfm_Bylines/public/schema
 */

class Amfm_Location_Handler {

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
     * The master location data.
     *
     * @since    3.1.0
     * @access   private
     * @var      array    $master_locations    The cached master location data.
     */
    private $master_locations;

    /**
     * The data file path.
     *
     * @since    3.1.0
     * @access   private
     * @var      string    $data_file_path    Path to the master locations JSON file.
     */
    private $data_file_path;

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
        $plugin_path = defined('AMFM_BYLINES_PLUGIN_PATH') ? AMFM_BYLINES_PLUGIN_PATH . 'public/schema/' : plugin_dir_path(__FILE__);
        $this->data_file_path = $plugin_path . 'data/master-locations.json';
        $this->master_locations = null;
    }

    /**
     * Get master location data.
     *
     * @since    3.1.0
     * @param    bool    $force_refresh    Whether to force refresh the data.
     * @return   array                     The master location data.
     */
    public function get_master_locations($force_refresh = false) {
        if ($this->master_locations === null || $force_refresh) {
            $this->load_master_locations();
        }
        
        return $this->master_locations;
    }

    /**
     * Load master location data from database (wp_options).
     *
     * @since    3.1.0
     * @access   private
     */
    private function load_master_locations() {
        // First try to get data from amfm-maps plugin wp_options
        $db_locations = get_option('amfm_maps_json_data', array());
        
        if (!empty($db_locations) && is_array($db_locations)) {
            // Filter out "Statewide" locations as they don't have complete addresses
            $filtered_locations = array_filter($db_locations, function($location) {
                return isset($location['City']) && 
                       !empty($location['City']) && 
                       strtolower($location['City']) !== 'statewide' &&
                       isset($location['Complete Address']) && 
                       !empty($location['Complete Address']);
            });
            
            // Map the database structure to our expected format
            $this->master_locations = $this->map_database_locations($filtered_locations);
        } else {
            // Fallback to file-based loading if database is empty
            if (file_exists($this->data_file_path)) {
                $file_content = file_get_contents($this->data_file_path);
                $file_locations = json_decode($file_content, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('AMFM Location Handler: JSON decode error in master-locations.json - ' . json_last_error_msg());
                    $this->master_locations = $this->get_default_location_data();
                } else {
                    $this->master_locations = $file_locations;
                }
            } else {
                $this->master_locations = $this->get_default_location_data();
            }
        }
    }

    /**
     * Get default location data.
     *
     * @since    3.1.0
     * @return   array    Default location data.
     */
    private function get_default_location_data() {
        return array(
            array(
                'Business name' => 'Mission Connection',
                '(Internal) Shortname' => 'mc',
                'Street' => '2900 S. Quincy St',
                'Unit' => '#810',
                'City' => 'Arlington',
                'State' => 'VA',
                'Zipcode' => '22206',
                'GMB_Phone' => '+1-555-555-5555',
                'Region' => 'southeast'
            ),
            array(
                'Business name' => 'Mission Connection',
                '(Internal) Shortname' => 'mc',
                'Street' => '6900 East Green Lake Way N',
                'Unit' => 'Suite G',
                'City' => 'Seattle',
                'State' => 'WA',
                'Zipcode' => '98115',
                'GMB_Phone' => '+1-555-555-5555',
                'Region' => 'northwest'
            ),
            array(
                'Business name' => 'Mission Connection',
                '(Internal) Shortname' => 'mc',
                'Street' => '11000 NE 33rd Pl',
                'Unit' => '#340',
                'City' => 'Bellevue',
                'State' => 'WA',
                'Zipcode' => '98004',
                'GMB_Phone' => '+1-555-555-5555',
                'Region' => 'northwest'
            ),
            array(
                'Business name' => 'Mission Connection',
                '(Internal) Shortname' => 'mc',
                'Street' => '30300 Rancho Viejo Rd',
                'Unit' => 'Suite A',
                'City' => 'San Juan Capistrano',
                'State' => 'CA',
                'Zipcode' => '92675',
                'GMB_Phone' => '+1-555-555-5555',
                'Region' => 'southwest'
            ),
            array(
                'Business name' => 'Mission Connection',
                '(Internal) Shortname' => 'mc',
                'Street' => '7777 Leesburg Pike',
                'Unit' => 'Suite 10N',
                'City' => 'Falls Church',
                'State' => 'VA',
                'Zipcode' => '22043',
                'GMB_Phone' => '+1-555-555-5555',
                'Region' => 'southeast'
            )
        );
    }

    /**
     * Save master location data to file.
     *
     * @since    3.1.0
     */
    public function save_master_locations() {
        $data_dir = dirname($this->data_file_path);
        
        if (!file_exists($data_dir)) {
            wp_mkdir_p($data_dir);
        }
        
        file_put_contents(
            $this->data_file_path,
            wp_json_encode($this->master_locations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Filter locations based on criteria.
     *
     * @since    3.1.0
     * @param    array    $filters    Filter criteria (brand, region, state).
     * @return   array               Filtered locations.
     */
    public function filter_locations($filters = array()) {
        $locations = $this->get_master_locations();
        
        if (empty($filters)) {
            return $locations;
        }
        
        $filtered = array();
        
        foreach ($locations as $location) {
            $include = true;
            
            if (isset($filters['brand'])) {
                $location_brand = strtolower($location['(Internal) Shortname'] ?? '');
                $filter_brand = strtolower($filters['brand']);
                if ($location_brand !== $filter_brand) {
                    $include = false;
                }
            }
            
            if (isset($filters['region']) && $include) {
                $location_region = strtolower($location['Region'] ?? '');
                $filter_region = strtolower($filters['region']);
                if ($location_region !== $filter_region) {
                    $include = false;
                }
            }
            
            if (isset($filters['state']) && $include) {
                $location_state = strtoupper($location['State'] ?? '');
                $filter_state = strtoupper($filters['state']);
                if ($location_state !== $filter_state) {
                    $include = false;
                }
            }
            
            if ($include) {
                $filtered[] = $location;
            }
        }
        
        return $filtered;
    }

    /**
     * Generate location schema based on filters.
     *
     * @since    3.1.0
     * @param    array    $filters    Filter criteria.
     * @return   array|false         The location schema or false on error.
     */
    public function generate_location_schema($filters = array()) {
        $filtered_locations = $this->filter_locations($filters);
        
        if (empty($filtered_locations)) {
            return false;
        }

        $first_location = $filtered_locations[0];
        $business_name = $first_location['Business name'];
        $brand_shortname = $first_location['(Internal) Shortname'];
        
        $site_url = get_home_url();
        $org_id = $site_url . '/#org';
        
        $medical_clinics = array();
        
        foreach ($filtered_locations as $location) {
            $street_address = trim($location['Street'] . ' ' . ($location['Unit'] ?? ''));
            
            $medical_clinics[] = array(
                '@type' => 'MedicalClinic',
                'name' => $location['Business name'],
                'address' => array(
                    '@type' => 'PostalAddress',
                    'streetAddress' => $street_address,
                    'addressLocality' => $location['City'],
                    'addressRegion' => $location['State'],
                    'postalCode' => $location['Zipcode'],
                    'addressCountry' => 'US'
                ),
                'telephone' => $location['GMB_Phone']
            );
        }
        
        $logo_url = $this->get_organization_logo($brand_shortname);
        $description = $this->get_organization_description($business_name);
        
        $medical_organization = array(
            '@type' => 'MedicalOrganization',
            '@id' => $org_id,
            'name' => $business_name,
            'url' => $site_url . '/',
            'logo' => $logo_url,
            'description' => $description,
            'contactPoint' => array(
                '@type' => 'ContactPoint',
                'telephone' => $first_location['GMB_Phone'],
                'areaServed' => 'US',
                'availableLanguage' => 'English'
            ),
            'department' => $medical_clinics
        );
        
        return $medical_organization;
    }

    /**
     * Get organization logo URL.
     *
     * @since    3.1.0
     * @param    string    $brand_shortname    The brand shortname.
     * @return   string                        The logo URL.
     */
    private function get_organization_logo($brand_shortname) {
        $site_url = get_home_url();
        
        $logo_paths = array(
            'mc' => '/wp-content/uploads/2024/03/logo.svg',
            'default' => '/wp-content/uploads/2024/03/logo.svg'
        );
        
        $logo_path = isset($logo_paths[$brand_shortname]) ? 
                     $logo_paths[$brand_shortname] : 
                     $logo_paths['default'];
        
        return $site_url . $logo_path;
    }

    /**
     * Get organization description.
     *
     * @since    3.1.0
     * @param    string    $business_name    The business name.
     * @return   string                      The description.
     */
    private function get_organization_description($business_name) {
        global $post;
        
        $base_description = $business_name . ' offers specialized mental health treatment services';
        
        if ($post) {
            $post_title = get_the_title($post->ID);
            
            if (stripos($post_title, 'depression') !== false) {
                return $base_description . ', including comprehensive depression treatment and therapy.';
            } elseif (stripos($post_title, 'anxiety') !== false) {
                return $base_description . ', including anxiety disorder treatment and support.';
            } elseif (stripos($post_title, 'anger') !== false) {
                return $base_description . ', including support for anger issues and behavioral disorders.';
            } elseif (stripos($post_title, 'addiction') !== false) {
                return $base_description . ', including addiction treatment and recovery programs.';
            }
        }
        
        return $base_description . ', providing comprehensive mental health care and therapeutic support.';
    }

    /**
     * Update master location data.
     *
     * @since    3.1.0
     * @param    array    $new_locations    New location data.
     * @return   bool                       Success status.
     */
    public function update_master_locations($new_locations) {
        if (!is_array($new_locations)) {
            return false;
        }
        
        $this->master_locations = $new_locations;
        $this->save_master_locations();
        
        return true;
    }

    /**
     * Get location statistics.
     *
     * @since    3.1.0
     * @return   array    Location statistics.
     */
    public function get_location_statistics() {
        $locations = $this->get_master_locations();
        
        $stats = array(
            'total_locations' => count($locations),
            'brands' => array(),
            'regions' => array(),
            'states' => array()
        );
        
        foreach ($locations as $location) {
            $brand = $location['(Internal) Shortname'] ?? 'unknown';
            $region = $location['Region'] ?? 'unknown';
            $state = $location['State'] ?? 'unknown';
            
            $stats['brands'][$brand] = ($stats['brands'][$brand] ?? 0) + 1;
            $stats['regions'][$region] = ($stats['regions'][$region] ?? 0) + 1;
            $stats['states'][$state] = ($stats['states'][$state] ?? 0) + 1;
        }
        
        return $stats;
    }

    /**
     * Map database location structure to expected format.
     *
     * @since    3.1.0
     * @param    array    $db_locations    Database locations.
     * @return   array                     Mapped locations.
     */
    private function map_database_locations($db_locations) {
        $mapped_locations = array();
        
        foreach ($db_locations as $db_location) {
            // Map region based on state and existing regional data
            $region = $this->map_location_region($db_location);
            
            // Set default phone if not available (database doesn't have phone numbers)
            $phone = '+1-555-555-5555'; // Default phone number
            
            // Check if phone exists in various possible fields
            $phone_fields = array('GMB_Phone', 'Phone', 'phone', 'Telephone', 'telephone');
            foreach ($phone_fields as $field) {
                if (isset($db_location[$field]) && !empty($db_location[$field])) {
                    $phone = $db_location[$field];
                    break;
                }
            }
            
            $mapped_location = array(
                'Business name' => $db_location['Business name'] ?? 'Unknown Business',
                '(Internal) Shortname' => $db_location['(Internal) Shortname'] ?? 'unknown',
                'Street' => $db_location['Street'] ?? '',
                'Unit' => $db_location['Unit'] ?? '',
                'City' => $db_location['City'] ?? '',
                'State' => $db_location['State'] ?? '',
                'Zipcode' => $db_location['Zipcode'] ?? '',
                'GMB_Phone' => $phone,
                'Region' => $region
            );
            
            $mapped_locations[] = $mapped_location;
        }
        
        return $mapped_locations;
    }
    
    /**
     * Map location region based on state and other data.
     *
     * @since    3.1.0
     * @param    array    $db_location    Database location.
     * @return   string                   Mapped region.
     */
    private function map_location_region($db_location) {
        // First check if region is already set in database
        if (isset($db_location['Region']) && !empty($db_location['Region'])) {
            $db_region = strtolower($db_location['Region']);
            
            // Map common region variations to our standard regions
            $region_mappings = array(
                'orange county' => 'southwest',
                'san diego county' => 'southwest', 
                'los angeles county' => 'southwest',
                'california' => 'southwest',
                'ca' => 'southwest',
                'virginia' => 'southeast',
                'va' => 'southeast',
                'washington' => 'northwest',
                'wa' => 'northwest',
                'seattle' => 'northwest',
                'bellevue' => 'northwest',
                'arlington' => 'southeast',
                'falls church' => 'southeast'
            );
            
            if (isset($region_mappings[$db_region])) {
                return $region_mappings[$db_region];
            }
            
            // Return the original region if it's already in our standard format
            if (in_array($db_region, array('northwest', 'southwest', 'southeast', 'northeast'))) {
                return $db_region;
            }
        }
        
        // Fallback to state-based mapping
        $state = strtoupper($db_location['State'] ?? '');
        $state_region_map = array(
            'CA' => 'southwest',
            'VA' => 'southeast', 
            'WA' => 'northwest',
            'OR' => 'northwest',
            'MN' => 'midwest'
        );
        
        return isset($state_region_map[$state]) ? $state_region_map[$state] : 'unknown';
    }
}