-- schema 139
ALTER TABLE `items` ADD `timestamped` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `items` ADD `timestampedby` INT NULL DEFAULT NULL;
ALTER TABLE `items` ADD `timestamped_at` TIMESTAMP NULL DEFAULT NULL;
UPDATE config SET conf_value = 139 WHERE conf_name = 'schema';
