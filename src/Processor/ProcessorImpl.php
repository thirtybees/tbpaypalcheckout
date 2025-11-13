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

namespace Thirtybees\Module\TbPayPal\Processor;

use Cart;
use Configuration;
use Customer;
use Order;
use PrestaShopException;
use SmartyException;
use TbPayPalCheckout;
use Thirtybees\Module\TbPayPal\Api\PayPalAPI;
use Thirtybees\Module\TbPayPal\Logger\Logger;
use Thirtybees\Module\TbPayPal\Model\PayPalOrder;
use Thirtybees\Module\TbPayPal\Settings\Settings;
use Validate;

class ProcessorImpl implements Processor
{

    private TbPayPalCheckout $module;

    /**
     * @var PayPalAPI
     */
    private PayPalAPI $api;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * @param TbPayPalCheckout $module
     * @param Settings $settings
     * @param PayPalAPI $api
     * @param Logger $logger
     */
    public function __construct(TbPayPalCheckout $module, Settings $settings, PayPalAPI $api, Logger $logger)
    {
        $this->module = $module;
        $this->settings = $settings;
        $this->api = $api;
        $this->logger = $logger;
    }


    /**
     * @param Cart $cart
     * @param string $returnUrl
     * @param string $cancelUrl
     * @return array
     * @throws PrestaShopException
     */
    public function createOrder(Cart $cart, string $returnUrl = '', string $cancelUrl = ''): array
    {
        $paypalOrder = $this->api->createOrder($cart, $returnUrl, $cancelUrl);
        PayPalOrder::createFromPayPalOrder($this->logger, (int)$cart->id, $paypalOrder);
        return $paypalOrder;
    }

    /**
     * @param string $paypalOrderId
     * @return Order
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function validateOrder(string $paypalOrderId): Order
    {
        $this->logger->debug('Validate Order', ['paypalOrderId' => $paypalOrderId]);
        $paypalOrder = PaypalOrder::getByPaypalId($paypalOrderId);
        if (! Validate::isLoadedObject($paypalOrder)) {
            throw new PrestaShopException("Paypal order with id $paypalOrderId does not exist");
        }
        if ($paypalOrder->id_order) {
            $order = new Order($paypalOrder->id_order);
            if (Validate::isLoadedObject($order)) {
                $this->logger->debug("Paypal order $paypalOrderId is already linked with order " . $order->id);
                return $order;
            }
        }

        $cartId = (int)$paypalOrder->id_cart;
        $cart = new Cart((int)$paypalOrder->id_cart);
        if (! Validate::isLoadedObject($cart)) {
            throw new PrestaShopException("Cart with id $cartId does not exist");
        }

        // Get latest order details from PayPal
        $details = $this->api->getOrderDetails($paypalOrderId);

        if (!$details) {
            throw new PrestaShopException('Failed to get order details from PayPal');
        }

        // Update PayPal order status
        $paypalOrder->updateFromPayPalOrder($details);

        // Check if order is approved
        if ($details['status'] !== 'APPROVED') {
            throw new PrestaShopException('Payment was not approved. Status: ' . $details['status']);
        }

        // Capture or Authorize the payment
        $intent = $this->settings->getIntent();

        if ($intent === Settings::INTENT_AUTHORIZE) {
            $captureResult = $this->api->authorizeOrder($paypalOrder->paypal_order_id);
        } else {
            $captureResult = $this->api->captureOrder($paypalOrder->paypal_order_id);
        }

        // Update order with capture/auth details
        $paypalOrder->updateFromPayPalOrder($captureResult);


        // Create ThirtyBees order
        $order = $this->createThirtybeesOrder($cart, $captureResult);

        // Link PayPal order to shop order
        $paypalOrder->linkToOrder((int)$order->id);

        return $order;
    }

    /**
     * Create ThirtyBees Order
     *
     * @param Cart $cart
     * @param array $captureResult
     * @return Order
     * @throws PrestaShopException
     * @throws SmartyException
     */
    private function createThirtybeesOrder(Cart $cart, array $captureResult): Order
    {
        $customer = new Customer((int)$cart->id_customer);

        // Determine order state based on intent
        $intent = $this->settings->getIntent();

        if ($intent === Settings::INTENT_AUTHORIZE) {
            $order_state = (int)Configuration::get('PS_OS_BANKWIRE'); // Awaiting payment
        } else {
            $order_state = (int)Configuration::get('PS_OS_PAYMENT'); // Payment accepted
        }

        // Get transaction ID
        $transactionId = $this->getTransactionId($captureResult);

        // Calculate total
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

        // Validate order
        $this->module->validateOrder(
            $cart->id,
            $order_state,
            $total,
            $this->module->displayName,
            null,
            [
                'transaction_id' => $transactionId
            ],
            null,
            false,
            $customer->secure_key
        );

        $orderId = (int)$this->module->currentOrder;

        if (! $orderId) {
            throw new PrestaShopException('Order could not be created');
        }

        $this->logger->debug('Order created successfully', [
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'total' => $total
        ]);

        $order = new Order($orderId);
        return $order;
    }

    /**
     * Get Transaction ID from PayPal Response
     */
    private function getTransactionId($capture_result)
    {
        // For capture
        if (isset($capture_result['purchase_units'][0]['payments']['captures'][0]['id'])) {
            return $capture_result['purchase_units'][0]['payments']['captures'][0]['id'];
        }

        // For authorization
        if (isset($capture_result['purchase_units'][0]['payments']['authorizations'][0]['id'])) {
            return $capture_result['purchase_units'][0]['payments']['authorizations'][0]['id'];
        }

        // Fallback to order ID
        return $capture_result['id'] ?? 'UNKNOWN';
    }

}