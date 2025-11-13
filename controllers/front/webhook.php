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

use Thirtybees\Module\TbPayPal\Api\PayPalAPI;
use Thirtybees\Module\TbPayPal\Logger\Logger;

if (!defined('_TB_VERSION_')) {
    exit;
}

class TbPayPalCheckoutWebhookModuleFrontController extends ModuleFrontController
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
     * Initialize - Disable display
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function init()
    {
        parent::init();
        $this->display_header = false;
        $this->display_footer = false;
    }

    /**
     * Process Webhook
     *
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $factory = $this->module->getFactory();
        $logger = $factory->getLogger();

        try {
            // Get raw POST body
            $rawBody = (string)file_get_contents('php://input');
            if (empty($rawBody)) {
                $this->replyError($logger, 400, 'Empty payload');
            }
            $eventData = json_decode($rawBody, true);
            if (! is_array($eventData)) {
                $this->replyError($logger, 400, 'Failed to parse request payload', ['payload' => $rawBody]);
            }

            // Get headers
            $headers = $this->getHeaders();

            // Verify webhook signature (PCI-DSS security requirement)
            $settings = $factory->getSettings();
            $api = $factory->getApi();
            if (! $this->signatureValid($logger, $settings->getWebhookId(), $api, $eventData, $headers)) {
                $this->replyError($logger, 401, 'Signature verification failed');
            }

            $logger->debug('Webhook received', [
                'event_type' => $eventData['event_type'],
                'event_id' => $eventData['id']
            ]);
            
            // Process webhook
            $factory->getWebhookProcessor()->process($eventData);

            // Return success
            http_response_code(200);
            $this->ajaxDie(json_encode(['status' => 'success']));
            
        } catch (Exception $e) {
            $logger->exception('Webhook processing error', $e);
            $this->replyError($logger, 500, 'Processing failed');
        }
    }
    
    /**
     * Get HTTP Headers
     */
    private function getHeaders()
    {
        $headers = [];
        
        // Get headers from $_SERVER
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header_key = str_replace('HTTP_', '', $key);
                $header_key = str_replace('_', '-', $header_key);
                $headers[$header_key] = $value;
            }
        }
        
        return $headers;
    }

    /**
     * @param Logger $logger
     * @param string $webhookId
     * @param PayPalAPI $api
     * @param array $body
     * @param array $headers
     * @return bool
     */
    protected function signatureValid(Logger $logger, string $webhookId, PayPalAPI $api, array $body, array $headers): bool
    {
        $logger->debug("Verifying webhook signature", [
            'headers' => $headers,
            'payload' => $body
        ]);
        if (! $webhookId) {
            $logger->debug("Webhooks are not configured. Ignoring webhook request");
            return false;
        }
        return $api->verifyWebhookSignature($webhookId, $headers, $body);
    }

    /**
     * @param Logger $logger
     * @param int $responseCode
     * @param string $message
     * @param array $data
     * @return void
     * @throws PrestaShopException
     */
    protected function replyError(Logger $logger, int $responseCode, string $message, array $data = [])
    {
        http_response_code($responseCode);
        if (! headers_sent()) {
            header('Content-Type: application/json');
        }
        $logger->error("Failed to process webhook: $message", $data);
        $this->ajaxDie(json_encode(['error' => $message]));
    }
}