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

namespace Thirtybees\Module\TbPayPal\Logger;


use Throwable;

interface Logger
{
    /**
     * Log Debug Message
     *
     * @param string $message
     * @param array $context
     * @return Logger
     */
    public function debug(string $message, array $context = []): Logger;

    /**
     * Log Error Message
     *
     * @param string $message
     * @param array $context
     * @return Logger
     */
    public function error(string $message, array $context = []): Logger;

    /**
     * @param string $message
     * @param Throwable $throwable
     * @param array $context
     * @return Logger
     */
    public function exception(string $message, Throwable $throwable, array $context = []): Logger;
}