{*
* PayPal Return Page Template (Error Handling)
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
    {l s='Payment Error' mod='tbpaypalcheckout'}
{/capture}

<h1 class="page-heading">
    {l s='Payment Processing Error' mod='tbpaypalcheckout'}
</h1>

<div class="alert alert-danger">
    <h3>{l s='Unable to Complete Payment' mod='tbpaypalcheckout'}</h3>
    
    {if isset($error_message)}
        <p><strong>{l s='Error Details:' mod='tbpaypalcheckout'}</strong></p>
        <p>{$error_message|escape:'html':'UTF-8'}</p>
    {else}
        <p>{l s='An unexpected error occurred while processing your PayPal payment.' mod='tbpaypalcheckout'}</p>
    {/if}
    
    <hr>
    
    <p>{l s='What can you do?' mod='tbpaypalcheckout'}</p>
    <ul>
        <li>{l s='Try again using the same payment method' mod='tbpaypalcheckout'}</li>
        <li>{l s='Choose a different payment method' mod='tbpaypalcheckout'}</li>
        <li>{l s='Contact our customer support if the problem persists' mod='tbpaypalcheckout'}</li>
    </ul>
    
    <div style="margin-top: 20px;">
        <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" 
           class="btn btn-default">
            <i class="icon-chevron-left"></i>
            {l s='Return to payment selection' mod='tbpaypalcheckout'}
        </a>
        
        <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}" 
           class="btn btn-primary">
            <i class="icon-envelope"></i>
            {l s='Contact Support' mod='tbpaypalcheckout'}
        </a>
    </div>
</div>

{if isset($cart_id)}
<div class="box" style="margin-top: 20px;">
    <p class="text-muted">
        <small>
            {l s='Reference:' mod='tbpaypalcheckout'} 
            {l s='Cart ID' mod='tbpaypalcheckout'} #{$cart_id|escape:'html':'UTF-8'}
        </small>
    </p>
</div>
{/if}