-- schema 172
ALTER TABLE `compounds` ADD COLUMN `is_serious_health_hazard` TINYINT UNSIGNED NOT NULL DEFAULT 0;
