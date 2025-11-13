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

namespace Thirtybees\Module\TbPayPal\Webhook\Event\Handler;

use Thirtybees\Module\TbPayPal\Logger\Logger;
use Thirtybees\Module\TbPayPal\Model\PayPalWebhook;
use Thirtybees\Module\TbPayPal\Webhook\WebhookEventHandler;

class EventOrderApprovedHandler implements WebhookEventHandler
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param Logger $getLogger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param PayPalWebhook $webhook
     * @param array $eventData
     * @return bool
     */
    public function handle(PayPalWebhook $webhook, array $eventData): bool
    {
        $this->logger->debug("Order has been approved.");
        return true;
    }
}