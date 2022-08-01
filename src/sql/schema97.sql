-- Schema 97
-- add immutable column to uploads
ALTER TABLE `uploads` ADD `immutable` TINYINT(1) NOT NULL DEFAULT 0;
-- allow upload comments to be null
ALTER TABLE `uploads` CHANGE `comment` `comment` TEXT NULL DEFAULT NULL;
UPDATE config SET conf_value = 97 WHERE conf_name = 'schema';
