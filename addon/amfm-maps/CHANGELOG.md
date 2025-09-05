# AMFM Maps Plugin Changelog

## Version 2.1.0 - July 2025

### Major Layout Improvements
- **Fixed Button Layout Structure**: Button filters now properly display side-by-side with the map instead of stacking vertically
- **Responsive Width Distribution**: 
  - Desktop (1025px+): Filters 35% / Map 65%
  - Tablet (769-1024px): Filters 40% / Map 60%
  - Mobile (<768px): Stacked layout with map on top
- **Enhanced Two-Column Layout**: Proper flexbox implementation for better visual balance

### Technical Changes
- Modified HTML structure in `class-map-v2-widget.php` to use `amfm-button-layout-content` wrapper
- Updated CSS in `style.css` with proper responsive breakpoints
- Added tablet-specific styles for optimal viewing on medium screens
- Improved mobile responsive behavior with proper element ordering

### Performance & UX
- Better space utilization on desktop and tablet screens
- Maintained mobile-first responsive design principles
- Improved filter accessibility and visual hierarchy
- Enhanced visual separation between filter and map sections

### Files Modified
- `amfm-maps.php` - Updated version to 2.1.0
- `includes/elementor/class-map-v2-widget.php` - Fixed button layout HTML structure
- `assets/css/style.css` - Added responsive layout styles
- `demo-widget-layout.html` - Updated demo to reflect new layout

### Previous Versions

## Version 2.0.9 - July 2025
- Initial implementation of AMFM Map V2 Widget
- PlaceID-only precision loading
- Dual layout system (buttons vs sidebar)
- Elementor styling controls
- Filter categories: Location, Gender, Conditions, Programs, Accommodations
