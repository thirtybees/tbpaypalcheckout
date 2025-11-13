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

use Thirtybees\Module\TbPayPal\Processor\Processor;

if (!defined('_TB_VERSION_')) {
    exit;
}

class TbPayPalCheckoutValidationModuleFrontController extends ModuleFrontController
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
     *
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();

        $factory = $this->module->getFactory();
        $processor = $factory->getProcessor();
        $logger = $factory->getLogger();

        $cart = $this->context->cart;

        // Validate cart
        if (!$this->validateCart($cart)) {
            $this->redirectToCheckout();
            return;
        }
        
        try {

            // Create PayPal order
            $paypalOrder = $this->createPayPalOrder($processor, $cart);
            
            // Get approval URL
            $approvalUrl = $this->getApprovalUrl($paypalOrder);
            
            if (!$approvalUrl) {
                throw new PrestaShopException('No approval URL found in PayPal response');
            }
            
            // Assign template variables
            $this->context->smarty->assign([
                'approval_url' => $approvalUrl,
                'paypal_order_id' => $paypalOrder['id'],
                'cart_total' => $cart->getOrderTotal(true, Cart::BOTH),
                'currency' => $this->context->currency,
            ]);
            
            $this->setTemplate('validation.tpl');
            
        } catch (Exception $e) {
            $logger->exception('Payment validation error', $e, [
                'message' => $e->getMessage(),
                'cart_id' => $cart->id
            ]);
            
            $this->context->smarty->assign([
                'error_message' => $e->getMessage(),
            ]);
            
            $this->setTemplate('validation.tpl');
        }
    }

    /**
     * Validate Cart
     *
     * @param Cart $cart
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function validateCart(Cart $cart)
    {
        if (!Validate::isLoadedObject($cart)) {
            return false;
        }
        
        if ((int)$cart->id_customer === 0 ||
            (int)$cart->id_address_delivery === 0 ||
            (int)$cart->id_address_invoice === 0
        ) {
            return false;
        }
        
        if (!$this->module->checkCurrency($cart)) {
            return false;
        }
        
        return true;
    }

    /**
     * Create PayPal Order
     * @param Processor $processor
     * @param Cart $cart
     * @return array
     * @throws PrestaShopException
     */
    private function createPayPalOrder(Processor $processor, Cart $cart): array
    {
        $returnUrl = $this->context->link->getModuleLink(
            $this->module->name,
            'return',
            ['cart_id' => $cart->id],
            true
        );
        
        $cancelUrl = $this->context->link->getPageLink('order', true, null, ['step' => 3]);
        
        return $processor->createOrder($cart, $returnUrl, $cancelUrl);
    }
    
    /**
     * Get Approval URL from PayPal Order Response
     */
    private function getApprovalUrl($paypal_order)
    {
        if (isset($paypal_order['links'])) {
            foreach ($paypal_order['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return $link['href'];
                }
            }
        }
        return false;
    }

    /**
     * Redirect to checkout with Error
     *
     * @throws PrestaShopException
     */
    private function redirectToCheckout()
    {
        Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => 3]));
    }
}