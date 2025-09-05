<?php
/**
 * Multi-brand schema testing script
 * Tests amfm, mp, and mc brands
 * Run with: wp eval-file test-multi-brand.php
 */

// Load WordPress environment
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/../../../wp-load.php');
}

// Colors for output
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[0;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

echo "\n{$blue}========================================{$reset}\n";
echo "{$blue}Multi-Brand Schema Testing{$reset}\n";
echo "{$blue}========================================{$reset}\n\n";

// Initialize handlers
$location_handler = new Amfm_Location_Handler('amfm-bylines', '3.1.0');
$shortcode_parser = new Amfm_Shortcode_Parser('amfm-bylines', '3.1.0', $location_handler);

// Test all brands
$brands = ['amfm', 'mc', 'mp'];
$test_results = [];

foreach ($brands as $brand) {
    echo "{$yellow}Testing Brand: " . strtoupper($brand) . "{$reset}\n";
    echo "========================================\n";
    
    // 1. Test location filtering by brand
    $brand_locations = $location_handler->filter_locations(['brand' => $brand]);
    $location_count = count($brand_locations);
    
    echo "1. Location Data:\n";
    echo "   - Total locations: {$location_count}\n";
    
    if ($location_count > 0) {
        echo "{$green}   ✓ Brand has locations{$reset}\n";
        
        // Show first 3 locations as examples
        echo "   - Sample locations:\n";
        $sample_count = min(3, $location_count);
        for ($i = 0; $i < $sample_count; $i++) {
            $loc = $brand_locations[$i];
            $city = $loc['City'] ?? 'N/A';
            $state = $loc['State'] ?? 'N/A';
            $region = $loc['Region'] ?? 'N/A';
            echo "     • {$city}, {$state} (Region: {$region})\n";
        }
        
        // Analyze regions
        $regions = [];
        foreach ($brand_locations as $loc) {
            $region = $loc['Region'] ?? 'unknown';
            if (!isset($regions[$region])) {
                $regions[$region] = 0;
            }
            $regions[$region]++;
        }
        
        echo "   - Regional distribution:\n";
        foreach ($regions as $region => $count) {
            echo "     • {$region}: {$count} locations\n";
        }
    } else {
        echo "{$red}   ✗ No locations found for brand{$reset}\n";
    }
    
    echo "\n";
    
    // 2. Test shortcode parsing
    echo "2. Shortcode Parsing:\n";
    
    $test_schema = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="' . $brand . '"}},
    {
      "@type": "WebPage",
      "name": "Test Page for ' . strtoupper($brand) . '"
    }
  ]
}';
    
    $parsed_json = $shortcode_parser->parse_to_json($test_schema);
    $parsed_array = json_decode($parsed_json, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "{$green}   ✓ Schema parsed successfully{$reset}\n";
        
        // Check for MedicalOrganization
        $has_org = false;
        $clinic_count = 0;
        $org_name = '';
        
        if (isset($parsed_array['@graph'])) {
            foreach ($parsed_array['@graph'] as $item) {
                if (isset($item['@type']) && $item['@type'] === 'MedicalOrganization') {
                    $has_org = true;
                    $org_name = $item['name'] ?? 'Unknown';
                    if (isset($item['department']) && is_array($item['department'])) {
                        $clinic_count = count($item['department']);
                    }
                    break;
                }
            }
        }
        
        if ($has_org) {
            echo "{$green}   ✓ MedicalOrganization generated{$reset}\n";
            echo "   - Organization name: {$org_name}\n";
            echo "   - Number of clinics: {$clinic_count}\n";
            
            if ($clinic_count != $location_count) {
                echo "{$yellow}   ⚠ Clinic count ({$clinic_count}) doesn't match location count ({$location_count}){$reset}\n";
            }
        } else {
            echo "{$red}   ✗ MedicalOrganization not found in schema{$reset}\n";
        }
    } else {
        echo "{$red}   ✗ JSON parsing error: " . json_last_error_msg() . "{$reset}\n";
    }
    
    echo "\n";
    
    // 3. Test regional filtering
    echo "3. Regional Filtering:\n";
    
    // Get unique regions for this brand
    $brand_regions = [];
    foreach ($brand_locations as $loc) {
        $region = $loc['Region'] ?? '';
        if (!empty($region) && $region !== 'unknown') {
            $brand_regions[$region] = true;
        }
    }
    
    if (!empty($brand_regions)) {
        foreach (array_keys($brand_regions) as $region) {
            $filtered = $location_handler->filter_locations([
                'brand' => $brand,
                'region' => $region
            ]);
            $count = count($filtered);
            echo "   - {$region}: {$count} locations\n";
            
            // Test shortcode with region filter
            $region_schema = '{{location brand="' . $brand . '" region="' . $region . '"}}';
            $region_parsed = $shortcode_parser->parse_to_json($region_schema);
            $region_array = json_decode($region_parsed, true);
            
            if (isset($region_array['department'])) {
                $region_clinic_count = count($region_array['department']);
                if ($region_clinic_count === $count) {
                    echo "{$green}     ✓ Regional shortcode working ({$region_clinic_count} clinics){$reset}\n";
                } else {
                    echo "{$red}     ✗ Regional shortcode mismatch{$reset}\n";
                }
            }
        }
    } else {
        echo "   - No regional data available\n";
    }
    
    echo "\n";
    
    // 4. Test state filtering
    echo "4. State Filtering:\n";
    
    // Get unique states for this brand
    $brand_states = [];
    foreach ($brand_locations as $loc) {
        $state = $loc['State'] ?? '';
        if (!empty($state)) {
            if (!isset($brand_states[$state])) {
                $brand_states[$state] = 0;
            }
            $brand_states[$state]++;
        }
    }
    
    if (!empty($brand_states)) {
        // Show top 3 states
        arsort($brand_states);
        $state_count = 0;
        foreach ($brand_states as $state => $count) {
            if ($state_count >= 3) break;
            
            $filtered = $location_handler->filter_locations([
                'brand' => $brand,
                'state' => $state
            ]);
            $actual_count = count($filtered);
            echo "   - {$state}: {$actual_count} locations\n";
            
            // Test shortcode with state filter
            $state_schema = '{{location brand="' . $brand . '" state="' . $state . '"}}';
            $state_parsed = $shortcode_parser->parse_to_json($state_schema);
            $state_array = json_decode($state_parsed, true);
            
            if (isset($state_array['department'])) {
                $state_clinic_count = count($state_array['department']);
                if ($state_clinic_count === $actual_count) {
                    echo "{$green}     ✓ State shortcode working ({$state_clinic_count} clinics){$reset}\n";
                } else {
                    echo "{$red}     ✗ State shortcode mismatch{$reset}\n";
                }
            }
            
            $state_count++;
        }
    } else {
        echo "   - No state data available\n";
    }
    
    // Store results
    $test_results[$brand] = [
        'location_count' => $location_count,
        'has_org' => $has_org,
        'clinic_count' => $clinic_count,
        'regions' => count($brand_regions),
        'states' => count($brand_states)
    ];
    
    echo "\n";
}

// Summary
echo "{$blue}========================================{$reset}\n";
echo "{$blue}Test Summary{$reset}\n";
echo "{$blue}========================================{$reset}\n\n";

$all_passed = true;

foreach ($test_results as $brand => $results) {
    $brand_passed = $results['location_count'] > 0 && $results['has_org'];
    $status = $brand_passed ? "{$green}✓ PASSED{$reset}" : "{$red}✗ FAILED{$reset}";
    
    echo strtoupper($brand) . ": {$status}\n";
    echo "  - Locations: {$results['location_count']}\n";
    echo "  - Clinics in schema: {$results['clinic_count']}\n";
    echo "  - Regions: {$results['regions']}\n";
    echo "  - States: {$results['states']}\n";
    echo "\n";
    
    if (!$brand_passed) {
        $all_passed = false;
    }
}

// Test combined filtering
echo "{$yellow}Combined Filtering Tests:{$reset}\n";
echo "========================================\n";

// Test complex filter combinations
$complex_tests = [
    ['brand' => 'amfm', 'region' => 'southwest'],
    ['brand' => 'mp', 'state' => 'CA'],
    ['brand' => 'mc', 'region' => 'northwest'],
    ['brand' => 'amfm', 'state' => 'VA'],
];

foreach ($complex_tests as $filter) {
    $filter_desc = json_encode($filter);
    $filtered = $location_handler->filter_locations($filter);
    $count = count($filtered);
    
    echo "Filter: {$filter_desc}\n";
    echo "  - Results: {$count} locations\n";
    
    // Build shortcode
    $shortcode_parts = [];
    foreach ($filter as $key => $value) {
        $shortcode_parts[] = $key . '="' . $value . '"';
    }
    $shortcode = '{{location ' . implode(' ', $shortcode_parts) . '}}';
    
    // Test shortcode
    $test_parsed = $shortcode_parser->parse_to_json($shortcode);
    $test_array = json_decode($test_parsed, true);
    
    if (isset($test_array['department'])) {
        $dept_count = count($test_array['department']);
        if ($dept_count === $count) {
            echo "{$green}  ✓ Shortcode generates {$dept_count} clinics correctly{$reset}\n";
        } else {
            echo "{$red}  ✗ Shortcode mismatch: expected {$count}, got {$dept_count}{$reset}\n";
        }
    } else if ($count === 0) {
        echo "{$yellow}  ⚠ No locations match this filter{$reset}\n";
    } else {
        echo "{$red}  ✗ Shortcode failed to generate departments{$reset}\n";
    }
    echo "\n";
}

// Final verdict
echo "{$blue}========================================{$reset}\n";
if ($all_passed) {
    echo "{$green}✅ All brands tested successfully!{$reset}\n";
    echo "The schema system supports multiple brands correctly.\n";
} else {
    echo "{$red}❌ Some brands failed testing.{$reset}\n";
    echo "Please review the output above for details.\n";
}
echo "{$blue}========================================{$reset}\n\n";