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

namespace Thirtybees\Module\TbPayPal\Webhook;

use Thirtybees\Module\TbPayPal\Logger\Logger;
use Thirtybees\Module\TbPayPal\Model\PayPalWebhook;
use Throwable;
use PrestaShopException;

class WebhookProcessorImpl implements WebhookProcessor
{

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var WebhookEventHandler[]
     */
    private array $handlers;

    /**
     * @param Logger $logger
     * @param WebhookEventHandler[] $handlers
     */
    public function __construct(Logger $logger, array $handlers)
    {
        $this->logger = $logger;
        $this->handlers = $handlers;
    }

    /**
     * Process Webhook Event
     *
     * @param array $eventData
     * @return PayPalWebhook
     * @throws PrestaShopException
     */
    public function process(array $eventData): PayPalWebhook
    {

        $eventType = (string)$eventData['event_type'];
        $webhook = new PayPalWebhook();
        $webhook->webhook_id = (string)$eventData['id'];
        $webhook->event_type = $eventType;
        $webhook->resource_id = (string)($eventData['resource']['id'] ?? '');
        $webhook->payload = json_encode($eventData);
        $webhook->processed = false;
        $webhook->date_add = date('Y-m-d H:i:s');
        $webhook->add();

        try {
            if (array_key_exists($eventType, $this->handlers)) {
                $this->logger->debug("Processing webhook event '$eventType'");
                $handler = $this->handlers[$eventType];
                $handler->handle($webhook, $eventData);
            } else {
                $this->logger->debug("Ignoring webhook event '$eventType'");
            }
            // Process based on event type
            $webhook->processed = true;
            $webhook->update();
        } catch (Throwable $e) {
            $this->logger->exception('Webhook processing failed', $e, $eventData);
        }
        return $webhook;
    }
}