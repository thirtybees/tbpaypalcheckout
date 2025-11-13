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

namespace Thirtybees\Module\TbPayPal\Api;

if (!defined('_TB_VERSION_')) {
    exit;
}

use Cart;
use Configuration;
use Currency;
use GuzzleHttp\Client;
use PrestaShopException;
use stdClass;
use Thirtybees\Module\TbPayPal\Logger\Logger;
use Thirtybees\Module\TbPayPal\Settings\Settings;
use Throwable;

class PayPalAPI
{
    const BN_CODE = 'THIRTYBEES_SP_PPCP';

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * @var array
     */
    private array $clients = [];

    /**
     * @var array
     */
    private array $tokens = [];

    /**
     * Constructor
     * @param Settings $settings
     * @param Logger $logger
     */
    public function __construct(Settings $settings, Logger $logger)
    {
        $this->logger = $logger;
        $this->settings = $settings;
    }

    /**
     * Get PayPal Access Token
     *
     * @return string
     * @throws PrestaShopException
     */
    public function getAccessToken(): string
    {
        $mode = $this->settings->getMode();
        if (array_key_exists($mode, $this->tokens)) {
            $this->logger->debug('Using cached access token');
            return $this->tokens[$mode];
        }

        $data = $this->retrieveAccessToken();
        if ($data) {
            $token = (string)$data['access_token'];
            if ($token) {
                $this->tokens[$mode] = $token;
                return $token;
            }
        }
        throw new PrestaShopException('Unable to authenticate with paypal');
    }

    /**
     * @param string $webhookId
     * @param array $requestHeaders
     * @param array $payload
     * @return bool
     */
    public function verifyWebhookSignature(string $webhookId, array $requestHeaders, array $payload): bool
    {
        try {
            $this->logger->debug('Verifying webhook signature');
            $payload = [
                'auth_algo' => $requestHeaders['PAYPAL-AUTH-ALGO'] ?? '',
                'cert_url' => $requestHeaders['PAYPAL-CERT-URL'] ?? '',
                'transmission_id' => $requestHeaders['PAYPAL-TRANSMISSION-ID'] ?? '',
                'transmission_sig' => $requestHeaders['PAYPAL-TRANSMISSION-SIG'] ?? '',
                'transmission_time' => $requestHeaders['PAYPAL-TRANSMISSION-TIME'] ?? '',
                'webhook_id' => $webhookId,
                'webhook_event' => $payload,
            ];

            $response = $this->getClient()->post('/v1/notifications/verify-webhook-signature', [
                'auth' => $this->getAuth(),
                'json' => $payload,
            ]);

            $content = $response->getBody()->getContents();
            $body = json_decode($content, true);

            if (is_array($body) && isset($body['verification_status'])) {
                return $body['verification_status'] === 'SUCCESS';
            }
            if (is_array($body)) {
                $this->logger->error('Failed to verify webhook signature', $body);
            } else {
                $this->logger->error('Failed to verify webhook signature: '. $content);
            }
        } catch (Throwable $e) {
            $this->logger->exception('Failed to verify webhook signature', $e);
        }
        return false;

    }

    /**
     * @return array|null
     */
    public function retrieveAccessToken(): ?array
    {
        try {
            $response = $this->getClient()->post('/v1/oauth2/token', [
                'auth' => $this->getAuth(),
                'form_params' => [
                    'grant_type' => 'client_credentials'
                ]
            ]);
            
            $statusCode = (int)$response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
            
            if ($statusCode === 200 && is_array($body)) {
                return $body;
            }

            $this->logger->error('Failed to get access token', [
                'status_code' => $statusCode,
                'response' => $body,
            ]);
            return null;
        } catch (Throwable $e) {
            $this->logger->exception('Access token request exception', $e);
        }
        return null;
    }

    /**
     * Create PayPal Order
     * @param Cart $cart
     * @param string $returnUrl
     * @param string $cancelUrl
     * @return array
     * @throws PrestaShopException
     */
    public function createOrder(Cart $cart, string $returnUrl = '', string $cancelUrl = ''): array
    {
        $token = $this->getAccessToken();

        $currency = Currency::getCurrencyInstance($cart->id_currency);
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $shopName = (string)Configuration::get('PS_SHOP_NAME');

        $appContext = [
            'brand_name' => $shopName,
            'locale' => 'en-US',
            'landing_page' => 'NO_PREFERENCE',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action' => 'PAY_NOW',
        ];
        if ($returnUrl && $cancelUrl) {
            $appContext['return_url'] = $returnUrl;
            $appContext['cancel_url'] = $cancelUrl;
        }

        $order_data = [
            'intent' => $this->settings->getIntent(),
            'purchase_units' => [
                [
                    'reference_id' => 'cart_' . $cart->id,
                    'amount' => [
                        'currency_code' => $currency->iso_code,
                        'value' => number_format($total, 2, '.', '')
                    ],
                    'description' => $shopName . ' - Cart #' . $cart->id
                ]
            ],
            'application_context' => $appContext
        ];
        
        $this->logger->debug('Creating PayPal order', [
            'cart_id' => $cart->id,
            'total' => $total,
            'currency' => $currency->iso_code,
            'bn_code' => static::BN_CODE,
        ]);
        
        try {
            $response = $this->getClient()->post('/v2/checkout/orders', [
                'json' => $order_data,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'PayPal-Partner-Attribution-Id' => static::BN_CODE,
                    'PayPal-Request-Id' => uniqid('tb_', true)
                ]
            ]);
            
            $status_code = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($status_code !== 201 || !is_array($body)) {
                $this->logger->error('Failed to create order', [
                    'status_code' => $status_code,
                    'response' => $body
                ]);
                throw new PrestaShopException('Failed to create PayPal order: HTTP ' . $status_code);
            }
            return $body;
        } catch (Throwable $e) {
            $this->logger->exception('Create order request exception', $e);
            throw new PrestaShopException('PayPal API error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Capture PayPal Order
     * @param string $orderId
     * @return array
     * @throws PrestaShopException
     */
    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();
        $this->logger->debug('Capturing PayPal order', [
            'order_id' => $orderId,
            'bn_code' => static::BN_CODE
        ]);
        
        try {
            $response = $this->getClient()->post('/v2/checkout/orders/' . $orderId . '/capture', [
                'json' => (new stdClass()),
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'PayPal-Partner-Attribution-Id' => static::BN_CODE,
                    'PayPal-Request-Id' => uniqid('tb_capture_', true)
                ],
            ]);
            
            $status_code = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
            
            if ($status_code !== 201 || !is_array($body)) {
                $this->logger->error('Failed to capture order', [
                    'status_code' => $status_code,
                    'order_id' => $orderId,
                    'response' => $body
                ]);
                throw new PrestaShopException('Failed to capture PayPal order: HTTP ' . $status_code);
            }

            $this->logger->debug('Capture order response', $body);
            return $body;
            
        } catch (Throwable $e) {
            $this->logger->exception('Capture order request exception', $e, [
                'order_id' => $orderId
            ]);
            throw new PrestaShopException('PayPal API error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Authorize PayPal Order
     * @param string $orderId
     * @return array
     * @throws PrestaShopException
     */
    public function authorizeOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $this->logger->debug('Authorizing PayPal order', [
            'order_id' => $orderId,
            'bn_code' => static::BN_CODE
        ]);
        
        try {
            $response = $this->getClient()->post('/v2/checkout/orders/' . $orderId . '/authorize', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'PayPal-Partner-Attribution-Id' => static::BN_CODE,
                    'PayPal-Request-Id' => uniqid('tb_auth_', true)
                ],
                'json' => (new stdClass())
            ]);
            
            $status_code = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
            
            if ($status_code !== 201 || !is_array($body)) {
                $this->logger->error('Failed to authorize order', [
                    'status_code' => $status_code,
                    'order_id' => $orderId,
                    'response' => $body
                ]);
                throw new PrestaShopException('Failed to authorize PayPal order: HTTP ' . $status_code);
            }
            
            return $body;

        } catch (Throwable $e) {
            $this->logger->exception('Guzzle exception during authorization', $e);
            throw new PrestaShopException('Failed to authorize paypal order', 0, $e);
        }
    }

    /**
     * Get Order Details
     * @param string $orderId
     * @return false|array
     * @throws PrestaShopException
     */
    public function getOrderDetails(string $orderId)
    {
        $token = $this->getAccessToken();

        try {
            $response = $this->getClient()->get('/v2/checkout/orders/' . $orderId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'PayPal-Partner-Attribution-Id' => static::BN_CODE
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
            
            if ($statusCode !== 200 || !is_array($body)) {
                $this->logger->error('Failed to get order details', [
                    'status_code' => $statusCode,
                    'order_id' => $orderId,
                    'response' => $body
                ]);
                return false;
            }
            return $body;
        } catch (Throwable $e) {
            $this->logger->exception('Failed to get order details', $e);
            return false;
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function getWebhooks(): array
    {
        $token = $this->getAccessToken();

        try {
            $response = $this->getClient()->get('/v1/notifications/webhooks', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 || !is_array($body)) {
                $this->logger->error('Failed to get webhooks', [
                    'status_code' => $statusCode,
                    'response' => $body
                ]);
                return [];
            }
            return $body['webhooks'] ?? [];
        } catch (Throwable $e) {
            $this->logger->exception('Failed to retrieve webhooks', $e);
            throw new PrestaShopException('Failed to retrieve webhooks', 0, $e);
        }
    }

    /**
     * @param string $webhookUrl
     * @param array $eventTypes
     * @return string
     * @throws PrestaShopException
     */
    public function createWebhook(string $webhookUrl): ?string
    {
        $token = $this->getAccessToken();
        try {
            $payload = [
                'url' => $webhookUrl,
                'event_types' => [[
                    'name' => '*'
                ]]
            ];
            $this->logger->debug('Creating webhook', $payload);
            $response = $this->getClient()->post('/v1/notifications/webhooks', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => $payload
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 201 || !is_array($body)) {
                $this->logger->error('Failed to create webhook', [
                    'status_code' => $statusCode,
                    'response' => $body
                ]);
                return null;
            }
            $id = (string)$body['id'];
            $this->logger->debug('Created webhook with id: ' . $id);
            return $id;
        } catch (Throwable $e) {
            $this->logger->exception('Failed to create webhook', $e);
            throw new PrestaShopException('Failed to create webhook', 0, $e);
        }
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        $mode = $this->settings->getMode();
        if (! array_key_exists($mode, $this->clients)) {
            $url = ($mode === Settings::MODE_LIVE)
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';
            $this->clients[$mode] = new Client([
                'base_uri' => $url,
                'timeout' => 30,
                'connect_timeout' => 10,
                'http_errors' => false, // We'll handle errors manually
                'headers' => [
                    'Accept' => 'application/json',
                    'Accept-Language' => 'en_US',
                ]
            ]);
        }
        return $this->clients[$mode];
    }

    /**
     * @return array
     */
    private function getAuth(): array
    {
        return [
            $this->settings->getClientId(),
            $this->settings->getClientSecret(),
        ];
    }
}