-- billing module — master schema
-- Full CREATE statements for the billing and point management tables.

CREATE TABLE IF NOT EXISTS `billing_payment_methods` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `reseller_id` INT(11) NOT NULL DEFAULT 0,
  `name` VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` VARCHAR(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` VARCHAR(128) COLLATE utf8mb4_unicode_ci DEFAULT 'ti tabler-credit-card',
  `instructions` MEDIUMTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rate_per_point` DECIMAL(12,4) DEFAULT 1.0000,
  `currency` VARCHAR(16) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `min_points` INT(11) DEFAULT 1,
  `max_points` INT(11) DEFAULT 0,
  `active` TINYINT(1) DEFAULT 1,
  `sort_order` INT(11) DEFAULT 0,
  `created_at` INT(11) NOT NULL,
  `updated_at` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reseller_active` (`reseller_id`, `active`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `billing_orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(32) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  `user_id` INT(11) NOT NULL,
  `seller_id` INT(11) NOT NULL,
  `payment_method_id` INT(11) NOT NULL,
  `method_name` VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `points_requested` DECIMAL(16,4) NOT NULL,
  `price_total` DECIMAL(16,4) DEFAULT 0.0000,
  `currency` VARCHAR(16) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `transaction_reference` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_notes` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_image` VARCHAR(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` ENUM('pending', 'processing', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
  `rejection_reason` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processed_by` INT(11) DEFAULT NULL,
  `processed_at` INT(11) DEFAULT NULL,
  `created_at` INT(11) NOT NULL,
  `updated_at` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_seller` (`user_id`, `seller_id`),
  KEY `idx_seller_status` (`seller_id`, `status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `billing_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) DEFAULT NULL,
  `user_id` INT(11) NOT NULL,
  `target_user_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` MEDIUMTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` VARCHAR(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_user_action` (`user_id`, `action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
