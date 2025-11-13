CREATE TABLE IF NOT EXISTS `PREFIX_tbpaypal_order`(
    `id_tbpaypal_order` INT(11) NOT NULL AUTO_INCREMENT,
    `id_cart` INT(11) NOT NULL,
    `id_order` INT(11) DEFAULT NULL,
    `paypal_order_id` VARCHAR(255) NOT NULL,
    `paypal_status` VARCHAR(50) DEFAULT NULL,
    `intent` VARCHAR(50) DEFAULT NULL,
    `payer_id` VARCHAR(255) DEFAULT NULL,
    `payer_email` VARCHAR(255) DEFAULT NULL,
    `amount` DECIMAL(20,6) DEFAULT NULL,
    `currency` VARCHAR(3) DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_tbpaypal_order`),
    KEY `id_cart` (`id_cart`),
    KEY `paypal_order_id` (`paypal_order_id`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_tbpaypal_webhook`(
    `id_tbpaypal_webhook` INT(11) NOT NULL AUTO_INCREMENT,
    `webhook_id` VARCHAR(255) NOT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `resource_id` VARCHAR(255) NOT NULL,
    `payload` TEXT NOT NULL,
    `processed` TINYINT(1) NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_tbpaypal_webhook`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;