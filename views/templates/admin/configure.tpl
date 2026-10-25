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
{$tbpaypal_messages nofilter}

<ul class="nav nav-tabs" id="tbpaypal-tabs">
  <li{if !$tbpaypal_show_manual} class="active"{/if}>
    <a href="#tbpaypal-settings" data-toggle="tab"><i class="icon-cog"></i> {l s='Settings' mod='tbpaypalcheckout'}</a>
  </li>
  <li{if $tbpaypal_show_manual} class="active"{/if}>
    <a href="#tbpaypal-manual" data-toggle="tab"><i class="icon-book"></i> {l s='Manual' mod='tbpaypalcheckout'}</a>
  </li>
</ul>

<div class="tab-content">
  <div class="tab-pane{if !$tbpaypal_show_manual} active{/if}" id="tbpaypal-settings">
    {$tbpaypal_form nofilter}
  </div>
  <div class="tab-pane{if $tbpaypal_show_manual} active{/if}" id="tbpaypal-manual">
    {include file='./manual.tpl'}
  </div>
</div>
