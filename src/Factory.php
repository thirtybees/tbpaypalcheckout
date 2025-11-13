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

namespace Thirtybees\Module\TbPayPal;

use PrestaShopException;
use TbPayPalCheckout;
use Thirtybees\Module\TbPayPal\Api\PayPalAPI;
use Thirtybees\Module\TbPayPal\Logger\DummyLogger;
use Thirtybees\Module\TbPayPal\Logger\Logger;
use Thirtybees\Module\TbPayPal\Logger\LoggerImpl;
use Thirtybees\Module\TbPayPal\Processor\Processor;
use Thirtybees\Module\TbPayPal\Processor\ProcessorImpl;
use Thirtybees\Module\TbPayPal\Settings\Settings;
use Thirtybees\Module\TbPayPal\Webhook\WebhookEvent;
use Thirtybees\Module\TbPayPal\Webhook\WebhookEventHandler;
use Thirtybees\Module\TbPayPal\Webhook\WebhookProcessor;
use Thirtybees\Module\TbPayPal\Webhook\WebhookProcessorImpl;

class Factory
{
    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * @var object[]
     */
    private array $instances = [];

    /**
     * @var TbPayPalCheckout
     */
    private TbPayPalCheckout $module;

    /**
     * @var WebhookEventHandler[]
     */
    private array $webhookEventHandlers;

    /**
     * Constructor
     * @throws PrestaShopException
     */
    public function __construct(TbPayPalCheckout $module)
    {
        $this->module = $module;
        $this->settings = new Settings();
        $this->webhookEventHandlers = $this->collectWebhookEventHandlers();
    }

    /**
     * @return Settings
     */
    public function getSettings(): Settings
    {
        return $this->settings;
    }

    public function getProcessor(): Processor
    {
        return $this->getOrCreate(Processor::class, function() {
            return new ProcessorImpl(
                $this->module,
                $this->getSettings(),
                $this->getApi(),
                $this->getLogger()
            );
        });
    }

    /**
     * @return PayPalAPI
     */
    public function getApi(): PayPalAPI
    {
        return $this->getOrCreate(PayPalAPI::class, function() {
            return new PayPalAPI(
                $this->getSettings(),
                $this->getLogger()
            );
        });
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->getOrCreate(Logger::class, function() {
            return ($this->settings->isDebugMode())
                ? new LoggerImpl()
                : new DummyLogger();
        });
    }

    /**
     * @return WebhookProcessor
     */
    public function getWebhookProcessor(): WebhookProcessor
    {
        return $this->getOrCreate(WebhookProcessor::class, function() {
            return new WebhookProcessorImpl($this->getLogger(), $this->webhookEventHandlers);
        });
    }

    /**
     * @param string $className
     * @param callable $constructor
     * @return mixed
     */
    private function getOrCreate(string $className, callable $constructor)
    {
        if (! isset($this->instances[$className])) {
            $this->instances[$className] = $constructor();
        }
        return $this->instances[$className];
    }

    /**
     * @return WebhookEventHandler[]
     * @throws PrestaShopException
     */
    private function collectWebhookEventHandlers(): array
    {
        $events = [];
        $directory = rtrim(__DIR__, '/') . '/Webhook/Event';
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file !== 'index.php' && preg_match("/(.*)\.php$/", $file, $matches)) {
                $event = $this->instantiateWebhookEvent($matches[1]);
                $events[$event->getId()] = $event->getHandler($this);
            }
        }
        return $events;
    }

    /**
     * @param string $event
     * @return WebhookEvent
     * @throws PrestaShopException
     */
    private function instantiateWebhookEvent(string $event): WebhookEvent
    {
        $className = 'Thirtybees\Module\TbPayPal\Webhook\Event' . '\\' . $event;
        if (class_exists($className)) {
            $instance = new $className();
            if ($instance instanceof WebhookEvent) {
                return $instance;
            } else {
                throw new PrestaShopException("Failed to instantiate webhook event '$event': not subclass of WebhookEvent class");
            }
        } else {
            throw new PrestaShopException("Failed to instantiate webhook event '$event': class not found");
        }
    }
}