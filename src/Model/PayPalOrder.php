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

use Db;
use DbQuery;
use ObjectModel;
use PrestaShopException;
use Thirtybees\Module\TbPayPal\Logger\Logger;

if (!defined('_TB_VERSION_')) {
    exit;
}

class PayPalOrder extends ObjectModel
{
    /**
     * @var int
     */
    public $id_cart;

    /**
     * @var int
     */
    public $id_order;

    /**
     * @var string
     */
    public $paypal_order_id;

    /**
     * @var string
     */
    public $paypal_status;

    /**
     * @var string
     */
    public $intent;

    /**
     * @var string
     */
    public $payer_id;

    /**
     * @var string
     */
    public $payer_email;

    /**
     * @var float
     */
    public $amount;

    /**
     * @var string
     */
    public $currency;

    /**
     * @var string
     */
    public $date_add;

    /**
     * @var string
     */
    public $date_upd;

    /**
     * Model Definition
     */
    public static $definition = [
        'table' => 'tbpaypal_order',
        'primary' => 'id_tbpaypal_order',
        'fields' => [
            'id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'paypal_order_id' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'paypal_status' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 50],
            'intent' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 50],
            'payer_id' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'payer_email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 255],
            'amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'currency' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 3],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * Create PayPal Order from API Response
     *
     * @param Logger $logger
     * @param int $cartId
     * @param array $paypalOrder
     * @return PayPalOrder|null
     * @throws PrestaShopException
     */
    public static function createFromPayPalOrder(Logger $logger, int $cartId, array $paypalOrder): ?PayPalOrder
    {
        $order = new static();
        $order->id_cart = (int)$cartId;
        $order->paypal_order_id = (string)$paypalOrder['id'];
        $order->paypal_status = (string)$paypalOrder['status'];
        $order->intent = (string)$paypalOrder['intent'];
        
        // Extract amount and currency
        if (isset($paypalOrder['purchase_units'][0]['amount'])) {
            $order->amount = (float)$paypalOrder['purchase_units'][0]['amount']['value'];
            $order->currency = (string)$paypalOrder['purchase_units'][0]['amount']['currency_code'];
        }
        
        $order->date_add = date('Y-m-d H:i:s');
        $order->date_upd = date('Y-m-d H:i:s');
        
        if ($order->save()) {
            $logger->debug('PayPal order saved to database', [
                'id_tbpaypal_order' => $order->id,
                'paypal_order_id' => $order->paypal_order_id,
                'cart_id' => $cartId
            ]);
            return $order;
        }
        
        return null;
    }


    /**
     * Update from PayPal Order Response
     *
     * @param array $paypalOrder
     * @return bool
     * @throws PrestaShopException
     */
    public function updateFromPayPalOrder(array $paypalOrder): bool
    {
        $this->paypal_status = $paypalOrder['status'];
        
        // Update payer info if available
        if (isset($paypalOrder['payer'])) {
            $this->payer_id = $paypalOrder['payer']['payer_id'] ?? null;
            $this->payer_email = $paypalOrder['payer']['email_address'] ?? null;
        }
        
        // Update amount if available
        if (isset($paypalOrder['purchase_units'][0]['amount'])) {
            $this->amount = (float)$paypalOrder['purchase_units'][0]['amount']['value'];
            $this->currency = $paypalOrder['purchase_units'][0]['amount']['currency_code'];
        }
        
        $this->date_upd = date('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * Link to ThirtyBees Order
     *
     * @param int $orderId
     * @return bool
     * @throws PrestaShopException
     */
    public function linkToOrder(int $orderId)
    {
        $this->id_order = (int)$orderId;
        $this->date_upd = date('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * Get PayPal Order by Cart ID
     *
     * @param int $cartId
     * @return PayPalOrder|null
     * @throws PrestaShopException
     */
    public static function getByCartId(int $cartId): ?PayPalOrder
    {
        $sql = (new DbQuery())
            ->select('id_tbpaypal_order')
            ->from('tbpaypal_order')
            ->where('id_cart = ' . (int)$cartId)
            ->orderBy('date_add DESC');
        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new static((int)$id);
        }
        return null;
    }

    /**
     * @param string $paypalOrderId
     * @return PayPalOrder|null
     * @throws PrestaShopException
     */
    public static function getByPaypalId(string $paypalOrderId): ?PayPalOrder
    {
        $sql = (new DbQuery())
            ->select('id_tbpaypal_order')
            ->from('tbpaypal_order')
            ->where('paypal_order_id = "' . pSQL($paypalOrderId) . '"')
            ->orderBy('date_add DESC');
        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return new static((int)$id);
        }
        return null;
    }
}