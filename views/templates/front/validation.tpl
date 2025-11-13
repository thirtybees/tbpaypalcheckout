{*
* PayPal Validation Page Template
*
* @author    thirty bees b.v.
* @copyright Copyright (c) 2025 thirty bees b.v. https://thirtybees.com
* @license   Proprietary License - All rights reserved
*}

{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
        {l s='Payment' mod='tbpaypalcheckout'}
    </a>
    <span class="navigation-pipe">{$navigationPipe}</span>
    {l s='PayPal Checkout' mod='tbpaypalcheckout'}
{/capture}

<h1 class="page-heading">
    {l s='PayPal Payment' mod='tbpaypalcheckout'}
</h1>

{if isset($error_message)}
    <div class="alert alert-danger">
        <h3>{l s='Payment Error' mod='tbpaypalcheckout'}</h3>
        <p>{$error_message|escape:'html':'UTF-8'}</p>
        <p>
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" 
               class="btn btn-default">
                <i class="icon-chevron-left"></i>
                {l s='Return to payment selection' mod='tbpaypalcheckout'}
            </a>
        </p>
    </div>
{else}
    <div class="box">
        <h3>{l s='Redirecting to PayPal...' mod='tbpaypalcheckout'}</h3>
        
        <div class="paypal-redirect-info">
            <p>
                <img src="{$module_dir|escape:'html':'UTF-8'}views/img/paypal-loading.gif" 
                     alt="{l s='Loading...' mod='tbpaypalcheckout'}" 
                     style="width: 32px; height: 32px; vertical-align: middle; margin-right: 10px;" />
                {l s='Please wait while we redirect you to PayPal to complete your payment securely.' mod='tbpaypalcheckout'}
            </p>
            
            <div class="payment-summary" style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 4px;">
                <strong>{l s='Order Total:' mod='tbpaypalcheckout'}</strong> 
                {displayPrice price=$cart_total currency=$currency->id}
            </div>
            
            <p class="text-muted">
                {l s='If you are not redirected automatically, please click the button below:' mod='tbpaypalcheckout'}
            </p>
            
            <p style="margin-top: 20px;">
                <a href="{$approval_url|escape:'html':'UTF-8'}" 
                   class="btn btn-primary btn-lg" 
                   id="paypal-redirect-btn">
                    <i class="icon-external-link"></i>
                    {l s='Continue to PayPal' mod='tbpaypalcheckout'}
                </a>
            </p>
        </div>
    </div>
    
    <script type="text/javascript">
        // Auto-redirect after 2 seconds
        setTimeout(function() {
            window.location.href = '{$approval_url|escape:'javascript':'UTF-8'}';
        }, 2000);
    </script>
{/if}