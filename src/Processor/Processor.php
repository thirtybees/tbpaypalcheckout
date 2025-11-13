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
use Order;
use PrestaShopException;

interface Processor
{

    /**
     * @param Cart $cart
     * @param string $returnUrl
     * @param string $cancelUrl
     * @return array
     * @throws PrestaShopException
     */
    public function createOrder(Cart $cart, string $returnUrl = '', string $cancelUrl = ''): array;


    /**
     * @param string $paypalOrderId
     * @return Order
     * @throws PrestaShopException
     */
    public function validateOrder(string $paypalOrderId): Order;

}