{**
 * Payment Page Template
 * 
 * Displays PayPal Smart Buttons for checkout
 * Uses official PayPal SDK (loaded in hookDisplayHeader)
 *}

<div class="tbpaypal-payment-container">
    <div class="tbpaypal-description">
        <p>{l s='You will be redirected to PayPal to complete your payment securely.' mod='tbpaypalcheckout'}</p>
        <p class="tbpaypal-methods">
            <img src="{$module_dir}views/img/paypal-logo.png" alt="PayPal" class="tbpaypal-logo">
            {if Configuration::get('TBPAYPAL_ENABLE_VENMO')}
                <img src="{$module_dir}views/img/venmo-logo.png" alt="Venmo" class="tbpaypal-logo">
            {/if}
            {if Configuration::get('TBPAYPAL_ENABLE_CARD')}
                <img src="{$module_dir}views/img/card-logos.png" alt="Credit Cards" class="tbpaypal-logo">
            {/if}
        </p>
    </div>
    
    {* PayPal Smart Button Container *}
    <div id="paypal-button-container"></div>
    
    {* Loading indicator *}
    <div id="paypal-loading" style="display:none;">
        <p>{l s='Processing your payment...' mod='tbpaypalcheckout'}</p>
    </div>
    
    {* Error container *}
    <div id="paypal-error" class="alert alert-danger" style="display:none;"></div>
    
    {* Trademark acknowledgment (Contract requirement) *}
    <p class="tbpaypal-trademark">
        <small>{l s='PayPal is a registered trademark of PayPal, Inc.' mod='tbpaypalcheckout'}</small>
    </p>
</div>

<script>
    var tbpaypal_validation_url = '{$link->getModuleLink('tbpaypalcheckout', 'validation', [], true)|escape:'javascript':'UTF-8'}';
    var tbpaypal_cart_id = {$cart->id|intval};
</script>