<?php
/**
 * Schema Data Testing Tool
 * Usage: wp eval-file test-schema.php --url="https://your-site.com/page"
 */

if (!defined('WP_CLI') && !defined('ABSPATH')) {
    die('This script should be run via WP-CLI or WordPress context');
}

// Get URL from command line argument or use current site
$test_url = isset($args[0]) ? $args[0] : home_url();
if (defined('WP_CLI') && WP_CLI::get_flag('url')) {
    $test_url = WP_CLI::get_flag('url');
}

function extract_schema_data($url) {
    $html = wp_remote_get($url);
    
    if (is_wp_error($html)) {
        return ['error' => $html->get_error_message()];
    }
    
    $body = wp_remote_retrieve_body($html);
    $schema_data = [];
    
    // Extract JSON-LD scripts
    if (preg_match_all('/<script type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $body, $matches)) {
        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);
            if ($decoded) {
                $schema_data['json-ld'][] = $decoded;
            }
        }
    }
    
    // Extract microdata
    if (preg_match_all('/itemtype="([^"]*)"/', $body, $matches)) {
        $schema_data['microdata'] = array_unique($matches[1]);
    }
    
    // Extract Open Graph
    if (preg_match_all('/<meta property="og:([^"]*)" content="([^"]*)"/', $body, $matches)) {
        for ($i = 0; $i < count($matches[1]); $i++) {
            $schema_data['opengraph'][$matches[1][$i]] = $matches[2][$i];
        }
    }
    
    // Extract Twitter Cards
    if (preg_match_all('/<meta name="twitter:([^"]*)" content="([^"]*)"/', $body, $matches)) {
        for ($i = 0; $i < count($matches[1]); $i++) {
            $schema_data['twitter'][$matches[1][$i]] = $matches[2][$i];
        }
    }
    
    return $schema_data;
}

function validate_schema($schema_data) {
    $validation_results = [];
    
    if (isset($schema_data['json-ld'])) {
        foreach ($schema_data['json-ld'] as $index => $schema) {
            $type = isset($schema['@type']) ? $schema['@type'] : 'Unknown';
            $validation_results['json-ld'][$index] = [
                'type' => $type,
                'valid' => isset($schema['@context']) && isset($schema['@type']),
                'required_props' => get_required_props($type, $schema)
            ];
        }
    }
    
    return $validation_results;
}

function get_required_props($type, $schema) {
    $required = [];
    
    switch (strtolower($type)) {
        case 'article':
            $props = ['headline', 'author', 'datePublished'];
            break;
        case 'organization':
            $props = ['name', 'url'];
            break;
        case 'person':
            $props = ['name'];
            break;
        case 'webpage':
            $props = ['name', 'url'];
            break;
        default:
            $props = [];
    }
    
    foreach ($props as $prop) {
        $required[$prop] = isset($schema[$prop]);
    }
    
    return $required;
}

// Main execution
echo "🔍 Testing Schema Data for: " . $test_url . "\n";
echo str_repeat("-", 50) . "\n";

$schema_data = extract_schema_data($test_url);

if (isset($schema_data['error'])) {
    echo "❌ Error: " . $schema_data['error'] . "\n";
    exit(1);
}

// Display results
if (isset($schema_data['json-ld'])) {
    echo "📊 JSON-LD Schema Found:\n";
    foreach ($schema_data['json-ld'] as $index => $schema) {
        echo "  " . ($index + 1) . ". Type: " . ($schema['@type'] ?? 'Unknown') . "\n";
        echo "     Context: " . ($schema['@context'] ?? 'Missing') . "\n";
    }
    echo "\n";
}

if (isset($schema_data['microdata'])) {
    echo "🏷️  Microdata Found:\n";
    foreach ($schema_data['microdata'] as $type) {
        echo "  - " . $type . "\n";
    }
    echo "\n";
}

if (isset($schema_data['opengraph'])) {
    echo "📘 Open Graph Tags:\n";
    foreach ($schema_data['opengraph'] as $prop => $content) {
        echo "  og:" . $prop . " = " . $content . "\n";
    }
    echo "\n";
}

if (isset($schema_data['twitter'])) {
    echo "🐦 Twitter Card Tags:\n";
    foreach ($schema_data['twitter'] as $prop => $content) {
        echo "  twitter:" . $prop . " = " . $content . "\n";
    }
    echo "\n";
}

// Validation
$validation = validate_schema($schema_data);
if (!empty($validation)) {
    echo "✅ Validation Results:\n";
    foreach ($validation as $type => $schemas) {
        echo "  " . strtoupper($type) . ":\n";
        foreach ($schemas as $index => $result) {
            echo "    Schema " . ($index + 1) . " (" . $result['type'] . "): " . 
                 ($result['valid'] ? '✅ Valid' : '❌ Invalid') . "\n";
            
            if (!empty($result['required_props'])) {
                echo "      Required Properties:\n";
                foreach ($result['required_props'] as $prop => $present) {
                    echo "        " . $prop . ": " . ($present ? '✅' : '❌') . "\n";
                }
            }
        }
    }
}

echo "\n🎉 Schema analysis complete!\n";