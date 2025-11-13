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

namespace Thirtybees\Module\TbPayPal\Model;

use ObjectModel;


class PayPalWebhook extends ObjectModel
{
    /**
     * @var string
     */
    public $webhook_id;

    /**
     * @var string
     */
    public $event_type;

    /**
     * @var string
     */
    public $resource_id;

    /**
     * @var string
     */
    public $payload;

    /**
     * @var bool
     */
    public $processed;

    /**
     * @var string
     */
    public $date_add;

    /**
     * @var array
     */
    public static $definition = [
        'table' => 'tbpaypal_webhook',
        'primary' => 'id_tbpaypal_webhook',
        'fields' => [
            'webhook_id' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'event_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 100],
            'resource_id' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'payload' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'processed' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ]
    ];
}