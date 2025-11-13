{*
* PayPal EU Advanced Payment Option Template
*
* @author    thirty bees b.v.
* @copyright Copyright (c) 2025 thirty bees b.v. https://thirtybees.com
* @license   Proprietary License - All rights reserved
*}

<div class="paypal-eu-payment">
    <a href="{$payment_url|escape:'html':'UTF-8'}" 
       class="paypal-eu-link" 
       title="{l s='Pay with PayPal' mod='tbpaypalcheckout'}">
        <img src="{$module_dir|escape:'html':'UTF-8'}views/img/paypal-logo.png" 
             alt="{l s='PayPal' mod='tbpaypalcheckout'}" />
    </a>
</div>