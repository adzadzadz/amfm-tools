jQuery(document).ready(function ($) {
    // Ensure the drawer container exists - try multiple parent selectors
    if (!$('#amfm-drawer').length) {
        var parentElement = $('.amfm-map-wrapper').length ? $('.amfm-map-wrapper') : $('body');
        parentElement.append(`
            <div id="amfm-drawer-overlay" class="amfm-drawer-overlay"></div>
            <div id="amfm-drawer" class="amfm-drawer">
                <div id="amfm-drawer-content" class="amfm-drawer-content"></div>
            </div>
        `);

        // Close drawer when clicking outside the drawer
        jQuery('#amfm-drawer-overlay').on('click', function () {
            closeDrawer();
        });

    }

    function closeDrawer() {
        jQuery('#amfm-drawer').removeClass('open');
        jQuery('#amfm-drawer-overlay').removeClass('visible');
        jQuery('body').off('click.drawer'); // Use namespaced event to avoid conflicts
    }

    function openDrawer(content) {
        // First, ensure the drawer exists
        if (!jQuery('#amfm-drawer').length) {
            var parentElement = jQuery('.amfm-map-wrapper').length ? jQuery('.amfm-map-wrapper') : jQuery('body');
            parentElement.append(`
                <div id="amfm-drawer-overlay" class="amfm-drawer-overlay"></div>
                <div id="amfm-drawer" class="amfm-drawer">
                    <div id="amfm-drawer-content" class="amfm-drawer-content"></div>
                </div>
            `);
    
            jQuery('#amfm-drawer-overlay').on('click', function () {
                closeDrawer();
            });
        }
        
        // Set content and show drawer
        jQuery('#amfm-drawer-content').html(content);
        
        // Use a small delay to ensure DOM is ready
        setTimeout(function() {
            jQuery('#amfm-drawer').addClass('open');
            jQuery('#amfm-drawer-overlay').addClass('visible');
        }, 10);

        // Remove previous body click handlers and add new one with namespace
        jQuery('body').off('click.drawer');
        setTimeout(function() {
            jQuery('body').on('click.drawer', function (event) {
                const drawer = jQuery('#amfm-drawer');
                const overlay = jQuery('#amfm-drawer-overlay');
                if (!drawer.is(event.target) && drawer.has(event.target).length === 0) {
                    closeDrawer();
                }
            });
        }, 100); // Delay to prevent immediate closing
    }

    // Expose openDrawer globally for debugging
    window.openDrawer = openDrawer;
    
    // Add debugging function
    window.testDrawer = function() {
        openDrawer('<div style="padding: 20px;"><h3>Test Drawer</h3><p>This is a test to see if the drawer is working.</p></div>');
    };
});

var amfm = {};
window.amfm = amfm;

// AMFM Map Widget JavaScript
// Check if global object already exists, if not create it with registry structure
if (!window.amfmMap) {
    window.amfmMap = {
        maps: {},        // Store multiple map instances by ID
        markers: {},     // Store markers by map ID  
        instances: {},   // Store configuration by map ID
        filteredData: {},// Store filtered data by map ID
        loadLocations: {},// Store loadLocations functions by map ID
        // Backward compatibility properties (will point to first initialized map)
        map: null,
        markers: [],
        loadLocations: null
    };
}
var amfmMap = window.amfmMap;

amfmMap.init = function(settings) {
    var unique_id = settings.unique_id;
    
    // Prevent double initialization
    if (amfmMap.instances && amfmMap.instances[unique_id]) {
        return;
    }
    
    var json_data = settings.json_data;
    var api_key = settings.api_key;
    
    var map;
    var markers = [];
    var filteredData = json_data.slice(); // Copy of data for filtering
    
    // Store this instance's data in the registry with null checks
    if (!amfmMap.markers) amfmMap.markers = {};
    if (!amfmMap.filteredData) amfmMap.filteredData = {};
    if (!amfmMap.instances) amfmMap.instances = {};
    if (!amfmMap.maps) amfmMap.maps = {};
    if (!amfmMap.loadLocations) amfmMap.loadLocations = {};
    
    amfmMap.markers[unique_id] = markers;
    amfmMap.filteredData[unique_id] = filteredData;
    amfmMap.instances[unique_id] = settings;
    
    // Initialize the map
    function initMap() {
        var mapElement = document.getElementById(unique_id + '_map');
        if (!mapElement) {
            return;
        }
        
        // Remove any loading indicator
        mapElement.style.background = '';
        
        var centerPoint = { lat: 39.8283, lng: -98.5795 }; // Center US
        map = new google.maps.Map(mapElement, {
            center: centerPoint,
            zoom: 4,
            mapTypeControl: false,
            zoomControl: true,
            streetViewControl: false,
            fullscreenControl: true
        });
        
        // Expose the map instance in the registry
        amfmMap.maps[unique_id] = map;
        
        // For backward compatibility, set the first map as the default
        if (!amfmMap.map) {
            amfmMap.map = map;
            // Note: Don't overwrite amfmMap.markers object, it contains multiple map instances
        }
        
        // Instead of relying on the unreliable 'idle' event, use a more robust approach
        function initializeMapData() {
            
            // Filter data to only include locations with PlaceID for precision
            filteredData = json_data.filter(function(location) {
                return location.PlaceID; // Only use locations with PlaceID
            });
            
            // Update results counter for initial load
            updateResultsCounter(filteredData.length);
            
            // Load locations initially
            loadLocations(filteredData);
            
            // Store loadLocations function in registry
            amfmMap.loadLocations[unique_id] = loadLocations;
            
            // For backward compatibility, set the first loadLocations as the default
            if (!amfmMap.loadLocations) {
                amfmMap.loadLocations = loadLocations;
            }
            
            // Set up filter event listeners
            setupFilterListeners();
        }
        
        // Try immediate initialization (most reliable for most environments)
        setTimeout(function() {
            if (!amfmMap.loadLocations || !amfmMap.loadLocations[unique_id]) {
                initializeMapData();
            }
        }, 100);
        
        // Also try idle event as fallback (for environments where it works)
        google.maps.event.addListenerOnce(map, 'idle', function() {
            if (!amfmMap.loadLocations || !amfmMap.loadLocations[unique_id]) {
                initializeMapData();
            }
        });
        
        // Final fallback timeout (in case both above methods fail)
        setTimeout(function() {
            if (!amfmMap.loadLocations || !amfmMap.loadLocations[unique_id]) {
                initializeMapData();
            }
        }, 2000);
    }
    
    // Check if Google Maps is loaded and initialize
    function checkGoogleMapsAndInit() {
        var mapElement = document.getElementById(unique_id + '_map');
        if (mapElement && typeof google !== 'undefined' && google.maps && google.maps.Map) {
            initMap();
        } else {
            // Add loading indicator
            if (mapElement) {
                mapElement.style.background = '#f0f0f0 url("data:image/svg+xml;charset=utf8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\' viewBox=\'0 0 50 50\'%3E%3Cpath d=\'M25,5A20.14,20.14,0,0,1,45,22.88a2.51,2.51,0,0,0,2.49,2.26h0A2.52,2.52,0,0,0,50,22.33a25.14,25.14,0,0,0-50,0,2.52,2.52,0,0,0,2.5,2.81h0A2.51,2.51,0,0,0,5,22.88,20.14,20.14,0,0,1,25,5Z\'%3E%3CanimateTransform attributeName=\'transform\' type=\'rotate\' from=\'0 25 25\' to=\'360 25 25\' dur=\'0.5s\' repeatCount=\'indefinite\'/%3E%3C/path%3E%3C/svg%3E") center center no-repeat';
            }
            // Wait a bit more for Google Maps to load
            setTimeout(checkGoogleMapsAndInit, 300);
        }
    }
    
    // Start the initialization process
    checkGoogleMapsAndInit();
    
    // Load locations on the map using PlaceID only - IMPROVED VERSION
    function loadLocations(data) {
        
        if (!data || data.length === 0) {
            hideAllMarkers();
            updateResultsCounter(0);
            return;
        }
        
        var bounds = new google.maps.LatLngBounds();
        var service = new google.maps.places.PlacesService(map);
        var validLocations = 0;
        var processedCount = 0;
        var totalLocations = data.filter(location => location.PlaceID).length;
        var dataPlaceIds = data.map(location => location.PlaceID).filter(id => id);
        
        if (totalLocations === 0) {
            hideAllMarkers();
            updateResultsCounter(0);
            return;
        }
        
        // Hide all markers first
        hideAllMarkers();
        
        // Show only markers that match the filtered data
        markers.forEach(function(markerData) {
            if (dataPlaceIds.includes(markerData.placeId)) {
                markerData.marker.setVisible(true);
                bounds.extend(markerData.marker.getPosition());
                validLocations++;
            }
        });
        
        // If we already have all the markers we need, just update the bounds and counter
        var existingPlaceIds = markers.map(m => m.placeId);
        var newPlaceIds = dataPlaceIds.filter(id => !existingPlaceIds.includes(id));
        
        if (newPlaceIds.length === 0) {
            // All markers already exist, just fit bounds and update counter
            updateResultsCounter(validLocations);
            if (validLocations > 0 && !bounds.isEmpty()) {
                map.fitBounds(bounds);
            }
            return;
        }
        
        // Create new markers for places we don't have yet
        data.forEach(function(location) {
            if (!location.PlaceID || existingPlaceIds.includes(location.PlaceID)) {
                return; // Skip if no PlaceID or marker already exists
            }
            
            var request = {
                placeId: location.PlaceID,
                fields: ['name', 'geometry', 'formatted_address', 'photos', 'rating', 'opening_hours', 'formatted_phone_number', 'website']
            };
            
            service.getDetails(request, function(place, status) {
                processedCount++;
                
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    var marker = new google.maps.Marker({
                        map: map,
                        position: place.geometry.location,
                        title: place.name
                    });
                    
                    // Store marker with placeId for efficient filtering
                    var markerData = {
                        marker: marker,
                        placeId: location.PlaceID,
                        location: location,
                        place: place
                    };
                    
                    markers.push(markerData);
                    bounds.extend(place.geometry.location);
                    validLocations++;
                    
                    // Add click listener for drawer (replaced popup)
                    marker.addListener('click', function() {
                        var content = generateInfoWindowContent(place, location);
                        // Always use drawer instead of popup for cleaner UX
                        if (typeof openDrawer === 'function') {
                            openDrawer(content);
                        } else if (typeof window.openDrawer === 'function') {
                            window.openDrawer(content);
                        }
                        
                        // Close any existing info windows (if any exist from legacy code)
                        markers.forEach(function(m) {
                            if (m.infoWindow) {
                                m.infoWindow.close();
                            }
                        });
                    });
                } else {
                    // Places API error - silently continue
                }
                
                // When all new locations are processed, update the map view
                if (processedCount === newPlaceIds.length) {
                    updateResultsCounter(validLocations);
                    if (!bounds.isEmpty() && validLocations > 0) {
                        map.fitBounds(bounds);
                        // Limit zoom level
                        google.maps.event.addListenerOnce(map, 'idle', function() {
                            if (map.getZoom() > 10) {
                                map.setZoom(10);
                            }
                        });
                    }
                }
            });
        });
    }
    
    // Hide all markers without removing them
    function hideAllMarkers() {
        markers.forEach(function(markerData) {
            markerData.marker.setVisible(false);
            if (markerData.infoWindow) {
                markerData.infoWindow.close();
            }
        });
    }
    
    // Clear all markers from the map (only used for complete reset)
    function clearMarkers() {
        markers.forEach(function(markerData) {
            if (markerData.infoWindow) {
                markerData.infoWindow.close();
            }
            markerData.marker.setMap(null);
        });
        markers = [];
        // Keep global markers array in sync
        amfmMap.markers = markers;
    }
    
    // Generate enhanced info window content with image slider
    function generateInfoWindowContent(place, locationData) {
        var photos = place.photos || [];
        var photoSlider = '';

        // Create single photo display if photos exist (only use first photo)
        if (photos.length > 0) {
            var firstPhoto = photos[0];
            var photoUrl = firstPhoto.getUrl({ maxWidth: 400, maxHeight: 250 });
            photoSlider = `
                <img src="${photoUrl}" alt="Location Photo">
            `;
        } else if (locationData && locationData.Image) {
            // Fallback to location data image
            photoSlider = `
                <img src="${locationData.Image}" alt="Location Image">
            `;
        }

        // Clean website URL (remove UTM parameters)
        var cleanWebsite = place.website;
        if (cleanWebsite) {
            try {
                const url = new URL(cleanWebsite);
                const params = new URLSearchParams(url.search);
                params.delete('utm_source');
                params.delete('utm_medium');
                params.delete('utm_campaign');
                cleanWebsite = url.origin + url.pathname + (params.toString() ? '?' + params.toString() : '');
            } catch (e) {
                // Keep original URL if parsing fails
            }
        }

        var drawer_info = `
            <!-- Name -->
            <div class="amfm-map-drawer-info-name">
            ${place.name || locationData?.['(Internal) Name'] || 'AMFM Location'}
            </div>
            
            <!-- Address -->
            <div class="amfm-map-drawer-info-address">
            <i class="fas fa-map-marker-alt" style="margin-right: 5px;"></i>
            ${place.formatted_address || "Address not available"}
            </div>

            <!-- Rating -->
            ${place.rating ? `<div class="amfm-map-drawer-info-rating">
            <span style="color: #333; margin-right: 5px;">${place.rating.toFixed(1)}</span> 
            <span style="color: #ffc107;">${generateStars(place.rating)}</span>
            </div>` : ""}
        `;

        var drawer_actions = `
            <!-- Website Button -->
            ${cleanWebsite ? `<div>
                <a href="${cleanWebsite}" target="_blank" class="amfm-website-button amfm-drawer-action">
                    View Location
                </a>
            </div>` : ""}

            <!-- Phone Number Button -->
            ${place.formatted_phone_number ? `<div>
                <a href="tel:${place.formatted_phone_number}" class="amfm-phone-button amfm-drawer-action">
                    ${place.formatted_phone_number}
                </a>
            </div>` : ""}
        `;

        var content = `
            <div class="amfm-map-info-content">
                <div class="amfm-maps-drawer-left">
                    <div class="amfm-maps-single-photo">
                        ${photoSlider}
                    </div>
                </div>
                <div class="amfm-maps-drawer-right">
                    <div class="amfm-maps-info-wrapper">
                        ${drawer_info}
                    </div>
                    <div class="amfm-maps-drawer-actions">
                        ${drawer_actions}
                    </div>
                </div>
            </div>
        `;
        return content;
    }
    
    // Generate star rating HTML
    function generateStars(rating) {
        var stars = '';
        var fullStars = Math.floor(rating);
        var hasHalfStar = rating % 1 >= 0.5;
        
        for (var i = 0; i < fullStars; i++) {
            stars += '★';
        }
        if (hasHalfStar) {
            stars += '☆';
        }
        for (var i = fullStars + (hasHalfStar ? 1 : 0); i < 5; i++) {
            stars += '☆';
        }
        return stars;
    }
    
    // Mobile detection
    function isMobile() {
        return window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    // Clear all markers from the map (only used for complete reset)
    function clearMarkers() {
        markers.forEach(function(markerData) {
            if (markerData.infoWindow) {
                markerData.infoWindow.close();
            }
            markerData.marker.setMap(null);
        });
        markers = [];
        // Keep global markers array in sync
        amfmMap.markers[unique_id] = markers;
    }
    
    // Update the results counter
    function updateCounter(count) {
        updateResultsCounter(count);
    }
    
    // Update results counter (universal function)
    function updateResultsCounter(count) {
        var counterElement = document.getElementById(unique_id + '_counter');
        if (counterElement) {
            counterElement.textContent = 'Showing ' + count + ' location' + (count !== 1 ? 's' : '');
        }
    }
    
    // Set up filter event listeners
    function setupFilterListeners() {
        var container = document.getElementById(unique_id);
        if (!container) {
            return;
        }
        
        // All map widgets listen for external filter updates
        container.addEventListener('amfmFilterUpdate', function(event) {
            var externalFilters = event.detail.filters;
            applyExternalFilters(externalFilters);
        });
        
        // Check if this widget has internal filters (legacy combined widget support)
        var filterButtons = container.querySelectorAll('.amfm-filter-button:not(.amfm-clear-filters)');
        var checkboxes = container.querySelectorAll('input[type="checkbox"]');
        var clearButton = container.querySelector('.amfm-clear-filters');
        
        if (filterButtons.length > 0 || checkboxes.length > 0) {
            // Add event listeners to filter buttons (internal filters)
            filterButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var filterType = button.getAttribute('data-filter-type');
                    
                    if (filterType === 'gender') {
                        // Single selection for gender with ability to deselect
                        if (button.classList.contains('active')) {
                            // If clicking the active gender button, deselect it
                            button.classList.remove('active');
                        } else {
                            // Deactivate other gender buttons first
                            var genderButtons = container.querySelectorAll('.amfm-filter-button[data-filter-type="gender"]');
                            genderButtons.forEach(function(genderBtn) {
                                genderBtn.classList.remove('active');
                            });
                            // Then activate the clicked button
                            button.classList.add('active');
                        }
                    } else {
                        // Multi-selection for other filter types
                        button.classList.toggle('active');
                    }
                    
                    applyFilters();
                });
            });
            
            // Add event listeners to checkboxes (for sidebar layout)
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    applyFilters();
                });
            });
            
            // Add event listener to clear button
            if (clearButton) {
                clearButton.addEventListener('click', function() {
                    clearAllFilters();
                });
            }
        }
    }
    
    // Apply external filters from filter widget
    function applyExternalFilters(externalFilters) {
        // Get the map and data for this specific instance
        var currentMap = amfmMap.maps[unique_id];
        var currentMarkers = amfmMap.markers[unique_id];
        var currentData = amfmMap.instances[unique_id]?.json_data;
        
        // Ensure map is initialized before applying filters
        if (!currentMap || !currentData) {
            return;
        }
        
        // Filter the data based on PlaceID precision and external filters
        var newFilteredData = currentData.filter(function(location) {
            // Skip locations without PlaceID for precision
            if (!location.PlaceID) {
                return false;
            }
            
            // If no filters are active, show all locations with PlaceID
            var hasActiveFilters = Object.values(externalFilters).some(arr => arr.length > 0);
            if (!hasActiveFilters) {
                return true;
            }
            
            // Location filter
            if (externalFilters.location && externalFilters.location.length > 0) {
                var locationMatch = false;
                externalFilters.location.forEach(function(filterLocation) {
                    var stateAbbr = getStateAbbreviation(filterLocation);
                    if (location.State === stateAbbr) {
                        locationMatch = true;
                    }
                });
                if (!locationMatch) {
                    return false;
                }
            }
            
            // Region filter
            if (externalFilters.region && externalFilters.region.length > 0) {
                var regionMatch = false;
                externalFilters.region.forEach(function(filterRegion) {
                    if (location.Region === filterRegion) {
                        regionMatch = true;
                    }
                });
                if (!regionMatch) {
                    return false;
                }
            }
            
            // Gender filter
            if (externalFilters.gender && externalFilters.gender.length > 0) {
                if (!externalFilters.gender.includes(location['Details: Gender'])) {
                    return false;
                }
            }
            
            // Conditions filter (AND logic - location must have ALL selected conditions)
            if (externalFilters.conditions && externalFilters.conditions.length > 0) {
                var allConditionsMatch = true;
                externalFilters.conditions.forEach(function(condition) {
                    if (location['Conditions: ' + condition] != 1) {
                        allConditionsMatch = false;
                    }
                });
                if (!allConditionsMatch) {
                    return false;
                }
            }
            
            // Programs filter (AND logic - location must have ALL selected programs)
            if (externalFilters.programs && externalFilters.programs.length > 0) {
                var allProgramsMatch = true;
                externalFilters.programs.forEach(function(program) {
                    if (location['Programs: ' + program] != 1) {
                        allProgramsMatch = false;
                    }
                });
                if (!allProgramsMatch) {
                    return false;
                }
            }
            
            // Accommodations filter (AND logic - location must have ALL selected accommodations)
            if (externalFilters.accommodations && externalFilters.accommodations.length > 0) {
                var allAccommodationsMatch = true;
                externalFilters.accommodations.forEach(function(accommodation) {
                    if (location['Accommodations: ' + accommodation] != 1) {
                        allAccommodationsMatch = false;
                    }
                });
                if (!allAccommodationsMatch) {
                    return false;
                }
            }
            
            // Level of Care filter (AND logic - location must have ALL selected care levels)
            if (externalFilters.level_of_care && externalFilters.level_of_care.length > 0) {
                var allCareLevelsMatch = true;
                externalFilters.level_of_care.forEach(function(careLevel) {
                    if (location['Level of Care: ' + careLevel] != 1) {
                        allCareLevelsMatch = false;
                    }
                });
                if (!allCareLevelsMatch) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Update this instance's filtered data in the registry
        amfmMap.filteredData[unique_id] = newFilteredData;
        
        // For backward compatibility, also update global if this is the default map
        if (amfmMap.map === currentMap) {
            amfmMap.filteredData = newFilteredData;
        }
        
        // Update results counter
        updateResultsCounter(newFilteredData.length);
        
        // Load filtered locations using the loadLocations function for this instance
        var loadLocationsFn = amfmMap.loadLocations[unique_id];
        if (loadLocationsFn) {
            loadLocationsFn(newFilteredData);
        }
    }
    
    // Apply filters to the data (internal filters)
    function applyFilters() {
        var container = document.getElementById(unique_id);
        if (!container) return;
        
        var activeFilters = {
            location: [],
            region: [],
            gender: [],
            conditions: [],
            programs: [],
            accommodations: [],
            level_of_care: []
        };
        
        // Collect active filters from buttons
        var activeButtons = container.querySelectorAll('.amfm-filter-button.active:not(.amfm-clear-filters)');
        activeButtons.forEach(function(button) {
            var filterType = button.getAttribute('data-filter-type');
            var filterValue = button.getAttribute('data-filter-value');
            
            if (activeFilters[filterType]) {
                activeFilters[filterType].push(filterValue);
            }
        });
        
        // Collect active filters from checkboxes (sidebar layout)
        var checkedBoxes = container.querySelectorAll('input[type="checkbox"]:checked');
        checkedBoxes.forEach(function(checkbox) {
            var filterType = checkbox.name;
            var filterValue = checkbox.value;
            
            if (activeFilters[filterType]) {
                activeFilters[filterType].push(filterValue);
            }
        });
        
        // Apply filters to the data (internal filters)
        filteredData = json_data.filter(function(location) {
            // Skip locations without PlaceID for precision
            if (!location.PlaceID) {
                return false;
            }
            
            // If no filters are active, show all locations with PlaceID
            var hasActiveFilters = Object.values(activeFilters).some(arr => arr.length > 0);
            if (!hasActiveFilters) {
                return true;
            }
            
            // Location filter
            if (activeFilters.location.length > 0) {
                var locationMatch = false;
                activeFilters.location.forEach(function(filterLocation) {
                    var stateAbbr = getStateAbbreviation(filterLocation);
                    if (location.State === stateAbbr) {
                        locationMatch = true;
                    }
                });
                if (!locationMatch) {
                    return false;
                }
            }
            
            // Region filter
            if (activeFilters.region && activeFilters.region.length > 0) {
                var regionMatch = false;
                activeFilters.region.forEach(function(filterRegion) {
                    if (location.Region === filterRegion) {
                        regionMatch = true;
                    }
                });
                if (!regionMatch) {
                    return false;
                }
            }
            
            // Gender filter
            if (activeFilters.gender.length > 0) {
                if (!activeFilters.gender.includes(location['Details: Gender'])) {
                    return false;
                }
            }
            
            // Conditions filter (AND logic - location must have ALL selected conditions)
            if (activeFilters.conditions.length > 0) {
                var allConditionsMatch = true;
                activeFilters.conditions.forEach(function(condition) {
                    if (location['Conditions: ' + condition] != 1) {
                        allConditionsMatch = false;
                    }
                });
                if (!allConditionsMatch) {
                    return false;
                }
            }
            
            // Programs filter (AND logic - location must have ALL selected programs)
            if (activeFilters.programs.length > 0) {
                var allProgramsMatch = true;
                activeFilters.programs.forEach(function(program) {
                    if (location['Programs: ' + program] != 1) {
                        allProgramsMatch = false;
                    }
                });
                if (!allProgramsMatch) {
                    return false;
                }
            }
            
            // Accommodations filter (AND logic - location must have ALL selected accommodations)
            if (activeFilters.accommodations.length > 0) {
                var allAccommodationsMatch = true;
                activeFilters.accommodations.forEach(function(accommodation) {
                    if (location['Accommodations: ' + accommodation] != 1) {
                        allAccommodationsMatch = false;
                    }
                });
                if (!allAccommodationsMatch) {
                    return false;
                }
            }
            
            // Level of Care filter (AND logic - location must have ALL selected care levels)
            if (activeFilters.level_of_care.length > 0) {
                var allCareLevelsMatch = true;
                activeFilters.level_of_care.forEach(function(careLevel) {
                    if (location['Level of Care: ' + careLevel] != 1) {
                        allCareLevelsMatch = false;
                    }
                });
                if (!allCareLevelsMatch) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Update this instance's filtered data in the registry
        amfmMap.filteredData[unique_id] = filteredData;
        
        // For backward compatibility, also update global if this is the default map
        var currentMap = amfmMap.maps[unique_id];
        if (amfmMap.map === currentMap) {
            amfmMap.filteredData = filteredData;
        }
        
        // Update results counter
        updateResultsCounter(filteredData.length);
        
        // Load filtered locations using the loadLocations function for this instance
        var loadLocationsFn = amfmMap.loadLocations[unique_id];
        if (loadLocationsFn) {
            loadLocationsFn(filteredData);
        }
    }
    
    // Clear all filters
    function clearAllFilters() {
        var container = document.getElementById(unique_id);
        if (!container) return;
        
        // Clear button filters
        var activeButtons = container.querySelectorAll('.amfm-filter-button.active:not(.amfm-clear-filters)');
        activeButtons.forEach(function(button) {
            button.classList.remove('active');
        });
        
        // Clear checkbox filters
        var checkboxes = container.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = false;
        });
        
        // Reset to show all locations with PlaceID
        filteredData = json_data.filter(function(location) {
            return location.PlaceID; // Only show locations with PlaceID
        });
        
        // Update this instance's filtered data in the registry
        amfmMap.filteredData[unique_id] = filteredData;
        
        // For backward compatibility, also update global if this is the default map
        var currentMap = amfmMap.maps[unique_id];
        if (amfmMap.map === currentMap) {
            amfmMap.filteredData = filteredData;
        }
        
        // Update results counter
        updateResultsCounter(filteredData.length);
        
        // Load filtered locations using the loadLocations function for this instance
        var loadLocationsFn = amfmMap.loadLocations[unique_id];
        if (loadLocationsFn) {
            loadLocationsFn(filteredData);
        }
    }
    
    // Helper function to get state abbreviation from full name
    function getStateAbbreviation(stateName) {
        var states = {
            'California': 'CA',
            'Virginia': 'VA',
            'Washington': 'WA',
            'Minnesota': 'MN',
            'Oregon': 'OR'
        };
        return states[stateName] || stateName;
    }
};

// AMFM Map V2 Filter Widget JavaScript
var amfmMapFilter = {};
window.amfmMapFilter = amfmMapFilter;

amfmMapFilter.init = function(settings) {
    var unique_id = settings.unique_id;
    var target_map_id = settings.target_map_id;
    var json_data = settings.json_data;
    
    // Set up filter event listeners
    function setupFilterListeners() {
        var container = document.getElementById(unique_id);
        if (!container) {
            return;
        }
        
        // Handle button filters
        var filterButtons = container.querySelectorAll('.amfm-filter-button:not(.amfm-clear-filters)');
        var checkboxes = container.querySelectorAll('input[type="checkbox"]');
        var clearButton = container.querySelector('.amfm-clear-filters');
        
        // Add event listeners to filter buttons
        filterButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var filterType = button.getAttribute('data-filter-type');
                
                if (filterType === 'gender') {
                    // Single selection for gender with ability to deselect
                    if (button.classList.contains('active')) {
                        // If clicking the active gender button, deselect it
                        button.classList.remove('active');
                    } else {
                        // Deactivate other gender buttons first
                        var genderButtons = container.querySelectorAll('.amfm-filter-button[data-filter-type="gender"]');
                        genderButtons.forEach(function(genderBtn) {
                            genderBtn.classList.remove('active');
                        });
                        // Then activate the clicked button
                        button.classList.add('active');
                    }
                } else {
                    // Multi-selection for other filter types
                    button.classList.toggle('active');
                }
                
                notifyMapWidgets();
            });
        });
        
        // Add event listeners to checkboxes (for sidebar layout)
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                notifyMapWidgets();
            });
        });
        
        // Add event listener to clear button
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                clearAllFilters();
            });
        }
    }
    
    // Get active filters from the filter widget
    function getActiveFilters() {
        var container = document.getElementById(unique_id);
        if (!container) return {};
        
        var activeFilters = {
            location: [],
            region: [],
            gender: [],
            conditions: [],
            programs: [],
            accommodations: [],
            level_of_care: []
        };
        
        // Collect active filters from buttons
        var activeButtons = container.querySelectorAll('.amfm-filter-button.active:not(.amfm-clear-filters)');
        activeButtons.forEach(function(button) {
            var filterType = button.getAttribute('data-filter-type');
            var filterValue = button.getAttribute('data-filter-value');
            
            if (activeFilters[filterType]) {
                activeFilters[filterType].push(filterValue);
            }
        });
        
        // Collect active filters from checkboxes (sidebar layout)
        var checkedBoxes = container.querySelectorAll('input[type="checkbox"]:checked');
        checkedBoxes.forEach(function(checkbox) {
            var filterType = checkbox.name;
            var filterValue = checkbox.value;
            
            if (activeFilters[filterType]) {
                activeFilters[filterType].push(filterValue);
            }
        });
        
        return activeFilters;
    }
    
    // Notify target map widgets about filter changes
    function notifyMapWidgets() {
        var activeFilters = getActiveFilters();
        
        // Simple and reliable container targeting
        function findCorrectMapContainers(targetMapId) {
            var containers = [];
            
            if (targetMapId) {
                // Strategy 1: Look for .amfm-map-container within the target map ID
                var targetElement = document.getElementById(targetMapId);
                if (targetElement) {
                    var mapContainer = targetElement.querySelector('.amfm-map-container');
                    if (mapContainer) {
                        containers.push(mapContainer.id);
                    }
                }
                
                // Strategy 2: If target element itself has the class
                if (targetElement && targetElement.classList.contains('amfm-map-container')) {
                    containers.push(targetMapId);
                }
            }
            
            // Fallback: If no specific target or no containers found, target all .amfm-map-container
            if (containers.length === 0) {
                var allMapContainers = document.querySelectorAll('.amfm-map-container');
                allMapContainers.forEach(function(container) {
                    if (container.id) {
                        containers.push(container.id);
                    }
                });
            }
            
            return containers;
        }
        
        // Find target map widget(s)
        var mapWidgets = [];
        
        if (target_map_id) {
            mapWidgets = findCorrectMapContainers(target_map_id);
        } else {
            mapWidgets = findCorrectMapContainers(null);
        }
        
        // Send filter update to each map widget
        mapWidgets.forEach(function(mapId) {
            // Trigger custom event for map widget to listen to
            var event = new CustomEvent('amfmFilterUpdate', {
                detail: {
                    filters: activeFilters,
                    sourceFilterId: unique_id
                }
            });
            
            var mapContainer = document.getElementById(mapId);
            if (mapContainer) {
                mapContainer.dispatchEvent(event);
            }
        });
    }
    
    // Clear all filters in the filter widget
    function clearAllFilters() {
        var container = document.getElementById(unique_id);
        if (!container) return;
        
        // Clear button filters
        var activeButtons = container.querySelectorAll('.amfm-filter-button.active:not(.amfm-clear-filters)');
        activeButtons.forEach(function(button) {
            button.classList.remove('active');
        });
        
        // Clear checkbox filters
        var checkboxes = container.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = false;
        });
        
        // Notify map widgets
        notifyMapWidgets();
    }
    
    // Initialize the filter widget
    setupFilterListeners();
    
    // Expose methods for external access
    return {
        getActiveFilters: getActiveFilters,
        clearAllFilters: clearAllFilters,
        notifyMapWidgets: notifyMapWidgets
    };
};