-- schema 156
ALTER TABLE `teams` ADD `newcomer_threshold` INT UNSIGNED NOT NULL DEFAULT 15;
ALTER TABLE `teams` ADD `newcomer_banner` TEXT NULL DEFAULT NULL;
ALTER TABLE `teams` ADD `newcomer_banner_active` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
UPDATE `users` SET `created_at` = FROM_UNIXTIME(`register_date`);
ALTER TABLE `users` DROP COLUMN `register_date`;
UPDATE config SET conf_value = 156 WHERE conf_name = 'schema';
