CREATE TABLE IF NOT EXISTS `offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `goal_name` varchar(100) DEFAULT 'Lead',
  `postback_template` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `clicks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offer_id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `sub1` varchar(255) DEFAULT NULL,
  `sub2` varchar(255) DEFAULT NULL,
  `sub3` varchar(255) DEFAULT NULL,
  `sub4` varchar(255) DEFAULT NULL,
  `sub5` varchar(255) DEFAULT NULL,
  `meta` text,
  `source` varchar(50) DEFAULT 'click',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `converted_at` timestamp NULL DEFAULT NULL,
  `conversion_name` varchar(255) DEFAULT NULL,
  `conversion_email` varchar(255) DEFAULT NULL,
  `conversion_goal` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_offer_id` (`offer_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`offer_id`) REFERENCES `offers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `postback_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `click_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `http_code` int(11) DEFAULT NULL,
  `response_body` text,
  `response_time` float DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_click_id` (`click_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`click_id`) REFERENCES `clicks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `manual_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(100) NOT NULL,
  `test_url` text NOT NULL,
  `http_code` int(11) DEFAULT NULL,
  `response_body` text,
  `response_time` float DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `key` varchar(100) NOT NULL,
  `value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`key`, `value`) VALUES
('global_postback_template', 'https://partner.com/postback?tid={transaction_id}&goal={goal}&name={name}&email={email}&offer={offer_id}&sub1={sub1}&sub2={sub2}&sub3={sub3}&sub4={sub4}&sub5={sub5}'),
('site_name', 'S2S Postback Checker'),
('timezone', 'UTC')
ON DUPLICATE KEY UPDATE value=VALUES(value);

-- Insert sample offers
INSERT INTO `offers` (`name`, `description`, `goal_name`, `is_active`) VALUES
('Sample Offer 1', 'Test offer for email signup', 'Email Lead', 1),
('Sample Offer 2', 'Test offer for phone lead', 'Phone Lead', 1),
('Sample Offer 3', 'Test offer for app download', 'App Install', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name);