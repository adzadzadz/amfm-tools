/* AMFM Tools Admin JavaScript */
document.addEventListener('DOMContentLoaded', function() {
    // Accordion functionality
    const accordionHeaders = document.querySelectorAll('.amfm-accordion-header');
    
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            const content = document.getElementById(target);
            const toggle = this.querySelector('.amfm-accordion-toggle');
            
            // Close all other accordions first
            document.querySelectorAll('.amfm-accordion-content').forEach(otherContent => {
                if (otherContent.id !== target) {
                    otherContent.style.display = 'none';
                    const otherHeader = document.querySelector(`[data-target="${otherContent.id}"]`);
                    if (otherHeader) {
                        const otherToggle = otherHeader.querySelector('.amfm-accordion-toggle');
                        if (otherToggle) otherToggle.textContent = '▼';
                        otherHeader.classList.remove('active');
                    }
                }
            });
            
            // Toggle current accordion
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.textContent = '▲';
                this.classList.add('active');
            } else {
                content.style.display = 'none';
                toggle.textContent = '▼';
                this.classList.remove('active');
            }
        });
    });

    // Handle taxonomy selection toggle
    const taxonomyRadios = document.querySelectorAll('input[name="taxonomy_selection"]');
    const taxonomyList = document.querySelector('.taxonomy-list');
    
    if (taxonomyRadios && taxonomyList) {
        taxonomyRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'selected') {
                    taxonomyList.style.display = 'block';
                } else {
                    taxonomyList.style.display = 'none';
                }
            });
        });
    }

    // Handle ACF selection toggle
    const acfRadios = document.querySelectorAll('input[name="acf_selection"]');
    const acfList = document.querySelector('.acf-list');
    
    if (acfRadios && acfList) {
        acfRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'selected') {
                    acfList.style.display = 'block';
                } else {
                    acfList.style.display = 'none';
                }
            });
        });
    }

    // Handle checkbox toggles for sections
    const toggleCheckboxes = document.querySelectorAll('.toggle-section');
    toggleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const sectionClass = this.getAttribute('data-section');
            const section = document.querySelector('.' + sectionClass);
            if (section) {
                section.style.display = this.checked ? 'block' : 'none';
            }
        });
        
        // Initialize the visibility based on checkbox state
        const sectionClass = checkbox.getAttribute('data-section');
        const section = document.querySelector('.' + sectionClass);
        if (section) {
            section.style.display = checkbox.checked ? 'block' : 'none';
        }
    });

    // Handle post type selection change to load taxonomies
    const postTypeSelect = document.getElementById('export_post_type');
    if (postTypeSelect) {
        // Load taxonomies on page load if a post type is already selected
        if (postTypeSelect.value) {
            loadTaxonomies(postTypeSelect.value);
            loadACFFields();
        }
        
        postTypeSelect.addEventListener('change', function() {
            const postType = this.value;
            const exportOptions = document.querySelector('.export-options');
            
            if (postType) {
                // Show export options if post type is selected
                if (exportOptions) {
                    exportOptions.style.display = 'block';
                }
                
                // Load taxonomies and ACF fields for this post type
                loadTaxonomies(postType);
                loadACFFields();
            } else {
                // Hide export options if no post type selected
                if (exportOptions) {
                    exportOptions.style.display = 'none';
                }
            }
        });
    }
    
    // Function to load taxonomies via AJAX
    function loadTaxonomies(postType) {
        const taxonomyList = document.querySelector('.taxonomy-list');
        if (!taxonomyList) return;
        
        const formData = new FormData();
        formData.append('action', 'amfm_get_post_type_taxonomies');
        formData.append('post_type', postType);
        formData.append('nonce', amfm_ajax.export_nonce);
        
        fetch(amfm_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                let html = '';
                data.data.forEach(taxonomy => {
                    html += `<label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="specific_taxonomies[]" value="${taxonomy.name}">
                        ${taxonomy.label}
                    </label>`;
                });
                taxonomyList.innerHTML = html;
            } else {
                taxonomyList.innerHTML = '<p>No taxonomies found for this post type.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading taxonomies:', error);
            taxonomyList.innerHTML = '<p>Error loading taxonomies.</p>';
        });
    }
    
    // Function to load ACF fields (they are already loaded in the template but we may need to refresh)
    function loadACFFields() {
        // ACF fields are already loaded in the template, no need for AJAX
        // This function is here for potential future enhancements
    }

    // Handle export form submission via AJAX
    const exportForm = document.getElementById('amfm_export_form');
    if (exportForm) {
        exportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const exportBtn = document.getElementById('amfm_export_btn');
            const exportText = exportBtn.querySelector('.export-text');
            const spinner = exportBtn.querySelector('.spinner');
            
            // Show loading state
            exportBtn.disabled = true;
            exportText.textContent = 'Exporting...';
            spinner.style.display = 'inline-block';
            
            // Validate form first
            const postType = document.getElementById('export_post_type').value;
            if (!postType) {
                showNotice('Please select a post type to export.', 'error');
                return;
            }
            
            // Check if at least one export option is selected
            const exportOptions = document.querySelectorAll('input[name="export_options[]"]');
            let hasSelectedOption = false;
            exportOptions.forEach(option => {
                if (option.checked) {
                    hasSelectedOption = true;
                }
            });
            
            if (!hasSelectedOption) {
                showNotice('Please select at least one export option.', 'error');
                return;
            }
            
            // Collect form data
            const formData = new FormData(this);
            formData.append('action', 'amfm_export_data');
            formData.append('nonce', amfm_ajax.export_nonce);
            
            console.log('Export form data:', Object.fromEntries(formData.entries()));
            
            // Make AJAX request
            fetch(amfm_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Export response status:', response.status);
                console.log('Export response headers:', response.headers);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                
                // Check if the response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // If not JSON, get the text to see what we actually received
                    return response.text().then(text => {
                        console.error('Expected JSON but got:', text);
                        throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Export response data:', data);
                if (data.success) {
                    // Create and download CSV file
                    downloadCSV(data.data.data, data.data.filename);
                    
                    // Show success message
                    showNotice('Export completed successfully! ' + data.data.total + ' posts exported.', 'success');
                } else {
                    showNotice('Export failed: ' + (data.data ? data.data : 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Export error:', error);
                showNotice('Export failed due to a network error. Please try again.', 'error');
            })
            .finally(() => {
                // Reset button state
                exportBtn.disabled = false;
                exportText.textContent = 'Export to CSV';
                spinner.style.display = 'none';
            });
        });
    }
    
    // Function to convert array to CSV and download
    function downloadCSV(data, filename) {
        let csvContent = '';
        
        data.forEach(row => {
            const escapedRow = row.map(field => {
                // Convert to string and escape quotes
                let stringField = String(field || '');
                if (stringField.includes(',') || stringField.includes('"') || stringField.includes('\n')) {
                    stringField = '"' + stringField.replace(/"/g, '""') + '"';
                }
                return stringField;
            });
            csvContent += escapedRow.join(',') + '\n';
        });
        
        // Create download link
        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Function to show admin notices
    function showNotice(message, type) {
        const notice = document.createElement('div');
        notice.className = 'notice notice-' + type + ' is-dismissible';
        notice.innerHTML = '<p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
        
        const container = document.querySelector('.amfm-container');
        if (container) {
            container.insertBefore(notice, container.firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (notice.parentNode) {
                    notice.parentNode.removeChild(notice);
                }
            }, 5000);
            
            // Handle dismiss button
            const dismissBtn = notice.querySelector('.notice-dismiss');
            if (dismissBtn) {
                dismissBtn.addEventListener('click', () => {
                    notice.parentNode.removeChild(notice);
                });
            }
        }
    }

    // Handle CSV file inputs for Import/Export tab
    function setupFileInput(inputId) {
        const fileInput = document.getElementById(inputId);
        if (fileInput) {
            const wrapper = fileInput.closest('.amfm-file-input-wrapper');
            const fileInfo = wrapper.querySelector('.amfm-file-info');
            const fileText = wrapper.querySelector('.amfm-file-text');
            const label = wrapper.querySelector('.amfm-file-label');

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    fileText.textContent = file.name;
                    fileInfo.innerHTML = `<small>File size: ${(file.size / 1024).toFixed(2)} KB</small>`;
                    if (label) {
                        label.classList.add('has-file');
                    }
                } else {
                    fileText.textContent = 'Choose CSV File';
                    fileInfo.innerHTML = '';
                    if (label) {
                        label.classList.remove('has-file');
                    }
                }
            });
        }
    }

    // Setup both file inputs
    setupFileInput('csv_file');
    setupFileInput('category_csv_file');

    // Handle collapsible instructions
    const helpButtons = document.querySelectorAll('.amfm-help-button');
    
    helpButtons.forEach(button => {
        button.addEventListener('click', function() {
            const header = this.closest('.amfm-instructions-header');
            const target = header.getAttribute('data-target');
            const content = document.getElementById(target);
            
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                this.textContent = 'Hide help';
                this.classList.add('active');
            } else {
                content.style.display = 'none';
                this.textContent = 'Need help?';
                this.classList.remove('active');
            }
        });
    });

    // Handle post columns select all/none buttons
    const selectAllBtn = document.querySelector('.post-columns-select-all');
    const selectNoneBtn = document.querySelector('.post-columns-select-none');
    
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('input[name="post_columns[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });
    }
    
    if (selectNoneBtn) {
        selectNoneBtn.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('input[name="post_columns[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    }

    // Handle component toggle switches (Dashboard and Elementor tabs)
    const componentCheckboxes = document.querySelectorAll('.amfm-component-checkbox, .amfm-widget-checkbox');
    
    componentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.amfm-component-card, .amfm-widget-card');
            const statusText = card.querySelector('.amfm-status-text');
            const statusIndicator = card.querySelector('.amfm-status-indicator');
            
            // Update visual state immediately
            if (this.checked) {
                // Enable component/widget
                card.classList.remove('amfm-component-disabled', 'amfm-widget-disabled');
                card.classList.add('amfm-component-enabled', 'amfm-widget-enabled');
                if (statusText) statusText.textContent = 'Enabled';
            } else {
                // Disable component/widget
                card.classList.remove('amfm-component-enabled', 'amfm-widget-enabled');
                card.classList.add('amfm-component-disabled', 'amfm-widget-disabled');
                if (statusText) statusText.textContent = 'Disabled';
            }
            
            // Auto-save the settings
            if (this.classList.contains('amfm-component-checkbox')) {
                autoSaveComponentSettings();
            } else if (this.classList.contains('amfm-widget-checkbox')) {
                autoSaveWidgetSettings();
            }
        });
    });
    
    // Auto-save component settings function
    function autoSaveComponentSettings() {
        const form = document.querySelector('.amfm-component-settings-form');
        if (!form) return;
        
        const formData = new FormData(form);
        formData.append('action', 'amfm_component_settings_update');
        
        // Show subtle saving indicator
        showSavingIndicator('components');
        
        fetch(amfm_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideSavingIndicator('components');
            if (data.success) {
                // Silently saved - no need for intrusive notifications
            } else {
                showNotice('Failed to save component settings: ' + (data.data || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error saving component settings:', error);
            hideSavingIndicator('components');
            showNotice('Failed to save component settings. Please try again.', 'error');
        });
    }
    
    // Auto-save widget settings function  
    function autoSaveWidgetSettings() {
        const form = document.querySelector('.amfm-elementor-widgets-form');
        if (!form) return;
        
        const formData = new FormData(form);
        formData.append('action', 'amfm_elementor_widgets_update');
        
        // Show subtle saving indicator
        showSavingIndicator('widgets');
        
        fetch(amfm_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideSavingIndicator('widgets');
            if (data.success) {
                // Silently saved - no need for intrusive notifications
            } else {
                showNotice('Failed to save widget settings: ' + (data.data || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error saving widget settings:', error);
            hideSavingIndicator('widgets');
            showNotice('Failed to save widget settings. Please try again.', 'error');
        });
    }
    
    // Show subtle saving indicator
    function showSavingIndicator(type) {
        let indicator = document.querySelector('.amfm-saving-indicator-' + type);
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'amfm-saving-indicator-' + type;
            indicator.style.cssText = `
                position: fixed;
                top: 32px;
                right: 20px;
                background: #667eea;
                color: white;
                padding: 8px 16px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 600;
                z-index: 100000;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            indicator.textContent = 'Saving...';
            document.body.appendChild(indicator);
        }
        
        // Fade in
        setTimeout(() => {
            indicator.style.opacity = '1';
        }, 10);
    }
    
    // Hide saving indicator
    function hideSavingIndicator(type) {
        const indicator = document.querySelector('.amfm-saving-indicator-' + type);
        if (indicator) {
            indicator.style.opacity = '0';
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.parentNode.removeChild(indicator);
                }
            }, 300);
        }
    }
});