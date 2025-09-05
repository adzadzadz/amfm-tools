# Schema Module Test Procedure

## 🧪 Complete Testing Guide for AMFM Bylines Schema Functionality

This document provides step-by-step testing procedures using WP-CLI and terminal tools to validate the custom schema implementation.

## 🔧 Prerequisites

### Required Tools
- WP-CLI installed and working
- Terminal access to the WordPress site
- `curl` or `wget` for HTTP requests
- `jq` for JSON parsing (optional but recommended)

### Installation Check
```bash
# Verify WP-CLI is working
wp --info

# Check if plugin is active
wp plugin list | grep amfm-bylines

# If not active, activate the plugin
wp plugin activate amfm-bylines
```

## 📋 Test Procedures

### Test 1: Plugin Activation and Class Loading
```bash
# Check if schema classes are loaded
wp eval 'var_dump(class_exists("Amfm_Schema_Manager"));'
wp eval 'var_dump(class_exists("Amfm_ACF_Schema_Fields"));'
wp eval 'var_dump(class_exists("Amfm_Shortcode_Parser"));'
wp eval 'var_dump(class_exists("Amfm_Location_Handler"));'
wp eval 'var_dump(class_exists("Amfm_Schema_Merger"));'
```
**Expected**: All should return `bool(true)`

### Test 2: Location Data Loading
```bash
# Test location handler initialization
wp eval '
$handler = new Amfm_Location_Handler("amfm-bylines", "2.10.0");
$locations = $handler->get_master_locations();
echo "Total locations: " . count($locations) . "\n";
foreach ($locations as $i => $loc) {
    echo "Location " . ($i+1) . ": " . $loc["Business name"] . " - " . $loc["City"] . ", " . $loc["State"] . "\n";
}
'
```
**Expected**: Shows 5 Mission Connection locations

### Test 3: Location Filtering
```bash
# Test brand filtering
wp eval '
$handler = new Amfm_Location_Handler("amfm-bylines", "2.10.0");
$filtered = $handler->filter_locations(array("brand" => "mc"));
echo "MC Brand locations: " . count($filtered) . "\n";
'

# Test state filtering
wp eval '
$handler = new Amfm_Location_Handler("amfm-bylines", "2.10.0");
$filtered = $handler->filter_locations(array("state" => "WA"));
echo "Washington state locations: " . count($filtered) . "\n";
'

# Test region filtering
wp eval '
$handler = new Amfm_Location_Handler("amfm-bylines", "2.10.0");
$filtered = $handler->filter_locations(array("region" => "northwest"));
echo "Northwest region locations: " . count($filtered) . "\n";
'
```
**Expected**: 5 MC locations, 2 WA locations, 2 northwest locations

### Test 4: Schema Generation
```bash
# Test location schema generation
wp eval '
$handler = new Amfm_Location_Handler("amfm-bylines", "2.10.0");
$schema = $handler->generate_location_schema(array("brand" => "mc"));
echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
'
```
**Expected**: JSON output with MedicalOrganization and 5 MedicalClinic entries

### Test 5: Shortcode Parsing
```bash
# Test basic location shortcode
wp eval '
$handler = new Amfm_Location_Handler("amfm-bylines", "2.10.0");
$parser = new Amfm_Shortcode_Parser("amfm-bylines", "2.10.0", $handler);
$json = "{\"test\": {{location brand=\"mc\"}}}";
$result = $parser->parse($json);
echo json_encode($result, JSON_PRETTY_PRINT);
'
```
**Expected**: JSON with location schema replacing the shortcode

### Test 6: ACF Field Registration
```bash
# Check if ACF fields are registered
wp eval '
if (function_exists("get_field_object")) {
    $field = get_field_object("amfm_custom_schema");
    if ($field) {
        echo "ACF field registered: " . $field["label"] . "\n";
        echo "Field type: " . $field["type"] . "\n";
    } else {
        echo "ACF field not found\n";
    }
} else {
    echo "ACF not available\n";
}
'
```
**Expected**: Shows field is registered with label "Custom Schema JSON"

### Test 7: Create Test Page with Schema
```bash
# Create a test page
TEST_PAGE_ID=$(wp post create --post_type=page --post_title="Schema Test Page" --post_status=publish --porcelain)
echo "Created test page ID: $TEST_PAGE_ID"

# Add custom schema to the page
wp eval "
\$schema_json = '{
  \"@context\": \"https://schema.org\",
  \"@graph\": [
    {{location brand=\"mc\"}},
    {
      \"@type\": \"MedicalCondition\",
      \"name\": \"Test Condition\",
      \"description\": \"Test description for schema validation\"
    }
  ]
}';
update_field('amfm_custom_schema', \$schema_json, $TEST_PAGE_ID);
echo \"Schema added to page $TEST_PAGE_ID\n\";
"
```

### Test 8: Verify Schema Output on Frontend
```bash
# Get the page URL
PAGE_URL=$(wp post list --post_type=page --post__in=$TEST_PAGE_ID --field=url --format=csv | tail -n 1)
echo "Test page URL: $PAGE_URL"

# Fetch page and check for schema in head
curl -s "$PAGE_URL" | grep -A 50 'application/ld+json' | head -n 100

# Alternative: Use wp eval to simulate the frontend output
wp eval "
\$post = get_post($TEST_PAGE_ID);
setup_postdata(\$post);
\$manager = new Amfm_Schema_Manager('amfm-bylines', '2.10.0');
ob_start();
\$manager->output_custom_schema();
\$output = ob_get_clean();
echo \$output;
wp_reset_postdata();
"
```
**Expected**: JSON-LD script tag with MedicalOrganization and MedicalCondition

### Test 9: Schema Validation
```bash
# Validate the generated schema structure
wp eval "
\$manager = new Amfm_Schema_Manager('amfm-bylines', '2.10.0');
\$custom_schema = get_field('amfm_custom_schema', $TEST_PAGE_ID);
\$parsed = \$manager->get_shortcode_parser()->parse(\$custom_schema);
\$merged = \$manager->get_schema_merger()->merge_with_byline_schema(\$parsed);
\$validation = \$manager->get_schema_merger()->validate_merged_schema(\$merged);
echo 'Schema valid: ' . (\$validation['valid'] ? 'YES' : 'NO') . \"\n\";
if (!empty(\$validation['errors'])) {
    echo 'Errors: ' . implode(', ', \$validation['errors']) . \"\n\";
}
"
```
**Expected**: "Schema valid: YES" with no errors

### Test 10: Performance and Statistics
```bash
# Get location statistics
wp eval '
$handler = new Amfm_Location_Handler("amfm-bylines", "2.10.0");
$stats = $handler->get_location_statistics();
echo "Location Statistics:\n";
echo "Total: " . $stats["total_locations"] . "\n";
echo "Brands: " . implode(", ", array_keys($stats["brands"])) . "\n";
echo "States: " . implode(", ", array_keys($stats["states"])) . "\n";
echo "Regions: " . implode(", ", array_keys($stats["regions"])) . "\n";
'

# Test schema merge statistics
wp eval "
\$manager = new Amfm_Schema_Manager('amfm-bylines', '2.10.0');
\$custom_schema = get_field('amfm_custom_schema', $TEST_PAGE_ID);
\$parsed = \$manager->get_shortcode_parser()->parse(\$custom_schema);
\$merged = \$manager->get_schema_merger()->merge_with_byline_schema(\$parsed);
\$stats = \$manager->get_schema_merger()->get_merge_statistics(\$merged);
echo 'Total schema items: ' . \$stats['total_items'] . \"\n\";
echo 'Schema types: ' . implode(', ', array_keys(\$stats['types'])) . \"\n\";
"
```

### Test 11: Error Handling
```bash
# Test invalid JSON handling
wp eval '
$handler = new Amfm_Location_Handler("amfm-bylines", "2.10.0");
$parser = new Amfm_Shortcode_Parser("amfm-bylines", "2.10.0", $handler);
$bad_json = "{\"test\": {{location brand=\"nonexistent\"}";
$result = $parser->parse($bad_json);
var_dump($result);
'

# Test empty shortcode
wp eval '
$handler = new Amfm_Location_Handler("amfm-bylines", "2.10.0");
$parser = new Amfm_Shortcode_Parser("amfm-bylines", "2.10.0", $handler);
$empty_json = "{}";
$result = $parser->parse($empty_json);
var_dump($result);
'
```
**Expected**: Graceful error handling with appropriate responses

## 🎯 Integration Tests

### Test 12: End-to-End Workflow
```bash
# Create multiple test pages with different shortcodes
for i in {1..3}; do
    PAGE_ID=$(wp post create --post_type=page --post_title="Schema Test Page $i" --post_status=publish --porcelain)
    
    case $i in
        1)
            SCHEMA='{"@context": "https://schema.org", "@graph": [{{location brand="mc"}}, {"@type": "MedicalCondition", "name": "Depression"}]}'
            ;;
        2)
            SCHEMA='{"@context": "https://schema.org", "@graph": [{{location state="WA"}}, {"@type": "FAQPage", "mainEntity": []}]}'
            ;;
        3)
            SCHEMA='{"@context": "https://schema.org", "@graph": [{{location region="southeast"}}, {{breadcrumbs}}]}'
            ;;
    esac
    
    wp eval "update_field('amfm_custom_schema', '$SCHEMA', $PAGE_ID);"
    echo "Created test page $i with ID: $PAGE_ID"
done

# Test all pages have proper schema
wp post list --post_type=page --meta_key=amfm_custom_schema --format=table
```

### Test 13: Cleanup
```bash
# Remove test pages
wp post list --post_type=page --post_title__like="Schema Test Page" --field=ID | xargs wp post delete --force

echo "Cleanup completed"
```

## 📊 Expected Results Summary

| Test | Expected Result |
|------|----------------|
| Class Loading | All 5 classes return `true` |
| Location Data | 5 locations loaded |
| Brand Filter | 5 MC locations |
| State Filter | 2 WA locations |
| Region Filter | 2 northwest locations |
| Schema Generation | Valid MedicalOrganization JSON |
| Shortcode Parsing | Shortcodes replaced with data |
| ACF Registration | Field shows as registered |
| Frontend Output | JSON-LD in page head |
| Validation | Schema marked as valid |
| Statistics | Proper counts and breakdowns |
| Error Handling | Graceful failure responses |

## 🔍 Troubleshooting

### Common Issues
- **ACF not found**: Ensure ACF plugin is active
- **Class not found**: Check plugin activation
- **Empty schema**: Verify ACF field has data
- **Parse errors**: Check JSON syntax in custom schema

### Debug Commands
```bash
# Enable WP debug mode
wp config set WP_DEBUG true

# Check error logs
wp eval 'error_log("Schema debug test");'

# View recent errors
tail -f /path/to/wordpress/debug.log
```

## ✅ Success Criteria

The implementation passes testing if:
- [ ] All classes load without errors
- [ ] Location data loads correctly (5 locations)
- [ ] Filtering works for brand, state, and region
- [ ] Shortcodes parse and replace properly
- [ ] ACF fields register and save data
- [ ] Schema outputs to frontend in JSON-LD format
- [ ] Validation passes without errors
- [ ] Error handling works gracefully
- [ ] Performance is acceptable (<1 second for schema generation)

Run all tests in sequence to ensure complete functionality validation.