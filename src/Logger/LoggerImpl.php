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

use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Thirtybees\Core\Error\ErrorUtils;
use Throwable;

if (!defined('_TB_VERSION_')) {
    exit;
}

class LoggerImpl implements Logger
{

    /**
     * Log Debug Message
     * @param string $message
     * @param array $context
     * @return Logger
     */
    public function debug(string $message, array $context = []): Logger
    {
        return $this->writeLog('DEBUG', $message, $context);
    }

    /**
     * Log Error Message
     * @param string $message
     * @param array $context
     * @return Logger
     */
    public function error(string $message, array $context = []): Logger
    {
        return $this->writeLog('ERROR', $message, $context);
    }

    /**
     * @param string $message
     * @param Throwable $throwable
     * @param array $context
     * @return Logger
     */
    public function exception(string $message, Throwable $throwable, array $context = []): Logger
    {
        $desc = ErrorUtils::describeException($throwable);
        ServiceLocator::getInstance()->getErrorHandler()->logFatalError($desc);
        return $this->writeLog('EXCEPTION', $message . "\nCaused by ".$desc->getExtendedMessage(), $context);
    }

    /**
     * Write Log Entry
     * @param string $level
     * @param string $message
     * @param array $context
     * @return Logger
     */
    private function writeLog($level, $message, $context = []): Logger
    {
        $log_dir = _PS_ROOT_DIR_ . '/log';

        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }

        $file = $log_dir . '/paypal-' . date('Ymd') . '.log';

        $entry = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            !empty($context) ? json_encode($context, JSON_PRETTY_PRINT) : ''
        );

        @file_put_contents($file, $entry, FILE_APPEND);
        return $this;
    }
}