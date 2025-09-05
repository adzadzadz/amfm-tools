# AMFM Maps Elementor Plugin

## Overview
AMFM Maps is a comprehensive Elementor plugin designed to display mental health treatment facility locations with sophisticated mapping and filtering capabilities. This plugin enhances the Elementor page builder by providing advanced map widgets that can be easily integrated into your WordPress designs.

**Current Version**: 2.2.0  
**WordPress Version**: 5.0+  
**Elementor Version**: 3.0+  
**PHP Version**: 7.4+

## Key Features

### 🗺️ Advanced Mapping System
- **Google Maps Integration**: Uses Google Maps JavaScript API with Places library
- **PlaceID Precision Loading**: Only displays facilities with valid PlaceIDs for accuracy
- **Interactive Map Controls**: Zoom, fullscreen, and custom marker interactions
- **Responsive Map Display**: Adapts to all screen sizes

### 🎛️ Dual Layout System
- **Button Layout**: Modern button-based filters that display side-by-side with the map
- **Sidebar Layout**: Traditional sidebar with checkboxes (legacy layout)
- **Responsive Design**: Automatically adapts to mobile screens
- **Custom Styling**: Full Elementor styling controls integration

### 🔍 Comprehensive Filtering
- **Multi-Type Filtering**: Apply multiple filter types simultaneously
- **Real-Time Results**: Instant map updates as filters are applied
- **Smart Logic**: Within-type OR logic, cross-type AND logic
- **Results Counter**: Shows active location count

### 🎨 Elementor Integration
- **Native Styling Controls**: Filter button colors, padding, margins, border radius
- **Live Preview**: Real-time styling in Elementor editor
- **Content Controls**: Toggle filter categories on/off
- **Layout Options**: Choose between button and sidebar layouts

## Available Widgets

### 🗺️ AMFM Map V2
- **Purpose**: Displays the interactive map with location markers
- **Features**: Google Maps integration, marker clustering, location details
- **Controls**: Map height, title, data source configuration
- **Filtering**: Can work standalone or with external filter widget

### 🎛️ AMFM Map V2 Filter
- **Purpose**: Standalone filter controls for map widgets
- **Features**: Independent filter widget that can control any map on the page
- **Controls**: Filter layout (buttons/sidebar), category toggles, styling options
- **Flexibility**: Can target specific map widgets or control all maps globally

### 🔗 Widget Communication
- **Cross-Widget Filtering**: Filter widgets can control map widgets anywhere on the page
- **Flexible Layout**: Place filters and maps in separate sections or columns
- **Multiple Configurations**: Use multiple filter widgets for different map displays
- **Real-Time Updates**: Instant communication between filter and map widgets
- **100% Width Design**: Both widgets are full-width for complete Elementor layout control

## Filter Categories

### Location Filter Widget
The location filter allows users to select facilities from specific geographic locations. This filter is highly customizable and integrates seamlessly with the map display.

#### Available Locations
- **California (CA)**: Treatment facilities throughout California
- **Virginia (VA)**: East Coast treatment options
- **Washington (WA)**: Pacific Northwest facilities
- **Minnesota (MN)**: Midwest treatment centers
- **Oregon (OR)**: Additional Pacific Northwest options

#### Location Filter Configuration
**Widget Controls:**
- **Show Location Filter**: Toggle to enable/disable location filtering
- **Location Icon**: Customizable icon displayed next to location filter buttons
- **Filter Layout**: Choose between button-style or sidebar checkbox layout
- **Sorting Options**: Alphabetical or custom ordering of location options

**Data Integration:**
- Automatically extracts location data from the `State` field in facility records
- Supports both abbreviated (CA, VA) and full state names
- Dynamically generates filter options based on available facility locations
- Real-time filtering updates map markers instantly

**User Experience:**
- Click any location button to filter facilities to that specific state
- Multiple location selections use OR logic (shows facilities from any selected state)
- Clear all filters with dedicated reset button
- Visual feedback shows active filter states
- Results counter updates to reflect filtered location count

#### Location Data Structure
Location information is stored in the facility data using the `State` field:
```json
{
  "Business name": "AMFM Treatment Center",
  "State": "CA",
  "PlaceID": "ChIJxxxxxxxxxxxxxx"
}
```

### Treatment Criteria
- **Gender**: Male, Female
- **Conditions**: Anxiety, Depression, PTSD, Trauma, Mood Disorders, Personality Disorders
- **Programs**: CBT, DBT, EMDR, Equine Therapy, Music Therapy
- **Accommodations**: Pool, Gym & Wellness Center, In-House Chefs, Music Library

## Installation & Setup

### 1. Plugin Installation
```bash
# Option 1: WordPress Admin
1. Download the AMFM Maps plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Select the ZIP file and click "Install Now"
4. Activate the plugin

# Option 2: Manual Installation
1. Extract the plugin ZIP file
2. Upload the `amfm-maps` folder to `/wp-content/plugins/`
3. Activate through the WordPress Plugins menu
```

### 2. Google Maps API Configuration
1. Get a Google Maps API key from [Google Cloud Console](https://console.cloud.google.com/)
2. Enable the following APIs:
   - Maps JavaScript API
   - Places API
3. Configure the API key in the plugin settings

### 3. Data Setup
- The plugin uses WordPress option `amfm_maps_json_data` for facility data
- Data structure includes PlaceID, location details, and filter metadata
- Admin interface available for data management

## Usage in Elementor

### Method 1: Combined Widget (Legacy)
1. Open any page/post in Elementor editor
2. Search for "AMFM Map V2" in the widget panel
3. Drag and drop the widget into your layout
4. Configure map and filter settings in one widget

### Method 2: Separate Widgets (Recommended)
1. **Add the Filter Widget:**
   - Search for "AMFM Map V2 Filter" 
   - Drag to desired location (e.g., left column)
   - Configure filter options and styling

2. **Add the Map Widget:**
   - Search for "AMFM Map V2"
   - Drag to desired location (e.g., right column)
   - Link to filter widget using Target Map Widget ID (optional)

3. **Configure Communication:**
   - **Option A**: Leave Target Map Widget ID empty in filter - controls all maps on page
   - **Option B**: Enter specific map widget ID for targeted control

### Widget Configuration

#### AMFM Map V2 (Map Only)
**Content Tab:**
- **Map Title**: Optional title displayed above the map
- **Data Source**: Use stored data or custom JSON
- **Custom JSON Data**: Manual JSON input for testing

**Map Settings Tab:**
- **Map Height**: Adjust map container height (300px - 1200px)

**Style Tab:**
- **Map Border Radius**: Rounded corners
- **Box Shadow**: Drop shadow effects

#### AMFM Map V2 Filter (Filter Only)
**Content Tab:**
- **Filter Title**: Optional title for filter section
- **Filter Layout**: Choose between "buttons" or "sidebar"
- **Data Source**: Use stored data or custom JSON
- **Target Map Widget ID**: Specific map to control (optional)

**Filter Categories Tab:**
- **Show Location Filter**: Toggle state/location filtering
- **Show Gender Filter**: Toggle gender-based filtering
- **Show Conditions Filter**: Toggle medical conditions
- **Show Programs Filter**: Toggle treatment programs
- **Show Accommodations Filter**: Toggle facility amenities

**Style Tab:**
- **Filter Button Colors**: Background, text, hover, active states
- **Button Spacing**: Padding and margins
- **Border Styling**: Border radius and effects
- **Container Styling**: Background and padding options

## Layout Flexibility

### Full-Width Design Benefits
Both the **AMFM Map V2** and **AMFM Map V2 Filter** widgets are designed with 100% width, giving you complete control over layouts using Elementor's built-in column system.

### Layout Examples

#### Side-by-Side Layout
```
[Filter Widget - 30% Column] [Map Widget - 70% Column]
```
**Setup:**
1. Create a 2-column section (30/70 split)
2. Add Filter Widget to left column
3. Add Map Widget to right column

#### Stacked Layout
```
[Filter Widget - 100% Column]
[Map Widget - 100% Column]
```
**Setup:**
1. Create single-column sections
2. Add Filter Widget to top section
3. Add Map Widget to bottom section

#### Multi-Map Layout
```
[Filter Widget - 100% Column]
[Map Widget 1 - 50% Column] [Map Widget 2 - 50% Column]
```
**Setup:**
1. Add one Filter Widget (controls both maps)
2. Create 2-column section below
3. Add Map Widgets to each column

#### Complex Layouts
```
[Header Content]
[Filter Widget - 25%] [Map Widget - 50%] [Sidebar Content - 25%]
[Footer Content]
```
**Benefits:**
- Complete design freedom
- Mobile-responsive automatically
- Works with any Elementor theme
- Easy to customize spacing and alignment

## Technical Implementation

### File Structure
```
amfm-maps/
├── amfm-maps.php                         # Main plugin file
├── includes/
│   └── elementor/
│       ├── class-map-widget.php          # Map widget (map only)
│       └── class-map-filter-widget.php   # Filter widget (filter only)
├── admin/
│   └── class-amfm-maps-admin.php         # Admin interface
├── assets/
│   ├── css/
│   │   └── style.css                     # Widget styles
│   └── js/
│       └── script.js                     # Map & filter functionality
├── demo-widget-layout.html               # Demo page
├── filter-test.html                      # Filter testing
└── README.md                             # This file
```

### Widget Classes

#### MapWidget (`class-map-widget.php`)
- **Class**: `AMFM_Maps\Elementor\MapWidget`
- **Extends**: `\Elementor\Widget_Base`
- **Purpose**: Map display only
- **Features**: Google Maps integration, marker display, external filter communication

#### MapFilterWidget (`class-map-filter-widget.php`)
- **Class**: `AMFM_Maps\Elementor\MapFilterWidget`
- **Extends**: `\Elementor\Widget_Base`
- **Purpose**: Filter controls only
- **Features**: Filter UI, cross-widget communication, styling controls

### JavaScript API

#### Map Widget Initialization
```javascript
// Initialize V2 map widget (simplified)
amfmMapV2.init({
    unique_id: "amfm_map_v2_123456",
    json_data: facilityData,
    api_key: "google_maps_api_key"
});
```

#### Filter Widget Initialization
```javascript
// Initialize V2 filter widget
amfmMapV2Filter.init({
    unique_id: "amfm_filter_v2_789123",
    target_map_id: "amfm_map_v2_123456", // Optional: target specific map
    json_data: facilityData
});
```

#### Cross-Widget Communication
```javascript
// Custom event system for filter updates
var event = new CustomEvent('amfmFilterUpdate', {
    detail: {
        filters: activeFilters,
        sourceFilterId: filterId
    }
});

// Map widgets listen for this event
mapContainer.addEventListener('amfmFilterUpdate', function(event) {
    var externalFilters = event.detail.filters;
    applyExternalFilters(externalFilters);
});
```

#### Filter Management
```javascript
// Apply filters programmatically (internal filters)
amfmMapV2.applyFilters();

// Apply external filters (from filter widget)
amfmMapV2.applyExternalFilters(filterObject);

// Clear all filters
amfmMapV2.clearAllFilters();

// Update results counter
amfmMapV2.updateResultsCounter(count);
```

## Layout Options

### Button Layout (Recommended)
**Desktop/Tablet Layout:**
- Filters: 35-40% width (left side)
- Map: 60-65% width (right side)
- Side-by-side arrangement

**Mobile Layout:**
- Map displayed on top
- Filters stack below in collapsed format
- Vertical arrangement

#### HTML Structure
```html
<div class="amfm-map-v2-container amfm-layout-buttons">
    <div class="amfm-map-title">
        <h3>Find AMFM Locations</h3>
    </div>
    <div class="amfm-button-layout-content">
        <div class="amfm-filter-buttons-container">
            <!-- Filter buttons grouped by type -->
        </div>
        <div class="amfm-map-panel">
            <!-- Google Map and results counter -->
        </div>
    </div>
</div>
```

### Sidebar Layout (Legacy)
- Traditional left sidebar with checkboxes
- Map takes remaining width
- Better for desktop-only implementations

#### HTML Structure
```html
<div class="amfm-map-v2-container amfm-layout-sidebar">
    <div class="amfm-map-v2-content">
        <div class="amfm-filter-panel">
            <!-- Checkbox filters in sidebar -->
        </div>
        <div class="amfm-map-panel">
            <!-- Google Map -->
        </div>
    </div>
</div>
```

## Styling Classes

### Container Classes
```css
.amfm-map-v2-container          /* Main widget container */
.amfm-layout-buttons            /* Button layout modifier */
.amfm-layout-sidebar            /* Sidebar layout modifier */
.amfm-button-layout-content     /* Two-column wrapper for button layout */
```

### Filter Classes
```css
.amfm-filter-button             /* Individual filter buttons */
.amfm-filter-button.active      /* Active filter state */
.amfm-filter-group-buttons      /* Button group containers */
.amfm-filter-group-title        /* Category labels */
.amfm-clear-filters             /* Clear all button */
```

### Map Classes
```css
.amfm-map-panel                 /* Map container */
.amfm-map-wrapper               /* Google Map wrapper */
.amfm-results-counter           /* Results display */
```

## Data Structure

### JSON Data Format
The widget expects JSON data with the following structure:

```json
[
  {
    "Business name": "AMFM Mental Health Treatment",
    "Name": "Facility Display Name",
    "Website Key": "Location Key",
    "PlaceID": "ChIJxxxxxxxxxxxxxx",
    "State": "CA",
    "Details: Gender": "Male",
    "Conditions: Anxiety": 1,
    "Conditions: Depression": 0,
    "Conditions: PTSD": 1,
    "Conditions: Trauma": 0,
    "Programs: CBT": 1,
    "Programs: DBT": 0,
    "Programs: EMDR": 1,
    "Accommodations: Pool": 1,
    "Accommodations: Gym": 0,
    "Accommodations: In-House Chefs": 1
  }
]
```

### Field Descriptions
- **PlaceID**: Google Places ID for accurate location data (required)
- **State**: Two-letter state abbreviation (CA, VA, WA, etc.)
- **Details: Gender**: Target gender for facility ("Male", "Female")
- **Conditions: [Name]**: Binary fields (1/0) for supported conditions
- **Programs: [Name]**: Binary fields (1/0) for available programs
- **Accommodations: [Name]**: Binary fields (1/0) for facility amenities

### Data Sources
- **Stored Data**: WordPress option `amfm_maps_json_data`
- **Custom Data**: Direct JSON input via widget settings
- **Admin Interface**: Managed through plugin admin panel

### Filter Logic
The widget automatically extracts filter options by:
1. Scanning all records for unique values
2. Grouping by filter categories (Conditions:, Programs:, etc.)
3. Sorting alphabetically
4. Creating interactive filter elements

### WordPress Options
- `amfm_maps_json_data`: Main facility dataset
- `amfm_google_maps_api_key`: Google Maps API key

## Configuration

### Required APIs
- **Google Maps JavaScript API**: For map display
- **Google Places API**: For PlaceID validation and location data

### Browser Support
- Modern browsers with ES6 support
- Google Maps JavaScript API compatible browsers
- Responsive design for mobile devices
- Tested on Chrome, Firefox, Safari, Edge

### Performance Considerations
- **PlaceID-only loading**: Only loads locations with valid PlaceIDs for accuracy
- **Lazy loading**: Map markers loaded progressively for better performance
- **Debounced filtering**: Filter updates optimized to prevent excessive API calls
- **Efficient DOM manipulation**: Minimized re-rendering during filter operations
- **Mobile optimization**: Touch-friendly interactions and optimized scrolling
- **Marker clustering**: Automatic grouping for large datasets (planned enhancement)

### Browser Compatibility Details
- **Chrome**: 60+ (recommended for best performance)
- **Firefox**: 55+ (full feature support)
- **Safari**: 12+ (iOS Safari 12+ for mobile)
- **Edge**: 79+ (Chromium-based)
- **Mobile**: Optimized for touch interfaces
- **Requirements**: ES6 support, Google Maps JavaScript API compatibility

## Demo & Testing

### Demo Files
- `demo-widget-layout.html`: Standalone button layout demo
- `filter-test.html`: Filter system testing with debug output
- Both include sample data for development

### Testing the Widget
```bash
# Open demo in browser
file:///path/to/amfm-maps/demo-widget-layout.html

# Test filtering system
file:///path/to/amfm-maps/filter-test.html
```

## Changelog

### Version 2.1.1 (Current) - July 2025
**Filtering System Improvements:**
- Fixed multi-type filter logic (different types AND'ed, same type OR'ed)
- Enhanced debugging and console logging
- Improved results counter updates
- Better event handling for filter interactions
- PlaceID precision validation maintained

### Version 2.1.0 - July 2025
**Major Layout Improvements:**
- Fixed button layout structure for proper side-by-side display
- Responsive width distribution (35-40% filters, 60-65% map)
- Enhanced two-column flexbox implementation
- Improved mobile responsive behavior
- Added tablet-specific breakpoints

### Version 2.0.9 - July 2025
**Initial V2 Implementation:**
- AMFM Map V2 Widget creation
- PlaceID-only precision loading
- Dual layout system (buttons vs sidebar)
- Elementor styling controls integration
- Comprehensive filter categories

## Troubleshooting

### Common Issues

#### Map Not Loading
1. Check Google Maps API key configuration
2. Verify Places API is enabled
3. Check browser console for JavaScript errors
4. Ensure network connectivity

#### Filters Not Working
1. Open browser developer tools
2. Check console for filter debug messages
3. Verify JSON data structure includes PlaceID
4. Test with demo files to isolate issues

#### Styling Issues
1. Check for CSS conflicts with theme
2. Verify Elementor is up to date
3. Clear cache (both WordPress and browser)
4. Test with default WordPress theme

### Debug Mode
Enable debug logging by adding to wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support & Development

### Getting Help
- Check the troubleshooting section above
- Review demo files for implementation examples
- Test with filter-test.html for debugging

### Contributing
1. Follow WordPress coding standards
2. Test thoroughly with multiple datasets
3. Maintain responsive design principles
4. Document any new features

### Future Roadmap
- **Advanced Clustering**: Map clustering for large datasets
- **Enhanced Search**: Text-based location search within filters
- **Custom Markers**: Facility-specific marker designs and icons
- **Booking Integration**: Connect with reservation systems
- **Analytics**: Usage tracking and filter analytics
- **Performance**: Further optimization for large datasets
- **Export/Print**: Filtered results export functionality
- **Location Details**: Enhanced modal windows with detailed information
- **Driving Directions**: Integration with Google Maps directions
- **Favorites/Bookmarks**: User preference system for locations
- **Advanced Filtering**: Multiple selections within same category
- **Mobile App**: Companion mobile application

---

**Plugin Author**: Adrian T. Saycon  
**Author URI**: https://adzbyte.com/  
**License**: GPL-2.0+  
**Text Domain**: amfm-maps