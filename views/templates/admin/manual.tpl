{*
 * Copyright (C) 2026-2026 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2026-2026 thirty bees
 * @license   Academic Free License (AFL 3.0)
 *}
<div class="panel" id="tbpaypal-manual-panel">
  <h3><i class="icon-book"></i> {l s='Manual' mod='tbpaypalcheckout'}</h3>

  <p>{l s='This module needs two codes from PayPal: a Client ID and a Client Secret. This manual explains where to find them and how to test the module before you accept real payments.' mod='tbpaypalcheckout'}</p>

  <h4>{l s='1. What you need' mod='tbpaypalcheckout'}</h4>
  <ul>
    <li>
      {l s='A PayPal business account for the shop.' mod='tbpaypalcheckout'}
      <a href="https://www.paypal.com/business" target="_blank" rel="noopener noreferrer">paypal.com/business</a>
    </li>
    <li>
      {l s='Access to the PayPal developer dashboard. You log in there with the same business account, a separate developer account is not needed.' mod='tbpaypalcheckout'}
      <a href="https://developer.paypal.com/dashboard/" target="_blank" rel="noopener noreferrer">developer.paypal.com</a>
    </li>
  </ul>

  <h4>{l s='2. Get the test codes (Sandbox)' mod='tbpaypalcheckout'}</h4>
  <p>{l s='Sandbox is the test environment of PayPal. Payments there use fake money, so you can test the whole checkout safely.' mod='tbpaypalcheckout'}</p>
  <ol>
    <li>
      {l s='Open the developer dashboard and log in with the business account.' mod='tbpaypalcheckout'}
      <a href="https://developer.paypal.com/dashboard/" target="_blank" rel="noopener noreferrer">developer.paypal.com/dashboard</a>
    </li>
    <li>
      {l s='Click Apps & Credentials at the top and set the switch at the top of the page to Sandbox.' mod='tbpaypalcheckout'}
      <a href="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank" rel="noopener noreferrer">{l s='Open Apps & Credentials (Sandbox)' mod='tbpaypalcheckout'}</a>
    </li>
    <li>{l s='Open the app called Default Application. If there is no app, click Create App, choose the type Merchant and enter a name, for example the name of the shop.' mod='tbpaypalcheckout'}</li>
    <li>{l s='Copy the Client ID. Then click the eye icon or Show next to Secret key 1 and copy the secret as well.' mod='tbpaypalcheckout'}</li>
  </ol>

  <h4>{l s='3. Enter the codes in this module' mod='tbpaypalcheckout'}</h4>
  <ol>
    <li>{l s='Open the Settings tab of this page.' mod='tbpaypalcheckout'}</li>
    <li>{l s='Set Environment to Sandbox (Testing).' mod='tbpaypalcheckout'}</li>
    <li>{l s='Paste the codes into Client ID and Client Secret and click Save.' mod='tbpaypalcheckout'}</li>
    <li>{l s='When only the message Settings saved successfully appears, PayPal has accepted the codes. The message Unable to authenticate with PayPal API means that the codes do not belong to the chosen environment, or that a character got lost while copying.' mod='tbpaypalcheckout'}</li>
  </ol>
  {if $tbpaypal_multistore}
    <div class="alert alert-info">
      {l s='This shop uses multistore. The settings are saved for the shop or shop group selected at the top of the back office. Select All shops to save them for every shop at once. A value saved for a single shop overrides the value of All shops.' mod='tbpaypalcheckout'}
    </div>
  {/if}

  <h4>{l s='4. Place a test order' mod='tbpaypalcheckout'}</h4>
  <ol>
    <li>
      {l s='In the developer dashboard, open Testing Tools and then Sandbox Accounts. PayPal has already created a Personal test account there.' mod='tbpaypalcheckout'}
      <a href="https://developer.paypal.com/dashboard/accounts" target="_blank" rel="noopener noreferrer">{l s='Open Sandbox Accounts' mod='tbpaypalcheckout'}</a>
    </li>
    <li>{l s='Click the three dots next to the Personal account and choose View/Edit Account to see its e-mail address and password.' mod='tbpaypalcheckout'}</li>
    <li>{l s='Place an order in the shop, choose PayPal and log in with this Personal test account. Never use your own PayPal account in Sandbox.' mod='tbpaypalcheckout'}</li>
    <li>{l s='After the payment the order appears in the back office with the status Payment accepted.' mod='tbpaypalcheckout'}</li>
  </ol>

  <h4>{l s='5. Go live' mod='tbpaypalcheckout'}</h4>
  <ol>
    <li>
      {l s='In Apps & Credentials, set the switch to Live. A live app is not created automatically: click Create App when the list is empty.' mod='tbpaypalcheckout'}
      <a href="https://developer.paypal.com/dashboard/applications/live" target="_blank" rel="noopener noreferrer">{l s='Open Apps & Credentials (Live)' mod='tbpaypalcheckout'}</a>
    </li>
    <li>{l s='Copy the live Client ID and secret. They differ from the Sandbox codes.' mod='tbpaypalcheckout'}</li>
    <li>{l s='In the Settings tab, set Environment to Live (Production), paste the live codes and click Save.' mod='tbpaypalcheckout'}</li>
    <li>{l s='Switch Debug Mode off once everything works.' mod='tbpaypalcheckout'}</li>
    <li>{l s='Place one small real order and refund it in your PayPal account to be sure.' mod='tbpaypalcheckout'}</li>
  </ol>

  <h4>{l s='Payment Action' mod='tbpaypalcheckout'}</h4>
  <ul>
    <li>{l s='Sale: the customer pays immediately. The order gets the status Payment accepted. This is the right choice for almost every shop.' mod='tbpaypalcheckout'}</li>
    <li>{l s='Authorization: PayPal only reserves the amount and the order waits for payment. This module has no capture button, so you capture the payment in your PayPal account. An authorization expires after 29 days.' mod='tbpaypalcheckout'}</li>
  </ul>

  <h4>{l s='Webhook' mod='tbpaypalcheckout'}</h4>
  <p>{l s='When you save valid codes, the module registers this address at PayPal by itself. You do not have to enter it anywhere.' mod='tbpaypalcheckout'}</p>
  <p><code>{$tbpaypal_webhook_url|escape:'html':'UTF-8'}</code></p>
  <p>{l s='PayPal uses the webhook to report a payment when the customer does not return to the shop after paying. The order is also created when the customer returns to the shop.' mod='tbpaypalcheckout'}</p>
  {if !$tbpaypal_shop_enabled}
    <div class="alert alert-warning">
      {l s='The shop is in maintenance mode. PayPal cannot reach the webhook now, so only customers who return to the shop after paying get an order.' mod='tbpaypalcheckout'}
    </div>
  {/if}

  <h4>{l s='Troubleshooting' mod='tbpaypalcheckout'}</h4>
  <ul>
    <li>
      {l s='The PayPal button does not appear in the checkout: check that both codes are saved, and that the module is enabled for the currency and the country of the customer.' mod='tbpaypalcheckout'}
      <a href="{$tbpaypal_payment_link|escape:'html':'UTF-8'}">{l s='Open the payment restrictions' mod='tbpaypalcheckout'}</a>
    </li>
    <li>{l s='The customer sees an error after paying: switch Debug Mode on, repeat the payment and read the log file of today:' mod='tbpaypalcheckout'} <code>{$tbpaypal_log_file|escape:'html':'UTF-8'}</code></li>
    <li>
      {l s='The PayPal documentation about codes and credentials:' mod='tbpaypalcheckout'}
      <a href="https://developer.paypal.com/api/rest/" target="_blank" rel="noopener noreferrer">developer.paypal.com/api/rest</a>
    </li>
  </ul>

  {if $tbpaypal_sandbox}
    <p class="text-muted">{l s='The module is in Sandbox mode now.' mod='tbpaypalcheckout'}</p>
  {else}
    <p class="text-muted">{l s='The module is in Live mode now.' mod='tbpaypalcheckout'}</p>
  {/if}
</div>
