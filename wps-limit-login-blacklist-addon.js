/**
 * WPS Limit Login - Quick Blacklist
 * JavaScript for handling blacklist button clicks
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        /**
         * Handle blacklist button clicks
         */
        $(document).on('click', '.wps-quick-blacklist-btn', function(e) {
            e.preventDefault();
            
            var btn = $(this);
            var ip = btn.data('ip');
            
            // Prevent double-clicking
            if (btn.hasClass('disabled')) {
                return false;
            }
            
            // Disable button during AJAX
            btn.addClass('disabled');
            
            // Show loading state
            var originalText = btn.text();
            btn.text('Processing...');
            
            // Send AJAX request
            $.ajax({
                url: wpsQuickBlacklist.ajax_url,
                type: 'POST',
                data: {
                    action: 'wps_quick_blacklist',
                    nonce: wpsQuickBlacklist.nonce,
                    ip: ip
                },
                success: function(response) {
                    if (response.success) {
                        // Show admin notice
                        showAdminNotice(response.data.message);
                        
                        // Save reference to row BEFORE modifying DOM
                        var row = btn.closest('tr');
                        
                        // Update button state
                        btn.parent().removeClass('wps_blacklist').addClass('wps_blacklisted');
                        btn.parent().html('<span>Blacklisted</span>');
                        
                        // Remove the row after a delay (since log is cleared)
                        setTimeout(function() {
                            row.fadeOut(400, function() {
                                $(this).remove();
                                
                                // Check if table is now empty
                                var remainingRows = $('.wps-limit-login-log table tr').not('.hide-mobile').length;
                                if (remainingRows === 0) {
                                    // Reload page to show "No lockouts yet" message
                                    location.reload();
                                }
                            });
                        }, 1000);
                        
                    } else {
                        // Show error message
                        showAdminNotice(response.data.message || 'Error occurred', 'error');
                        
                        // Re-enable button
                        btn.removeClass('disabled');
                        btn.text(originalText);
                    }
                },
                error: function() {
                    // Show connection error
                    showAdminNotice('Connection error', 'error');
                    
                    // Re-enable button
                    btn.removeClass('disabled');
                    btn.text(originalText);
                }
            });
            
            return false;
        });
        
        /**
         * Show admin notice message
         */
        function showAdminNotice(message, type) {
            type = type || 'updated';
            
            // Remove existing notice if any
            $('#wps-blacklist-notice-inline').remove();
            
            // Create notice
            var notice = $('<div id="wps-blacklist-notice-inline" class="' + type + ' fade"><p>' + message + '</p></div>');
            
            // Insert after h1.wps-title
            var title = $('.wps-title');
            if (title.length > 0) {
                notice.insertAfter(title);
            } else {
                // Fallback: insert at top of wrap
                $('.wrap').prepend(notice);
            }
            
            // Fade in
            notice.hide().fadeIn();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    });
    
})(jQuery);
