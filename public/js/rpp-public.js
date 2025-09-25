(function($) {
    'use strict';

    $(document).ready(function() {
        // Partner registration form handler
        $('#rpp-partner-registration-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $message = $('#rpp-form-message');
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.text();
            
            // Basic client-side validation
            if (!validateRegistrationForm()) {
                return;
            }
            
            $button.prop('disabled', true).text('Submitting...');
            $message.hide();
            
            $.ajax({
                url: rpp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_partner_registration',
                    nonce: rpp_ajax.nonce,
                    website: $('#website').val(),
                    social_media: $('#social_media').val(),
                    experience: $('#experience').val(),
                    audience: $('#audience').val(),
                    motivation: $('#motivation').val()
                },
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('rpp-error').addClass('rpp-success')
                               .html(response.data).show();
                        $form[0].reset();
                        
                        // Redirect after 3 seconds if dashboard URL exists
                        var dashboardUrl = $form.data('dashboard-url');
                        if (dashboardUrl) {
                            setTimeout(function() {
                                window.location.href = dashboardUrl;
                            }, 3000);
                        }
                    } else {
                        $message.removeClass('rpp-success').addClass('rpp-error')
                               .html(response.data).show();
                    }
                },
                error: function() {
                    $message.removeClass('rpp-success').addClass('rpp-error')
                           .html('An error occurred. Please try again.').show();
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                    
                    // Scroll to message
                    if ($message.is(':visible')) {
                        $('html, body').animate({
                            scrollTop: $message.offset().top - 100
                        }, 500);
                    }
                }
            });
        });
        
        // Partner dashboard functionality
        initializeDashboard();
        
        // Copy referral link functionality
        window.copyReferralLink = function() {
            var linkInput = document.getElementById('referral-link');
            if (linkInput) {
                linkInput.select();
                linkInput.setSelectionRange(0, 99999);
                
                try {
                    navigator.clipboard.writeText(linkInput.value).then(function() {
                        showCopyFeedback('Copied!');
                    });
                } catch (err) {
                    // Fallback for older browsers
                    document.execCommand('copy');
                    showCopyFeedback('Copied!');
                }
            }
        };
        
        // Generate custom referral link
        window.generateCustomLink = function() {
            var customUrl = document.getElementById('custom-page').value;
            var partnerCode = $('#referral-link').data('partner-code');
            
            if (!customUrl) {
                alert('Please enter a URL');
                return;
            }
            
            if (!isValidUrl(customUrl)) {
                alert('Please enter a valid URL');
                return;
            }
            
            var separator = customUrl.includes('?') ? '&' : '?';
            var customLink = customUrl + separator + 'ref=' + partnerCode;
            
            document.getElementById('referral-link').value = customLink;
        };
        
        // Statistics refresh
        $('.rpp-refresh-stats').on('click', function() {
            var button = $(this);
            var originalText = button.text();
            
            button.prop('disabled', true).text('Refreshing...');
            
            $.ajax({
                url: rpp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_refresh_stats',
                    nonce: rpp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateDashboardStats(response.data);
                        showNotice('Statistics refreshed!', 'success');
                    } else {
                        showNotice('Failed to refresh statistics.', 'error');
                    }
                },
                error: function() {
                    showNotice('An error occurred while refreshing statistics.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Load more commissions
        $('.rpp-load-more-commissions').on('click', function() {
            var button = $(this);
            var page = parseInt(button.data('page')) + 1;
            var originalText = button.text();
            
            button.prop('disabled', true).text('Loading...');
            
            $.ajax({
                url: rpp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_load_more_commissions',
                    page: page,
                    nonce: rpp_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.commissions.length > 0) {
                        $('.rpp-commissions-tbody').append(response.data.html);
                        button.data('page', page);
                        
                        if (!response.data.has_more) {
                            button.hide();
                        }
                    } else {
                        button.hide();
                    }
                },
                error: function() {
                    showNotice('Failed to load more commissions.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
    });
    
    // Initialize dashboard specific functionality
    function initializeDashboard() {
        // Add click tracking to external links
        $('a[href^="http"]:not([href*="' + window.location.hostname + '"])').on('click', function() {
            var link = $(this).attr('href');
            
            // Track external link click
            $.ajax({
                url: rpp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rpp_track_external_click',
                    link: link,
                    nonce: rpp_ajax.nonce
                }
            });
        });
        
        // Initialize tooltips
        $('.rpp-tooltip').on('mouseenter', function() {
            var tooltip = $(this).data('tooltip');
            if (tooltip) {
                $(this).attr('title', tooltip);
            }
        });
        
        // Performance metrics animation
        animateCounters();
    }
    
    // Validate registration form
    function validateRegistrationForm() {
        var isValid = true;
        var errors = [];
        
        // Website validation
        var website = $('#website').val();
        if (!website || !isValidUrl(website)) {
            errors.push('Please enter a valid website URL.');
            isValid = false;
        }
        
        // Experience validation
        var experience = $('#experience').val();
        if (!experience || experience.length < 50) {
            errors.push('Please provide more details about your marketing experience (minimum 50 characters).');
            isValid = false;
        }
        
        // Motivation validation
        var motivation = $('#motivation').val();
        if (!motivation || motivation.length < 30) {
            errors.push('Please tell us more about your motivation (minimum 30 characters).');
            isValid = false;
        }
        
        // Terms acceptance
        if (!$('input[name="terms"]').is(':checked')) {
            errors.push('Please accept the terms and conditions.');
            isValid = false;
        }
        
        if (!isValid) {
            var errorMessage = '<ul><li>' + errors.join('</li><li>') + '</li></ul>';
            $('#rpp-form-message').removeClass('rpp-success').addClass('rpp-error')
                                  .html(errorMessage).show();
            
            $('html, body').animate({
                scrollTop: $('#rpp-form-message').offset().top - 100
            }, 500);
        }
        
        return isValid;
    }
    
    // URL validation helper
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Show copy feedback
    function showCopyFeedback(message) {
        var button = event.target;
        var originalText = button.textContent;
        
        button.textContent = message;
        button.style.backgroundColor = '#28a745';
        
        setTimeout(function() {
            button.textContent = originalText;
            button.style.backgroundColor = '';
        }, 2000);
    }
    
    // Show notification
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'rpp-success' : 'rpp-error';
        var notice = $('<div class="rpp-message ' + noticeClass + '">' + message + '</div>');
        
        $('.rpp-partner-dashboard').prepend(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Update dashboard statistics
    function updateDashboardStats(data) {
        $('.rpp-stat-value').each(function() {
            var stat = $(this).data('stat');
            if (data[stat] !== undefined) {
                animateValue(this, parseInt($(this).text().replace(/[^0-9]/g, '')), data[stat], 1000);
            }
        });
    }
    
    // Animate counter values
    function animateCounters() {
        $('.rpp-stat-value').each(function() {
            var $this = $(this);
            var countTo = parseInt($this.text().replace(/[^0-9]/g, ''));
            
            if (countTo > 0) {
                $this.text('0');
                animateValue(this, 0, countTo, 2000);
            }
        });
    }
    
    // Animate numeric value
    function animateValue(element, start, end, duration) {
        var startTime = performance.now();
        var $element = $(element);
        var prefix = $element.data('prefix') || '';
        var suffix = $element.data('suffix') || '';
        
        function updateValue(currentTime) {
            var elapsed = currentTime - startTime;
            var progress = Math.min(elapsed / duration, 1);
            var currentValue = Math.floor(start + (end - start) * progress);
            
            $element.text(prefix + currentValue.toLocaleString() + suffix);
            
            if (progress < 1) {
                requestAnimationFrame(updateValue);
            }
        }
        
        requestAnimationFrame(updateValue);
    }
    
    // Social sharing functionality
    window.shareReferralLink = function(platform) {
        var link = document.getElementById('referral-link').value;
        var text = 'Check out this amazing opportunity!';
        var url;
        
        switch (platform) {
            case 'facebook':
                url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(link);
                break;
            case 'twitter':
                url = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(link) + '&text=' + encodeURIComponent(text);
                break;
            case 'linkedin':
                url = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(link);
                break;
            case 'email':
                url = 'mailto:?subject=' + encodeURIComponent(text) + '&body=' + encodeURIComponent(text + ' ' + link);
                break;
            default:
                return;
        }
        
        if (platform === 'email') {
            window.location.href = url;
        } else {
            window.open(url, '_blank', 'width=600,height=400');
        }
    };

})(jQuery);