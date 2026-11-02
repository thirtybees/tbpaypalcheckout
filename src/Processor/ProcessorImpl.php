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
use Currency;
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

        // The approved amount must still match the cart, the cart can change after the PayPal order was created
        $approvedAmount = $details['purchase_units'][0]['amount'] ?? null;
        if (! $this->amountMatchesCart($cart, $approvedAmount)) {
            $this->logger->error('Approved PayPal amount does not match the cart', [
                'paypalOrderId' => $paypalOrderId,
                'cart_id' => $cartId,
                'approved' => $approvedAmount,
                'cart_total' => $this->formatAmount((float)$cart->getOrderTotal(true, Cart::BOTH)),
                'cart_currency' => $this->getCartCurrencyCode($cart),
            ]);
            throw new PrestaShopException('Cart amount or currency has changed, payment was not captured');
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

        // Amount that PayPal really captured or authorized. Core puts the order in the payment
        // error state when this differs from the cart total
        $total = $this->getPaidAmount($cart, $captureResult);

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

    /**
     * Returns the amount PayPal captured or authorized, in the cart currency.
     * A payment in another currency counts as nothing paid.
     *
     * @param Cart $cart
     * @param array $captureResult
     * @return float
     * @throws PrestaShopException
     */
    private function getPaidAmount(Cart $cart, array $captureResult): float
    {
        $payments = $captureResult['purchase_units'][0]['payments'] ?? [];
        $amount = $payments['captures'][0]['amount'] ?? $payments['authorizations'][0]['amount'] ?? null;
        if (! is_array($amount) || ! isset($amount['value'], $amount['currency_code'])) {
            $this->logger->error('PayPal response has no captured amount', ['cart_id' => (int)$cart->id]);
            return 0.0;
        }
        if (strtoupper((string)$amount['currency_code']) !== $this->getCartCurrencyCode($cart)) {
            $this->logger->error('PayPal captured another currency than the cart', [
                'cart_id' => (int)$cart->id,
                'captured' => $amount,
                'cart_currency' => $this->getCartCurrencyCode($cart),
            ]);
            return 0.0;
        }
        return (float)$amount['value'];
    }

    /**
     * Checks that a PayPal amount (currency_code + value) equals the current cart total
     *
     * @param Cart $cart
     * @param array|null $amount
     * @return bool
     * @throws PrestaShopException
     */
    private function amountMatchesCart(Cart $cart, $amount): bool
    {
        if (! is_array($amount) || ! isset($amount['value'], $amount['currency_code'])) {
            return false;
        }
        if (strtoupper((string)$amount['currency_code']) !== $this->getCartCurrencyCode($cart)) {
            return false;
        }
        $cartTotal = $this->formatAmount((float)$cart->getOrderTotal(true, Cart::BOTH));
        return $this->formatAmount((float)$amount['value']) === $cartTotal;
    }

    /**
     * Same format as the amount sent to PayPal when the order is created
     *
     * @param float $amount
     * @return string
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @param Cart $cart
     * @return string
     */
    private function getCartCurrencyCode(Cart $cart): string
    {
        $currency = new Currency((int)$cart->id_currency);
        return strtoupper((string)$currency->iso_code);
    }

}