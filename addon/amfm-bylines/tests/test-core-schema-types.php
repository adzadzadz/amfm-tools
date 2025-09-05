<?php
/**
 * Core Schema Types Testing Script
 * Tests all schema types from core_schema_types.md through our system
 * Run with: wp eval-file tests/test-core-schema-types.php
 */

// Load WordPress environment
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/../../../../wp-load.php');
}

// Colors for output
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[0;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

echo "\n{$blue}========================================{$reset}\n";
echo "{$blue}Core Schema Types Testing{$reset}\n";
echo "{$blue}========================================{$reset}\n\n";

// Initialize handlers
$location_handler = new Amfm_Location_Handler('amfm-bylines', '3.1.0');
$shortcode_parser = new Amfm_Shortcode_Parser('amfm-bylines', '3.1.0', $location_handler);

// Test results tracking
$test_results = [];
$total_tests = 0;
$passed_tests = 0;

/**
 * Helper function to test a schema
 */
function test_schema($name, $schema_json, $shortcode_parser, &$test_results, &$total_tests, &$passed_tests, $green, $red, $reset) {
    $total_tests++;
    
    echo "{$name}:\n";
    echo str_repeat('-', strlen($name) + 1) . "\n";
    
    // Test JSON parsing
    $parsed_json = $shortcode_parser->parse_to_json($schema_json);
    $parsed_array = json_decode($parsed_json, true);
    
    $success = true;
    $errors = [];
    
    // Check JSON validity
    if (json_last_error() !== JSON_ERROR_NONE) {
        $success = false;
        $errors[] = "JSON parsing error: " . json_last_error_msg();
    }
    
    // Check basic structure
    if ($success && !isset($parsed_array['@context'])) {
        $success = false;
        $errors[] = "Missing @context";
    }
    
    // Check for required @type fields
    if ($success) {
        if (isset($parsed_array['@graph'])) {
            $has_types = false;
            foreach ($parsed_array['@graph'] as $item) {
                if (isset($item['@type'])) {
                    $has_types = true;
                    break;
                }
            }
            if (!$has_types) {
                $success = false;
                $errors[] = "No @type found in @graph items";
            }
        } elseif (!isset($parsed_array['@type'])) {
            $success = false;
            $errors[] = "Missing @type in root or @graph structure";
        }
    }
    
    if ($success) {
        echo "{$green}✓ PASSED{$reset}\n";
        $passed_tests++;
        $test_results[$name] = ['status' => 'passed', 'errors' => []];
    } else {
        echo "{$red}✗ FAILED{$reset}\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
        $test_results[$name] = ['status' => 'failed', 'errors' => $errors];
    }
    
    echo "\n";
}

// Test Schema 1: Medical Condition with Organization
echo "{$yellow}Testing Schema Type 1: Medical Condition + Medical Organization{$reset}\n";
echo "=============================================================\n";

$schema1 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="amfm"}},
    {
      "@type": "MedicalCondition",
      "@id": "https://amfmstg.wpengine.com/mental-health/depression/#condition",
      "name": "Depression",
      "code": {
        "@type": "MedicalCode",
        "code": "F32.9",
        "codingSystem": "ICD-10"
      },
      "possibleTreatment": [
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },
        { "@type": "MedicalTherapy", "name": "Interpersonal Psychotherapy (IPT)" },
        { "@type": "MedicalTherapy", "name": "Group Therapy" }
      ],
      "signOrSymptom": [
        { "@type": "MedicalSignOrSymptom", "name": "Persistent low mood" },
        { "@type": "MedicalSignOrSymptom", "name": "Loss of interest or pleasure" },
        { "@type": "MedicalSignOrSymptom", "name": "Fatigue or low energy" }
      ]
    }
  ]
}';

test_schema('Medical Condition + Medical Organization', $schema1, $shortcode_parser, $test_results, $total_tests, $passed_tests, $green, $red, $reset);

// Test Schema 2: FAQ Page
echo "{$yellow}Testing Schema Type 2: FAQ Page{$reset}\n";
echo "====================================\n";

$schema2 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mc"}},
    {
      "@type": "FAQPage",
      "@id": "https://amfmstg.wpengine.com/mental-health/depression/faq/#faq",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What are the most effective treatments for depression?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "The most effective treatments for depression include cognitive behavioral therapy (CBT), interpersonal psychotherapy (IPT), and antidepressant medications when appropriate."
          }
        },
        {
          "@type": "Question", 
          "name": "How long does depression treatment typically take?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Depression treatment duration varies, but most people see improvement within 6-12 weeks of consistent therapy or medication management."
          }
        }
      ]
    }
  ]
}';

test_schema('FAQ Page', $schema2, $shortcode_parser, $test_results, $total_tests, $passed_tests, $green, $red, $reset);

// Test Schema 3: Medical Web Page with Breadcrumbs
echo "{$yellow}Testing Schema Type 3: Medical Web Page + Breadcrumbs{$reset}\n";
echo "====================================================\n";

$schema3 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mp" region="southwest"}},
    {
      "@type": "MedicalWebPage",
      "@id": "https://amfmstg.wpengine.com/mental-health/depression/treatment-approaches/#webpage",
      "url": "https://amfmstg.wpengine.com/mental-health/depression/treatment-approaches/",
      "name": "The Best Treatment Options for Depression",
      "datePublished": "2025-01-04",
      "author": { "@type": "Person", "name": "Emma Loker" },
      "reviewedBy": { "@type": "Person", "name": "Ashley Pena" },
      "breadcrumb": {
        "@type": "BreadcrumbList",
        "itemListElement": [
          { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://amfmstg.wpengine.com/" },
          { "@type": "ListItem", "position": 2, "name": "Mental Health", "item": "https://amfmstg.wpengine.com/mental-health/" },
          { "@type": "ListItem", "position": 3, "name": "Depression", "item": "https://amfmstg.wpengine.com/mental-health/depression/" }
        ]
      }
    }
  ]
}';

test_schema('Medical Web Page + Breadcrumbs', $schema3, $shortcode_parser, $test_results, $total_tests, $passed_tests, $green, $red, $reset);

// Test Schema 4: Service and Offer
echo "{$yellow}Testing Schema Type 4: Service + Offer{$reset}\n";
echo "======================================\n";

$schema4 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="amfm" state="CA"}},
    {
      "@type": "Service",
      "@id": "https://amfmstg.wpengine.com/services/depression-treatment/#service",
      "name": "Depression Treatment",
      "description": "Comprehensive depression treatment including therapy and medication management",
      "provider": { "@id": "https://amfmstg.wpengine.com/#org" },
      "offers": {
        "@type": "Offer",
        "name": "Depression Treatment Program",
        "description": "Individual and group therapy for depression",
        "availability": "https://schema.org/InStock"
      }
    }
  ]
}';

test_schema('Service + Offer', $schema4, $shortcode_parser, $test_results, $total_tests, $passed_tests, $green, $red, $reset);

// Test Schema 5: Medical Therapy with Drug
echo "{$yellow}Testing Schema Type 5: Medical Therapy + Drug{$reset}\n";
echo "==============================================\n";

$schema5 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mc" region="northwest"}},
    {
      "@type": "MedicalTherapy",
      "@id": "https://amfmstg.wpengine.com/treatments/medication-management/#therapy",
      "name": "Medication Management for Depression",
      "indication": {
        "@type": "MedicalCondition",
        "name": "Depression"
      },
      "drug": {
        "@type": "Drug",
        "name": "Antidepressants (SSRIs, SNRIs, etc.)",
        "description": "Selective serotonin reuptake inhibitors and serotonin-norepinephrine reuptake inhibitors"
      }
    }
  ]
}';

test_schema('Medical Therapy + Drug', $schema5, $shortcode_parser, $test_results, $total_tests, $passed_tests, $green, $red, $reset);

// Test Schema 6: Complex Medical Condition
echo "{$yellow}Testing Schema Type 6: Complex Medical Condition{$reset}\n";
echo "===============================================\n";

$schema6 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mp"}},
    {
      "@type": "MedicalCondition",
      "@id": "https://amfmstg.wpengine.com/mental-health/anger-management/#condition",
      "name": "Intermittent Explosive Disorder",
      "alternateName": "IED",
      "code": {
        "@type": "MedicalCode",
        "code": "F63.81",
        "codingSystem": "ICD-10"
      },
      "signOrSymptom": [
        { "@type": "MedicalSignOrSymptom", "name": "Recurrent aggressive outbursts" },
        { "@type": "MedicalSignOrSymptom", "name": "Irritability" },
        { "@type": "MedicalSignOrSymptom", "name": "Impulsivity" }
      ],
      "riskFactor": [
        { "@type": "MedicalRiskFactor", "name": "History of trauma" },
        { "@type": "MedicalRiskFactor", "name": "Early life adversity" }
      ],
      "possibleTreatment": [
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },
        { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" }
      ]
    }
  ]
}';

test_schema('Complex Medical Condition', $schema6, $shortcode_parser, $test_results, $total_tests, $passed_tests, $green, $red, $reset);

// Test Schema 7: Multiple Organization Filter
echo "{$yellow}Testing Schema Type 7: Multiple Organization Shortcode{$reset}\n";
echo "====================================================\n";

$schema7 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="amfm"}},
    {{location brand="mc"}},
    {
      "@type": "MedicalWebPage",
      "@id": "https://amfmstg.wpengine.com/locations/#webpage",
      "name": "Our Treatment Locations",
      "description": "Find mental health treatment centers near you"
    }
  ]
}';

test_schema('Multiple Organization Shortcodes', $schema7, $shortcode_parser, $test_results, $total_tests, $passed_tests, $green, $red, $reset);

// Test Schema 8: Nested Graph Structure
echo "{$yellow}Testing Schema Type 8: Nested Graph Structure{$reset}\n";
echo "============================================\n";

$schema8 = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="amfm" region="southeast"}},
    {
      "@type": "MedicalWebPage",
      "@id": "https://amfmstg.wpengine.com/comprehensive-treatment/#webpage",
      "name": "Comprehensive Mental Health Treatment",
      "about": [
        {
          "@type": "MedicalCondition",
          "name": "Depression",
          "possibleTreatment": [
            { "@type": "MedicalTherapy", "name": "CBT" },
            { "@type": "MedicalTherapy", "name": "DBT" }
          ]
        },
        {
          "@type": "MedicalCondition", 
          "name": "Anxiety Disorders",
          "possibleTreatment": [
            { "@type": "MedicalTherapy", "name": "Exposure Therapy" }
          ]
        }
      ]
    },
    {
      "@type": "FAQPage",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What conditions do you treat?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "We treat depression, anxiety, and other mental health conditions."
          }
        }
      ]
    }
  ]
}';

test_schema('Nested Graph Structure', $schema8, $shortcode_parser, $test_results, $total_tests, $passed_tests, $green, $red, $reset);

// Display Summary
echo "{$blue}========================================{$reset}\n";
echo "{$blue}Test Summary{$reset}\n";
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
if ($passed_tests === $total_tests) {
    echo "{$green}✅ All core schema types tested successfully!{$reset}\n";
    echo "The schema system properly handles all required schema types from core_schema_types.md\n";
} else {
    echo "{$red}❌ Some schema types failed testing.{$reset}\n";
    echo "Please review the failures above and ensure proper schema parsing.\n";
}
echo "{$blue}========================================{$reset}\n\n";