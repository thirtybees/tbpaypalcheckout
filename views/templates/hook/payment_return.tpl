{**
 * Payment Return Template
 * Displayed on order confirmation page
 *}

{if $order->getCurrentState() == Configuration::get('PS_OS_PAYMENT') 
    || $order->getCurrentState() == Configuration::get('TBPAYPAL_OS_AUTHORIZED')}
    
    <div class="box tbpaypal-confirmation">
        <h3>{l s='Payment Confirmation' mod='tbpaypalcheckout'}</h3>
        
        <p>
            <strong>{l s='Your PayPal payment has been received.' mod='tbpaypalcheckout'}</strong>
        </p>
        
        <p>
            {l s='Order reference:' mod='tbpaypalcheckout'} <strong>{$order->reference}</strong><br>
            {l s='Total amount:' mod='tbpaypalcheckout'} <strong>{displayPrice price=$order->total_paid currency=$order->id_currency}</strong>
        </p>
        
        <p>
            {l s='An email has been sent with your order details.' mod='tbpaypalcheckout'}
        </p>
        
        <p class="tbpaypal-trademark">
            <small>{l s='PayPal is a registered trademark of PayPal, Inc.' mod='tbpaypalcheckout'}</small>
        </p>
    </div>
    
{else}
    
    <div class="box tbpaypal-error">
        <h3>{l s='Payment Error' mod='tbpaypalcheckout'}</h3>
        
        <p>
            {l s='There was an issue processing your PayPal payment.' mod='tbpaypalcheckout'}
        </p>
        
        <p>
            {l s='Please contact our support team for assistance.' mod='tbpaypalcheckout'}
        </p>
    </div>
    
{/if}