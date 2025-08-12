jQuery(document).ready(function($) {
    $('#dt-historical-stats-form').on('submit', function(e) {
        e.preventDefault();

        const startDate = $('#dt_start_date').val();
        const endDate = $('#dt_end_date').val();

        if (!startDate || !endDate) {
            showError('Please select both start and end dates.');
            return;
        }
        if (new Date(startDate) > new Date(endDate)) {
            showError('Start date must be before or equal to end date.');
            return;
        }

        const daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1;
        if (daysDiff > 365) {
            if (!confirm(`You are about to process ${daysDiff} days of data. This may take a very long time (${Math.ceil(daysDiff * 0.5 / 60)} minutes or more). Continue?`)) {
                return;
            }
        }

        $('#run-dt-stats').prop('disabled', true);
        $('#dt-loading').show();
        $('#dt-results').hide();
        $('#dt-error').hide();

        $.ajax({
            url: dtHistoricalStats.ajaxurl,
            type: 'POST',
            data: {
                action: 'run_dt_historical_stats',
                start_date: startDate,
                end_date: endDate,
                _ajax_nonce: dtHistoricalStats.nonce
            },
            timeout: 0,
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
                $('#run-dt-stats').prop('disabled', false);
                $('#dt-loading').hide();
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
                    details = JSON.stringify(result.metrics);
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

        $('#dt-results-content').html(html);
        $('#dt-results').show();
    }

    function showError(message) {
        $('#dt-error-content').text(message);
        $('#dt-error').show();
        $('#dt-results').hide();
    }

    $('#dt-api-key-form').on('submit', function(e) {
        e.preventDefault();
        const apiKey = $('#dt_api_key').val();
        if (!apiKey) {
            $('#dt-api-key-message').html('<span style="color:red;">Please enter an API key.</span>');
            return;
        }
        $('#dt-save-api-key').prop('disabled', true);
        $('#dt-api-key-loading').show();
        $('#dt-api-key-message').empty();
        $.ajax({
            url: dtHistoricalStats.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_dt_stats_key',
                api_key: apiKey,
                _ajax_nonce: dtHistoricalStats.nonce
            },
            success: function(response) {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data && data.success) {
                    const preview = data.data && data.data.preview ? data.data.preview : '••••';
                    $('#dt-api-key-message').html('<span style="color:green;">API key saved.</span>');
                    if ($('#dt-api-key-status').length) {
                        $('#dt-api-key-status').html('<strong>✅ API key configured:</strong> ' + preview).css('color', 'green');
                    } else {
                        $('<p id="dt-api-key-status" style="color: green;"><strong>✅ API key configured:</strong> ' + preview + '</p>').insertBefore('#dt-api-key-form');
                    }
                    $('#dt_api_key').val('');
                } else {
                    const msg = data && data.data && data.data.message ? data.data.message : 'Failed to save API key';
                    $('#dt-api-key-message').html('<span style="color:red;">' + msg + '</span>');
                }
            },
            error: function() {
                $('#dt-api-key-message').html('<span style="color:red;">Request failed.</span>');
            },
            complete: function() {
                $('#dt-save-api-key').prop('disabled', false);
                $('#dt-api-key-loading').hide();
            }
        });
    });
});


