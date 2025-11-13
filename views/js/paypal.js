(function($) {
    'use strict';
    
    var PayPalCheckout = {
        
        /**
         * Initialize PayPal Checkout
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind Event Handlers
         */
        bindEvents: function() {
            // Payment option click
            $(document).on('click', '.paypal-checkout', function(e) {
                // Show loading state
                PayPalCheckout.showLoadingState($(this));
            });
            
            // EU payment option click
            $(document).on('click', '.paypal-eu-link', function(e) {
                PayPalCheckout.showLoadingState($(this));
            });
        },
        
        /**
         * Show Loading State
         */
        showLoadingState: function($element) {
            var originalText = $element.html();
            
            $element.data('original-text', originalText);
            $element.html(
                '<i class="icon-spinner icon-spin"></i> ' +
                'Redirecting to PayPal...'
            );
            $element.css('pointer-events', 'none');
            
            // Restore original state after 10 seconds (fallback)
            setTimeout(function() {
                if ($element.data('original-text')) {
                    $element.html($element.data('original-text'));
                    $element.css('pointer-events', 'auto');
                }
            }, 10000);
        },
        
        /**
         * Show Error Message
         */
        showError: function(message) {
            var errorHtml = '<div class="alert alert-danger paypal-error" style="margin: 10px 0;">';
            errorHtml += '<button type="button" class="close" data-dismiss="alert">&times;</button>';
            errorHtml += '<strong>PayPal Error:</strong> ' + message;
            errorHtml += '</div>';
            
            $('.payment_module').prepend(errorHtml);
            
            // Auto-dismiss after 10 seconds
            setTimeout(function() {
                $('.paypal-error').fadeOut(function() {
                    $(this).remove();
                });
            }, 10000);
        },
        
        /**
         * Log Debug Message
         */
        debug: function(message, data) {
            if (window.console && typeof console.log === 'function') {
                console.log('[PayPal Debug]', message, data || '');
            }
        }
    };
    
    /**
     * Initialize on Document Ready
     */
    $(document).ready(function() {
        PayPalCheckout.init();
    });
    
    // Expose to global scope if needed
    window.PayPalCheckout = PayPalCheckout;
    
})(jQuery);