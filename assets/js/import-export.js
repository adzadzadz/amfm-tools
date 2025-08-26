/**
 * Import/Export Page JavaScript
 * Simplified version without drawer functionality
 */

// Initialize form handlers
function initializeFormHandlers() {
    const $ = window.jQuery;
    if (!$) {
        console.error('jQuery not available');
        return;
    }

    $(() => {
        // Toggle post data selection
        $('input[name="export_options[]"][value="post_data"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.amfm-post-data-selection').show();
            } else {
                $('.amfm-post-data-selection').hide();
            }
        });

        // Toggle specific post columns
        $('input[name="post_data_selection"]').off('change').on('change', function() {
            if ($(this).val() === 'selected') {
                $('.amfm-specific-post-columns').show();
            } else {
                $('.amfm-specific-post-columns').hide();
            }
        });

        // Toggle taxonomy selection
        $('input[name="export_options[]"][value="taxonomies"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.amfm-taxonomy-selection').show();
            } else {
                $('.amfm-taxonomy-selection').hide();
            }
        });

        // Toggle specific taxonomies
        $('input[name="taxonomy_selection"]').off('change').on('change', function() {
            if ($(this).val() === 'selected') {
                $('.amfm-specific-taxonomies').show();
            } else {
                $('.amfm-specific-taxonomies').hide();
            }
        });

        // Toggle ACF selection
        $('input[name="export_options[]"][value="acf_fields"]').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.amfm-acf-selection').show();
            } else {
                $('.amfm-acf-selection').hide();
            }
        });

        // Toggle specific ACF groups
        $('input[name="acf_selection"]').off('change').on('change', function() {
            if ($(this).val() === 'selected') {
                $('.amfm-specific-acf-groups').show();
            } else {
                $('.amfm-specific-acf-groups').hide();
            }
        });

        // Show/hide export options based on post type
        $('#export_post_type').off('change').on('change', function() {
            const postType = $(this).val();
            
            // Show or hide export options section
            if (postType) {
                $('.amfm-export-options').show();
            } else {
                $('.amfm-export-options').hide();
                $('.amfm-post-data-selection').hide();
                $('.amfm-taxonomy-selection').hide();
                $('.amfm-acf-selection').hide();
            }
        });

        // File upload handling
        const fileInput = $('#csv_file');
        const fileWrapper = $('.amfm-file-upload-wrapper');
        const fileDisplay = $('.amfm-file-upload-display');
        const filePlaceholder = $('.amfm-file-placeholder');
        const fileSelected = $('.amfm-file-selected');

        // Handle file selection
        fileInput.on('change', function() {
            const file = this.files[0];
            if (file) {
                filePlaceholder.hide();
                fileSelected.text(`Selected: ${file.name}`).show();
                fileWrapper.addClass('file-selected');
            } else {
                filePlaceholder.show();
                fileSelected.hide();
                fileWrapper.removeClass('file-selected');
            }
        });

        // Drag and drop functionality on the display label
        fileDisplay.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileWrapper.addClass('dragover');
        });

        fileDisplay.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileWrapper.removeClass('dragover');
        });

        fileDisplay.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileWrapper.removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type === 'text/csv' || file.name.endsWith('.csv')) {
                    // Manually set the files to the input
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput[0].files = dt.files;
                    
                    // Trigger change event
                    fileInput.trigger('change');
                }
            }
        });


        // AJAX import form submission - NEW BATCHED VERSION
        $('#amfm-import-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = $('#amfm-import-submit');
            const resultsSection = $('#amfm-import-results');
            const resultsContent = $('#amfm-import-results-content');
            
            // Show loading state
            submitButton.prop('disabled', true).text('Loading CSV...');
            resultsSection.show();
            resultsContent.html('<div class="amfm-loading"><div class="amfm-loading-spinner"></div>Reading CSV file...</div>');
            
            // First, get CSV preview to show table
            formData.append('action', 'amfm_csv_preview');
            
            $.ajax({
                url: window.amfmData?.ajaxUrl || ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        console.log('CSV preview loaded:', response.data);
                        displayCsvTable(response.data, resultsContent);
                        startBatchImport(response.data, resultsContent);
                        submitButton.text('Import Data');
                    } else {
                        resultsSection.removeClass('amfm-import-success').addClass('amfm-import-error');
                        resultsContent.html('<p><strong>Preview Failed:</strong> ' + (response.data || 'Unknown error occurred.') + '</p>');
                        submitButton.prop('disabled', false).text('Import Data');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Preview Error:', {status, error, xhr});
                    resultsSection.removeClass('amfm-import-success').addClass('amfm-import-error');
                    resultsContent.html('<p><strong>Preview Failed:</strong> ' + error + '</p>');
                    submitButton.prop('disabled', false).text('Import Data');
                }
            });
        });
    });
}

// Display CSV table with progress status
function displayCsvTable(csvData, container) {
    const $ = window.jQuery; // Ensure jQuery is available
    if (!$) {
        console.error('jQuery not available for displayCsvTable');
        return;
    }
    
    const headers = csvData.headers;
    const rows = csvData.rows;
    
    let html = '<div class="amfm-csv-preview">';
    html += '<h3>CSV Preview (' + rows.length + ' rows)</h3>';
    html += '<div class="amfm-csv-table-wrapper" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px;">';
    html += '<table class="amfm-csv-table" style="width: 100%; border-collapse: collapse; font-size: 12px;">';
    
    // Headers
    html += '<thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">';
    html += '<tr>';
    html += '<th style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Status</th>';
    headers.forEach(header => {
        html += '<th style="padding: 8px; border: 1px solid #ddd; font-weight: bold; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">' + header + '</th>';
    });
    html += '<th style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Post Title</th>';
    html += '</tr>';
    html += '</thead>';
    
    // Rows
    html += '<tbody>';
    rows.forEach(function(row) {
        html += '<tr data-row-number="' + row.row_number + '" data-post-id="' + row.post_id + '">';
        html += '<td class="status-cell" style="padding: 8px; border: 1px solid #ddd; width: 80px; text-align: center;">';
        html += '<span class="status-badge status-pending" style="padding: 2px 8px; border-radius: 12px; font-size: 11px; background: #ffc107; color: #000;">Pending</span>';
        html += '</td>';
        
        headers.forEach(header => {
            const cellValue = row.data[header] || '';
            html += '<td style="padding: 8px; border: 1px solid #ddd; max-width: 150px; overflow: hidden; text-overflow: ellipsis;" title="' + cellValue + '">' + cellValue + '</td>';
        });
        
        html += '<td style="padding: 8px; border: 1px solid #ddd; max-width: 200px; overflow: hidden; text-overflow: ellipsis;">' + row.post_title + '</td>';
        html += '</tr>';
    });
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    html += '</div>';
    
    container.html(html);
}

// Start batch import process
function startBatchImport(csvData, container) {
    const $ = window.jQuery; // Ensure jQuery is available
    if (!$) {
        console.error('jQuery not available for batch import');
        return;
    }
    
    const batchSize = 10;
    const rows = csvData.rows;
    const headers = csvData.headers;
    let currentBatch = 0;
    let totalBatches = Math.ceil(rows.length / batchSize);
    let processedRows = 0;
    let successCount = 0;
    let errorCount = 0;
    let skippedCount = 0;
    let importStopped = false;
    
    console.log('Starting batch import:', {
        totalRows: rows.length,
        batchSize: batchSize,
        totalBatches: totalBatches
    });
    
    // Add progress summary above table
    const progressHtml = '<div class="amfm-import-progress" style="margin-bottom: 20px; padding: 15px; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #2196F3;">' +
        '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">' +
        '<h4 style="margin: 0;">Import Progress</h4>' +
        '<button id="amfm-stop-import" class="button button-secondary" style="background: #dc3545; color: white; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer;">Stop Import</button>' +
        '</div>' +
        '<div class="progress-stats">' +
        '<span class="processed-count">Processed: <strong>0</strong> / <strong>' + rows.length + '</strong></span> | ' +
        '<span class="success-count">Updated: <strong>0</strong></span> | ' +
        '<span class="skipped-count">Skipped: <strong>0</strong></span> | ' +
        '<span class="error-count">Errors: <strong>0</strong></span>' +
        '</div>' +
        '<div class="progress-bar-wrapper" style="margin-top: 10px; background: #e0e0e0; border-radius: 10px; height: 20px;">' +
        '<div class="progress-bar" style="background: linear-gradient(90deg, #4CAF50, #2196F3); height: 100%; border-radius: 10px; width: 0%; transition: width 0.3s ease;"></div>' +
        '</div>' +
        '</div>';
    
    container.prepend(progressHtml);
    
    // Add stop button event handler
    container.find('#amfm-stop-import').on('click', function() {
        importStopped = true;
        $(this).prop('disabled', true).text('Stopping...');
        console.log('Import stopped by user');
        
        // Show stopped message
        const stoppedHtml = '<div class="amfm-stopped-message" style="margin-top: 15px; padding: 12px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; color: #856404;">' +
            '<strong>⏹️ Import Stopped</strong> - Import was stopped by user. Processed <strong>' + processedRows + '</strong> rows before stopping.' +
            '</div>';
        container.find('.amfm-import-progress').after(stoppedHtml);
        
        // Re-enable the import button
        $('#amfm-import-submit').prop('disabled', false);
    });
    
    function processBatch() {
        // Check if import was stopped
        if (importStopped) {
            console.log('Import stopped, not processing batch', currentBatch + 1);
            return;
        }
        
        const startIdx = currentBatch * batchSize;
        const endIdx = Math.min(startIdx + batchSize, rows.length);
        const batchRows = rows.slice(startIdx, endIdx);
        
        console.log('Processing batch', currentBatch + 1, 'of', totalBatches, '(rows', startIdx + 1, '-', endIdx, ')');
        
        // Update status to "Saving" for current batch
        batchRows.forEach(row => {
            const rowElement = container.find('tr[data-row-number="' + row.row_number + '"]');
            const statusCell = rowElement.find('.status-cell .status-badge');
            statusCell.removeClass('status-pending').addClass('status-saving')
                .css({background: '#17a2b8', color: 'white'})
                .text('Saving...');
        });
        
        // Send batch to server
        $.ajax({
            url: window.amfmData?.ajaxUrl || window.ajaxurl,
            type: 'POST',
            data: {
                action: 'amfm_csv_import_batch',
                amfm_csv_import_nonce: $('input[name="amfm_csv_import_nonce"]').val(),
                batch_data: JSON.stringify({
                    headers: headers,
                    rows: batchRows
                })
            },
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    const results = response.data;
                    console.log('Batch processed successfully:', results);
                    
                    // Update status for each processed row
                    results.processed_rows.forEach(processedRow => {
                        const rowElement = container.find('tr[data-row-number="' + processedRow.row_number + '"]');
                        const statusCell = rowElement.find('.status-cell .status-badge');
                        
                        if (processedRow.status === 'completed') {
                            statusCell.removeClass('status-saving').addClass('status-completed')
                                .css({background: '#28a745', color: 'white'})
                                .text('Updated');
                            successCount++;
                        } else if (processedRow.status === 'skipped') {
                            statusCell.removeClass('status-saving').addClass('status-skipped')
                                .css({background: '#6c757d', color: 'white'})
                                .text('Skipped')
                                .attr('title', processedRow.message);
                            skippedCount++;
                        } else {
                            statusCell.removeClass('status-saving').addClass('status-error')
                                .css({background: '#dc3545', color: 'white'})
                                .text('Error')
                                .attr('title', processedRow.message);
                            errorCount++;
                        }
                        processedRows++;
                    });
                    
                    // Update progress
                    updateProgress();
                    
                    // Process next batch
                    currentBatch++;
                    if (currentBatch < totalBatches && !importStopped) {
                        setTimeout(processBatch, 500); // Small delay between batches
                    } else {
                        onImportComplete();
                    }
                    
                } else {
                    console.error('Batch failed:', response.data);
                    // Mark all rows in this batch as error
                    batchRows.forEach(row => {
                        const rowElement = container.find('tr[data-row-number="' + row.row_number + '"]');
                        const statusCell = rowElement.find('.status-cell .status-badge');
                        statusCell.removeClass('status-saving').addClass('status-error')
                            .css({background: '#dc3545', color: 'white'})
                            .text('Error')
                            .attr('title', response.data || 'Batch processing failed');
                        errorCount++;
                        processedRows++;
                    });
                    
                    updateProgress();
                    currentBatch++;
                    if (currentBatch < totalBatches && !importStopped) {
                        setTimeout(processBatch, 500);
                    } else {
                        onImportComplete();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Batch AJAX error:', {status, error, xhr});
                // Mark all rows in this batch as error
                batchRows.forEach(row => {
                    const rowElement = container.find('tr[data-row-number="' + row.row_number + '"]');
                    const statusCell = rowElement.find('.status-cell .status-badge');
                    statusCell.removeClass('status-saving').addClass('status-error')
                        .css({background: '#dc3545', color: 'white'})
                        .text('Error')
                        .attr('title', 'Network error: ' + error);
                    errorCount++;
                    processedRows++;
                });
                
                updateProgress();
                currentBatch++;
                if (currentBatch < totalBatches && !importStopped) {
                    setTimeout(processBatch, 500);
                } else {
                    onImportComplete();
                }
            }
        });
    }
    
    function updateProgress() {
        const progressPercent = Math.round((processedRows / rows.length) * 100);
        container.find('.processed-count strong').first().text(processedRows);
        container.find('.success-count strong').text(successCount);
        container.find('.skipped-count strong').text(skippedCount);
        container.find('.error-count strong').text(errorCount);
        container.find('.progress-bar').css('width', progressPercent + '%');
    }
    
    function onImportComplete() {
        console.log('Import completed:', {processedRows, successCount, errorCount, importStopped});
        $('#amfm-import-submit').prop('disabled', false);
        
        // Hide stop button since import is done
        container.find('#amfm-stop-import').hide();
        
        // Show completion message - different message if stopped
        let completionHtml;
        if (importStopped) {
            completionHtml = '<div class="amfm-completion-message" style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; color: #856404;">' +
                '<h4 style="margin: 0 0 10px 0;">⏹️ Import Stopped</h4>' +
                '<p style="margin: 0;">Import was stopped by user. Processed <strong>' + processedRows + '</strong> rows with <strong>' + successCount + '</strong> updates, <strong>' + skippedCount + '</strong> skipped (same values), and <strong>' + errorCount + '</strong> errors before stopping.</p>' +
                '</div>';
        } else {
            completionHtml = '<div class="amfm-completion-message" style="margin-top: 20px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724;">' +
                '<h4 style="margin: 0 0 10px 0;">✅ Import Completed!</h4>' +
                '<p style="margin: 0;">Successfully processed <strong>' + processedRows + '</strong> rows with <strong>' + successCount + '</strong> updates, <strong>' + skippedCount + '</strong> skipped (same values), and <strong>' + errorCount + '</strong> errors.</p>' +
                '</div>';
        }
        
        container.find('.amfm-csv-preview').after(completionHtml);
    }
    
    // Start processing
    setTimeout(processBatch, 1000); // Small delay to let table render
}

// Format import results for display (legacy function - kept for compatibility)
function formatImportResults(data) {
    if (typeof data === 'string') {
        return '<p>' + data + '</p>';
    }
    
    let html = '<div class="amfm-import-summary">';
    
    if (data.total_processed) {
        html += '<p><strong>Import completed successfully!</strong></p>';
        html += '<p>Total records processed: <strong>' + data.total_processed + '</strong></p>';
        
        if (data.updated) {
            html += '<p>Posts updated: <strong>' + data.updated + '</strong></p>';
        }
        if (data.errors && data.errors.length > 0) {
            html += '<p>Errors: <strong>' + data.errors.length + '</strong></p>';
            html += '<div class="amfm-import-errors">';
            html += '<h4>Error Details:</h4>';
            html += '<ul>';
            data.errors.forEach(function(error) {
                html += '<li>' + error + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
    } else {
        html += '<p>' + (data.message || 'Import completed.') + '</p>';
    }
    
    html += '</div>';
    return html;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form handlers immediately since forms are already in the DOM
    initializeFormHandlers();
});