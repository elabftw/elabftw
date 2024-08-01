-- schema 159
ALTER TABLE `idps` ADD `source` TINYINT UNSIGNED NULL DEFAULT NULL;
CREATE TABLE IF NOT EXISTS `idps_sources` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` TEXT NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auto_refresh` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `last_fetched_at` timestamp NULL DEFAULT NULL,
  `enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_url` (`url`(255))
);
UPDATE config SET conf_value = 159 WHERE conf_name = 'schema';
