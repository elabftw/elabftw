-- schema 207
ALTER TABLE `items` ADD `booking_hourly_rate_notax` DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00;
ALTER TABLE `items` ADD `booking_hourly_rate_tax` DECIMAL(10, 2) UNSIGNED NOT NULL DEFAULT 0.00;
ALTER TABLE `items` ADD `booking_hourly_rate_currency` TINYINT UNSIGNED NOT NULL DEFAULT 0;
