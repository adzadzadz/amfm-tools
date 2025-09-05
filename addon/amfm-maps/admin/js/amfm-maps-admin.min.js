(function( $ ) {
	'use strict';

	/**
	 * AMFM Maps Admin JavaScript
	 * Enhanced functionality for the Maps configuration page
	 */

	$(document).ready(function() {
		console.log('AMFM Maps admin script loaded');
		console.log('amfmMapsAdmin object:', amfmMapsAdmin);
		
		// Hide WordPress footer on this admin page
		$('#wpfooter').hide();
		
		// Add body class for better CSS targeting
		$('body').addClass('amfm-maps-admin-page');
		
		// Initialize admin functionality
		initializeFormValidation();
		initializeSyncFunctionality();
		initializeTooltips();
		initializeAnimations();
		initializeTabs();
		initializeFilterConfiguration();
		
		// Initialize data table if we're on the data view tab
		if ($('#tab-data-view').hasClass('active')) {
			initializeDataTable();
		}
	});

	/**
	 * Form validation functionality
	 */
	function initializeFormValidation() {
		const urlInput = $('#json_url');
		const saveButton = $('[name="submit"]');
		const syncButton = $('[name="manual_sync"]');

		// Real-time URL validation
		urlInput.on('input', function() {
			const url = $(this).val().trim();
			const isValid = isValidUrl(url);
			
			$(this).toggleClass('valid', isValid && url.length > 0);
			$(this).toggleClass('invalid', !isValid && url.length > 0);
			
			// Enable/disable sync button based on URL validity
			syncButton.prop('disabled', !isValid || url.length === 0);
		});

		// Form submission validation
		$('form').on('submit', function(e) {
			const form = $(this);
			const urlField = form.find('#json_url');
			
			if (urlField.length && !isValidUrl(urlField.val())) {
				e.preventDefault();
				showNotification('Please enter a valid URL', 'error');
				urlField.focus();
			}
		});
	}

	/**
	 * Sync functionality with loading states
	 */
	function initializeSyncFunctionality() {
		const syncForm = $('.amfm-maps-sync-form');
		const syncButton = syncForm.find('[name="manual_sync"]');
		
		// Check if we have AJAX capabilities
		if (typeof amfmMapsAdmin !== 'undefined' && amfmMapsAdmin.ajax_url) {
			// Handle form submission with AJAX
			syncForm.on('submit', function(e) {
				e.preventDefault();
				
				// Add loading state
				syncButton.prop('disabled', true);
				const originalText = syncButton.html();
				syncButton.html('<i class="dashicons dashicons-update spin"></i> ' + 'Syncing...');
				
				// Perform AJAX sync
				$.ajax({
					url: amfmMapsAdmin.ajax_url,
					type: 'POST',
					data: {
						action: 'amfm_maps_manual_sync',
						nonce: amfmMapsAdmin.nonce
					},
					success: function(response) {
						if (response.success) {
							showNotification('Data synced successfully!', 'success');
							// Refresh the page to show updated data
							setTimeout(function() {
								location.reload();
							}, 1500);
						} else {
							showNotification('Sync failed: ' + (response.data.message || 'Unknown error'), 'error');
						}
					},
					error: function(xhr, status, error) {
						showNotification('Sync failed: Network error', 'error');
						console.error('AJAX Error:', status, error);
					},
					complete: function() {
						// Restore button state
						syncButton.prop('disabled', false);
						syncButton.html(originalText);
					}
				});
			});
		} else {
			// Fallback to regular form submission
			syncForm.on('submit', function() {
				// Add loading state
				syncButton.prop('disabled', true);
				syncButton.html('<i class="dashicons dashicons-update spin"></i> Syncing...');
				
				// Allow form to submit normally
				return true;
			});
		}
	}

	/**
	 * Initialize tooltips for help text
	 */
	function initializeTooltips() {
		// Add tooltip functionality for form fields
		$('.amfm-maps-help-text').each(function() {
			const helpText = $(this);
			const formGroup = helpText.closest('.amfm-maps-form-group');
			const input = formGroup.find('input, select');
			
			input.on('focus', function() {
				helpText.addClass('highlighted');
			}).on('blur', function() {
				helpText.removeClass('highlighted');
			});
		});
	}

	/**
	 * Initialize animations and interactions
	 */
	function initializeAnimations() {
		// Animate panels on load
		$('.amfm-maps-panel').each(function(index) {
			$(this).css({
				'opacity': '0',
				'transform': 'translateY(20px)'
			}).delay(index * 100).animate({
				'opacity': '1'
			}, 500).css('transform', 'translateY(0)');
		});

		// Enhance button interactions
		$('.amfm-maps-button').on('mouseenter', function() {
			$(this).addClass('hover-effect');
		}).on('mouseleave', function() {
			$(this).removeClass('hover-effect');
		});

		// Add focus effects to form elements
		$('.amfm-maps-input, .amfm-maps-select').on('focus', function() {
			$(this).closest('.amfm-maps-form-group').addClass('focused');
		}).on('blur', function() {
			$(this).closest('.amfm-maps-form-group').removeClass('focused');
		});
	}

	/**
	 * Initialize tab functionality
	 */
	function initializeTabs() {
		// Tab switching
		$('.amfm-maps-tab-button').on('click', function(e) {
			e.preventDefault();
			const tabId = $(this).data('tab');
			switchTab(tabId);
			// Update URL hash
			window.location.hash = 'tab-' + tabId;
		});
		
		// Check for URL hash on load
		const hash = window.location.hash;
		if (hash && hash.startsWith('#tab-')) {
			const tabId = hash.substring(5); // Remove '#tab-'
			if ($('.amfm-maps-tab-button[data-tab="' + tabId + '"]').length > 0) {
				switchTab(tabId);
				return;
			}
		}
		
		// Ensure only the first tab is active on load if no hash
		if ($('.amfm-maps-tab-button.active').length === 0) {
			$('.amfm-maps-tab-button').first().addClass('active');
		}
		if ($('.amfm-maps-tab-pane.active').length === 0) {
			$('.amfm-maps-tab-pane').first().addClass('active');
		}
		
		// Listen for hash changes (browser back/forward)
		$(window).on('hashchange', function() {
			const hash = window.location.hash;
			if (hash && hash.startsWith('#tab-')) {
				const tabId = hash.substring(5);
				if ($('.amfm-maps-tab-button[data-tab="' + tabId + '"]').length > 0) {
					switchTab(tabId);
				}
			}
		});
	}

	/**
	 * Switch to a specific tab
	 */
	function switchTab(tabId) {
		// Update tab buttons
		$('.amfm-maps-tab-button').removeClass('active');
		$('.amfm-maps-tab-button[data-tab="' + tabId + '"]').addClass('active');
		
		// Update tab content
		$('.amfm-maps-tab-pane').removeClass('active');
		$('#tab-' + tabId).addClass('active');
		
		// Initialize tab-specific functionality
		if (tabId === 'data-view') {
			initializeDataTable();
		}
	}

	/**
	 * Initialize data table functionality
	 */
	function initializeDataTable() {
		// Search functionality
		$('#data-search').on('input', function() {
			const searchTerm = $(this).val().toLowerCase();
			filterTableRows(searchTerm);
		});

		// Expand/Collapse functionality
		$('#expand-all').on('click', function() {
			expandAllRows();
		});

		$('#collapse-all').on('click', function() {
			collapseAllRows();
		});

		// Export JSON functionality
		$('#export-json').on('click', function() {
			exportFullJSON();
		});

		// Row toggle functionality
		$('.toggle-children').on('click', function(e) {
			e.stopPropagation();
			const key = $(this).data('key');
			toggleChildRows(key);
			$(this).toggleClass('expanded');
		});

		// Row hover effects
		$('.data-row').on('mouseenter', function() {
			$(this).addClass('hover');
		}).on('mouseleave', function() {
			$(this).removeClass('hover');
		});
	}

	/**
	 * Filter table rows based on search term
	 */
	function filterTableRows(searchTerm) {
		$('.data-row').each(function() {
			const row = $(this);
			const key = row.find('.key-name').text().toLowerCase();
			const value = row.find('.value-content').text().toLowerCase();
			const type = row.find('.type-badge').text().toLowerCase();
			
			const matches = key.includes(searchTerm) || 
							value.includes(searchTerm) || 
							type.includes(searchTerm);
			
			if (matches || searchTerm === '') {
				row.show();
				// Also show parent rows if child matches
				showParentRows(row);
			} else {
				row.hide();
			}
		});
	}

	/**
	 * Show parent rows for a given row
	 */
	function showParentRows(row) {
		const level = parseInt(row.data('level'));
		if (level > 0) {
			const key = row.data('key');
			const parentKey = key.substring(0, key.lastIndexOf('.'));
			const parentRow = $('.data-row[data-key="' + parentKey + '"]');
			if (parentRow.length) {
				parentRow.show();
				showParentRows(parentRow);
			}
		}
	}

	/**
	 * Toggle child rows for a given key
	 */
	function toggleChildRows(parentKey) {
		const childRows = $('.data-row[data-key^="' + parentKey + '."]');
		const directChildren = childRows.filter(function() {
			const key = $(this).data('key');
			const parts = key.split('.');
			const parentParts = parentKey.split('.');
			return parts.length === parentParts.length + 1;
		});
		
		directChildren.toggleClass('show');
		
		// If collapsing, also collapse all nested children
		if (!directChildren.first().hasClass('show')) {
			childRows.removeClass('show');
			childRows.find('.toggle-children').removeClass('expanded');
		}
	}

	/**
	 * Expand all rows
	 */
	function expandAllRows() {
		$('.data-row.child-row').addClass('show');
		$('.toggle-children').addClass('expanded');
	}

	/**
	 * Collapse all rows
	 */
	function collapseAllRows() {
		$('.data-row.child-row').removeClass('show');
		$('.toggle-children').removeClass('expanded');
	}

	/**
	 * Export full JSON data
	 */
	function exportFullJSON() {
		// Get the JSON data from the hidden pre element
		const jsonData = $('#json-raw-data').text();
		downloadJSON(jsonData, 'amfm-maps-data.json');
	}

	/**
	 * Download JSON data as file
	 */
	function downloadJSON(jsonString, filename) {
		const blob = new Blob([jsonString], { type: 'application/json' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = filename;
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	}

	/**
	 * Show full value in a modal/popup
	 */
	window.showFullValue = function(key) {
		// Find the row and get the full value
		const row = $('.data-row[data-key="' + key + '"]');
		const fullValue = row.data('full-value') || row.find('.value-content').text();
		
		// Create a simple modal
		const modal = $(`
			<div class="amfm-modal-overlay">
				<div class="amfm-modal">
					<div class="amfm-modal-header">
						<h3>Full Value: ${key}</h3>
						<button class="amfm-modal-close">&times;</button>
					</div>
					<div class="amfm-modal-body">
						<pre class="amfm-modal-content">${escapeHtml(fullValue)}</pre>
					</div>
					<div class="amfm-modal-footer">
						<button class="amfm-maps-button amfm-maps-button-secondary amfm-modal-close">Close</button>
						<button class="amfm-maps-button amfm-maps-button-primary" onclick="copyToClipboard('modal-content', true)">Copy</button>
					</div>
				</div>
			</div>
		`);
		
		$('body').append(modal);
		
		// Close modal functionality
		modal.find('.amfm-modal-close').on('click', function() {
			modal.remove();
		});
		
		modal.on('click', function(e) {
			if (e.target === this) {
				modal.remove();
			}
		});
	};

	/**
	 * Export subset of data
	 */
	window.exportSubset = function(key) {
		// This would need to be implemented with server-side support
		// For now, show a notification
		showNotification('Export subset functionality would require server-side implementation', 'info');
	};

	/**
	 * Copy to clipboard functionality
	 */
	function copyToClipboard(elementId) {
		const element = document.getElementById(elementId);
		if (!element) {
			showNotification('Element not found for copying', 'error');
			return;
		}
		
		// Create a text area element to copy from
		const textArea = document.createElement('textarea');
		textArea.value = element.textContent || element.innerText;
		document.body.appendChild(textArea);
		
		// Select and copy the text
		textArea.select();
		textArea.setSelectionRange(0, 99999); // For mobile devices
		
		try {
			const successful = document.execCommand('copy');
			if (successful) {
				showNotification('JSON data copied to clipboard!', 'success');
			} else {
				showNotification('Failed to copy to clipboard', 'error');
			}
		} catch (err) {
			showNotification('Failed to copy to clipboard', 'error');
			console.error('Copy failed:', err);
		}
		
		// Remove the temporary text area
		document.body.removeChild(textArea);
	}
	
	/**
	 * Show notification
	 */
	function showNotification(message, type = 'info') {
		// Remove any existing notifications
		$('.amfm-maps-notification').remove();
		
		const notification = $('<div class="amfm-maps-notification">')
			.addClass('notification-' + type)
			.text(message);
		
		$('body').append(notification);
		
		// Show notification
		setTimeout(() => notification.addClass('show'), 100);
		
		// Hide notification after 3 seconds
		setTimeout(() => {
			notification.removeClass('show');
			setTimeout(() => notification.remove(), 300);
		}, 3000);
	}

	/**
	 * Escape HTML for safe display
	 */
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * Show notification messages
	 */
	function showNotification(message, type = 'info') {
		// Remove any existing notifications first
		$('.amfm-maps-wrap .notice').remove();
		
		const notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
		$('.amfm-maps-wrap').prepend(notification);
		
		// Auto-dismiss after 5 seconds
		setTimeout(function() {
			notification.fadeOut(300, function() {
				$(this).remove();
			});
		}, 5000);
		
		// Scroll to top to show notification
		$('html, body').animate({ scrollTop: 0 }, 300);
	}

	/**
	 * Utility function to validate URLs
	 */
	function isValidUrl(url) {
		// Simple URL validation regex
		const urlPattern = new RegExp('^(https?:\\/\\/)?' + // protocol
			'((([a-z0-9][a-z0-9-]*[a-z0-9])\\.)+[a-z]{2,}|' + // domain name
			'localhost|' + // localhost
			'\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|' + // IP address
			'\\[?[a-f0-9]*:[a-f0-9:%.]*\\]?)' + // IPv6
			'(:\\d+)?(\\/[-a-z0-9%_.~+]*)*' + // port and path
			'(\\?[;&a-z0-9%_.~+=-]*)?' + // query string
			'(#[~-a-z0-9%_.~+=]*)?$', 'i'); // fragment locator
		
		return !!urlPattern.test(url);
	}

	// Add CSS animations
	jQuery(document).ready(function($) {
		// Add spin animation for loading states
		const spinCSS = `
			<style>
				.spin {
					animation: spin 1s linear infinite;
				}
				
				@keyframes spin {
					from { transform: rotate(0deg); }
					to { transform: rotate(360deg); }
				}
				
				.amfm-maps-input.valid {
					border-color: #28a745 !important;
					box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1) !important;
				}
				
				.amfm-maps-input.invalid {
					border-color: #dc3545 !important;
					box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
				}
				
				.amfm-maps-form-group.focused .amfm-maps-label {
					color: #667eea;
				}
				
				.amfm-maps-help-text.highlighted {
					color: #495157;
					font-weight: 500;
				}
				
				.amfm-maps-button.hover-effect {
					transform: translateY(-2px);
				}
			</style>
		`;
		
		$('head').append(spinCSS);
	});
	
	/**
	 * Initialize filter configuration functionality
	 */
	function initializeFilterConfiguration() {
		// Load filter data on page load
		loadFilterData();
		
		// Handle save filter configuration
		$('#save-filter-config').on('click', saveFilterConfiguration);
		
		// Handle refresh filter data
		$('#refresh-filter-data').on('click', loadFilterData);
		
		// Handle filter type enable/disable
		$(document).on('change', '.filter-type-enabled', function() {
			const filterType = $(this).data('filter-type');
			const isEnabled = $(this).is(':checked');
			const settingsRow = $(this).closest('.filter-type-item').find('.filter-type-settings');
			
			settingsRow.toggle(isEnabled);
		});
	}
	
	/**
	 * Load filter data from server
	 */
	function loadFilterData() {
		const $loading = $('#filter-loading');
		const $content = $('#filter-config-content');
		const $noData = $('#filter-no-data');
		
		// Show loading
		$loading.show();
		$content.hide();
		$noData.hide();
		
		$.ajax({
			url: amfmMapsAdmin.ajax_url,
			type: 'POST',
			data: {
				action: 'amfm_maps_get_available_filters',
				nonce: amfmMapsAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					const filterData = response.data;
					if (Object.keys(filterData).length > 0) {
						renderFilterConfiguration(filterData);
						$content.show();
					} else {
						$noData.show();
					}
				} else {
					showNotification('Failed to load filter data: ' + (response.data.message || 'Unknown error'), 'error');
					$noData.show();
				}
			},
			error: function(xhr, status, error) {
				showNotification('Failed to load filter data: Network error', 'error');
				console.error('AJAX Error:', status, error);
				$noData.show();
			},
			complete: function() {
				$loading.hide();
			}
		});
	}
	
	/**
	 * Render filter configuration UI
	 */
	function renderFilterConfiguration(filterData) {
		const $container = $('#filter-types-container');
		let html = '';
		
		const filterTypes = {
			'location': 'Location',
			'region': 'Region',
			'gender': 'Gender',
			'conditions': 'Conditions',
			'programs': 'Programs',
			'accommodations': 'Accommodations',
			'level_of_care': 'Level of Care'
		};
		
	   $.each(filterTypes, function(type, defaultLabel) {
		   const data = filterData[type] || {};
		   const options = data.options || [];
		   const enabled = data.enabled !== false;
		   const label = data.label || defaultLabel;
		   const limit = data.limit || 0;
		   const sortOrder = data.sort_order || 'asc';
		   // Option order and custom labels
		   let optionsHtml = '';
		   options.forEach(function(option, i) {
			   const optLabel = typeof option === 'object' ? (option.label || option.value || option) : option;
			   const optValue = typeof option === 'object' ? (option.value || option.label || option) : option;
			   optionsHtml += `<div class="option-tag" data-value="${optValue}" data-order="${option.order || i}">
				   <input type="text" class="option-label-input" value="${optLabel}" placeholder="${optValue}">
				   <span class="option-move-handle dashicons dashicons-move"></span>
			   </div>`;
		   });
		   html += `
			   <div class="filter-type-item" data-filter-type="${type}">
				   <div class="filter-type-header">
					   <label class="filter-type-title">
						   <input type="checkbox" class="filter-type-enabled" data-filter-type="${type}" ${enabled ? 'checked' : ''}>
						   <strong>${defaultLabel}</strong>
						   <span class="filter-type-count">(${options.length} options)</span>
					   </label>
				   </div>
				   <div class="filter-type-settings" style="display: ${enabled ? 'block' : 'none'};">
					   <div class="filter-setting-row">
						   <label>
							   <span class="setting-label">Display Label:</span>
							   <input type="text" class="filter-label" value="${label}" placeholder="${defaultLabel}">
						   </label>
					   </div>
					   <div class="filter-setting-row">
						   <label>
							   <span class="setting-label">Limit (0 = no limit):</span>
							   <input type="number" class="filter-limit" value="${limit}" min="0" max="100">
						   </label>
					   </div>
					   <div class="filter-setting-row">
						   <label>
							   <span class="setting-label">Sort Order:</span>
							   <select class="filter-sort-order">
								   <option value="asc" ${sortOrder === 'asc' ? 'selected' : ''}>Ascending</option>
								   <option value="desc" ${sortOrder === 'desc' ? 'selected' : ''}>Descending</option>
							   </select>
						   </label>
					   </div>
					   <div class="filter-options-preview">
						   <strong>Available Options:</strong>
						   <div class="options-list">
							   ${optionsHtml}
						   </div>
					   </div>
				   </div>
			   </div>
		   `;
	   });
		
		$container.html(html);
	}
	
	/**
	 * Save filter configuration
	 */
	function saveFilterConfiguration() {
		const $button = $('#save-filter-config');
		const originalText = $button.html();
		
		console.log('saveFilterConfiguration called');
		
		// Check if required objects exist
		if (typeof amfmMapsAdmin === 'undefined') {
			console.error('amfmMapsAdmin is undefined');
			showNotification('Configuration error: amfmMapsAdmin not found', 'error');
			return;
		}
		
		if (!amfmMapsAdmin.ajax_url) {
			console.error('AJAX URL not found in amfmMapsAdmin');
			showNotification('Configuration error: AJAX URL not found', 'error');
			return;
		}
		
		if (!amfmMapsAdmin.nonce) {
			console.error('Nonce not found in amfmMapsAdmin');
			showNotification('Configuration error: Nonce not found', 'error');
			return;
		}
		
		// Collect configuration data
		const config = {};
		
	   $('.filter-type-item').each(function(i) {
		   const $item = $(this);
		   const filterType = $item.data('filter-type');
		   const enabled = $item.find('.filter-type-enabled').is(':checked');
		   const label = $item.find('.filter-label').val() || '';
		   const limit = parseInt($item.find('.filter-limit').val()) || 0;
		   const sortOrder = $item.find('.filter-sort-order').val() || 'asc';
		   const order = parseInt($item.attr('data-order')) || i;
		   // Collect options with custom label and order
		   const options = [];
		   $item.find('.options-list .option-tag').each(function(j) {
			   const $opt = $(this);
			   const optValue = $opt.data('value');
			   const optLabel = $opt.find('.option-label-input').val() || optValue;
			   const optOrder = parseInt($opt.attr('data-order')) || j;
			   options.push({ label: optLabel, value: optValue, order: optOrder });
		   });
		   config[filterType] = {
			   enabled: enabled,
			   label: label,
			   limit: limit,
			   sort_order: sortOrder,
			   order: order,
			   options: options
		   };
	   });
		
		console.log('Saving filter configuration:', config);
		
		// Show loading state
		$button.prop('disabled', true);
		$button.html('<i class="dashicons dashicons-update spin"></i> Saving...');
		
		// Save configuration
		console.log('Sending AJAX request with data:', {
			action: 'amfm_maps_save_filter_config',
			nonce: amfmMapsAdmin.nonce,
			config: JSON.stringify(config)
		});
		
		$.ajax({
			url: amfmMapsAdmin.ajax_url,
			type: 'POST',
			data: {
				action: 'amfm_maps_save_filter_config',
				nonce: amfmMapsAdmin.nonce,
				config: JSON.stringify(config)
			},
			success: function(response) {
				console.log('Save response:', response);
				if (response.success) {
					showNotification('Filter configuration saved successfully!', 'success');
				} else {
					showNotification('Failed to save configuration: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'), 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', xhr.responseText, status, error);
				console.error('XHR object:', xhr);
				showNotification('Failed to save configuration: Network error', 'error');
			},
			complete: function() {
				// Restore button state
				$button.prop('disabled', false);
				$button.html(originalText);
			}
		});
	}
	
	// Make functions globally available
	window.switchTab = switchTab;
	window.copyToClipboard = copyToClipboard;
})( jQuery );
