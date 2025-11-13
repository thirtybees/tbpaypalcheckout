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

namespace Thirtybees\Module\TbPayPal\Settings;

use Configuration;
use PrestaShopException;

if (!defined('_TB_VERSION_')) {
    exit;
}

class Settings
{
    const CONFIG_KEY_MODE = 'TBPAYPAL_MODE';
    const CONFIG_KEY_CLIENT_ID = 'TBPAYPAL_CLIENT_ID';
    const CONFIG_KEY_CLIENT_SECRET = 'TBPAYPAL_CLIENT_SECRET';
    const CONFIG_KEY_DEBUG_MODE = 'TBPAYPAL_DEBUG_MODE';
    const CONFIG_KEY_INTENT = 'TBPAYPAL_INTENT';
    const CONFIG_KEY_WEBHOOK_ID = 'TBPAYPAL_WEBHOOK_ID';


    // modes
    const MODE_LIVE = 'live';
    const MODE_SANDBOX = 'sandbox';

    // intents
    const INTENT_CAPTURE = 'CAPTURE';
    const INTENT_AUTHORIZE = 'AUTHORIZE';

    /**
     * @var string
     */
    private string $mode;

    /**
     * @var string
     */
    private string $clientId;

    /**
     * @var string
     */
    private string $clientSecret;

    /**
     * @var bool
     */
    private bool $debugMode;

    /**
     * @var string
     */
    private string $intent;

    /**
     * @var string
     */
    private string $webhookId;

    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->mode = $this->getStringConfig(static::CONFIG_KEY_MODE, static::MODE_SANDBOX);
        $this->clientId = $this->getStringConfig(static::CONFIG_KEY_CLIENT_ID);
        $this->clientSecret = $this->getStringConfig(static::CONFIG_KEY_CLIENT_SECRET);
        $this->debugMode = (bool)(int)$this->getStringConfig(static::CONFIG_KEY_DEBUG_MODE, '1');
        $this->intent = $this->getStringConfig(static::CONFIG_KEY_INTENT, static::INTENT_CAPTURE);
        $this->webhookId = $this->getStringConfig(static::CONFIG_KEY_WEBHOOK_ID, '');
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     * @return $this
     */
    public function setMode(string $mode): Settings
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return $this
     */
    public function setClientId(string $clientId): Settings
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     * @return $this
     */
    public function setClientSecret(string $clientSecret): Settings
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * @param bool $debugMode
     * @return $this
     */
    public function setDebugMode(bool $debugMode): Settings
    {
        $this->debugMode = $debugMode;
        return $this;
    }

    /**
     * @return string
     */
    public function getIntent(): string
    {
        return $this->intent;
    }

    /**
     * @param string $intent
     * @return $this
     */
    public function setIntent(string $intent): Settings
    {
        $this->intent = $intent;
        return $this;
    }


    /**
     * @param string $webhookId
     * @return $this
     */
    public function setWebhookId(string $webhookId): Settings
    {
        $this->webhookId = $webhookId;
        return $this;
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function save()
    {
        $this->saveStringConfig(static::CONFIG_KEY_MODE, $this->getMode());
        $this->saveStringConfig(static::CONFIG_KEY_CLIENT_ID, $this->getClientId());
        $this->saveStringConfig(static::CONFIG_KEY_CLIENT_SECRET, $this->getClientSecret());
        $this->saveStringConfig(static::CONFIG_KEY_DEBUG_MODE, $this->isDebugMode() ? '1' : '0');
        $this->saveStringConfig(static::CONFIG_KEY_INTENT, $this->getIntent());
        $this->saveStringConfig(static::CONFIG_KEY_WEBHOOK_ID, $this->getWebhookId());
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function delete()
    {
        Configuration::deleteByName(static::CONFIG_KEY_MODE);
        Configuration::deleteByName(static::CONFIG_KEY_CLIENT_ID);
        Configuration::deleteByName(static::CONFIG_KEY_CLIENT_SECRET);
        Configuration::deleteByName(static::CONFIG_KEY_DEBUG_MODE);
        Configuration::deleteByName(static::CONFIG_KEY_INTENT);
        Configuration::deleteByName(static::CONFIG_KEY_WEBHOOK_ID);
    }

    /**
     * @param string $configKey
     * @param string $default
     * @return string
     *
     * @throws PrestaShopException
     */
    private function getStringConfig(string $configKey, string $default = ''): string
    {
        $value = Configuration::get($configKey);
        if ($value === false || is_null($value)) {
            return $default;
        }
        return (string)$value;
    }

    /**
     * @param string $configKey
     * @param string $value
     * @return void
     * @throws PrestaShopException
     */
    private function saveStringConfig(string $configKey, string $value)
    {
        Configuration::updateValue($configKey, $value);
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function reset(): void
    {
        $this->delete();
        $this->save();
    }

    /**
     * @return bool
     */
    public function validCredentials(): bool
    {
        return (
            !empty($this->clientId) &&
            !empty($this->clientSecret)
        );
    }

    /**
     * @return string
     */
    public function getWebhookId(): string
    {
        return $this->webhookId;
    }
}