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


use Thirtybees\Module\TbPayPal\Model\PayPalOrder;

if (!defined('_TB_VERSION_')) {
    exit;
}

class TbPayPalCheckoutReturnModuleFrontController extends ModuleFrontController
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
        
        $cart_id = (int)Tools::getValue('cart_id');

        if (!$cart_id) {
            $this->redirectToHome('Missing cart ID');
            return;
        }
        
        $cart = new Cart($cart_id);
        
        if (!Validate::isLoadedObject($cart)) {
            $this->redirectToHome('Invalid cart');
            return;
        }
        
        // Check if order already exists
        $orderId = Order::getOrderByCartId($cart_id);
        $order = new Order($orderId);
        if (Validate::isLoadedObject($order)) {
            $this->redirectToOrderConfirmation($order);
            return;
        }
        
        try {
            // Get PayPal order from database
            $paypalOrder = PayPalOrder::getByCartId($cart_id);
            
            if (!$paypalOrder) {
                throw new PrestaShopException('PayPal order not found');
            }

            $processor = $this->module->getFactory()->getProcessor();
            $order = $processor->validateOrder($paypalOrder->paypal_order_id);
            $this->redirectToOrderConfirmation($order);
        } catch (Exception $e) {
            $logger = $this->module->getFactory()->getLogger();
            $logger->exception('Return controller error', $e, [
                'message' => $e->getMessage(),
                'cart_id' => $cart_id
            ]);
            
            $this->context->smarty->assign([
                'error_message' => $e->getMessage(),
                'cart_id' => $cart_id,
            ]);
            
            $this->setTemplate('return.tpl');
        }
    }

    /**
     * Redirect to Order Confirmation
     * @param Order $order
     * @throws PrestaShopException
     */
    private function redirectToOrderConfirmation(Order $order)
    {
        Tools::redirect($this->getOrderConfirmationLink($order));
    }

    /**
     * @param Order $order
     * @return string
     * @throws PrestaShopException
     */
    private function getOrderConfirmationLink(Order $order): string
    {
        $customer = new Customer($order->id_customer);

        return $this->context->link->getPageLink(
            'order-confirmation',
            true,
            null,
            [
                'id_cart' => $order->id_cart,
                'id_module' => $this->module->id,
                'id_order' => $order->id,
                'key' => $customer->secure_key
            ]
        );
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
}