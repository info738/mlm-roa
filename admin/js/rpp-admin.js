(function($) {
    'use strict';

    $(document).ready(function() {
        // Partner approval/rejection handlers
        $('.approve-partner').on('click', function() {
            var partnerId = $(this).data('partner-id');
            var button = $(this);
            
            if (!confirm('Are you sure you want to approve this partner?')) {
                return;
            }
            
            button.prop('disabled', true).addClass('loading');
            
            $.ajax({
                url: rpp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_approve_partner',
                    partner_id: partnerId,
                    nonce: rpp_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data, 'success');
                        // Update the row
                        var row = button.closest('tr');
                        row.find('.status-badge').removeClass('status-pending').addClass('status-approved').text('Approved');
                        button.parent().html('<span class="approved-text">✓ Approved</span>');
                    } else {
                        showNotice(response.data, 'error');
                        button.prop('disabled', false).removeClass('loading');
                    }
                },
                error: function() {
                    showNotice('An error occurred. Please try again.', 'error');
                    button.prop('disabled', false).removeClass('loading');
                }
            });
        });
        
        $('.reject-partner').on('click', function() {
            var partnerId = $(this).data('partner-id');
            var button = $(this);
            
            if (!confirm('Are you sure you want to reject this partner?')) {
                return;
            }
            
            button.prop('disabled', true).addClass('loading');
            
            $.ajax({
                url: rpp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_reject_partner',
                    partner_id: partnerId,
                    nonce: rpp_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data, 'success');
                        // Update the row
                        var row = button.closest('tr');
                        row.find('.status-badge').removeClass('status-pending').addClass('status-rejected').text('Rejected');
                        button.parent().html('<span class="rejected-text">✗ Rejected</span>');
                    } else {
                        showNotice(response.data, 'error');
                        button.prop('disabled', false).removeClass('loading');
                    }
                },
                error: function() {
                    showNotice('An error occurred. Please try again.', 'error');
                    button.prop('disabled', false).removeClass('loading');
                }
            });
        });
        
        // Commission approval handlers
        $('.approve-commission').on('click', function() {
            var commissionId = $(this).data('commission-id');
            var button = $(this);
            
            button.prop('disabled', true).addClass('loading');
            
            $.ajax({
                url: rpp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_approve_commission',
                    commission_id: commissionId,
                    nonce: rpp_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data, 'success');
                        var row = button.closest('tr');
                        row.find('.status-badge').removeClass('status-pending').addClass('status-approved').text('Approved');
                        button.text('Mark as Paid').removeClass('approve-commission').addClass('pay-commission')
                              .data('commission-id', commissionId).prop('disabled', false).removeClass('loading');
                    } else {
                        showNotice(response.data, 'error');
                        button.prop('disabled', false).removeClass('loading');
                    }
                },
                error: function() {
                    showNotice('An error occurred. Please try again.', 'error');
                    button.prop('disabled', false).removeClass('loading');
                }
            });
        });
        
        // Commission payment handlers
        $(document).on('click', '.pay-commission', function() {
            var commissionId = $(this).data('commission-id');
            var button = $(this);
            
            if (!confirm('Mark this commission as paid?')) {
                return;
            }
            
            button.prop('disabled', true).addClass('loading');
            
            $.ajax({
                url: rpp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_pay_commission',
                    commission_id: commissionId,
                    nonce: rpp_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data, 'success');
                        var row = button.closest('tr');
                        row.find('.status-badge').removeClass('status-approved').addClass('status-paid').text('Paid');
                        button.remove();
                    } else {
                        showNotice(response.data, 'error');
                        button.prop('disabled', false).removeClass('loading');
                    }
                },
                error: function() {
                    showNotice('An error occurred. Please try again.', 'error');
                    button.prop('disabled', false).removeClass('loading');
                }
            });
        });
        
        // Filter handling
        $('#status-filter').on('change', function() {
            var status = $(this).val();
            var url = new URL(window.location.href);
            
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            
            url.searchParams.delete('paged'); // Reset pagination
            window.location.href = url.toString();
        });
        
        // Search handling
        $('input[name="s"]').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                var search = $(this).val();
                var url = new URL(window.location.href);
                
                if (search) {
                    url.searchParams.set('s', search);
                } else {
                    url.searchParams.delete('s');
                }
                
                url.searchParams.delete('paged'); // Reset pagination
                window.location.href = url.toString();
            }
        });
        
        // Bulk actions
        $('.rpp-bulk-action-apply').on('click', function() {
            var action = $('.rpp-bulk-action-select').val();
            var selected = $('.rpp-bulk-checkbox:checked');
            
            if (!action || selected.length === 0) {
                alert('Please select an action and at least one item.');
                return;
            }
            
            if (!confirm('Are you sure you want to perform this bulk action?')) {
                return;
            }
            
            var ids = selected.map(function() {
                return $(this).val();
            }).get();
            
            $.ajax({
                url: rpp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_bulk_action',
                    bulk_action: action,
                    ids: ids,
                    nonce: rpp_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(response.data, 'success');
                        location.reload();
                    } else {
                        showNotice(response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('An error occurred. Please try again.', 'error');
                }
            });
        });
        
        // Select all checkbox
        $('.rpp-select-all').on('change', function() {
            $('.rpp-bulk-checkbox').prop('checked', this.checked);
        });
        
        // Update select all when individual checkboxes change
        $('.rpp-bulk-checkbox').on('change', function() {
            var total = $('.rpp-bulk-checkbox').length;
            var checked = $('.rpp-bulk-checkbox:checked').length;
            $('.rpp-select-all').prop('checked', total === checked);
        });
        
        // Copy to clipboard functionality
        $('.rpp-copy-link').on('click', function() {
            var link = $(this).data('link');
            navigator.clipboard.writeText(link).then(function() {
                showNotice('Link copied to clipboard!', 'success');
            });
        });
        
        // Chart initialization (if Chart.js is loaded)
        if (typeof Chart !== 'undefined') {
            initCharts();
        }
    });
    
    // Utility function to show admin notices
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Add dismiss functionality
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    // Initialize charts
    function initCharts() {
        // Commission trends chart
        var commissionTrendsCanvas = document.getElementById('commission-trends-chart');
        if (commissionTrendsCanvas) {
            var ctx = commissionTrendsCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Commissions',
                        data: [12, 19, 3, 5, 2, 3],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // Partner status distribution chart
        var partnerStatusCanvas = document.getElementById('partner-status-chart');
        if (partnerStatusCanvas) {
            var ctx = partnerStatusCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Approved', 'Pending', 'Rejected'],
                    datasets: [{
                        data: [65, 25, 10],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    }
    
    // Export functionality
    $('.rpp-export-button').on('click', function(e) {
        e.preventDefault();
        
        var exportType = $(this).data('export');
        var button = $(this);
        var originalText = button.text();
        
        button.text('Exporting...').prop('disabled', true);
        
        $.ajax({
            url: rpp_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'rpp_export_data',
                export_type: exportType,
                nonce: rpp_admin_ajax.nonce
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data, status, xhr) {
                // Create download link
                var blob = new Blob([data], { type: xhr.getResponseHeader('Content-Type') });
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = exportType + '_export_' + new Date().toISOString().split('T')[0] + '.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                showNotice('Export completed successfully!', 'success');
            },
            error: function() {
                showNotice('Export failed. Please try again.', 'error');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    });

})(jQuery);