jQuery(document).ready(function($) {
    // Prayer Global historical stats form handler
    $('#pg-historical-stats-form').on('submit', function(e) {
        e.preventDefault();
        
        const startDate = $('#pg_start_date').val();
        const endDate = $('#pg_end_date').val();
        
        // Validate dates
        if (!startDate || !endDate) {
            showError('Please select both start and end dates.');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            showError('Start date must be before or equal to end date.');
            return;
        }
        
        // Calculate number of days
        const daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1;
        
        if (daysDiff > 365) {
            if (!confirm(`You are about to process ${daysDiff} days of data. This may take a very long time (${Math.ceil(daysDiff * 0.5 / 60)} minutes or more). Continue?`)) {
                return;
            }
        }
        
        // Show loading state
        $('#run-pg-stats').prop('disabled', true);
        $('#pg-loading').show();
        $('#pg-results').hide();
        $('#pg-error').hide();
        
        // Make AJAX request
        $.ajax({
            url: pgHistoricalStats.ajaxurl,
            type: 'POST',
            data: {
                action: 'run_pg_historical_stats',
                start_date: startDate,
                end_date: endDate,
                _ajax_nonce: pgHistoricalStats.nonce
            },
            timeout: 0, // No timeout for long-running requests
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (data.error) {
                        showError(data.error);
                    } else {
                        showResults(data);
                    }
                } catch (error) {
                    showError('Failed to parse response: ' + error.message);
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    showError('Request timed out. Check the server logs for progress.');
                } else {
                    showError('AJAX request failed: ' + error);
                }
            },
            complete: function() {
                $('#run-pg-stats').prop('disabled', false);
                $('#pg-loading').hide();
            }
        });
    });
    
    function showResults(data) {
        let html = '<div class="notice notice-success"><p><strong>Processing Complete!</strong></p></div>';
        
        html += '<div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">';
        html += '<h4>Summary</h4>';
        html += '<ul>';
        html += '<li><strong>Total Dates Processed:</strong> ' + data.total_dates + '</li>';
        html += '<li><strong>Successfully Sent:</strong> ' + data.processed + '</li>';
        html += '<li><strong>Errors:</strong> ' + data.errors + '</li>';
        html += '</ul>';
        html += '</div>';
        
        if (data.results && data.results.length > 0) {
            html += '<h4>Detailed Results</h4>';
            html += '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; background: white;">';
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>Date</th><th>Status</th><th>Details</th></tr></thead>';
            html += '<tbody>';
            
            data.results.forEach(function(result) {
                const statusClass = result.status === 'success' ? 'success' : 'error';
                const statusText = result.status === 'success' ? 'Success' : 'Error';
                
                let details = '';
                if (result.status === 'success' && result.metrics) {
                    details = `Warriors: ${result.metrics.prayer_warriors}, Minutes: ${result.metrics.minutes_of_prayer}, Prayers: ${result.metrics.total_prayers}, Laps: ${result.metrics.laps_completed}, Locations: ${result.metrics.locations_covered_by_laps}`;
                } else if (result.message) {
                    details = result.message;
                }
                
                html += `<tr><td>${result.date}</td><td><span style="color: ${statusClass === 'success' ? 'green' : 'red'}">${statusText}</span></td><td>${details}</td></tr>`;
            });
            
            html += '</tbody></table>';
            html += '</div>';
        }
        
        if (data.errors > 0) {
            html += '<div class="notice notice-warning"><p><strong>Note:</strong> Some dates had errors. Check the WordPress error log for detailed error messages.</p></div>';
        }
        
        $('#pg-results-content').html(html);
        $('#pg-results').show();
    }
    
    function showError(message) {
        $('#pg-error-content').text(message);
        $('#pg-error').show();
        $('#pg-results').hide();
    }
});