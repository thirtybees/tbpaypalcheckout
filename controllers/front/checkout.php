<?php
/**
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
 */



if (!defined('_TB_VERSION_')) {
    exit;
}

class TbPayPalCheckoutCheckoutModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool
     */
    public $ssl = true;

    /**
     * @var TbPayPalCheckout
     */
    public $module;

    /**
     * Initialize Content
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();

        $context = Context::getContext();
        $smarty = $context->smarty;
        $link = $context->link;
        $cart = $context->cart;

        if (!Validate::isLoadedObject($cart)) {
            $this->redirectToHome('Invalid cart');
            return;
        }

        $total = Tools::roundPrice((float)$cart->getOrderTotal(true, Cart::BOTH));
        if ($total <= 0.0) {
            $this->redirectToHome('This cart is empty');
        }

        if (Order::getOrderByCartId((int)$cart->id)) {
            $this->redirectToHome('This cart has already been converted to order');
        }


        $sdkUrl = $this->getSdkUrl($cart);
        $this->addJS($sdkUrl);
        $this->addJS(_PS_MODULE_DIR_ . '/tbpaypalcheckout/views/js/checkout.js?'.filemtime(__DIR__ . '/../../views/js/checkout.js'));

        $smarty->assign([
            'tbPaypalApiUrl' => $link->getModuleLink($this->module->name, 'api'),
        ]);
        $this->setTemplate('checkout.tpl');
    }


    /**
     * Redirect to Home with Error
     * @param string $error
     * @throws PrestaShopException
     */
    private function redirectToHome(string $error)
    {
        $logger = $this->module->getFactory()->getLogger();
        $logger->error($error);

        $this->errors[] = $this->module->l($error);
        Tools::redirect($this->context->link->getPageLink('index'));
    }

    /**
     * @throws PrestaShopException
     */
    private function getSdkUrl(Cart $cart)
    {
        $factory = $this->module->getFactory();
        $settings = $factory->getSettings();
        $clientId = $settings->getClientId();
        $currency = Currency::getCurrencyInstance($cart->id_currency);

        return 'https://www.paypal.com/sdk/js?' . http_build_query([
            'components' => 'buttons',
            'client-id' => $clientId,
            'currency' => $currency->iso_code,
        ]);
    }
}