/**
 * ThirtyBees PayPal Checkout - Frontend JavaScript
 * 
 * Initializes PayPal Smart Buttons using official SDK
 * @see https://developer.paypal.com/sdk/js/reference/
 */

function initTbPaypal(buttonContainerId, apiUrl)
{
    // Check if PayPal SDK is loaded
    if (typeof window['paypal'] === 'undefined') {
        console.error('PayPal SDK not loaded');
        return;
    }

    const paypal = window['paypal'];
    
    // Check if button container exists
    const buttonContainer = document.getElementById(buttonContainerId);
    if (!buttonContainer) {
        console.error('Button container not found')
        return;
    }

    const loadingDiv = document.getElementById('paypal-loading');
    const errorDiv = document.getElementById('paypal-error');

    /**
     * Show error message
     */
    function showError(message) {
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
    }

    /**
     * Show loading indicator
     */
    function showLoading() {
        if (loadingDiv) {
            loadingDiv.style.display = 'block';
        }
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    /**
     * Hide loading indicator
     */
    function hideLoading() {
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
    }

    // Initialize PayPal Smart Buttons
    paypal.Buttons({
        /**
         * Set up the transaction
         */
        createOrder: function(data, actions) {
            showLoading();
            
            // Call our validation controller to create PayPal order
            return fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'createOrder'
                })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(response) {
                hideLoading();

                if (response.status === 'error') {
                    showError(response.error);
                    throw new Error(response.error);
                }
                
                return response.data.id;
            })
            .catch(function(error) {
                hideLoading();
                showError('Failed to create PayPal order. Please try again.');
                console.error('PayPal order creation error:', error);
            });
        },
        
        /**
         * Finalize the transaction after payer approval
         */
        onApprove: function(data, actions) {
            showLoading();
            return fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'approveOrder',
                    id: data.orderID
                })
            })
                .then(function(response) {
                    return response.json();
                })
                .then(function(response) {
                    if (response.status === 'error') {
                        showError(response.error);
                        throw new Error(response.error);
                    }
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        showError('Redirect url not provided');
                    }
                })
                .catch(function(error) {
                    hideLoading();
                    showError('Failed to approve PayPal order');
                    console.error('Failed to approve PayPal order:', error);
                });
        },
        
        /**
         * Handle errors
         */
        onError: function(err) {
            hideLoading();
            showError('An error occurred during payment. Please try again.');
            console.error('PayPal error:', err);
        },
        
        /**
         * Handle cancellation
         */
        onCancel: function(data) {
            hideLoading();
            showError('Payment was cancelled. Please try again when you are ready.');
        },
        
        /**
         * Button styling (using PayPal's official styling options)
         */
        style: {
            layout: 'vertical',
            color: 'gold',
            shape: 'rect',
            label: 'paypal',
            height: 45
        }
        
    }).render('#paypal-button-container');
}


