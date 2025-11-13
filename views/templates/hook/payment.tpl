{*
* PayPal Payment Option Template
*
* @author    thirty bees b.v.
* @copyright Copyright (c) 2025 thirty bees b.v. https://thirtybees.com
* @license   Proprietary License - All rights reserved
*}

<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a href="{$payment_url|escape:'html':'UTF-8'}" 
               class="paypal-checkout" 
               title="{l s='Pay with PayPal' mod='tbpaypalcheckout'}">
                <img src="{$module_dir|escape:'html':'UTF-8'}views/img/paypal-logo.png" 
                     alt="{l s='PayPal' mod='tbpaypalcheckout'}" 
                     style="height: 24px; vertical-align: middle; margin-right: 10px;" />
                {l s='Pay with PayPal' mod='tbpaypalcheckout'}
                <span class="paypal-description">
                    {l s='You will be redirected to PayPal to complete your payment securely.' mod='tbpaypalcheckout'}
                </span>
            </a>
        </p>
    </div>
</div>