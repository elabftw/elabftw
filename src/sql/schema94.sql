-- Schema 94
-- add created_at to all entities
UPDATE `experiments` SET `datetime` = CURRENT_TIMESTAMP WHERE `datetime` IS NULL;
ALTER TABLE `experiments` CHANGE `datetime` `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `items` ADD `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
UPDATE config SET conf_value = 94 WHERE conf_name = 'schema';
