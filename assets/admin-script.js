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
    });

    // Handle post type selection change to load taxonomies
    const postTypeSelect = document.getElementById('export_post_type');
    if (postTypeSelect) {
        postTypeSelect.addEventListener('change', function() {
            const postType = this.value;
            const exportOptions = document.querySelector('.export-options');
            
            if (postType) {
                // Show export options if post type is selected
                if (exportOptions) {
                    exportOptions.style.display = 'block';
                }
                
                // Load taxonomies for this post type
                loadTaxonomies(postType);
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
        formData.append('nonce', amfmAdmin.exportNonce);
        
        fetch(amfmAdmin.ajaxUrl, {
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
            
            // Collect form data
            const formData = new FormData(this);
            formData.append('action', 'amfm_export_data');
            formData.append('nonce', amfmAdmin.exportNonce);
            
            // Make AJAX request
            fetch(amfmAdmin.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create and download CSV file
                    downloadCSV(data.data.data, data.data.filename);
                    
                    // Show success message
                    showNotice('Export completed successfully! ' + data.data.total + ' posts exported.', 'success');
                } else {
                    showNotice('Export failed: ' + data.data, 'error');
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
});