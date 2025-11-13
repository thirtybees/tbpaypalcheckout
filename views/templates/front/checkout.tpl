{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
        {l s='Payment' mod='tbpaypalcheckout'}
    </a>
    <span class="navigation-pipe">{$navigationPipe}</span>
    {l s='Pay with Paypal' mod='tbpaypalcheckout'}
{/capture}

<h1 class="page-heading">
    {l s='Pay with Paypal' mod='tbpaypalcheckout'}
</h1>

<div>
    <div id="paypal-error" class="paypal-error"></div>
    <div id="paypal-button-container" class="paypal-button-container"></div>
    <div id="paypal-loading" class="paypal-loading"></div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        initTbPaypal('paypal-button-container', '{$tbPaypalApiUrl}')
    });
</script>