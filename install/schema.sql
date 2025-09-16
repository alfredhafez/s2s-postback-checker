-- S2S Postback Checker Database Schema

-- Settings table
CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offers table
CREATE TABLE IF NOT EXISTS `offers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `goal_name` varchar(100) NOT NULL DEFAULT 'lead',
    `postback_template` text,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clicks table
CREATE TABLE IF NOT EXISTS `clicks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `offer_id` int(11) NOT NULL,
    `transaction_id` varchar(100) NOT NULL,
    `sub1` varchar(255) DEFAULT NULL,
    `sub2` varchar(255) DEFAULT NULL,
    `sub3` varchar(255) DEFAULT NULL,
    `sub4` varchar(255) DEFAULT NULL,
    `sub5` varchar(255) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `referrer` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `transaction_id` (`transaction_id`),
    KEY `offer_id` (`offer_id`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversions table
CREATE TABLE IF NOT EXISTS `conversions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `click_id` int(11) NOT NULL,
    `offer_id` int(11) NOT NULL,
    `transaction_id` varchar(100) NOT NULL,
    `goal` varchar(100) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `phone` varchar(50) DEFAULT NULL,
    `payout` decimal(10,2) DEFAULT 0.00,
    `revenue` decimal(10,2) DEFAULT 0.00,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `click_id` (`click_id`),
    KEY `offer_id` (`offer_id`),
    KEY `transaction_id` (`transaction_id`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`click_id`) REFERENCES `clicks` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Postback logs table
CREATE TABLE IF NOT EXISTS `postback_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `conversion_id` int(11) NOT NULL,
    `postback_url` text NOT NULL,
    `http_status` int(11) DEFAULT NULL,
    `response_body` text,
    `response_time` decimal(8,3) DEFAULT NULL,
    `error_message` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `conversion_id` (`conversion_id`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`conversion_id`) REFERENCES `conversions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manual postback tests table
CREATE TABLE IF NOT EXISTS `manual_tests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `test_name` varchar(255) NOT NULL,
    `postback_url` text NOT NULL,
    `http_status` int(11) DEFAULT NULL,
    `response_body` text,
    `response_time` decimal(8,3) DEFAULT NULL,
    `error_message` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;