<?php
/**
 * Test with the exact example from schema_plan.md
 * Run with: wp eval-file test-real-example.php
 */

// Load WordPress environment
if (!defined('ABSPATH')) {
    require_once(dirname(__FILE__) . '/../../../wp-load.php');
}

echo "\n========================================\n";
echo "Real-World Schema Example Test\n";
echo "========================================\n\n";

// Initialize handlers
$location_handler = new Amfm_Location_Handler('amfm-bylines', '3.1.0');
$shortcode_parser = new Amfm_Shortcode_Parser('amfm-bylines', '3.1.0', $location_handler);

// Use the exact example from schema_plan.md (lines 75-168)
$input_schema = '{
  "@context": "https://schema.org",
  "@graph": [
    {{location brand="mc"}},
    {
      "@type": "MedicalCondition",
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/#condition",
      "name": "Intermittent Explosive Disorder (IED)",
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",
      "description": "A behavioral disorder marked by recurrent, impulsive aggressive outbursts that are disproportionate to stressors.",
      "signOrSymptom": [
        { "@type": "MedicalSignOrSymptom", "name": "Recurrent aggressive outbursts" },
        { "@type": "MedicalSignOrSymptom", "name": "Irritability" },
        { "@type": "MedicalSignOrSymptom", "name": "Impulsivity" }
      ],
      "riskFactor": [
        { "@type": "MedicalRiskFactor", "name": "History of trauma" },
        { "@type": "MedicalRiskFactor", "name": "Early life adversity" },
        { "@type": "MedicalRiskFactor", "name": "Substance misuse" }
      ],
      "typicalTest": [
        { "@type": "MedicalTest", "name": "Clinical interview (DSM-5-TR criteria)" },
        { "@type": "MedicalTest", "name": "Psychological evaluation / standardized anger assessments" }
      ],
      "differentialDiagnosis": [
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Bipolar disorder" } },
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Borderline personality disorder" } },
        { "@type": "DDxElement", "diagnosis": { "@type": "MedicalCondition", "name": "Antisocial personality disorder" } }
      ],
      "possibleTreatment": [
        { "@type": "MedicalTherapy", "name": "Cognitive Behavioral Therapy (CBT)" },
        { "@type": "MedicalTherapy", "name": "Dialectical Behavior Therapy (DBT)" },
        {
          "@type": "MedicalTherapy",
          "name": "Medication management",
          "drug": { "@type": "Drug", "name": "Selective serotonin reuptake inhibitors (SSRIs)" }
        }
      ],
      "primaryPrevention": [
        { "@type": "MedicalTherapy", "name": "Anger management education" },
        { "@type": "MedicalTherapy", "name": "Stress management and coping skills training" }
      ],
      "secondaryPrevention": [
        { "@type": "MedicalTherapy", "name": "Relapse prevention plan and follow-up care" },
        { "@type": "MedicalTherapy", "name": "Booster CBT/skills sessions" }
      ],
      "epidemiology": "Onset typically in adolescence; more common in males."
    },
    {
      "@type": "Offer",
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/#offer",
      "url": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/",
      "availability": "https://schema.org/InStock",
      "itemOffered": {
        "@type": "Service",
        "serviceType": "Anger management & Intermittent Explosive Disorder treatment",
        "provider": {
          "@id": "https://missionconnectionhealthcare.com/#org"
        }
      }
    },
    {
      "@type": "FAQPage",
      "@id": "https://missionconnectionhealthcare.com/mental-health/anger-issues/intermittent-explosive-disorder/#faq",
      "mainEntity": [
        {
          "@type": "Question",
          "name": "What is Intermittent Explosive Disorder (IED)?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "IED is a mental health condition characterized by sudden, repeated episodes of impulsive, aggressive, or violent behavior."
          }
        },
        {
          "@type": "Question",
          "name": "How is IED treated?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "Treatment for IED may include therapy such as CBT or DBT, medication like SSRIs, and structured anger management programs."
          }
        },
        {
          "@type": "Question",
          "name": "Who is most at risk for IED?",
          "acceptedAnswer": {
            "@type": "Answer",
            "text": "IED often begins in adolescence and is more common in males, particularly those with a history of trauma or early adversity."
          }
        }
      ]
    }
  ]
}';

echo "Input Schema:\n";
echo "- Contains {{location brand=\"mc\"}} shortcode\n";
echo "- Contains MedicalCondition for IED\n";
echo "- Contains Offer and FAQPage\n\n";

// Parse the schema
$parsed_json = $shortcode_parser->parse_to_json($input_schema);
$parsed_array = json_decode($parsed_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON parsing error: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "✅ Successfully parsed schema\n\n";

// Validate the structure
echo "Validation Results:\n";
echo "==================\n";

// Check @context
if (isset($parsed_array['@context']) && $parsed_array['@context'] === 'https://schema.org') {
    echo "✅ @context is correctly set\n";
} else {
    echo "❌ @context is missing or incorrect\n";
}

// Check @graph
if (isset($parsed_array['@graph']) && is_array($parsed_array['@graph'])) {
    $graph_count = count($parsed_array['@graph']);
    echo "✅ @graph array contains {$graph_count} items\n";
    
    // Analyze each item in the graph
    $types_found = [];
    foreach ($parsed_array['@graph'] as $item) {
        if (isset($item['@type'])) {
            $types_found[] = $item['@type'];
        }
    }
    
    echo "\nSchema Types Found:\n";
    foreach (array_unique($types_found) as $type) {
        $count = count(array_filter($types_found, function($t) use ($type) { return $t === $type; }));
        echo "  - {$type}: {$count}\n";
    }
    
    // Verify expected types
    $expected_types = ['MedicalOrganization', 'MedicalCondition', 'Offer', 'FAQPage'];
    echo "\nExpected Types Verification:\n";
    foreach ($expected_types as $expected) {
        if (in_array($expected, $types_found)) {
            echo "  ✅ {$expected} found\n";
        } else {
            echo "  ❌ {$expected} missing\n";
        }
    }
    
    // Check MedicalOrganization details
    foreach ($parsed_array['@graph'] as $item) {
        if (isset($item['@type']) && $item['@type'] === 'MedicalOrganization') {
            echo "\nMedicalOrganization Details:\n";
            echo "  - Name: " . ($item['name'] ?? 'N/A') . "\n";
            echo "  - URL: " . ($item['url'] ?? 'N/A') . "\n";
            
            if (isset($item['department']) && is_array($item['department'])) {
                $clinic_count = count($item['department']);
                echo "  - Departments: {$clinic_count} MedicalClinic(s)\n";
                
                echo "\n  Clinic Locations:\n";
                foreach ($item['department'] as $clinic) {
                    if (isset($clinic['address'])) {
                        $city = $clinic['address']['addressLocality'] ?? 'N/A';
                        $state = $clinic['address']['addressRegion'] ?? 'N/A';
                        echo "    - {$city}, {$state}\n";
                    }
                }
            }
            break;
        }
    }
} else {
    echo "❌ @graph is missing or not an array\n";
}

// Validate against schema.org
echo "\n========================================\n";
echo "Schema.org Validation:\n";
echo "========================================\n";
echo "To validate this schema:\n";
echo "1. Copy the generated schema below\n";
echo "2. Visit https://validator.schema.org/\n";
echo "3. Paste and validate\n\n";

// Pretty print the final schema
echo "Generated Schema (first 2000 characters):\n";
echo "==========================================\n";
$pretty_json = json_encode($parsed_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo substr($pretty_json, 0, 2000) . "...\n\n";

echo "Full schema length: " . strlen($pretty_json) . " characters\n";
echo "\n✅ Test completed successfully!\n\n";