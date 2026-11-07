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

use Thirtybees\Core\Error\Response\JSendErrorResponse;
use Thirtybees\Module\TbPayPal\Model\PayPalOrder;

if (!defined('_TB_VERSION_')) {
    exit;
}

class TbPayPalCheckoutApiModuleFrontController extends ModuleFrontController
{

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_OPTIONS = 'OPTIONS';

    /**
     * @var bool
     */
    public $ssl = true;

    /**
     * @var TbPayPalCheckout
     */
    public $module;

    /**
     * @return void
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function init()
    {
        static::getErrorHandler()->setErrorResponseHandler(new JSendErrorResponse(_PS_MODE_DEV_));

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? static::METHOD_GET);

        if (Configuration::getGlobalValue('TBPAYPAL_CORS_ENABLED')) {
            // Enable CORS when allowed. Useful during development
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: *");
            if ($method === self::METHOD_OPTIONS) {
                exit;
            }
        }

        if ($method !== static::METHOD_POST) {
            throw new PrestaShopException("Invalid HTTP request method");
        }
        $contentType = strtolower(trim(explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
        if ($contentType !== 'application/json') {
            throw new PrestaShopException("Invalid request content type");
        }

        parent::init();

        $body = $this->getBody();
        if (! hash_equals(Tools::getToken(false), (string)($body['token'] ?? ''))) {
            throw new PrestaShopException("Invalid request token");
        }
        $action = (string)($body['action'] ?? '');
        switch ($action) {
            case 'createOrder':
                $this->sendSuccessResponse($this->createOrder());
                break;
            case 'approveOrder':
                $this->sendSuccessResponse($this->approveOrder((string)$body['id']));
                break;
            default:
                throw new PrestaShopException("Invalid request action: '$action'");
        }
    }

    /**
     * @param array $payload
     * @return void
     */
    protected function sendSuccessResponse(array $payload)
    {
        $this->sendResponse([
            'status' => 'success',
            'data' => $payload
        ]);
    }

    /**
     * @param array $payload
     * @param int $responseCode
     */
    protected function sendResponse(array $payload, int $responseCode = 200)
    {
        if (! headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($responseCode);
        }
        die(json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    protected function getBody(): array
    {
        $content = file_get_contents('php://input');
        if (! $content) {
            throw new PrestaShopException("Request contains empty body");
        }
        if (trim($content) === 'null') {
            throw new PrestaShopException("Request contains null body");
        }
        $data = json_decode($content, true);
        if (is_array($data)) {
            return $data;
        }
        throw new PrestaShopException("Failed to parse request data: " . json_last_error_msg());
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    private function createOrder(): array
    {
        $processor = $this->module->getFactory()->getProcessor();
        $cart = Context::getContext()->cart;
        $paypalOrder = $processor->createOrder($cart);
        return [
            'id' => (string)$paypalOrder['id']
        ];
    }

    /**
     * @param string $paypalOrderId
     * @return array
     * @throws PrestaShopException
     */
    private function approveOrder(string $paypalOrderId): array
    {
        $paypalOrder = PayPalOrder::getByPaypalId($paypalOrderId);
        if (! Validate::isLoadedObject($paypalOrder)
            || ! $this->isVisitorCart(new Cart((int)$paypalOrder->id_cart))
        ) {
            throw new PrestaShopException("Paypal order with id $paypalOrderId does not belong to the visitor's cart");
        }
        $processor = $this->module->getFactory()->getProcessor();
        $order = $processor->validateOrder($paypalOrderId);
        return [
            'id' => (int)$order->id,
            'reference' => (string)$order->reference,
            'redirect' => $this->getOrderConfirmationLink($order)
        ];
    }

    /**
     * The cart must be the visitor's current cart, or a cart of the customer
     * (guest or registered) in the visitor's cookie. Core drops the cart from
     * the cookie once an order exists for it, so the customer check covers
     * a second approve after the order was created.
     *
     * @param Cart $cart
     * @return bool
     * @throws PrestaShopException
     */
    private function isVisitorCart(Cart $cart): bool
    {
        if (! Validate::isLoadedObject($cart)) {
            return false;
        }
        if ((int)$this->context->cart->id && (int)$cart->id === (int)$this->context->cart->id) {
            return true;
        }

        $customer = $this->context->customer;
        return (int)$customer->id
            && (int)$cart->id_customer === (int)$customer->id
            && (int)$customer->id === (int)$this->context->cookie->id_customer
            && $customer->isLogged(true);
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
}
