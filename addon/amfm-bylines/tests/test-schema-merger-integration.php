<?php
/**
 * Schema Merger Integration Testing Script
 * Tests integration between custom schema types and byline module schema merger
 * Run with: wp eval-file tests/test-schema-merger-integration.php
 */

// Load WordPress environment
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/../../../../wp-load.php');
}

// Load required schema classes
require_once(dirname(__FILE__) . '/../public/schema/class-amfm-location-handler.php');
require_once(dirname(__FILE__) . '/../public/schema/class-amfm-shortcode-parser.php');
require_once(dirname(__FILE__) . '/../public/schema/class-amfm-schema-merger.php');

// Colors for output
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[0;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

echo "\n{$blue}========================================{$reset}\n";
echo "{$blue}Schema Merger Integration Testing{$reset}\n";
echo "{$blue}========================================{$reset}\n\n";

// Initialize handlers
$location_handler = new Amfm_Location_Handler('amfm-bylines', '3.1.0');
$shortcode_parser = new Amfm_Shortcode_Parser('amfm-bylines', '3.1.0', $location_handler);

// Check if merger class exists and can be instantiated
if (class_exists('Amfm_Schema_Merger')) {
    $schema_merger = new Amfm_Schema_Merger('amfm-bylines', '3.1.0');
    echo "{$green}✓ Schema merger initialized successfully{$reset}\n\n";
} else {
    echo "{$red}✗ Schema merger class not found{$reset}\n\n";
    exit(1);
}

// Test results tracking
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

/**
 * Helper function to test schema merger integration
 */
function test_merger($name, $byline_schema, $custom_schema, $schema_merger, &$test_results, &$total_tests, &$passed_tests, $green, $red, $yellow, $reset) {
    $total_tests++;
    
    echo "{$yellow}{$name}:{$reset}\n";
    echo str_repeat('-', strlen($name) + 1) . "\n";
    
    $success = true;
    $errors = [];
    
    try {
        // Test the merger
        $merged_schema = $schema_merger->merge_schemas($byline_schema, $custom_schema);
        
        // Validate merged result
        if (empty($merged_schema)) {
            $success = false;
            $errors[] = "Merge returned empty result";
        } else {
            // Check if it's valid JSON
            $parsed = json_decode($merged_schema, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $success = false;
                $errors[] = "Merged schema is not valid JSON: " . json_last_error_msg();
            } else {
                // Check basic structure
                if (!isset($parsed['@context'])) {
                    $success = false;
                    $errors[] = "Missing @context in merged schema";
                }
                
                if (!isset($parsed['@graph'])) {
                    $success = false;
                    $errors[] = "Missing @graph in merged schema";
                }
                
                // Check that both schemas are present
                if (isset($parsed['@graph'])) {
                    $has_byline = false;
                    $has_custom = false;
                    
                    foreach ($parsed['@graph'] as $item) {
                        if (isset($item['@type'])) {
                            // Check for byline schema indicators
                            if (in_array($item['@type'], ['Article', 'WebPage', 'Person'])) {
                                $has_byline = true;
                            }
                            // Check for custom schema indicators
                            if (in_array($item['@type'], ['MedicalCondition', 'FAQPage', 'MedicalOrganization', 'MedicalWebPage'])) {
                                $has_custom = true;
                            }
                        }
                    }
                    
                    if (!$has_byline) {
                        $errors[] = "Byline schema elements not found in merged result";
                    }
                    
                    if (!$has_custom) {
                        $errors[] = "Custom schema elements not found in merged result";
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        $success = false;
        $errors[] = "Exception during merge: " . $e->getMessage();
    }
    
    if ($success) {
        echo "{$green}✓ PASSED{$reset}\n";
        $passed_tests++;
        $test_results[$name] = ['status' => 'passed', 'errors' => []];
        
        // Show sample of merged output
        $sample = substr($merged_schema, 0, 200);
        echo "Sample output: " . $sample . "...\n";
    } else {
        echo "{$red}✗ FAILED{$reset}\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
        $test_results[$name] = ['status' => 'failed', 'errors' => $errors];
    }
    
    echo "\n";
}

// Mock byline schema (simulating what the byline module would provide)
$sample_byline_schema = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Article',
            '@id' => 'https://amfmstg.wpengine.com/article#article',
            'headline' => 'Understanding Depression Treatment',
            'author' => [
                '@type' => 'Person',
                'name' => 'Dr. Jane Smith',
                '@id' => 'https://amfmstg.wpengine.com/author/dr-jane-smith#person'
            ],
            'datePublished' => '2025-01-15',
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'AMFM Mental Health',
                '@id' => 'https://amfmstg.wpengine.com#organization'
            ]
        ],
        [
            '@type' => 'WebPage',
            '@id' => 'https://amfmstg.wpengine.com/article#webpage',
            'url' => 'https://amfmstg.wpengine.com/article',
            'name' => 'Understanding Depression Treatment',
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => 'https://amfmstg.wpengine.com/'
                    ]
                ]
            ]
        ]
    ]
];

// Test 1: Basic Medical Condition Integration
echo "{$yellow}Test 1: Medical Condition + Article Schema{$reset}\n";
echo "==========================================\n";

$custom_schema1 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="amfm"}},
    {
      "@type": "MedicalCondition",
      "@id": "https://amfmstg.wpengine.com/depression#condition",
      "name": "Depression",
      "possibleTreatment": [
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy" }
      ]
    }
  ]
}';

test_merger('Medical Condition + Article', $sample_byline_schema, $custom_schema1, $schema_merger, $test_results, $total_tests, $passed_tests, $green, $red, $yellow, $reset);

// Test 2: FAQ Page Integration
echo "{$yellow}Test 2: FAQ Page + Article Schema{$reset}\n";
echo "=================================\n";

$custom_schema2 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mc"}},
    {
      "@type": "FAQPage",
      "@id": "https://amfmstg.wpengine.com/faq#faq",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is depression?",
          "acceptedAnswer": {
            "@type": "Answer", 
            "text": "Depression is a mental health condition characterized by persistent low mood."
          }
        }
      ]
    }
  ]
}';

test_merger('FAQ Page + Article', $sample_byline_schema, $custom_schema2, $schema_merger, $test_results, $total_tests, $passed_tests, $green, $red, $yellow, $reset);

// Test 3: Medical Web Page Upgrade
echo "{$yellow}Test 3: Medical Web Page Upgrade{$reset}\n";
echo "================================\n";

$custom_schema3 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mp"}},
    {
      "@type": "MedicalWebPage",
      "@id": "https://amfmstg.wpengine.com/article#medical-webpage",
      "medicalAudience": {
        "@type": "PatientsAudience"
      },
      "about": {
        "@type": "MedicalCondition",
        "name": "Depression"
      }
    }
  ]
}';

test_merger('Medical Web Page Upgrade', $sample_byline_schema, $custom_schema3, $schema_merger, $test_results, $total_tests, $passed_tests, $green, $red, $yellow, $reset);

// Test 4: Complex Multi-Schema Integration
echo "{$yellow}Test 4: Complex Multi-Schema Integration{$reset}\n";
echo "========================================\n";

$custom_schema4 = '{
  "@context": "https://schema.org", 
  "@graph": [
    {{location brand="amfm" region="southwest"}},
    {
      "@type": "MedicalCondition",
      "@id": "https://amfmstg.wpengine.com/depression#condition",
      "name": "Depression",
      "signOrSymptom": [
        { "@type": "MedicalSignOrSymptom", "name": "Persistent low mood" },
        { "@type": "MedicalSignOrSymptom", "name": "Loss of interest" }
      ]
    },
    {
      "@type": "FAQPage",
      "@id": "https://amfmstg.wpengine.com/depression-faq#faq",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "How is depression treated?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Depression is treated with therapy, medication, or both."
          }
        }
      ]
    },
    {
      "@type": "Service",
      "@id": "https://amfmstg.wpengine.com/depression-treatment#service",
      "name": "Depression Treatment Service",
      "provider": { "@id": "https://amfmstg.wpengine.com/#org" }
    }
  ]
}';

test_merger('Complex Multi-Schema', $sample_byline_schema, $custom_schema4, $schema_merger, $test_results, $total_tests, $passed_tests, $green, $red, $yellow, $reset);

// Test 5: Empty Custom Schema Handling
echo "{$yellow}Test 5: Empty Custom Schema Handling{$reset}\n";
echo "====================================\n";

$custom_schema5 = '';

test_merger('Empty Custom Schema', $sample_byline_schema, $custom_schema5, $schema_merger, $test_results, $total_tests, $passed_tests, $green, $red, $yellow, $reset);

// Test 6: Invalid JSON Handling
echo "{$yellow}Test 6: Invalid JSON Handling{$reset}\n";
echo "=============================\n";

$custom_schema6 = '{ "invalid": json, syntax }';

test_merger('Invalid JSON', $sample_byline_schema, $custom_schema6, $schema_merger, $test_results, $total_tests, $passed_tests, $green, $red, $yellow, $reset);

// Display Summary
echo "{$blue}========================================{$reset}\n";
echo "{$blue}Integration Test Summary{$reset}\n";
echo "{$blue}========================================{$reset}\n\n";

echo "Total Tests: {$total_tests}\n";
echo "Passed: {$green}{$passed_tests}{$reset}\n";
echo "Failed: " . ($total_tests - $passed_tests > 0 ? "{$red}" . ($total_tests - $passed_tests) . "{$reset}" : "0") . "\n";
echo "Success Rate: " . round(($passed_tests / $total_tests) * 100, 1) . "%\n\n";

// Detailed Results
foreach ($test_results as $test_name => $result) {
    $status_color = $result['status'] === 'passed' ? $green : $red;
    $status_text = strtoupper($result['status']);
    echo "{$test_name}: {$status_color}{$status_text}{$reset}\n";
    
    if (!empty($result['errors'])) {
        foreach ($result['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
}

echo "\n";

// Final Verdict
echo "{$blue}========================================{$reset}\n";
if ($passed_tests >= ($total_tests * 0.8)) {
    echo "{$green}✅ Schema merger integration working well!{$reset}\n";
    echo "The system successfully integrates custom schema types with byline module schema.\n";
} else {
    echo "{$red}❌ Schema merger integration needs attention.{$reset}\n";
    echo "Please review the failures above and fix integration issues.\n";
}
echo "{$blue}========================================{$reset}\n\n";